<?php

namespace App\Repositories;

use RuntimeException;

class AttendanceRepository extends BaseRepository
{
    public function adminEmployeeCards(array $filters = []): array
    {
        $date = $filters['date'] ?? date('Y-m-d');
        $params = ['date' => $date];
        $conditions = [
            "u.role IN ('manager', 'employee')",
            'u.deleted_at IS NULL',
        ];

        if (!empty($filters['department'])) {
            $conditions[] = 'u.department = :department';
            $params['department'] = $filters['department'];
        }

        $sql = "
            SELECT
                u.id,
                u.name,
                u.email,
                u.department,
                u.designation,
                u.profile_image,
                u.profile_thumbnail,
                u.profile_image_path,
                a.id AS attendance_id,
                a.date,
                a.status AS attendance_status,
                a.clock_in,
                a.clock_out,
                a.break_seconds,
                a.break_deduction_seconds,
                a.total_work_seconds,
                a.overtime_seconds,
                t.status AS tracking_status,
                t.total_work_seconds AS session_work_seconds,
                t.total_break_seconds AS session_break_seconds,
                t.updated_at AS session_updated_at
            FROM users u
            LEFT JOIN attendance a
                ON a.user_id = u.id
               AND a.date = :date
            LEFT JOIN time_tracking_sessions t
                ON t.user_id = u.id
               AND t.session_date = :date
            WHERE " . implode(' AND ', $conditions) . "
            ORDER BY u.name ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $cards = [];
        foreach ($stmt->fetchAll() as $row) {
            $cards[] = $this->decorateAdminCard($row);
        }

        if (!empty($filters['status'])) {
            $cards = array_values(array_filter($cards, static function (array $card) use ($filters): bool {
                return $card['filter_status'] === $filters['status'] || $card['ui_status'] === $filters['status'];
            }));
        }

        return $cards;
    }

    public function liveAttendanceCards(): array
    {
        return $this->adminEmployeeCards(['date' => date('Y-m-d')]);
    }

    public function adminEmployeeDetail(int $employeeId, ?int $month = null, ?int $year = null): array
    {
        $month = $month ?: (int) date('n');
        $year = $year ?: (int) date('Y');
        $rules = (new SettingsRepository())->attendanceRulesForEngine();

        $userStmt = $this->db->prepare("
            SELECT id, name, email, department, designation, role, profile_thumbnail, profile_image, profile_image_path, shift_name, shift_start, shift_end
            FROM users
            WHERE id = :id
              AND role IN ('manager', 'employee')
              AND deleted_at IS NULL
            LIMIT 1
        ");
        $userStmt->execute(['id' => $employeeId]);
        $employee = $userStmt->fetch();

        if (!$employee) {
            throw new RuntimeException('Employee not found.');
        }

        $summaryStmt = $this->db->prepare("
            SELECT
                SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) AS present_days,
                SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) AS late_days,
                SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) AS absent_days,
                SUM(CASE WHEN status = 'half_day' THEN 1 ELSE 0 END) AS half_day_days,
                SUM(CASE WHEN status = 'on_leave' THEN 1 ELSE 0 END) AS on_leave_days,
                COUNT(*) AS total_records,
                COALESCE(SUM(total_work_seconds), 0) AS total_work_seconds,
                COALESCE(SUM(overtime_seconds), 0) AS total_overtime_seconds,
                COALESCE(SUM(break_seconds), 0) AS total_break_seconds,
                COALESCE(SUM(break_deduction_seconds), 0) AS total_break_deduction_seconds
            FROM attendance
            WHERE user_id = :user_id
              AND MONTH(date) = :month
              AND YEAR(date) = :year
        ");
        $summaryStmt->execute([
            'user_id' => $employeeId,
            'month' => $month,
            'year' => $year,
        ]);
        $summary = $summaryStmt->fetch() ?: [];

