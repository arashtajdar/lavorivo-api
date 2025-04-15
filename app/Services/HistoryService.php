<?php

namespace App\Services;

use App\Jobs\LogHistoryJob;
use App\Models\History;
use Illuminate\Support\Facades\Auth;

class HistoryService
{
    public static function log(int $actionType, array $details = [])
    {
        LogHistoryJob::dispatch(Auth::id(), $actionType, $details);
    }
}
