<?php
namespace App\Services;

use App\Models\ShiftSwapRequest;
use App\Models\Shift;
use App\Models\User;
use App\Repositories\ShiftSwapRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Collection;


class ShiftSwapService
{
    protected $repository;

    public function __construct(ShiftSwapRepository $repository)
    {
        $this->repository = $repository;
    }

    public function createRequest(array $data): ShiftSwapRequest
    {
        return $this->repository->create($data);
    }

    public function getAllRequests(): Collection
    {
        return $this->repository->getAll();
    }

    public function getUserRequests(int $userId): Collection
    {
        return $this->repository->getByUserId($userId);
    }

    public function approveRequest(int $id): bool
    {
        DB::beginTransaction();

        try {
            $swapRequest = $this->repository->findById($id);

            if (!$swapRequest || $swapRequest->status !== 0) {
                return false;
            }

            $shift = Shift::where('shop_id', $swapRequest->shop_id)
                ->where('date', $swapRequest->shift_date)
                ->firstOrFail();

            $shiftData = $shift->shift_data;

            $index = array_search(true, array_map(function ($data) use ($swapRequest) {
                return $data['label']['id'] === $swapRequest->shift_label_id && $data['userId'] === $swapRequest->requester_id;
            }, $shiftData));

            if ($index !== false) {
                $shiftData[$index]['userId'] = $swapRequest->requested_id;
                $shiftData[$index]['username'] = User::findOrFail($swapRequest->requested_id)->name;
            }

            $shift->shift_data = $shiftData;
            $shift->save();

            $this->repository->updateStatus($id, 1); // Approved

            DB::commit();

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to approve shift swap request.', ['message' => $e->getMessage(), 'id' => $id]);
            return false;
        }
    }

    public function rejectRequest(int $id): bool
    {
        return $this->repository->updateStatus($id, 2); // Rejected
    }
}