        $todayAttendanceStmt = $this->db->prepare("
            SELECT *
            FROM attendance
            WHERE user_id = :user_id
              AND date = CURDATE()
            LIMIT 1
        ");
        $todayAttendanceStmt->execute(['user_id' => $employeeId]);
        $todayAttendance = $todayAttendanceStmt->fetch() ?: null;
        $todaySession = $this->sessionForDate($employeeId, date('Y-m-d'));
        $liveToday = $this->resolveLiveEntry($todayAttendance ?: ['date' => date('Y-m-d')], $todaySession, $rules);

        if ($month === (int) date('n') && $year === (int) date('Y') && $todayAttendance) {
            $summary['total_work_seconds'] = (int) ($summary['total_work_seconds'] ?? 0) - (int) ($todayAttendance['total_work_seconds'] ?? 0) + (int) ($liveToday['total_work_seconds'] ?? 0);
            $summary['total_overtime_seconds'] = (int) ($summary['total_overtime_seconds'] ?? 0) - (int) ($todayAttendance['overtime_seconds'] ?? 0) + (int) ($liveToday['overtime_seconds'] ?? 0);
            $summary['total_break_seconds'] = (int) ($summary['total_break_seconds'] ?? 0) - (int) ($todayAttendance['break_seconds'] ?? 0) + (int) ($liveToday['break_seconds'] ?? 0);
            $summary['total_break_deduction_seconds'] = (int) ($summary['total_break_deduction_seconds'] ?? 0) - (int) ($todayAttendance['break_deduction_seconds'] ?? 0) + (int) ($liveToday['break_deduction_seconds'] ?? 0);
        }

        $summary['total_work_time'] = $this->formatDuration((int) ($summary['total_work_seconds'] ?? 0));
        $summary['total_overtime'] = $this->formatDuration((int) ($summary['total_overtime_seconds'] ?? 0));
        $summary['total_break_time'] = $this->formatDuration((int) ($summary['total_break_seconds'] ?? 0));
        $summary['total_break_deduction_time'] = $this->formatDuration((int) ($summary['total_break_deduction_seconds'] ?? 0));

        $historyStmt = $this->db->prepare("
            SELECT *
            FROM attendance
            WHERE user_id = :user_id
            ORDER BY date DESC
            LIMIT 31
        ");
        $historyStmt->execute(['user_id' => $employeeId]);
        $dailyAttendance = [];
        foreach ($historyStmt->fetchAll() as $row) {
            if (($row['date'] ?? '') === date('Y-m-d')) {
                $row = $this->resolveLiveEntry($row, $todaySession, $rules);
            }
            $dailyAttendance[] = $this->decorateEntry($row);
        }

        $breakHistory = array_values(array_filter($dailyAttendance, static function (array $entry): bool {
            return !empty($entry['break_seconds']);
        }));

        $statusHistory = array_map(static function (array $entry): array {
            return [
                'date' => $entry['date'],
                'status' => $entry['status'],
                'clock_in' => $entry['clock_in'] ?? '--',
                'clock_out' => $entry['clock_out'] ?? '--',
            ];
        }, $dailyAttendance);

        return [
            'employee' => $employee,
            'monthly_summary' => $summary,
            'daily_attendance' => $dailyAttendance,
            'break_history' => $breakHistory,
            'status_history' => $statusHistory,
            'calendar' => $this->monthlyCalendar(['id' => $employeeId], $month, $year),
            'month' => $month,
            'year' => $year,
        ];
    }

    public function listForUser(array $user, array $filters = []): array
    {
        $params = [];
        $conditions = ['1=1'];
        $rules = (new SettingsRepository())->attendanceRulesForEngine();

        if (!empty($filters['date'])) {
            $conditions[] = 'a.date = :date';
            $params['date'] = $filters['date'];
        }

        if (!empty($filters['status'])) {
            $conditions[] = 'a.status = :status';
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['department'])) {
            $conditions[] = 'u.department = :department';
            $params['department'] = $filters['department'];
        }

        if ($user['role'] === 'admin') {
            $sql = "
                SELECT a.*, u.name, u.department
                FROM attendance a
                JOIN users u ON u.id = a.user_id
                WHERE " . implode(' AND ', $conditions) . "
                ORDER BY a.date DESC, u.name ASC
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            return array_map([$this, 'decorateEntry'], $stmt->fetchAll());
        }

        if ($user['role'] === 'manager') {
            $conditions[] = '(u.manager_id = :manager_id OR u.id = :manager_id)';
            $params['manager_id'] = $user['id'];
        } else {
            $conditions[] = 'a.user_id = :user_id';
            $params['user_id'] = $user['id'];
        }

        $stmt = $this->db->prepare("
            SELECT a.*, u.name, u.department
            FROM attendance a
            JOIN users u ON u.id = a.user_id
            WHERE " . implode(' AND ', $conditions) . "
            ORDER BY a.date DESC, u.name ASC
        ");
        $stmt->execute($params);

        $entries = [];
        foreach ($stmt->fetchAll() as $row) {
            if (($row['date'] ?? '') === date('Y-m-d')) {
                $session = $this->sessionForDate((int) $row['user_id'], $row['date']);
                $row = $this->resolveLiveEntry($row, $session, $rules);
            }
            $entries[] = $this->decorateEntry($row);
        }

        return $entries;
    }

    public function metrics(array $user): array
    {
        $rules = (new SettingsRepository())->attendanceRulesForEngine();
        $condition = '1=1';
        $params = [];

        if ($user['role'] === 'manager') {
            $condition = '(u.manager_id = :manager_id OR a.user_id = :manager_id)';
            $params['manager_id'] = $user['id'];
        } elseif ($user['role'] === 'employee') {
            $condition = 'a.user_id = :user_id';
            $params['user_id'] = $user['id'];
        }

        $stmt = $this->db->prepare("
            SELECT
                SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) AS present_days,
                SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) AS absent_days,
                SUM(CASE WHEN a.status = 'late' THEN 1 ELSE 0 END) AS late_days,
                SUM(CASE WHEN a.status = 'half_day' THEN 1 ELSE 0 END) AS half_day_days,
                SUM(CASE WHEN a.status = 'on_leave' THEN 1 ELSE 0 END) AS on_leave_days,
                COUNT(a.id) AS total_days,
                COALESCE(SUM(a.break_seconds), 0) AS total_break_seconds,
                COALESCE(SUM(a.break_deduction_seconds), 0) AS total_break_deduction_seconds,
                COALESCE(SUM(a.total_work_seconds), 0) AS total_work_seconds,
                COALESCE(SUM(a.overtime_seconds), 0) AS total_overtime_seconds,
                ROUND((SUM(CASE WHEN a.status IN ('present', 'late', 'half_day') THEN 1 ELSE 0 END) / NULLIF(COUNT(a.id), 0)) * 100, 2) AS attendance_percentage
            FROM attendance a
            JOIN users u ON u.id = a.user_id
            WHERE {$condition}
              AND MONTH(a.date) = MONTH(CURDATE())
              AND YEAR(a.date) = YEAR(CURDATE())
        ");
        $stmt->execute($params);
        $metrics = $stmt->fetch() ?: [];

        if ((int) date('n') === (int) date('n') && (int) date('Y') === (int) date('Y')) {
            $todayAdjustments = $this->liveMetricsAdjustments($user, $rules);
            $metrics['total_break_seconds'] = (int) ($metrics['total_break_seconds'] ?? 0) + $todayAdjustments['break_seconds_delta'];
            $metrics['total_break_deduction_seconds'] = (int) ($metrics['total_break_deduction_seconds'] ?? 0) + $todayAdjustments['break_deduction_seconds_delta'];
            $metrics['total_work_seconds'] = (int) ($metrics['total_work_seconds'] ?? 0) + $todayAdjustments['work_seconds_delta'];
            $metrics['total_overtime_seconds'] = (int) ($metrics['total_overtime_seconds'] ?? 0) + $todayAdjustments['overtime_seconds_delta'];
        }

        $metrics['total_raw_work_seconds'] = (int) ($metrics['total_work_seconds'] ?? 0) + (int) ($metrics['total_break_deduction_seconds'] ?? 0);
        $metrics['total_break_time'] = $this->formatDuration((int) ($metrics['total_break_seconds'] ?? 0));
        $metrics['total_break_deduction_time'] = $this->formatDuration((int) ($metrics['total_break_deduction_seconds'] ?? 0));
        $metrics['total_raw_work_time'] = $this->formatDuration((int) ($metrics['total_raw_work_seconds'] ?? 0));
        $metrics['total_work_time'] = $this->formatDuration((int) ($metrics['total_work_seconds'] ?? 0));
        $metrics['total_overtime'] = $this->formatDuration((int) ($metrics['total_overtime_seconds'] ?? 0));

        return $metrics;
    }

