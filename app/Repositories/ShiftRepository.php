<?php

namespace App\Repositories;

use App\Models\Shift;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class ShiftRepository
{
    protected Shift $model;

    public function __construct(Shift $model)
    {
        $this->model = $model;
    }

    /**
     * Get all shifts with optional shop filter
     *
     * @param int|null $shop_id
     * @return Collection
     */
    public function getAll(?int $shop_id = null): Collection
    {
        $query = $this->model->query();
        
        if ($shop_id) {
            $query->where('shop_id', $shop_id);
        }
        
        return $query->get();
    }

    /**
     * Get shifts by shop IDs
     *
     * @param array $shop_ids
     * @param int|null $specific_shop_id
     * @return Collection
     */
    public function getByShopIds(array $shop_ids, ?int $specific_shop_id = null): Collection
    {
        $query = $this->model->query()->whereIn('shop_id', $shop_ids);
        
        if ($specific_shop_id) {
            $query->where('shop_id', $specific_shop_id);
        }
        
        return $query->get();
    }

    /**
     * Find a shift by shop ID and date
     *
     * @param int $shop_id
     * @param string $date
     * @return Shift|null
     */
    public function findByShopIdAndDate(int $shop_id, string $date): ?Shift
    {
        return $this->model->where('shop_id', $shop_id)
            ->where('date', $date)
            ->first();
    }

    /**
     * Create or update a shift by shop ID and date
     *
     * @param array $data
     * @return Shift
     */
    public function createOrUpdateByShopIdAndDate(array $data): Shift
    {
        $shift = $this->findByShopIdAndDate($data['shop_id'], $data['date']);
        
        if ($shift) {
            // Merge shift data and remove entries with userId = 0
            $shiftData = array_merge($shift->shift_data, $data['shift_data']);
            foreach ($shiftData as $key => $item) {
                if ($item['userId'] === 0) {
                    unset($shiftData[$key]);
                }
            }
            
            $shift->shift_data = $shiftData;
            $shift->save();
            
            return $shift;
        }
        
        // Create new shift
        return $this->create($data);
    }

    /**
     * Find a shift by ID
     *
     * @param int $id
     * @return Shift|null
     */
    public function findById(int $id): ?Shift
    {
        return $this->model->find($id);
    }

    /**
     * Create a new shift
     *
     * @param array $data
     * @return Shift
     */
    public function create(array $data): Shift
    {
        return $this->model->create($data);
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
        return $this->model->where('id', $id)->update($data);
    }

    /**
     * Delete a shift by ID
     *
     * @param int $id
     * @return bool
     */
    public function deleteById(int $id): bool
    {
        return $this->model->destroy($id);
    }
} 