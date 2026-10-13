<?php

namespace App\Services;

use App\Repositories\TimeTrackingRepository;

class TimeTrackingService
{
    public function punchIn(int $userId): array
    {
        $data = (new TimeTrackingRepository())->punchIn($userId);
        $data['message'] = 'Timer started and attendance recorded.';
        return $data;
    }

    public function takeBreak(int $userId): array
    {
        $data = (new TimeTrackingRepository())->startBreak($userId);
        $data['message'] = 'Break started.';
        return $data;
    }

    public function resume(int $userId): array
    {
        $data = (new TimeTrackingRepository())->resumeWork($userId);
        $data['message'] = 'Work resumed.';
        return $data;
    }

    public function punchOut(int $userId): array
    {
        $data = (new TimeTrackingRepository())->punchOut($userId);
        $data['message'] = 'Shift closed and totals saved.';
        return $data;
    }
}
