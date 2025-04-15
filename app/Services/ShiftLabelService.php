<?php

namespace App\Services;

use App\Models\ShiftLabel;
use App\Repositories\ShiftLabelRepository;
use Illuminate\Support\Collection;

class ShiftLabelService
{
    protected ShiftLabelRepository $shiftLabelRepository;

    public function __construct(ShiftLabelRepository $shiftLabelRepository)
    {
        $this->shiftLabelRepository = $shiftLabelRepository;
    }

    public function getShiftLabelsByShopId(int $shopId): Collection
    {
        return $this->shiftLabelRepository->getAllByShopId($shopId);
    }

    public function getActiveShiftLabelsByShopId(int $shopId): Collection
    {
        return $this->shiftLabelRepository->getAllActiveByShopId($shopId);
    }

    public function createShiftLabel(array $data): ShiftLabel
    {
        return $this->shiftLabelRepository->create($data);
    }

    public function updateShiftLabel(int $id, array $data): bool
    {
        return $this->shiftLabelRepository->updateById($id, $data);
    }

    public function deleteShiftLabel(int $id): bool
    {
        return $this->shiftLabelRepository->deleteById($id);
    }

    public function getAllShiftLabelsForUser(int $userId): Collection
    {
        return $this->shiftLabelRepository->getShiftLabelsByUserId($userId);
    }

    public function findById(int $id): ?ShiftLabel
    {
        return $this->shiftLabelRepository->findById($id);
    }
}
