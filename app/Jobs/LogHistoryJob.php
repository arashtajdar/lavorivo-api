<?php
namespace App\Jobs;

use App\Models\History;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class LogHistoryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $userId;
    protected $actionType;
    protected $details;

    public function __construct($userId, $actionType, $details)
    {
        $this->userId = $userId;
        $this->actionType = $actionType;
        $this->details = $details;
    }

    public function handle()
    {
        History::create([
            'user_id' => $this->userId,
            'action_type' => $this->actionType,
            'details' => $this->details,
        ]);
    }
}