    public function todayOverview(array $filters = []): array
    {
        $date = $filters['date'] ?? date('Y-m-d');
        $department = $filters['department'] ?? null;

        $employeeStmt = $this->db->prepare("
            SELECT COUNT(*) AS total_employees
            FROM users
            WHERE role IN ('manager', 'employee')
              " . ($department ? 'AND department = :department' : '') . "
        ");
        $employeeParams = [];
        if ($department) {
            $employeeParams['department'] = $department;
        }
        $employeeStmt->execute($employeeParams);
        $employeeCount = (int) (($employeeStmt->fetch()['total_employees'] ?? 0));

        $stmt = $this->db->prepare("
            SELECT
                SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) AS present_today,
                SUM(CASE WHEN a.status = 'late' THEN 1 ELSE 0 END) AS late_today,
                SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) AS absent_today,
                SUM(CASE WHEN a.status = 'half_day' THEN 1 ELSE 0 END) AS half_day_today,
                SUM(CASE WHEN a.status = 'on_leave' THEN 1 ELSE 0 END) AS on_leave_today,
                SUM(CASE WHEN t.status = 'working' THEN 1 ELSE 0 END) AS working_now,
                SUM(CASE WHEN t.status = 'break' THEN 1 ELSE 0 END) AS on_break_now,
                SUM(CASE WHEN a.overtime_seconds > 0 THEN 1 ELSE 0 END) AS overtime_today,
                COALESCE(SUM(a.overtime_seconds), 0) AS overtime_seconds_today
            FROM users u
            LEFT JOIN attendance a
                ON a.user_id = u.id
               AND a.date = :date
            LEFT JOIN time_tracking_sessions t
                ON t.user_id = u.id
               AND t.session_date = :date
            WHERE u.role IN ('manager', 'employee')
              " . ($department ? 'AND u.department = :department' : '') . "
        ");
        $params = ['date' => $date];
        if ($department) {
            $params['department'] = $department;
        }
        $stmt->execute($params);
        $overview = $stmt->fetch() ?: [];
        $overview['total_employees'] = $employeeCount;
        $overview['overtime_time_today'] = $this->formatDuration((int) ($overview['overtime_seconds_today'] ?? 0));

        return $overview;
    }

