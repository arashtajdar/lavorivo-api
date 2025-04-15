<?php

namespace App\Services;

use App\Models\History;
use App\Models\Shift;
use App\Models\Shop;
use App\Models\User;
use App\Repositories\ShiftRepository;
use App\Services\HistoryService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ShiftService
{
    protected ShiftRepository $shiftRepository;
    protected HistoryService $historyService;

    public function __construct(ShiftRepository $shiftRepository, HistoryService $historyService)
    {
        $this->shiftRepository = $shiftRepository;
        $this->historyService = $historyService;
    }

    /**
     * Get all shifts with optional shop filter
     *
     * @param Request $request
     * @return Collection
     */
    public function getAllShifts(Request $request): Collection
    {
        $shop_id = $request->has('shop_id') ? $request->shop_id : null;
        
        return $this->shiftRepository->getAll($shop_id);
    }

    /**
     * Format shifts for API response
     *
     * @param Collection $shifts
     * @return Collection
     */
    public function formatShiftsForResponse(Collection $shifts): Collection
    {
        return $shifts->map(function ($shift) {
            return [
                'id' => $shift->id,
                'shop_id' => $shift->shop_id,
                'date' => $shift->date,
                'shift_data' => $shift->shift_data,
            ];
        });
    }

    /**
     * Store a shift
     *
     * @param array $data
     * @return array
     */
    public function storeShift(array $data): array
    {
        try {
            // Check if user can manage the shop
            $currentUser = auth()->user();
            if (!UserController::CheckIfUserCanManageThisShop($currentUser->id, $data['shop_id'])) {
                Log::error('You cannot manage this shop', $data);
                return [
                    'success' => false,
                    'status' => 403,
                    'message' => 'You cannot manage this shop'
                ];
            }
            
            // Create or update the shift
            $shift = $this->shiftRepository->createOrUpdateByShopIdAndDate($data);
            
            // Log the action if it's a new shift
            if (!$shift->wasRecentlyCreated) {
                $this->historyService->log(History::ADD_SHIFT, $data);
            }
            
            return [
                'success' => true,
                'status' => 201,
                'data' => $shift
            ];
        } catch (\Exception $e) {
            Log::error('An error occurred while storing the shift.', $data);
            return [
                'success' => false,
                'status' => 500,
                'message' => 'An error occurred while storing the shift.',
                'details' => $e->getMessage()
            ];
        }
    }

    /**
     * Get employee shifts
     *
     * @param Request $request
     * @return array
     */
    public function getEmployeeShifts(Request $request): array
    {
        $currentUser = auth()->user();

        // Get shops owned by the user
        $userShopsOwned = Shop::where('owner', $currentUser->id)
            ->where('state', 1)
            ->get(['id', 'name']);

        // Get shops managed by the user
        $userShopsManaged = Shop::join('shop_user', 'shops.id', '=', 'shop_user.shop_id')
            ->where('shop_user.user_id', $currentUser->id)
            ->where('shops.state', 1)
            ->get(['shops.id', 'shops.name']);

        $allUserShops = $userShopsOwned->merge($userShopsManaged);

        if ($allUserShops->isEmpty()) {
            return [
                'success' => true,
                'status' => 200,
                'data' => []
            ];
        }

        $shopMap = $allUserShops->pluck('name', 'id'); // [shop_id => shop_name]
        $shopIds = $shopMap->keys()->toArray();
        $specificShopId = $request->has('shop_id') ? $request->shop_id : null;

        // Get shifts for the shops
        $shifts = $this->shiftRepository->getByShopIds($shopIds, $specificShopId);

        // Get user names for mapping
        $userMap = User::pluck('name', 'id');

        // Parse date range
        $dateFrom = $request->has('dateFrom') ? Carbon::parse($request->dateFrom) : Carbon::now()->startOfWeek(Carbon::MONDAY);
        $dateTo = $request->has('dateTo') ? Carbon::parse($request->dateTo) : $dateFrom->copy()->addDays(6);

        if ($dateTo->lt($dateFrom)) {
            return [
                'success' => false,
                'status' => 400,
                'message' => 'dateTo cannot be earlier than dateFrom'
            ];
        }

        // Generate all dates in the range
        $allDates = [];
        for ($date = $dateFrom->copy(); $date->lte($dateTo); $date->addDay()) {
            $allDates[] = $date->toDateString();
        }

        // Group shifts by shop and date
        $shiftsByShop = $shifts->groupBy('shop_id')->map(function ($shiftsInShop) use ($userMap) {
            return $shiftsInShop->groupBy('date')->mapWithKeys(function ($shiftsOnDate, $date) use ($userMap) {
                return [
                    $date => $shiftsOnDate->map(function ($shift) use ($userMap) {
                        return collect($shift->shift_data)->map(function ($data) use ($userMap) {
                            return array_merge($data, [
                                'username' => $userMap->get($data['userId'], 'Unassigned'),
                            ]);
                        })->toArray();
                    })->flatten(1)->toArray(),
                ];
            });
        });

        // Format the response
        $fullShiftsByShop = $shopMap->map(function ($shopName, $shopId) use ($shiftsByShop, $allDates, $currentUser) {
            $datesWithShifts = $shiftsByShop->get($shopId, collect());

            $datesData = collect($allDates)->mapWithKeys(function ($date) use ($datesWithShifts) {
                return [
                    $date => $datesWithShifts->get($date, []),
                ];
            });
            
            return array_merge([
                'shop_id' => $shopId,
                'shop_name' => $shopName,
                'manager' => !!UserController::CheckIfUserCanManageThisShop($currentUser->id, $shopId),
            ], $datesData->toArray());
        });

        return [
            'success' => true,
            'status' => 200,
            'data' => $fullShiftsByShop->values()
        ];
    }

    /**
     * Remove a shift by updating its shift data
     *
     * @param array $data
     * @return array
     */
    public function removeShift(array $data): array
    {
        try {
            $shift = $this->shiftRepository->findByShopIdAndDate($data['shopId'], $data['date']);
            
            if (!$shift) {
                Log::error('Shift not found.', $data);
                return [
                    'success' => false,
                    'status' => 404,
                    'message' => 'Shift not found.'
                ];
            }

            $shift->shift_data = $data['shiftData'];
            $shift->save();
            
            $this->historyService->log(History::REMOVE_SHIFT, $data);

            return [
                'success' => true,
                'status' => 200,
                'message' => 'Shift updated successfully.'
            ];
        } catch (\Exception $e) {
            Log::error('An error occurred while removing the shift.', $data);
            return [
                'success' => false,
                'status' => 500,
                'message' => 'An error occurred while removing the shift.',
                'details' => $e->getMessage()
            ];
        }
    }

    /**
     * Find a shift by ID
     *
     * @param int $id
     * @return Shift|null
     */
    public function findById(int $id): ?Shift
    {
        return $this->shiftRepository->findById($id);
    }

    /**
     * Create a new shift
     *
     * @param array $data
     * @return Shift
     */
    public function create(array $data): Shift
    {
        return $this->shiftRepository->create($data);
    }

    /**
     * Update a shift by ID
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function updateById(int $id, array $data): bool
    {
        return $this->shiftRepository->updateById($id, $data);
    }

    /**
     * Delete a shift by ID
     *
     * @param int $id
     * @return bool
     */
    public function deleteById(int $id): bool
    {
        return $this->shiftRepository->deleteById($id);
    }
} 