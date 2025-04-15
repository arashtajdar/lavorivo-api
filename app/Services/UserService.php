<?php

namespace App\Services;

use App\Mail\ManagerRemovedUser;
use App\Mail\NewEmployeeRegistration;
use App\Models\History;
use App\Models\Notification;
use App\Models\Shop;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;

class UserService
{
    /**
     * Get all users with their shops
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllUsers()
    {
        return User::with('shops')->get();
    }

    /**
     * Find a user by ID
     *
     * @param int $id
     * @return \App\Models\User
     */
    public function findUserById(int $id)
    {
        return User::findOrFail($id);
    }

    /**
     * Create a new user
     *
     * @param array $data
     * @return \App\Models\User
     * @throws ValidationException
     */
    public function createUser(array $data)
    {
        $validated = $this->validateUserData($data);
        $validated['role'] = User::USER_ROLE_Customer;
        $validated['password'] = bcrypt($validated['password']);

        return User::create($validated);
    }

    /**
     * Validate user data
     *
     * @param array $data
     * @return array
     * @throws ValidationException
     */
    private function validateUserData(array $data)
    {
        $validator = validator($data, [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * Remove an employee
     *
     * @param int $userId
     * @param int $currentManagerId
     * @return bool
     * @throws Exception
     */
    public function removeEmployee(int $userId, int $currentManagerId)
    {
        try {
            $user = User::findOrFail($userId);
            $user->delete();
            
            Mail::to($user->email)->send(new ManagerRemovedUser());
            
            HistoryService::log(History::REMOVE_EMPLOYEE, [
                "employee_id" => $userId,
                "manager_id" => $currentManagerId,
            ]);
            
            return true;
        } catch (Exception $e) {
            Log::error("Error removing employee", [
                'message' => $e->getMessage(),
                'user_id' => $userId
            ]);
            throw $e;
        }
    }

    /**
     * Add a new employee
     *
     * @param array $data
     * @param int $currentManagerId
     * @return array
     * @throws Exception
     */
    public function addEmployee(array $data, int $currentManagerId)
    {
        $this->validateEmployeeData($data);
        
        // Check if manager has reached maximum employees
        $manager = User::find($currentManagerId);
        if (count($manager->employees) >= $manager->subscription->maximum_employees) {
            throw new Exception("Maximum Employees reached. Upgrade to have more employees!");
        }

        // Check if user already exists
        $user = User::firstWhere('email', $data['email']);
        if ($user) {
            return [
                'success' => false,
                'message' => 'Email already exists!'
            ];
        }

        // Create new employee
        $rawPassword = $this->generateRandomPassword();
        $data['password'] = bcrypt($rawPassword);
        $data['employer'] = $currentManagerId;
        $data['role'] = 1;

        $user = User::create($data);

        // Generate verification URL
        $verificationUrl = $this->generateVerificationUrl($user);

        // Send email
        Mail::to($data['email'])->send(new NewEmployeeRegistration($user, $rawPassword, $verificationUrl));
        
        // Create notification
        $message = "New employee created: " . $user->email;
        NotificationService::create(
            $currentManagerId, 
            Notification::NOTIFICATION_TYPE_NEW_EMPLOYEE_CREATED, 
            $message, 
            ["id" => $user->id]
        );
        
        // Log history
        HistoryService::log(History::ADD_EMPLOYEE, [
            "employee_id" => $user->id,
            "manager_id" => $currentManagerId,
        ]);

        return [
            'success' => true,
            'message' => 'Account created and email sent with credentials!'
        ];
    }

    /**
     * Validate employee data
     *
     * @param array $data
     * @return void
     * @throws ValidationException
     */
    private function validateEmployeeData(array $data)
    {
        $validator = validator($data, [
            'name' => 'required|string|max:255',
            'email' => 'required|email'
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * Generate a random password
     *
     * @param int $length
     * @return string
     */
    private function generateRandomPassword(int $length = 8)
    {
        return substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, $length);
    }

    /**
     * Generate verification URL for a user
     *
     * @param User $user
     * @return string
     */
    private function generateVerificationUrl(User $user)
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),
            [
                'id' => $user->id,
                'hash' => sha1($user->email),
            ]
        );
    }

    /**
     * Delete a user and associated data
     *
     * @param int $id
     * @return bool
     */
    public function deleteUser(int $id)
    {
        $user = User::findOrFail($id);
        $user->ownedShops()->delete();
        $user->employees()->delete();
        $user->delete();
        
        HistoryService::log(History::USER_DELETED_ACCOUNT, [
            "user_id" => $id,
        ]);
        
        return true;
    }

    /**
     * Get users by shop ID
     *
     * @param int $shopId
     * @param int $currentUserId
     * @return \Illuminate\Database\Eloquent\Collection
     * @throws Exception
     */
    public function getUsersByShop(int $shopId, int $currentUserId)
    {
        $shop = Shop::where('id', $shopId)->firstOrFail();
        
        // Check if user is authorized
        if (!$this->isUserAuthorizedForShop($shopId, $currentUserId)) {
            Log::error("Unauthorized access trial to usersByShop", [
                'shopId' => $shopId,
                'userId' => $currentUserId
            ]);
            throw new Exception('Unauthorized', 403);
        }

        return $shop->users;
    }

    /**
     * Check if user is authorized for a shop
     *
     * @param int $shopId
     * @param int $userId
     * @return bool
     */
    public function isUserAuthorizedForShop(int $shopId, int $userId)
    {
        $shop = Shop::where('id', $shopId)->first();
        
        // Check if user is shop owner
        if ($shop && $shop->owner == $userId) {
            return true;
        }
        
        // Check if user is shop manager
        $isShopManager = DB::table('shop_user')
            ->where('shop_id', $shopId)
            ->where('user_id', $userId)
            ->where('role', Shop::SHOP_USER_ROLE_MANAGER)
            ->exists();
            
        return $isShopManager;
    }

    /**
     * Get users by employer ID
     *
     * @param int $employerId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUsersByEmployer(int $employerId)
    {
        return User::where('employer', $employerId)->with('shops')->get();
    }

    /**
     * Get users to manage for a manager
     *
     * @param int $managerId
     * @return array
     */
    public function getUsersToManage(int $managerId)
    {
        $employees = User::where('employer', $managerId)->with('shops')->get()->toArray();
        $result = [];
        
        foreach ($employees as $employee) {
            $result[] = [
                'id' => $employee['id'],
                'is_active' => (bool)$employee['email_verified_at'],
                'name' => $employee['name'],
                'email' => $employee['email'],
                'email_verified_at' => $employee['email_verified_at'],
                'shops' => $employee['shops'],
            ];
        }
        
        return $result;
    }

    /**
     * Update user profile
     *
     * @param int $userId
     * @param array $data
     * @return \App\Models\User
     */
    public function updateProfile(int $userId, array $data)
    {
        $validator = validator($data, [
            'name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $user = User::findOrFail($userId);
        $user->name = $data['name'];
        $user->save();
        
        HistoryService::log(History::USER_UPDATED_PROFILE, $data);
        
        return $user;
    }

    /**
     * Change user password
     *
     * @param int $userId
     * @param array $data
     * @return bool
     * @throws Exception
     */
    public function changePassword(int $userId, array $data)
    {
        $validator = validator($data, [
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $user = User::findOrFail($userId);

        if (!Hash::check($data['current_password'], $user->password)) {
            throw new Exception('Current password is incorrect.');
        }

        $user->password = Hash::make($data['new_password']);
        $user->save();
        
        HistoryService::log(History::USER_CHANGED_PASSWORD, [
            'user_id' => $userId,
            'changed_at' => now()
        ]);

        return true;
    }

    /**
     * Check if user can manage a shop
     *
     * @param int $userId
     * @param int $shopId
     * @return bool
     */
    public function canUserManageShop(int $userId, int $shopId)
    {
        $ownThisShop = Shop::where([
            'id' => $shopId,
            'owner' => $userId
        ])->exists();
        
        $manageThisShop = DB::table('shop_user')
            ->where('shop_id', $shopId)
            ->where('user_id', $userId)
            ->where('role', Shop::SHOP_USER_ROLE_MANAGER)
            ->exists();
            
        return $ownThisShop || $manageThisShop;
    }

    /**
     * Generate main report for a user
     *
     * @param int $userId
     * @return array
     */
    public function generateMainReport(int $userId)
    {
        $totalItems = 0;
        $doneItems = 0;
        $responseData = [];
        
        // Get user's shops and employees
        $shops = Shop::where('owner', $userId)->get();
        $employees = User::where('employer', $userId)->get();

        // Shops
        $totalItems++;
        $responseData[] = [
            'title' => 'Shops',
            'count' => count($shops)
        ];
        if (count($shops)) {
            $doneItems++;
        }
        
        // Employees
        $totalItems++;
        $responseData[] = [
            'title' => 'Employees',
            'count' => count($employees)
        ];
        if (count($employees)) {
            $doneItems++;
        }
        
        // Shift labels
        $totalItems++;
        $shiftLabelCount = 0;
        foreach ($shops as $shop) {
            $shiftLabelCount += \App\Models\ShiftLabel::where('shop_id', $shop->id)->count();
        }
        $responseData[] = [
            'title' => 'Shift Labels',
            'count' => $shiftLabelCount
        ];
        if ($shiftLabelCount) {
            $doneItems++;
        }

        // Shops assigned to users
        $totalItems++;
        $shopUsers = 0;
        foreach ($shops as $shop) {
            $shopUser = DB::table('shop_user')
                ->where('shop_id', $shop->id)
                ->get();
            $shopUsers += count($shopUser);
        }
        $responseData[] = [
            'title' => 'Assigned shops',
            'count' => $shopUsers
        ];
        if ($shopUsers) {
            $doneItems++;
        }

        // Shifts
        $totalItems++;
        $shiftsCount = 0;
        foreach ($shops as $shop) {
            $shifts = \App\Models\Shift::where('shop_id', $shop->id)->get();
            $shiftsCount += count($shifts);
        }
        $responseData[] = [
            'title' => 'Assigned shifts',
            'count' => $shiftsCount
        ];
        if ($shiftsCount) {
            $doneItems++;
        }

        // Shift rules
        $totalItems++;
        $ruleCount = 0;
        $rulesHistory = History::where('user_id', $userId)
            ->where('action_type', History::RULE_ADDED)
            ->first();
            
        if ($rulesHistory) {
            $doneItems++;
            $ruleCount = 'some';
        }
        
        $responseData[] = [
            'title' => 'Shift Rules',
            'count' => $ruleCount
        ];

        $percent = round($doneItems / $totalItems * 100);
        
        return [
            'data' => $responseData,
            'percent' => $percent
        ];
    }
} 