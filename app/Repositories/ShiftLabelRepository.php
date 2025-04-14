<?php
namespace App\Repositories;

use App\Models\ShiftLabel;
use Illuminate\Support\Collection;

class ShiftLabelRepository
{
    public function getAllByShopId(int $shopId): Collection
    {
        return ShiftLabel::where('shop_id', $shopId)->get();
    }

    public function getAllActiveByShopId(int $shopId): Collection
    {
        return ShiftLabel::where(['shop_id' => $shopId, 'is_active' => true])->get();
    }

    public function create(array $data): ShiftLabel
    {
        return ShiftLabel::create($data);
    }

    public function findById(int $id): ?ShiftLabel
    {
        return ShiftLabel::find($id);
    }

    public function deleteById(int $id): bool
    {
        $shiftLabel = ShiftLabel::find($id);
        return $shiftLabel ? $shiftLabel->delete() : false;
    }

    public function updateById(int $id, array $data): bool
    {
        $shiftLabel = ShiftLabel::find($id);
        if ($shiftLabel) {
            return $shiftLabel->update($data);
        }
        return false;
    }

    public function getShiftLabelsByUserId(int $userId): Collection
    {
        return ShiftLabel::whereHas('shop', function ($query) use ($userId) {
            $query->where('owner', $userId);
        })->get();
    }
}
