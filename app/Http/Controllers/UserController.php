<?php

namespace App\Http\Controllers;

use App\Mail\ManagerRemovedUser;
use App\Mail\NewEmployeeRegistration;
use App\Models\History;
use App\Models\Notification;
use App\Models\Shift;
use App\Models\ShiftLabel;
use App\Models\User;
use App\Models\Shop;
use App\Services\HistoryService;
use App\Services\NotificationService;
use App\Services\UserService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function index()
    {
        return response()->json($this->userService->getAllUsers());
    }

    public function show($id)
    {
        $user = $this->userService->findUserById($id);
        return response()->json($user);
    }

    public function store(Request $request)
    {
        try {
            $user = $this->userService->createUser($request->all());
            return response()->json($user, 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    public function removeEmployee(Request $request)
    {
        try {
            $currentManagerId = auth()->id();
            $validated = $request->validate([
                'user_id' => 'required'
            ]);

            $this->userService->removeEmployee($validated['user_id'], $currentManagerId);

            return response()->json(
                ['message' => 'User removed!'],
                201);
        } catch (Exception $e) {
            Log::error("Error removing employee", [
                'message' => $e->getMessage(),
                'data' => $validated ?? null
            ]);
            return response()->json(
                ['message' => $e->getMessage()],
                402);
        }
    }

    public function addEmployee(Request $request)
    {
        try {
            $currentManagerId = auth()->id();
            $result = $this->userService->addEmployee($request->all(), $currentManagerId);

            return response()->json(
                ['message' => $result['message']],
                $result['success'] ? 201 : 400);
        } catch (ValidationException $e) {
            Log::error('Failed to add the user', [
                'message' => $e->getMessage(),
                'data' => $request->all()
            ]);
            return response()->json([
                'message' => 'Failed to add the user',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function destroy($id)
    {
        $this->userService->deleteUser($id);
        return response()->json(['message' => 'User and associated data deleted successfully']);
    }

    public function usersByShop($shopId)
    {
        try {
            $currentUserId = auth()->id();
            $users = $this->userService->getUsersByShop($shopId, $currentUserId);
            return response()->json($users);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }

    public function usersByEmployer()
    {
        $users = $this->userService->getUsersByEmployer(auth()->id());
        return response()->json($users);
    }

    public function listUsersToManage()
    {
        $users = $this->userService->getUsersToManage(auth()->id());
        return response()->json($users, 200);
    }

    public function getProfile()
    {
        return response()->json(auth()->user());
    }

    public function updateProfile(Request $request)
    {
        try {
            $user = $this->userService->updateProfile(auth()->id(), $request->all());
            return response()->json(['message' => 'Profile updated successfully.']);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    public function changePassword(Request $request)
    {
        try {
            $this->userService->changePassword(auth()->id(), $request->all());
            return response()->json(['message' => 'Password changed successfully.']);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public static function CheckIfUserCanManageThisShop($userId, $shopId)
    {
        $userService = app(UserService::class);
        return $userService->canUserManageShop($userId, $shopId);
    }

    public function mainReport(Request $request)
    {
        $userId = auth()->id();
        $report = $this->userService->generateMainReport($userId);
        return response()->json($report);
    }
}

