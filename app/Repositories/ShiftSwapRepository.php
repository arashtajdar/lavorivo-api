<?php
namespace App\Repositories;

use App\Models\ShiftSwapRequest;
use Illuminate\Database\Eloquent\Collection;

class ShiftSwapRepository
{
    public function create(array $data): ShiftSwapRequest
    {
        return ShiftSwapRequest::create($data);
    }

    public function getAll(): Collection
    {
        return ShiftSwapRequest::with(['requester', 'requested', 'shiftLabel'])->get();
    }

    public function getByUserId(int $userId): Collection
    {
        return ShiftSwapRequest::where('requester_id', $userId)
            ->orWhere('requested_id', $userId)
            ->with(['requester', 'requested', 'shiftLabel'])
            ->get();
    }

    public function findById(int $id): ?ShiftSwapRequest
    {
        return ShiftSwapRequest::find($id);
    }

    public function updateStatus(int $id, int $status): bool
    {
        $swapRequest = $this->findById($id);
        if ($swapRequest) {
            $swapRequest->status = $status;
            return $swapRequest->save();
        }
        return false;
    }
}
