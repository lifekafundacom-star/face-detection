<?php

namespace App\Repositories;

use RuntimeException;

class TimeTrackingRepository extends BaseRepository
{
    public function activeSession(int $userId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT *
            FROM time_tracking_sessions
            WHERE user_id = :user_id
              AND session_date = CURDATE()
            ORDER BY id DESC
            LIMIT 1
        ");
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetch() ?: null;
    }

    public function summary(int $userId): array
    {
        $rules = (new SettingsRepository())->attendanceRulesForEngine();
        $session = $this->activeSession($userId);
        $attendance = $this->todayAttendance($userId);

        $status = $session['status'] ?? 'offline';
        $breakSeconds = (int) ($attendance['break_seconds'] ?? 0);

        if ($session && !empty($session['total_break_seconds'])) {
            $breakSeconds = max($breakSeconds, (int) $session['total_break_seconds']);
        }

        if ($session && !empty($session['updated_at']) && $status === 'break') {
            $breakSeconds += max(0, time() - strtotime((string) $session['updated_at']));
        }

        $workSeconds = $this->liveWorkSecondsFromAttendance($attendance, $breakSeconds, $rules);
        if ($workSeconds === 0) {
            $workSeconds = (int) ($session['total_work_seconds'] ?? $attendance['total_work_seconds'] ?? 0);
            if ($session && !empty($session['updated_at']) && $status === 'working') {
                $workSeconds += max(0, time() - strtotime((string) $session['updated_at']));
            }
        }

        if ($attendance) {
            if (!empty($attendance['clock_in']) && empty($attendance['clock_out']) && $status === 'offline') {
                $status = 'working';
            } elseif (!empty($attendance['clock_out'])) {
                $status = 'offline';
            }
        }

        $effectiveAttendanceStatus = $this->effectiveAttendanceStatus($attendance, $workSeconds, $rules);
        $breakDeductionSeconds = $this->breakDeductionSeconds($breakSeconds, $effectiveAttendanceStatus, $rules);
        $rawWorkSeconds = max(0, $workSeconds + $breakDeductionSeconds);
        $overtimeSeconds = $effectiveAttendanceStatus === 'half_day'
            ? 0
            : ($rules['overtime_enabled'] ? max(0, $workSeconds - $rules['standard_duty_seconds']) : 0);

        return [
            'status' => $status,
            'attendance_status' => $effectiveAttendanceStatus,
            'clock_in' => $attendance['clock_in'] ?? null,
            'clock_out' => $attendance['clock_out'] ?? null,
            'raw_work_seconds' => $rawWorkSeconds,
            'work_seconds' => $workSeconds,
            'break_seconds' => $breakSeconds,
            'break_deduction_seconds' => $breakDeductionSeconds,
            'overtime_seconds' => $overtimeSeconds,
            'standard_duty_seconds' => $rules['standard_duty_seconds'],
            'minimum_work_seconds' => $rules['minimum_work_seconds'],
            'max_break_minutes' => $rules['max_break_minutes'],
            'break_alert_minutes' => $rules['break_alert_minutes'],
            'raw_work_time' => $this->formatDuration($rawWorkSeconds),
            'work_time' => $this->formatDuration($workSeconds),
            'break_time' => $this->formatDuration($breakSeconds),
            'break_deduction_time' => $this->formatDuration($breakDeductionSeconds),
            'overtime_time' => $this->formatDuration($overtimeSeconds),
        ];
    }