    public function lateMonitoring(array $filters = []): array
    {
        $rules = (new SettingsRepository())->attendanceRulesForEngine();
        $date = $filters['date'] ?? date('Y-m-d');
        $department = $filters['department'] ?? null;

        $todayStmt = $this->db->prepare("
            SELECT
                u.name,
                u.department,
                a.clock_in,
                GREATEST(TIMESTAMPDIFF(MINUTE, CONCAT(a.date, ' ', :late_threshold), CONCAT(a.date, ' ', a.clock_in)), 0) AS late_minutes
            FROM attendance a
            JOIN users u ON u.id = a.user_id
            WHERE a.date = :date
              AND a.status = 'late'
              " . ($department ? 'AND u.department = :department' : '') . "
            ORDER BY late_minutes DESC, u.name ASC
        ");
        $params = ['date' => $date, 'late_threshold' => $rules['late_threshold']];
        if ($department) {
            $params['department'] = $department;
        }
        $todayStmt->execute($params);

        $repeatStmt = $this->db->prepare("
            SELECT
                u.name,
                u.department,
                COUNT(*) AS late_count
            FROM attendance a
            JOIN users u ON u.id = a.user_id
            WHERE a.status = 'late'
              AND MONTH(a.date) = MONTH(:date)
              AND YEAR(a.date) = YEAR(:date)
              " . ($department ? 'AND u.department = :department' : '') . "
            GROUP BY u.id, u.name, u.department
            HAVING COUNT(*) > 0
            ORDER BY late_count DESC, u.name ASC
            LIMIT 5
        ");
        $repeatStmt->execute($params);

        return [
            'today' => $todayStmt->fetchAll(),
            'repeat' => $repeatStmt->fetchAll(),
        ];
    }

    public function absentManagement(array $filters = []): array
    {
        $date = $filters['date'] ?? date('Y-m-d');
        $department = $filters['department'] ?? null;

        $dailyStmt = $this->db->prepare("
            SELECT u.name, u.department, a.status
            FROM attendance a
            JOIN users u ON u.id = a.user_id
            WHERE a.date = :date
              AND a.status = 'absent'
              " . ($department ? 'AND u.department = :department' : '') . "
            ORDER BY u.name ASC
        ");
        $params = ['date' => $date];
        if ($department) {
            $params['department'] = $department;
        }
        $dailyStmt->execute($params);

        $consecutiveStmt = $this->db->prepare("
            SELECT
                u.name,
                u.department,
                COUNT(*) AS absent_days
            FROM attendance a
            JOIN users u ON u.id = a.user_id
            WHERE a.status = 'absent'
              AND a.date >= DATE_SUB(:date, INTERVAL 7 DAY)
              " . ($department ? 'AND u.department = :department' : '') . "
            GROUP BY u.id, u.name, u.department
            HAVING COUNT(*) >= 2
            ORDER BY absent_days DESC, u.name ASC
        ");
        $consecutiveStmt->execute($params);

        return [
            'daily' => $dailyStmt->fetchAll(),
            'consecutive' => $consecutiveStmt->fetchAll(),
        ];
    }

    public function monthlyCalendar(array $user, ?int $month = null, ?int $year = null): array
    {
        $month = $month ?: (int) date('n');
        $year = $year ?: (int) date('Y');
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);

        $stmt = $this->db->prepare("
            SELECT
                DAY(date) AS day_number,
                status,
                overtime_seconds
            FROM attendance
            WHERE user_id = :user_id
              AND MONTH(date) = :month
              AND YEAR(date) = :year
        ");
        $stmt->execute([
            'user_id' => $user['id'],
            'month' => $month,
            'year' => $year,
        ]);

        $rows = [];
        foreach ($stmt->fetchAll() as $row) {
            $rows[(int) $row['day_number']] = $row;
        }

        $calendar = [];
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $entry = $rows[$day] ?? null;
            $status = $entry['status'] ?? 'absent';
            $calendar[] = [
                'day' => $day,
                'status' => $status,
                'label' => ucfirst(str_replace('_', ' ', $status)),
                'is_overtime' => !empty($entry['overtime_seconds']),
            ];
        }

        return $calendar;
    }

    public function departments(): array
    {
        return $this->db->query("
            SELECT DISTINCT department
            FROM users
            WHERE department IS NOT NULL
              AND department != ''
            ORDER BY department
        ")->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT a.*, u.name, u.department
            FROM attendance a
            JOIN users u ON u.id = a.user_id
            WHERE a.id = :id
            LIMIT 1
        ");
        $stmt->execute(['id' => $id]);
        $record = $stmt->fetch();

        return $record ? $this->decorateEntry($record) : null;
    }

    public function updateByAdmin(int $attendanceId, array $payload, array $actor): array
    {
        $rules = (new SettingsRepository())->attendanceRulesForEngine();
        if ($actor['role'] !== 'admin') {
            throw new RuntimeException('Only admin can update attendance records.');
        }

        $record = $this->findById($attendanceId);

        if (!$record) {
            throw new RuntimeException('Attendance record not found.');
        }

        $status = (string) ($payload['status'] ?? $record['status']);
        $clockIn = $this->normalizeTime($payload['clock_in'] ?? $record['clock_in']);
        $clockOut = $this->normalizeTime($payload['clock_out'] ?? $record['clock_out']);
        $breakSeconds = $this->parseDurationToSeconds($payload['break_time'] ?? $record['break_time']);
        $remarks = trim((string) ($payload['remarks'] ?? $record['remarks'] ?? ''));
        $shiftName = trim((string) ($payload['shift_name'] ?? $record['shift_name'] ?? 'General'));
        $overtimeApproved = !empty($payload['overtime_approved']) ? 1 : 0;

        [$status, $totalWorkSeconds, $overtimeSeconds, $breakDeductionSeconds] = $this->resolveAttendanceComputation(
            $record,
            $clockIn,
            $clockOut,
            $breakSeconds,
            $rules,
            $status
        );

        $stmt = $this->db->prepare("
            UPDATE attendance
            SET status = :status,
                clock_in = :clock_in,
                clock_out = :clock_out,
                break_seconds = :break_seconds,
                break_deduction_seconds = :break_deduction_seconds,
                total_work_seconds = :total_work_seconds,
                overtime_seconds = :overtime_seconds,
                remarks = :remarks,
                shift_name = :shift_name,
                overtime_approved = :overtime_approved,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id
        ");
        $stmt->execute([
            'status' => $status,
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
            'break_seconds' => $breakSeconds,
            'break_deduction_seconds' => $breakDeductionSeconds,
            'total_work_seconds' => $totalWorkSeconds,
            'overtime_seconds' => $overtimeSeconds,
            'remarks' => $remarks !== '' ? $remarks : null,
            'shift_name' => $shiftName !== '' ? $shiftName : 'General',
            'overtime_approved' => $overtimeApproved,
            'id' => $attendanceId,
        ]);

        $this->syncSessionFromAttendance((int) $record['user_id'], $record['date'], $clockIn, $clockOut, $breakSeconds, $totalWorkSeconds, $status);

        return $this->findById($attendanceId);
    }

    public function autoMarkAbsences(?string $date = null): int
    {
        $targetDate = $date ?: date('Y-m-d');

        $stmt = $this->db->prepare("
            INSERT INTO attendance (user_id, date, status, break_seconds, break_deduction_seconds, total_work_seconds, overtime_seconds, shift_name)
            SELECT u.id, :target_date, 'absent', 0, 0, 0, 0, 'General'
            FROM users u
            WHERE u.role IN ('manager', 'employee')
              AND NOT EXISTS (
                  SELECT 1
                  FROM attendance a
                  WHERE a.user_id = u.id
                    AND a.date = :target_date
              )
        ");
        $stmt->execute(['target_date' => $targetDate]);

        return $stmt->rowCount();
    }

    public function recalculateStoredAttendance(?int $userId = null): int
    {
        $rules = (new SettingsRepository())->attendanceRulesForEngine();
        $params = [];
        $sql = "
            SELECT *
            FROM attendance
        ";

        if ($userId !== null) {
            $sql .= " WHERE user_id = :user_id";
            $params['user_id'] = $userId;
        }

        $sql .= " ORDER BY date ASC, id ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $updateStmt = $this->db->prepare("
            UPDATE attendance
            SET status = :status,
                break_deduction_seconds = :break_deduction_seconds,
                total_work_seconds = :total_work_seconds,
                overtime_seconds = :overtime_seconds,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id
        ");

        $updated = 0;
        foreach ($stmt->fetchAll() as $row) {
            [$status, $totalWorkSeconds, $overtimeSeconds, $breakDeductionSeconds] = $this->resolveAttendanceComputation(
                $row,
                $this->normalizeTime($row['clock_in'] ?? null),
                $this->normalizeTime($row['clock_out'] ?? null),
                (int) ($row['break_seconds'] ?? 0),
                $rules,
                (string) ($row['status'] ?? 'present')
            );

            $updateStmt->execute([
                'status' => $status,
                'break_deduction_seconds' => $breakDeductionSeconds,
                'total_work_seconds' => $totalWorkSeconds,
                'overtime_seconds' => $overtimeSeconds,
                'id' => (int) $row['id'],
            ]);
            $updated += $updateStmt->rowCount();
        }

        return $updated;
    }

    private function decorateEntry(array $entry): array
    {
        $rawWorkSeconds = $this->grossWorkSecondsFromEntry($entry);
        if ($rawWorkSeconds <= 0 && !empty($entry['total_work_seconds'])) {
            $rawWorkSeconds = (int) ($entry['total_work_seconds'] ?? 0) + (int) ($entry['break_deduction_seconds'] ?? 0);
        }

        $entry['raw_work_seconds'] = $rawWorkSeconds;
        $entry['raw_hours'] = $this->formatDuration($rawWorkSeconds);
        $entry['break_time'] = $this->formatDuration((int) ($entry['break_seconds'] ?? 0));
        $entry['break_deduction_time'] = $this->formatDuration((int) ($entry['break_deduction_seconds'] ?? 0));
        $entry['net_hours'] = $this->formatDuration((int) ($entry['total_work_seconds'] ?? 0));
        $entry['total_hours'] = $entry['net_hours'];
        $entry['overtime'] = $this->formatDuration((int) ($entry['overtime_seconds'] ?? 0));
        $entry['has_overtime'] = (int) ($entry['overtime_seconds'] ?? 0) > 0;
        $entry['remarks'] = $entry['remarks'] ?? '';
        $entry['shift_name'] = $entry['shift_name'] ?? 'General';
        $entry['overtime_approved'] = !empty($entry['overtime_approved']);

        if (!empty($entry['clock_in']) && $entry['status'] === 'late') {
            $entry['late_minutes'] = max(0, floor((strtotime($entry['date'] . ' ' . $entry['clock_in']) - strtotime($entry['date'] . ' 09:15:00')) / 60));
        } else {
            $entry['late_minutes'] = 0;
        }

        return $entry;
    }

    private function resolveLiveEntry(array $entry, ?array $session, array $rules): array
    {
        if (empty($entry['date']) || $entry['date'] !== date('Y-m-d')) {
            return $entry;
        }

        $breakSeconds = (int) ($entry['break_seconds'] ?? 0);
        $status = (string) ($session['status'] ?? 'offline');
        $updatedAt = !empty($session['updated_at']) ? strtotime((string) $session['updated_at']) : null;

        if (!empty($session['total_break_seconds'])) {
            $breakSeconds = max($breakSeconds, (int) $session['total_break_seconds']);
        }

        if ($updatedAt && $status === 'break') {
            $elapsed = max(0, time() - $updatedAt);
            $breakSeconds += $elapsed;
        }

        $computed = $this->resolveAttendanceComputation(
            $entry,
            $this->normalizeTime($entry['clock_in'] ?? null),
            $this->normalizeTime($entry['clock_out'] ?? null),
            $breakSeconds,
            $rules,
            (string) ($entry['status'] ?? 'present')
        );
        [, $workSeconds, , $breakDeductionSeconds] = $computed;

        if ($workSeconds === 0 && !empty($session['total_work_seconds'])) {
            $workSeconds = (int) $session['total_work_seconds'];
            if ($updatedAt && $status === 'working') {
                $workSeconds += max(0, time() - $updatedAt);
            }
        }

        $entry['break_seconds'] = $breakSeconds;
        $entry['break_deduction_seconds'] = $breakDeductionSeconds;
        $entry['total_work_seconds'] = max(0, $workSeconds);
        $entry['status'] = $computed[0];
        $entry['overtime_seconds'] = $computed[2];

        if ($status === 'break' && empty($entry['clock_out'])) {
            $entry['break_end_time'] = null;
        }

        return $entry;
    }

    private function sessionForDate(int $userId, string $date): ?array
    {
        $stmt = $this->db->prepare("
            SELECT *
            FROM time_tracking_sessions
            WHERE user_id = :user_id
              AND session_date = :session_date
            LIMIT 1
        ");
        $stmt->execute([
            'user_id' => $userId,
            'session_date' => $date,
        ]);

        return $stmt->fetch() ?: null;
    }

    private function liveMetricsAdjustments(array $user, array $rules): array
    {
        $conditions = [];
        $params = ['target_date' => date('Y-m-d')];

        if ($user['role'] === 'manager') {
            $conditions[] = '(u.manager_id = :manager_id OR a.user_id = :manager_id)';
            $params['manager_id'] = $user['id'];
        } elseif ($user['role'] === 'employee') {
            $conditions[] = 'a.user_id = :user_id';
            $params['user_id'] = $user['id'];
        }

        if (empty($conditions)) {
            return [
                'work_seconds_delta' => 0,
                'break_seconds_delta' => 0,
                'break_deduction_seconds_delta' => 0,
                'overtime_seconds_delta' => 0,
            ];
        }

        $stmt = $this->db->prepare("
            SELECT
                a.user_id,
                a.date,
                a.break_seconds,
                a.break_deduction_seconds,
                a.total_work_seconds,
                a.overtime_seconds
            FROM attendance a
            JOIN users u ON u.id = a.user_id
            WHERE a.date = :target_date
              AND " . implode(' AND ', $conditions)
        );
        $stmt->execute($params);

        $workDelta = 0;
        $breakDelta = 0;
        $breakDeductionDelta = 0;
        $overtimeDelta = 0;

        foreach ($stmt->fetchAll() as $row) {
            $session = $this->sessionForDate((int) $row['user_id'], (string) $row['date']);
            $live = $this->resolveLiveEntry($row, $session, $rules);

            $workDelta += (int) ($live['total_work_seconds'] ?? 0) - (int) ($row['total_work_seconds'] ?? 0);
            $breakDelta += (int) ($live['break_seconds'] ?? 0) - (int) ($row['break_seconds'] ?? 0);
            $breakDeductionDelta += (int) ($live['break_deduction_seconds'] ?? 0) - (int) ($row['break_deduction_seconds'] ?? 0);
            $overtimeDelta += (int) ($live['overtime_seconds'] ?? 0) - (int) ($row['overtime_seconds'] ?? 0);
        }

        return [
            'work_seconds_delta' => $workDelta,
            'break_seconds_delta' => $breakDelta,
            'break_deduction_seconds_delta' => $breakDeductionDelta,
            'overtime_seconds_delta' => $overtimeDelta,
        ];
    }

    private function decorateAdminCard(array $row): array
    {
        $rules = (new SettingsRepository())->attendanceRulesForEngine();
        $workSeconds = (int) ($row['session_work_seconds'] ?? $row['total_work_seconds'] ?? 0);
        $breakSeconds = (int) ($row['session_break_seconds'] ?? $row['break_seconds'] ?? 0);
        $trackingStatus = (string) ($row['tracking_status'] ?? 'offline');
        $updatedAt = !empty($row['session_updated_at']) ? strtotime((string) $row['session_updated_at']) : null;

        if ($updatedAt && in_array($trackingStatus, ['working', 'break'], true)) {
            $elapsed = max(0, time() - $updatedAt);
            if ($trackingStatus === 'working') {
                $workSeconds += $elapsed;
            } elseif ($trackingStatus === 'break') {
                $breakSeconds += $elapsed;
            }
        }

        $overtimeSeconds = max(0, $workSeconds - (int) ($rules['standard_duty_seconds'] ?? 32400));
        if (!empty($row['overtime_seconds'])) {
            $overtimeSeconds = max($overtimeSeconds, (int) $row['overtime_seconds']);
        }

        $attendanceStatus = (string) ($row['attendance_status'] ?? 'absent');
        $uiStatus = 'absent';
        if ($trackingStatus === 'break') {
            $uiStatus = 'on_break';
        } elseif (in_array($attendanceStatus, ['present', 'late', 'half_day'], true) || $trackingStatus === 'working') {
            $uiStatus = 'present';
        }

        $liveStatusClass = 'status-punchout';
        $currentStatus = 'Punch Out';
        $hasPunchIn = !empty($row['clock_in']);
        $hasPunchOut = !empty($row['clock_out']);
        $breakOpen = !empty($row['break_start_time']) && empty($row['break_end_time']) && !$hasPunchOut;

        if ($attendanceStatus === 'on_leave') {
            $currentStatus = 'Punch Out';
            $liveStatusClass = 'status-punchout';
        } elseif ($breakOpen || $trackingStatus === 'break') {
            $currentStatus = 'Break';
            $liveStatusClass = 'status-break';
        } elseif ($hasPunchIn && !$hasPunchOut && $overtimeSeconds > 0) {
            $currentStatus = 'Over Time';
            $liveStatusClass = 'status-overtime';
        } elseif ($hasPunchIn && !$hasPunchOut) {
            $currentStatus = 'Working';
            $liveStatusClass = 'status-working';
        }

        return [
            'id' => (int) $row['id'],
            'name' => $row['name'],
            'email' => $row['email'],
            'department' => $row['department'] ?: 'Unassigned',
            'designation' => $row['designation'] ?: 'Team Member',
            'profile_image_path' => $row['profile_image_path'] ?? null,
            'attendance_status' => $attendanceStatus,
            'tracking_status' => $trackingStatus,
            'ui_status' => $uiStatus,
            'filter_status' => $attendanceStatus,
            'work_seconds' => $workSeconds,
            'break_seconds' => $breakSeconds,
            'overtime_seconds' => $overtimeSeconds,
            'work_time' => $this->formatDuration($workSeconds),
            'break_time' => $this->formatDuration($breakSeconds),
            'overtime_time' => $this->formatDuration($overtimeSeconds),
            'current_status' => $currentStatus,
            'live_status_class' => $liveStatusClass,
        ];
    }

    private function calculateWorkAndOvertime(?string $clockIn, ?string $clockOut, int $breakSeconds, string $status, array $rules, ?string $date = null): array
    {
        if (!$clockIn || in_array($status, ['absent', 'on_leave'], true)) {
            return [0, 0, 0];
        }

        $grossSeconds = 0;
        if ($clockOut) {
            $grossSeconds = max(strtotime('1970-01-01 ' . $clockOut) - strtotime('1970-01-01 ' . $clockIn), 0);
        } elseif ($date === date('Y-m-d')) {
            $grossSeconds = $this->grossWorkSecondsFromClockTimes($date, $clockIn, null);
        }

        $breakDeductionSeconds = $this->breakDeductionSeconds($breakSeconds, $status, $rules);
        $totalSeconds = max(0, $grossSeconds - $breakDeductionSeconds);
        $overtimeSeconds = ($status === 'half_day' || empty($rules['overtime_enabled']))
            ? 0
            : max(0, $totalSeconds - $rules['standard_duty_seconds']);

        return [$totalSeconds, $overtimeSeconds, $breakDeductionSeconds];
    }

    private function liveWorkSecondsFromEntry(array $entry, int $breakSeconds, array $rules): int
    {
        $clockIn = $this->normalizeTime($entry['clock_in'] ?? null);
        $clockOut = $this->normalizeTime($entry['clock_out'] ?? null);
        [, $totalWorkSeconds] = $this->resolveAttendanceComputation(
            $entry,
            $clockIn,
            $clockOut,
            $breakSeconds,
            $rules,
            (string) ($entry['status'] ?? 'present')
        );

        return $totalWorkSeconds;
    }

    private function grossWorkSecondsFromEntry(array $entry): int
    {
        $date = (string) ($entry['date'] ?? '');
        $clockIn = $this->normalizeTime($entry['clock_in'] ?? null);
        $clockOut = $this->normalizeTime($entry['clock_out'] ?? null);

        if ($date === '' || !$clockIn) {
            return 0;
        }

        return $this->grossWorkSecondsFromClockTimes($date, $clockIn, $clockOut);
    }

    private function grossWorkSecondsFromClockTimes(string $date, string $clockIn, ?string $clockOut): int
    {
        $start = strtotime($date . ' ' . $clockIn);
        if ($start === false) {
            return 0;
        }

        if ($clockOut) {
            $end = strtotime($date . ' ' . $clockOut);
        } elseif ($date === date('Y-m-d')) {
            $end = time();
        } else {
            return 0;
        }

        if ($end === false) {
            return 0;
        }

        return max(0, ($end - $start));
    }

    private function deriveAttendanceStatus(array $record, ?string $clockIn, ?string $clockOut, int $totalWorkSeconds, array $rules, string $requestedStatus): string
    {
        if (!$clockIn) {
            if (($requestedStatus === 'on_leave') || (($record['status'] ?? '') === 'on_leave')) {
                return 'on_leave';
            }

            return 'absent';
        }

        $date = (string) ($record['date'] ?? date('Y-m-d'));
        $clockInStamp = strtotime($date . ' ' . $clockIn);
        $lateThresholdStamp = strtotime($date . ' ' . $rules['late_threshold']);

        if ($clockOut && $totalWorkSeconds < (int) $rules['half_day_seconds']) {
            return 'half_day';
        }

        if ($clockInStamp > $lateThresholdStamp) {
            return 'late';
        }

        if ($totalWorkSeconds >= (int) $rules['minimum_work_seconds']) {
            return 'present';
        }

        return 'present';
    }

    private function breakDeductionSeconds(int $breakSeconds, string $status, array $rules): int
    {
        $breakSeconds = max(0, $breakSeconds);
        $allowedBreakSeconds = (int) ($rules['break_time_seconds'] ?? (((int) ($rules['break_time'] ?? 0)) * 60));

        if ($status === 'half_day') {
            return $breakSeconds;
        }

        return max(0, $breakSeconds - $allowedBreakSeconds);
    }

    private function resolveAttendanceComputation(array $record, ?string $clockIn, ?string $clockOut, int $breakSeconds, array $rules, string $requestedStatus): array
    {
        if (!$clockIn) {
            $status = $this->deriveAttendanceStatus($record, $clockIn, $clockOut, 0, $rules, $requestedStatus);

            return [$status, 0, 0, 0];
        }

        $date = (string) ($record['date'] ?? date('Y-m-d'));
        $grossSeconds = $this->grossWorkSecondsFromClockTimes($date, $clockIn, $clockOut);
        if ($grossSeconds <= 0) {
            $status = $this->deriveAttendanceStatus($record, $clockIn, $clockOut, 0, $rules, $requestedStatus);

            return [$status, 0, 0, 0];
        }

        $provisionalStatus = in_array($requestedStatus, ['absent', 'on_leave'], true) ? $requestedStatus : 'present';
        [$totalWorkSeconds] = $this->calculateWorkAndOvertime(
            $clockIn,
            $clockOut,
            $breakSeconds,
            $provisionalStatus,
            $rules,
            $date
        );

        $status = $this->deriveAttendanceStatus($record, $clockIn, $clockOut, $totalWorkSeconds, $rules, $requestedStatus);
        [$totalWorkSeconds, $overtimeSeconds, $breakDeductionSeconds] = $this->calculateWorkAndOvertime(
            $clockIn,
            $clockOut,
            $breakSeconds,
            $status,
            $rules,
            $date
        );

        return [$status, max(0, $totalWorkSeconds), max(0, $overtimeSeconds), max(0, $breakDeductionSeconds)];
    }

    private function syncSessionFromAttendance(int $userId, string $date, ?string $clockIn, ?string $clockOut, int $breakSeconds, int $totalWorkSeconds, string $status): void
    {
        if ($date !== date('Y-m-d')) {
            return;
        }

        $sessionStatus = 'offline';
        if ($clockIn && !$clockOut) {
            $sessionStatus = 'working';
        }
        if (in_array($status, ['absent', 'on_leave'], true)) {
            $sessionStatus = 'offline';
        }

        $stmt = $this->db->prepare("
            INSERT INTO time_tracking_sessions
                (user_id, session_date, status, punch_in_time, punch_out_time, total_work_seconds, total_break_seconds, updated_at)
            VALUES
                (:user_id, :session_date, :status, :punch_in_time, :punch_out_time, :total_work_seconds, :total_break_seconds, NOW())
            ON DUPLICATE KEY UPDATE
                status = VALUES(status),
                punch_in_time = VALUES(punch_in_time),
                punch_out_time = VALUES(punch_out_time),
                total_work_seconds = VALUES(total_work_seconds),
                total_break_seconds = VALUES(total_break_seconds),
                updated_at = NOW()
        ");
        $stmt->execute([
            'user_id' => $userId,
            'session_date' => $date,
            'status' => $sessionStatus,
            'punch_in_time' => $clockIn ? ($date . ' ' . $clockIn) : null,
            'punch_out_time' => $clockOut ? ($date . ' ' . $clockOut) : null,
            'total_work_seconds' => $totalWorkSeconds,
            'total_break_seconds' => $breakSeconds,
        ]);
    }

    private function normalizeTime($time): ?string
    {
        $time = trim((string) $time);
        if ($time === '' || $time === '--') {
            return null;
        }

        if (preg_match('/^\d{2}:\d{2}$/', $time)) {
            return $time . ':00';
        }

        return preg_match('/^\d{2}:\d{2}:\d{2}$/', $time) ? $time : null;
    }

    private function parseDurationToSeconds($duration): int
    {
        $duration = trim((string) $duration);
        if ($duration === '') {
            return 0;
        }

        $parts = explode(':', $duration);
        if (count($parts) === 2) {
            [$hours, $minutes] = $parts;
            return (((int) $hours) * 3600) + (((int) $minutes) * 60);
        }

        if (count($parts) === 3) {
            [$hours, $minutes, $seconds] = $parts;
            return (((int) $hours) * 3600) + (((int) $minutes) * 60) + ((int) $seconds);
        }

        return 0;
    }

    private function formatDuration(int $seconds): string
    {
        $seconds = max(0, $seconds);
        $hours = str_pad((string) floor($seconds / 3600), 2, '0', STR_PAD_LEFT);
        $minutes = str_pad((string) floor(($seconds % 3600) / 60), 2, '0', STR_PAD_LEFT);
        $remainingSeconds = str_pad((string) ($seconds % 60), 2, '0', STR_PAD_LEFT);

        return $hours . ':' . $minutes . ':' . $remainingSeconds;
    }
}
