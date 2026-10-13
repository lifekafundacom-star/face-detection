<?php

namespace App\Repositories;

class SettingsRepository extends BaseRepository
{
    public function attendanceRules(): array
    {
        $defaults = $this->defaultAttendanceRules();

        $stmt = $this->db->prepare("
            SELECT setting_key, setting_value
            FROM application_settings
            WHERE setting_group = 'attendance_rules'
        ");
        $stmt->execute();

        $rules = $defaults;
        foreach ($stmt->fetchAll() as $row) {
            $rules[$row['setting_key']] = $row['setting_value'];
        }

        if (!isset($rules['break_time']) || $rules['break_time'] === '' || $rules['break_time'] === null) {
            $legacyBreak = $rules['max_break_minutes'] ?? $rules['break_alert_minutes'] ?? 60;
            $rules['break_time'] = (int) $legacyBreak;
        }

        $rules['grace_minutes'] = (int) $rules['grace_minutes'];
        $rules['break_time'] = (int) $rules['break_time'];
        $rules['max_break_minutes'] = (int) $rules['break_time'];
        $rules['break_alert_minutes'] = (int) $rules['break_time'];
        $rules['overtime_enabled'] = (int) $rules['overtime_enabled'];

        return $rules;
    }

    public function attendanceRulesForEngine(): array
    {
        $rules = $this->attendanceRules();

        $standardDutySeconds = $this->durationToSeconds($rules['standard_duty_hours']);
        $halfDaySeconds = $this->durationToSeconds($rules['half_day_hours']);
        $minimumWorkSeconds = $this->durationToSeconds($rules['minimum_work_hours']);
        $breakTimeSeconds = ((int) $rules['break_time']) * 60;
        $officeStart = $rules['office_start_time'];
        $lateThreshold = date('H:i:s', strtotime($officeStart) + ($rules['grace_minutes'] * 60));

        return [
            'office_start_time' => $officeStart,
            'grace_minutes' => $rules['grace_minutes'],
            'late_threshold' => $lateThreshold,
            'standard_duty_hours' => $rules['standard_duty_hours'],
            'standard_duty_seconds' => $standardDutySeconds,
            'half_day_hours' => $rules['half_day_hours'],
            'half_day_seconds' => $halfDaySeconds,
            'minimum_work_hours' => $rules['minimum_work_hours'],
            'minimum_work_seconds' => $minimumWorkSeconds,
            'break_time' => $rules['break_time'],
            'break_time_seconds' => $breakTimeSeconds,
            'max_break_minutes' => $rules['break_time'],
            'break_alert_minutes' => $rules['break_time'],
            'overtime_enabled' => $rules['overtime_enabled'],
        ];
    }

    public function updateAttendanceRules(array $payload): array
    {
        $allowed = array_keys($this->defaultAttendanceRules());
        $rules = $this->attendanceRules();

        foreach ($allowed as $key) {
            if (array_key_exists($key, $payload)) {
                $rules[$key] = is_string($payload[$key]) ? trim($payload[$key]) : $payload[$key];
            }
        }

        $rules['grace_minutes'] = max(0, (int) $rules['grace_minutes']);
        $rules['break_time'] = max(0, (int) $rules['break_time']);
        $rules['max_break_minutes'] = (int) $rules['break_time'];
        $rules['break_alert_minutes'] = (int) $rules['break_time'];
        $rules['overtime_enabled'] = !empty($rules['overtime_enabled']) ? 1 : 0;

        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare("
                INSERT INTO application_settings (setting_group, setting_key, setting_value)
                VALUES ('attendance_rules', :setting_key, :setting_value)
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
            ");

            foreach ($allowed as $key) {
                $stmt->execute([
                    'setting_key' => $key,
                    'setting_value' => (string) $rules[$key],
                ]);
            }

            $this->db->commit();
        } catch (\Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }

        return $this->attendanceRules();
    }

    private function defaultAttendanceRules(): array
    {
        return [
            'office_start_time' => '09:00:00',
            'grace_minutes' => 15,
            'minimum_work_hours' => '08:00:00',
            'half_day_hours' => '04:00:00',
            'standard_duty_hours' => '09:00:00',
            'break_time' => 60,
            'overtime_enabled' => 1,
        ];
    }

    private function durationToSeconds(string $duration): int
    {
        $parts = explode(':', $duration);
        if (count($parts) === 2) {
            return (((int) $parts[0]) * 3600) + (((int) $parts[1]) * 60);
        }

        if (count($parts) === 3) {
            return (((int) $parts[0]) * 3600) + (((int) $parts[1]) * 60) + ((int) $parts[2]);
        }

        return 0;
    }
}
