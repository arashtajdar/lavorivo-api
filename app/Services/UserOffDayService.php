<?php
namespace App\Services;

use App\Models\History;
use App\Models\User;
use App\Repositories\UserOffDayRepository;
use App\Services\HistoryService;
use Illuminate\Support\Facades\Auth;

class UserOffDayService
{
    protected $userOffDayRepo;

    public function __construct(UserOffDayRepository $userOffDayRepo)
    {
        $this->userOffDayRepo = $userOffDayRepo;
    }

    public function getAllOffDays()
    {
        return $this->userOffDayRepo->getAllOffDays();
    }

    public function getOffDaysForManagement()
    {
        $currentUserId = Auth::id();
        $userIds = User::where('employer', $currentUserId)->pluck('id')->toArray();
        $userIds[] = $currentUserId;
        return $this->userOffDayRepo->getOffDaysByUserIds($userIds);
    }

    public function updateOffDayStatus($id, $status)
    {
        $userOffDay = $this->userOffDayRepo->findOffDayById($id);
        $userOffDay->update(['status' => $status]);

        if ($status == 1) {
            HistoryService::log(History::APPROVE_OFF_DAY, ['id' => $id, 'status' => $status]);
        } else {
            HistoryService::log(History::REJECT_OFF_DAY, ['id' => $id, 'status' => $status]);
        }

        return $userOffDay;
    }

    public function storeOffDay(array $data)
    {
        return $this->userOffDayRepo->createOffDay($data);
    }

    public function updateOffDay($id, array $data)
    {
        return $this->userOffDayRepo->updateOffDay($id, $data);
    }

    public function deleteOffDay($id)
    {
        $this->userOffDayRepo->deleteOffDay($id);
    }
}