    public function punchIn(int $userId): array
    {
        $rules = (new SettingsRepository())->attendanceRulesForEngine();
        $existingAttendance = $this->todayAttendance($userId);

        if ($existingAttendance && !empty($existingAttendance['clock_in'])) {
            throw new RuntimeException('You have already punched in for today.');
        }

        $status = (date('H:i:s') > $rules['late_threshold']) ? 'late' : 'present';

        $this->db->beginTransaction();

        try {
            $attendanceStmt = $this->db->prepare("
                INSERT INTO attendance
                    (user_id, date, status, clock_in, break_seconds, total_work_seconds, overtime_seconds)
                VALUES
                    (:user_id, CURDATE(), :status, CURTIME(), 0, 0, 0)
                ON DUPLICATE KEY UPDATE
                    status = VALUES(status),
                    clock_in = COALESCE(clock_in, VALUES(clock_in)),
                    break_seconds = 0,
                    break_deduction_seconds = 0,
                    total_work_seconds = 0,
                    overtime_seconds = 0,
                    clock_out = NULL,
                    break_start_time = NULL,
                    break_end_time = NULL,
                    updated_at = CURRENT_TIMESTAMP
            ");
            $attendanceStmt->execute([
                'user_id' => $userId,
                'status' => $status,
            ]);

            $stmt = $this->db->prepare("
                INSERT INTO time_tracking_sessions
                    (user_id, session_date, status, punch_in_time, punch_out_time, total_work_seconds, total_break_seconds, updated_at)
                VALUES
                    (:user_id, CURDATE(), 'working', NOW(), NULL, 0, 0, NOW())
                ON DUPLICATE KEY UPDATE
                    status = 'working',
                    punch_in_time = COALESCE(punch_in_time, NOW()),
                    punch_out_time = NULL,
                    total_work_seconds = 0,
                    total_break_seconds = 0,
                    updated_at = NOW()
            ");
            $stmt->execute(['user_id' => $userId]);

            $this->db->commit();
        } catch (\Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }

        return $this->summary($userId);
    }

    public function startBreak(int $userId): array
    {
        $rules = (new SettingsRepository())->attendanceRulesForEngine();
        $session = $this->activeSession($userId);

        if (!$session || empty($session['punch_in_time'])) {
            throw new RuntimeException('Punch in first before starting a break.');
        }

        if (($session['status'] ?? 'offline') === 'break') {
            throw new RuntimeException('Break is already running.');
        }

        if (!empty($session['punch_out_time'])) {
            throw new RuntimeException('This shift is already closed.');
        }

        $updatedAt = strtotime((string) $session['updated_at']);
        $elapsedWork = max(0, time() - $updatedAt);
        $totalWorkSeconds = (int) $session['total_work_seconds'] + $elapsedWork;

        $this->db->beginTransaction();

        try {
            $sessionStmt = $this->db->prepare("
                UPDATE time_tracking_sessions
                SET status = 'break',
                    total_work_seconds = :total_work_seconds,
                    updated_at = NOW()
                WHERE user_id = :user_id
                  AND session_date = CURDATE()
            ");
            $sessionStmt->execute([
                'total_work_seconds' => $totalWorkSeconds,
                'user_id' => $userId,
            ]);

            $attendanceStmt = $this->db->prepare("
                UPDATE attendance
                SET break_start_time = CURTIME(),
                    break_end_time = NULL,
                    break_deduction_seconds = 0,
                    total_work_seconds = :total_work_seconds,
                    overtime_seconds = :overtime_seconds,
                    updated_at = CURRENT_TIMESTAMP
                WHERE user_id = :user_id
                  AND date = CURDATE()
            ");
            $attendanceStmt->execute([
                'total_work_seconds' => $totalWorkSeconds,
                'overtime_seconds' => $rules['overtime_enabled'] ? max(0, $totalWorkSeconds - $rules['standard_duty_seconds']) : 0,
                'user_id' => $userId,
            ]);

            $this->db->commit();
        } catch (\Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }

        return $this->summary($userId);
    }

    public function resumeWork(int $userId): array
    {
        $session = $this->activeSession($userId);

        if (!$session || ($session['status'] ?? 'offline') !== 'break') {
            throw new RuntimeException('No active break to resume.');
        }

        $updatedAt = strtotime((string) $session['updated_at']);
        $elapsedBreak = max(0, time() - $updatedAt);
        $totalBreakSeconds = (int) $session['total_break_seconds'] + $elapsedBreak;

        $this->db->beginTransaction();

        try {
            $sessionStmt = $this->db->prepare("
                UPDATE time_tracking_sessions
                SET status = 'working',
                    total_break_seconds = :total_break_seconds,
                    updated_at = NOW()
                WHERE user_id = :user_id
                  AND session_date = CURDATE()
            ");
            $sessionStmt->execute([
                'total_break_seconds' => $totalBreakSeconds,
                'user_id' => $userId,
            ]);

            $attendanceStmt = $this->db->prepare("
                UPDATE attendance
                SET break_end_time = CURTIME(),
                    break_seconds = :break_seconds,
                    break_deduction_seconds = 0,
                    updated_at = CURRENT_TIMESTAMP
                WHERE user_id = :user_id
                  AND date = CURDATE()
            ");
            $attendanceStmt->execute([
                'break_seconds' => $totalBreakSeconds,
                'user_id' => $userId,
            ]);

            $this->db->commit();
        } catch (\Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }

        return $this->summary($userId);
    }

    public function punchOut(int $userId): array
    {
        $rules = (new SettingsRepository())->attendanceRulesForEngine();
        $session = $this->activeSession($userId);

        if (!$session || empty($session['punch_in_time'])) {
            throw new RuntimeException('Punch in first before punching out.');
        }

        if (!empty($session['punch_out_time'])) {
            throw new RuntimeException('You have already punched out for today.');
        }

        $updatedAt = strtotime((string) $session['updated_at']);
        $elapsed = max(0, time() - $updatedAt);

        $totalWorkSeconds = (int) $session['total_work_seconds'];
        $totalBreakSeconds = (int) $session['total_break_seconds'];
        $breakEndedNow = false;

        if (($session['status'] ?? 'offline') === 'working') {
            $totalWorkSeconds += $elapsed;
        } elseif (($session['status'] ?? 'offline') === 'break') {
            $totalBreakSeconds += $elapsed;
            $breakEndedNow = true;
        }

        $attendance = $this->todayAttendance($userId);
        $grossSeconds = $totalWorkSeconds + $totalBreakSeconds;
        [$status, $totalWorkSeconds, $breakDeductionSeconds, $overtimeSeconds] = $this->resolveComputedTotals(
            $attendance,
            $grossSeconds,
            $totalBreakSeconds,
            $rules
        );

        $this->db->beginTransaction();

        try {
            $sessionStmt = $this->db->prepare("
                UPDATE time_tracking_sessions
                SET status = 'offline',
                    punch_out_time = NOW(),
                    total_work_seconds = :total_work_seconds,
                    total_break_seconds = :total_break_seconds,
                    updated_at = NOW()
                WHERE user_id = :user_id
                  AND session_date = CURDATE()
            ");
            $sessionStmt->execute([
                'total_work_seconds' => $totalWorkSeconds,
                'total_break_seconds' => $totalBreakSeconds,
                'user_id' => $userId,
            ]);

            $attendanceSql = "
                UPDATE attendance
                SET clock_out = CURTIME(),
                    status = :status,
                    break_seconds = :break_seconds,
                    break_deduction_seconds = :break_deduction_seconds,
                    total_work_seconds = :total_work_seconds,
                    overtime_seconds = :overtime_seconds,
                    updated_at = CURRENT_TIMESTAMP
            ";

            if ($breakEndedNow) {
                $attendanceSql .= ", break_end_time = CURTIME() ";
            }

            $attendanceSql .= "
                WHERE user_id = :user_id
                  AND date = CURDATE()
            ";

            $attendanceStmt = $this->db->prepare($attendanceSql);
            $attendanceStmt->execute([
                'status' => $status,
                'break_seconds' => $totalBreakSeconds,
                'break_deduction_seconds' => $breakDeductionSeconds,
                'total_work_seconds' => $totalWorkSeconds,
                'overtime_seconds' => $overtimeSeconds,
                'user_id' => $userId,
            ]);

            $this->db->commit();
        } catch (\Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }

        return $this->summary($userId);
    }

    public function todayAttendance(int $userId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT *
            FROM attendance
            WHERE user_id = :user_id
              AND date = CURDATE()
            LIMIT 1
        ");
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetch() ?: null;
    }

    private function formatDuration(int $seconds): string
    {
        $seconds = max(0, $seconds);
        $hours = str_pad((string) floor($seconds / 3600), 2, '0', STR_PAD_LEFT);
        $minutes = str_pad((string) floor(($seconds % 3600) / 60), 2, '0', STR_PAD_LEFT);
        $remainingSeconds = str_pad((string) ($seconds % 60), 2, '0', STR_PAD_LEFT);

        return $hours . ':' . $minutes . ':' . $remainingSeconds;
    }

    private function resolveAttendanceStatus(string $currentStatus, ?array $attendance, int $totalWorkSeconds, array $rules): string
    {
        if (in_array($currentStatus, ['absent', 'on_leave'], true)) {
            return $currentStatus;
        }

        if ($attendance && empty($attendance['clock_in'])) {
            return 'absent';
        }

        if ($attendance && !empty($attendance['clock_out']) && $totalWorkSeconds < (int) $rules['half_day_seconds']) {
            return 'half_day';
        }

        if ($attendance && !empty($attendance['clock_in'])) {
            $lateThreshold = strtotime(date('Y-m-d') . ' ' . $rules['late_threshold']);
            $clockInStamp = strtotime(date('Y-m-d') . ' ' . $attendance['clock_in']);
            if ($clockInStamp !== false && $lateThreshold !== false && $clockInStamp > $lateThreshold) {
                return 'late';
            }
        }

        if ($totalWorkSeconds >= (int) $rules['minimum_work_seconds']) {
            return 'present';
        }

        return 'present';
    }

    private function liveWorkSecondsFromAttendance(?array $attendance, int $breakSeconds, array $rules): int
    {
        if (!$attendance || empty($attendance['clock_in'])) {
            return 0;
        }

        $date = date('Y-m-d');
        $start = strtotime($date . ' ' . $attendance['clock_in']);
        if ($start === false) {
            return 0;
        }

        if (!empty($attendance['clock_out'])) {
            $end = strtotime($date . ' ' . $attendance['clock_out']);
        } else {
            $end = time();
        }

        if ($end === false) {
            return 0;
        }

        $grossSeconds = max(0, ($end - $start));
        [, $workedSeconds] = $this->resolveComputedTotals($attendance, $grossSeconds, $breakSeconds, $rules);

        return $workedSeconds;
    }

    private function applyBreakPolicy(int $grossSeconds, int $breakSeconds, string $status, array $rules): int
    {
        $deduction = $this->breakDeductionSeconds($breakSeconds, $status, $rules);

        return max(0, $grossSeconds - $deduction);
    }

    private function breakDeductionSeconds(int $breakSeconds, string $status, array $rules): int
    {
        $allowedBreakSeconds = (int) ($rules['break_time_seconds'] ?? (((int) ($rules['break_time'] ?? 0)) * 60));

        if ($status === 'half_day') {
            return max(0, $breakSeconds);
        }

        return max(0, $breakSeconds - $allowedBreakSeconds);
    }

    private function effectiveAttendanceStatus(?array $attendance, int $workSeconds, array $rules): string
    {
        if (!$attendance) {
            return 'absent';
        }

        return $this->resolveAttendanceStatus((string) ($attendance['status'] ?? 'absent'), $attendance, $workSeconds, $rules);
    }

    private function resolveComputedTotals(?array $attendance, int $grossSeconds, int $breakSeconds, array $rules): array
    {
        $baseStatus = (string) ($attendance['status'] ?? 'present');
        $provisionalStatus = in_array($baseStatus, ['absent', 'on_leave'], true) ? $baseStatus : 'present';
        $workedSeconds = $this->applyBreakPolicy($grossSeconds, $breakSeconds, $provisionalStatus, $rules);
        $status = $this->resolveAttendanceStatus($baseStatus, $attendance, $workedSeconds, $rules);
        $breakDeductionSeconds = $this->breakDeductionSeconds($breakSeconds, $status, $rules);
        $workedSeconds = max(0, $grossSeconds - $breakDeductionSeconds);
        $overtimeSeconds = $status === 'half_day'
            ? 0
            : ($rules['overtime_enabled'] ? max(0, $workedSeconds - $rules['standard_duty_seconds']) : 0);

        return [$status, $workedSeconds, $breakDeductionSeconds, $overtimeSeconds];
    }
}
