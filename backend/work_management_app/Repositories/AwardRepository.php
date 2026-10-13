<?php

namespace App\Repositories;

use RuntimeException;

class AwardRepository extends BaseRepository
{
    public function dashboardAwards(): array
    {
        $lastYear = (int) date('Y') - 1;
        $lastMonthDate = strtotime('first day of last month');
        $lastMonthYear = (int) date('Y', $lastMonthDate);
        $lastMonth = (int) date('n', $lastMonthDate);
        $lastMonthStart = date('Y-m-01', $lastMonthDate);
        $lastMonthEnd = date('Y-m-t', $lastMonthDate);

        return [
            'employee_of_year' => $this->employeeOfYearAward($lastYear),
            'top_performance' => $this->monthlyAward(
                'last_month_top_performance',
                $lastMonthYear,
                $lastMonth,
                $lastMonthStart,
                $lastMonthEnd,
                'Last Month Top Performance'
            ),
            'best_working_time' => $this->monthlyAward(
                'last_month_best_working_time',
                $lastMonthYear,
                $lastMonth,
                $lastMonthStart,
                $lastMonthEnd,
                'Last Month Top Working Time'
            ),
            'context' => [
                'last_year' => $lastYear,
                'last_month' => $lastMonth,
                'last_month_year' => $lastMonthYear,
                'last_month_label' => date('F Y', $lastMonthDate),
            ],
        ];
    }

    public function settingsCards(): array
    {
        $lastYear = (int) date('Y') - 1;
        $lastMonthDate = strtotime('first day of last month');
        $lastMonthYear = (int) date('Y', $lastMonthDate);
        $lastMonth = (int) date('n', $lastMonthDate);

        return [
            [
                'award_type' => 'employee_of_year',
                'heading' => 'Employee of the Year',
                'period_label' => (string) $lastYear,
                'award_year' => $lastYear,
                'award_month' => null,
                'display_mode' => 'manual',
                'locked_mode' => true,
                'selected_user_id' => (int) ($this->fetchConfig('employee_of_year', $lastYear, null)['user_id'] ?? 0),
                'award_title' => (string) ($this->fetchConfig('employee_of_year', $lastYear, null)['award_title'] ?? 'Employee of the Year'),
                'note' => (string) ($this->fetchConfig('employee_of_year', $lastYear, null)['note'] ?? ''),
            ],
            [
                'award_type' => 'last_month_top_performance',
                'heading' => 'Last Month Top Performance',
                'period_label' => date('F Y', $lastMonthDate),
                'award_year' => $lastMonthYear,
                'award_month' => $lastMonth,
                'display_mode' => (string) ($this->fetchConfig('last_month_top_performance', $lastMonthYear, $lastMonth)['display_mode'] ?? 'auto'),
                'locked_mode' => false,
                'selected_user_id' => (int) ($this->fetchConfig('last_month_top_performance', $lastMonthYear, $lastMonth)['user_id'] ?? 0),
                'award_title' => (string) ($this->fetchConfig('last_month_top_performance', $lastMonthYear, $lastMonth)['award_title'] ?? 'Last Month Top Performance'),
                'note' => (string) ($this->fetchConfig('last_month_top_performance', $lastMonthYear, $lastMonth)['note'] ?? ''),
            ],
            [
                'award_type' => 'last_month_best_working_time',
                'heading' => 'Last Month Best Working Time',
                'period_label' => date('F Y', $lastMonthDate),
                'award_year' => $lastMonthYear,
                'award_month' => $lastMonth,
                'display_mode' => (string) ($this->fetchConfig('last_month_best_working_time', $lastMonthYear, $lastMonth)['display_mode'] ?? 'auto'),
                'locked_mode' => false,
                'selected_user_id' => (int) ($this->fetchConfig('last_month_best_working_time', $lastMonthYear, $lastMonth)['user_id'] ?? 0),
                'award_title' => (string) ($this->fetchConfig('last_month_best_working_time', $lastMonthYear, $lastMonth)['award_title'] ?? 'Last Month Top Working Time'),
                'note' => (string) ($this->fetchConfig('last_month_best_working_time', $lastMonthYear, $lastMonth)['note'] ?? ''),
            ],
        ];
    }

