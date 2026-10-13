<?php

namespace App\Services;

use App\Repositories\AttendanceRepository;
use App\Repositories\AwardRepository;
use App\Repositories\ReportRepository;
use App\Repositories\TaskRepository;
use App\Repositories\TimeTrackingRepository;
use App\Repositories\UserRepository;
use App\Repositories\WorkflowRepository;

class DashboardService
{
    public function data(array $user): array
    {
        $taskRepository = new TaskRepository();
        $attendanceRepository = new AttendanceRepository();
        $timeTrackingRepository = new TimeTrackingRepository();

        return [
            'stats' => array_merge($taskRepository->metrics($user), $attendanceRepository->metrics($user)),
            'awards' => (new AwardRepository())->dashboardAwards(),
            'tasks' => array_slice($taskRepository->listForUser($user), 0, 6),
            'attendance' => array_slice($attendanceRepository->listForUser($user), 0, 6),
            'attendanceCalendar' => $attendanceRepository->monthlyCalendar($user),
            'leaderboard' => array_slice((new UserRepository())->forLeaderboard('monthly'), 0, 5),
            'performanceTrend' => (new ReportRepository())->performanceTrend($user),
            'workflowSummary' => (new ReportRepository())->workflowSummary(),
            'workflows' => (new WorkflowRepository())->all(),
            'timeTracking' => $timeTrackingRepository->summary((int) $user['id']),
        ];
    }
}
