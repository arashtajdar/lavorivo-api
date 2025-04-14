<?php

namespace App\Repositories;

use App\Models\UserOffDay;

class UserOffDayRepository
{
    public function getAllOffDays()
    {
        return UserOffDay::with('user')->get();
    }

    public function getOffDaysByUserIds(array $userIds)
    {
        return UserOffDay::whereIn('user_id', $userIds)->with('user')->get();
    }

    public function findOffDayById($id)
    {
        return UserOffDay::findOrFail($id);
    }

    public function createOffDay(array $data)
    {
        return UserOffDay::create($data);
    }

    public function updateOffDay($id, array $data)
    {
        $offDay = $this->findOffDayById($id);
        $offDay->update($data);
        return $offDay;
    }

    public function deleteOffDay($id)
    {
        $offDay = $this->findOffDayById($id);
        $offDay->delete();
    }
}
