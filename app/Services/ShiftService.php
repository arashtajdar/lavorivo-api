<?php

namespace App\Services;

use App\Models\Shift;
use App\Repositories\ShiftRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ShiftService
{
    protected ShiftRepository $shiftRepository;

    public function __construct(ShiftRepository $shiftRepository)
    {
        $this->shiftRepository = $shiftRepository;
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