    public function employeeOptions(): array
    {
        return $this->db->query("
            SELECT id, name, designation, department
            FROM users
            WHERE role IN ('manager', 'employee')
              AND status = 'active'
              AND deleted_at IS NULL
            ORDER BY name ASC
        ")->fetchAll();
    }

    public function awardSelections(): array
    {
        $stmt = $this->db->query("
            SELECT
                ea.*,
                u.name,
                u.designation
            FROM employee_awards ea
            LEFT JOIN users u ON u.id = ea.user_id
            ORDER BY ea.award_year DESC, COALESCE(ea.award_month, 0) DESC, ea.award_type ASC
            LIMIT 12
        ");

        return $stmt->fetchAll();
    }

    public function saveAwardSetting(array $payload): array
    {
        $awardType = trim((string) ($payload['award_type'] ?? ''));
        $awardYear = (int) ($payload['award_year'] ?? 0);
        $awardMonth = $payload['award_month'] === '' || !isset($payload['award_month']) ? null : (int) $payload['award_month'];
        $displayMode = trim((string) ($payload['display_mode'] ?? 'auto'));
        $userId = (int) ($payload['user_id'] ?? 0);
        $awardTitle = trim((string) ($payload['award_title'] ?? ''));
        $note = trim((string) ($payload['award_note'] ?? $payload['note'] ?? ''));

        $validTypes = [
            'employee_of_year',
            'last_month_top_performance',
            'last_month_best_working_time',
        ];

        if (!in_array($awardType, $validTypes, true)) {
            throw new RuntimeException('Invalid award type.');
        }

        if ($awardYear < 2000 || $awardYear > ((int) date('Y') + 1)) {
            throw new RuntimeException('Select a valid year.');
        }

        if ($awardType === 'employee_of_year') {
            $displayMode = 'manual';
            $awardMonth = null;
            if ($awardTitle === '') {
                $awardTitle = 'Employee of the Year';
            }
        } else {
            if ($awardMonth === null || $awardMonth < 1 || $awardMonth > 12) {
                throw new RuntimeException('Select a valid month.');
            }

            if (!in_array($displayMode, ['auto', 'manual'], true)) {
                throw new RuntimeException('Select a valid display mode.');
            }

            if ($awardTitle === '') {
                $awardTitle = $awardType === 'last_month_top_performance'
                    ? 'Last Month Top Performance'
                    : 'Last Month Top Working Time';
            }
        }

        if ($displayMode === 'manual') {
            if ($userId <= 0) {
                throw new RuntimeException('Select an employee for manual mode.');
            }

            $userStmt = $this->db->prepare("
                SELECT id
                FROM users
                WHERE id = :id
                  AND role IN ('manager', 'employee')
                  AND deleted_at IS NULL
                LIMIT 1
            ");
            $userStmt->execute(['id' => $userId]);
            if (!$userStmt->fetch()) {
                throw new RuntimeException('Selected employee is invalid.');
            }
        } else {
            $userId = 0;
        }

        $stmt = $this->db->prepare("
            INSERT INTO employee_awards
                (award_type, award_year, award_month, display_mode, user_id, award_title, note)
            VALUES
                (:award_type, :award_year, :award_month, :display_mode, :user_id, :award_title, :note)
            ON DUPLICATE KEY UPDATE
                display_mode = VALUES(display_mode),
                user_id = VALUES(user_id),
                award_title = VALUES(award_title),
                note = VALUES(note),
                updated_at = CURRENT_TIMESTAMP
        ");
        $stmt->execute([
            'award_type' => $awardType,
            'award_year' => $awardYear,
            'award_month' => $awardMonth,
            'display_mode' => $displayMode,
            'user_id' => $userId > 0 ? $userId : null,
            'award_title' => $awardTitle,
            'note' => $note !== '' ? $note : null,
        ]);

        return $this->fetchConfig($awardType, $awardYear, $awardMonth) ?: [];
    }

    private function employeeOfYearAward(int $year): array
    {
        $config = $this->fetchConfig('employee_of_year', $year, null);
        if (!$config || empty($config['user_id'])) {
            return $this->emptyAwardCard(
                'Employee of the Year',
                'No employee selected yet',
                'Admin can set last year\'s winner from Settings.',
                'manual'
            );
        }

        return $this->manualAwardCard((int) $config['user_id'], $config['award_title'] ?: 'Employee of the Year', (string) ($config['note'] ?? ''), 'manual');
    }

    private function monthlyAward(string $awardType, int $year, int $month, string $startDate, string $endDate, string $defaultTitle): array
    {
        $config = $this->fetchConfig($awardType, $year, $month);
        $mode = (string) ($config['display_mode'] ?? 'auto');

        if ($mode === 'manual' && !empty($config['user_id'])) {
            return $this->manualAwardCard((int) $config['user_id'], $config['award_title'] ?: $defaultTitle, (string) ($config['note'] ?? ''), 'manual');
        }

        $awardTitle = (string) (($config['award_title'] ?? '') ?: $defaultTitle);

        if ($awardType === 'last_month_top_performance') {
            return $this->lastMonthTopPerformance($startDate, $endDate, $awardTitle);
        }

        return $this->lastMonthBestWorkingTime($startDate, $endDate, $awardTitle);
    }

    private function fetchConfig(string $awardType, int $year, ?int $month): ?array
    {
        if ($month === null) {
            $stmt = $this->db->prepare("
                SELECT *
                FROM employee_awards
                WHERE award_type = :award_type
                  AND award_year = :award_year
                  AND award_month IS NULL
                LIMIT 1
            ");
            $stmt->execute([
                'award_type' => $awardType,
                'award_year' => $year,
            ]);
        } else {
            $stmt = $this->db->prepare("
                SELECT *
                FROM employee_awards
                WHERE award_type = :award_type
                  AND award_year = :award_year
                  AND award_month = :award_month
                LIMIT 1
            ");
            $stmt->execute([
                'award_type' => $awardType,
                'award_year' => $year,
                'award_month' => $month,
            ]);
        }

        return $stmt->fetch() ?: null;
    }

    private function manualAwardCard(int $userId, string $awardTitle, string $note, string $source): array
    {
        $stmt = $this->db->prepare("
            SELECT id, name, designation, department, profile_thumbnail, profile_image, profile_image_path
            FROM users
            WHERE id = :id
              AND deleted_at IS NULL
            LIMIT 1
        ");
        $stmt->execute(['id' => $userId]);
        $row = $stmt->fetch();

        if (!$row) {
            return $this->emptyAwardCard($awardTitle, 'Employee not found', 'Selected employee is no longer active.', $source);
        }

        return $this->buildAwardCard($row, $awardTitle, $note, $source);
    }

    private function lastMonthTopPerformance(string $startDate, string $endDate, string $awardTitle): array
    {
        $stmt = $this->db->prepare("
            SELECT
                u.id,
                u.name,
                u.designation,
                u.department,
                u.profile_thumbnail,
                u.profile_image,
                u.profile_image_path,
                COALESCE(SUM(t.points), 0) AS score,
                COUNT(t.id) AS completed_tasks
            FROM users u
            LEFT JOIN tasks t
                ON t.assigned_to = u.id
               AND t.status = 'completed'
               AND DATE(t.completed_at) BETWEEN :start_date AND :end_date
            WHERE u.role IN ('manager', 'employee')
              AND u.deleted_at IS NULL
            GROUP BY
                u.id, u.name, u.designation, u.department,
                u.profile_thumbnail, u.profile_image, u.profile_image_path
            ORDER BY score DESC, completed_tasks DESC, u.name ASC
            LIMIT 1
        ");
        $stmt->execute([
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);
        $row = $stmt->fetch();

        if (!$row || (int) ($row['score'] ?? 0) <= 0) {
            return $this->emptyAwardCard(
                $awardTitle,
                'No performance winner yet',
                'Completed task points from last month will appear here.',
                'auto'
            );
        }

        return $this->buildAwardCard($row, $awardTitle, '', 'auto');
    }

    private function lastMonthBestWorkingTime(string $startDate, string $endDate, string $awardTitle): array
    {
        $stmt = $this->db->prepare("
            SELECT
                u.id,
                u.name,
                u.designation,
                u.department,
                u.profile_thumbnail,
                u.profile_image,
                u.profile_image_path,
                COALESCE(SUM(a.total_work_seconds), 0) AS total_work_seconds
            FROM users u
            LEFT JOIN attendance a
                ON a.user_id = u.id
               AND a.date BETWEEN :start_date AND :end_date
            WHERE u.role IN ('manager', 'employee')
              AND u.deleted_at IS NULL
            GROUP BY
                u.id, u.name, u.designation, u.department,
                u.profile_thumbnail, u.profile_image, u.profile_image_path
            ORDER BY total_work_seconds DESC, u.name ASC
            LIMIT 1
        ");
        $stmt->execute([
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);
        $row = $stmt->fetch();

        if (!$row || (int) ($row['total_work_seconds'] ?? 0) <= 0) {
            return $this->emptyAwardCard(
                $awardTitle,
                'No working-time winner yet',
                'Last month attendance totals will appear here.',
                'auto'
            );
        }

        return $this->buildAwardCard($row, $awardTitle, '', 'auto');
    }

    private function buildAwardCard(array $row, string $awardTitle, string $note, string $source): array
    {
        return [
            'id' => !empty($row['id']) ? (int) $row['id'] : null,
            'name' => $row['name'] ?? 'Not available',
            'designation' => $row['designation'] ?: 'Team Member',
            'department' => $row['department'] ?: 'Unassigned',
            'award_title' => $awardTitle,
            'note' => $note,
            'profile_thumbnail' => $row['profile_thumbnail'] ?? null,
            'profile_image' => $row['profile_image'] ?? null,
            'profile_image_path' => $row['profile_image_path'] ?? null,
            'source' => $source,
            'is_empty' => false,
        ];
    }

    private function emptyAwardCard(string $awardTitle, string $name, string $designation, string $source): array
    {
        return [
            'id' => null,
            'name' => $name,
            'designation' => $designation,
            'department' => '',
            'award_title' => $awardTitle,
            'note' => '',
            'profile_thumbnail' => null,
            'profile_image' => null,
            'profile_image_path' => null,
            'source' => $source,
            'is_empty' => true,
        ];
    }
}
