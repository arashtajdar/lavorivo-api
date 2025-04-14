<?php
// app/Http/Controllers/UserOffDayController.php
namespace App\Http\Controllers;

use App\Http\Requests\OffDay\StoreOffDayRequest;
use App\Http\Requests\OffDay\UpdateOffDayRequest;
use App\Http\Requests\OffDay\UpdateOffDayStatusRequest;
use App\Repositories\UserOffDayRepository;
use App\Services\UserOffDayService;
use Illuminate\Http\Request;

class UserOffDayController extends Controller
{
    protected $userOffDayService;
    protected $userOffDayRepository;

    public function __construct(UserOffDayService $userOffDayService, UserOffDayRepository $userOffDayRepository)
    {
        $this->userOffDayService = $userOffDayService;
    }

    public function index()
    {
        $offDays = $this->userOffDayService->getAllOffDays();
        return response()->json($offDays);
    }

    public function listOffDaysToManage()
    {
        $offDays = $this->userOffDayService->getOffDaysForManagement();
        return response()->json($offDays);
    }

    public function updateOffDayStatus(UpdateOffDayStatusRequest $request)
    {
        $userOffDay = $this->userOffDayService->updateOffDayStatus($request->id, $request->status);
        return response()->json($userOffDay);
    }

    public function store(StoreOffDayRequest $request)
    {
        $offDay = $this->userOffDayService->storeOffDay($request->validated());
        return response()->json($offDay, 201);
    }

    public function show($id)
    {
        $offDay = $this->userOffDayRepository->findOffDayById($id);
        return response()->json($offDay);
    }

    public function update(UpdateOffDayRequest $request, $id)
    {
        $offDay = $this->userOffDayService->updateOffDay($id, $request->validated());
        return response()->json($offDay);
    }

    public function destroy($id)
    {
        $this->userOffDayService->deleteOffDay($id);
        return response()->json(null, 204);
    }
}
