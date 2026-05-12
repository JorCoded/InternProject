<?php
/**
 * Attendance.php
 * ==============
 * Handles all attendance-related database operations:
 *   - Clock in / clock out (triggered automatically on login/logout)
 *   - Break start / end
 *   - Marking employees absent or on leave
 *   - Getting attendance history and statistics
 *   - Admin modifications with audit logging
 */

class Attendance {

    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    // ── Getting attendance records ────────────────────────────

    /**
     * Get today's attendance record for a specific user.
     * Returns null if the user has not clocked in today.
     */
    public function getToday(int $userId): ?array {
        return $this->db->fetchOne(
            "SELECT * FROM attendance WHERE user_id=? AND date=CURDATE()",
            [$userId], 'i'
        );
    }

    /** Get a specific day's attendance record for a user. */
    public function getByDate(int $userId, string $date): ?array {
        return $this->db->fetchOne(
            "SELECT * FROM attendance WHERE user_id=? AND date=?",
            [$userId, $date], 'is'
        );
    }

    // ── Location helper ───────────────────────────────────────

    /**
     * Resolve a human-readable location string from the server-side IP.
     * Uses the ip-api.com free JSON endpoint (no API key required).
     * Falls back gracefully to just the raw IP if the lookup fails.
     *
     * @param string $ip  The IP address to look up
     * @return string     e.g. "Port Louis, Plaines Wilhems, MU (20.16, 57.50)"
     */
    public static function getLocation(?string $ip): string {
        // Loopback / private IPs cannot be looked up — return as-is
        /* if (empty($ip) || $ip === '127.0.0.1' || substr($ip, 0, 3) === '10.'
            || substr($ip, 0, 8) === '192.168.' || substr($ip, 0, 7) === '172.16.') {
            return $ip ?: 'Unknown';
            }

            $apiUrl = "http://ip-api.com/json/" . urlencode($ip) . "?fields=status,city,regionName,country,countryCode,lat,lon";

            // file_get_contents may be disabled — use cURL as fallback
            $response = false;
            if (function_exists('curl_init')) {
                $ch = curl_init($apiUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 3);
                $response = curl_exec($ch);
                curl_close($ch);
            } elseif (ini_get('allow_url_fopen')) {
                $context  = stream_context_create(['http' => ['timeout' => 3]]);
                $response = @file_get_contents($apiUrl, false, $context);
            }

            if ($response) {
                $data = json_decode($response, true);
                if ($data && ($data['status'] ?? '') === 'success') {
                    $city    = $data['city']        ?? '';
                    $region  = $data['regionName']  ?? '';
                    $country = $data['countryCode'] ?? '';
                    $lat     = $data['lat']         ?? '';
                    $lon     = $data['lon']         ?? '';

                    $parts = array_filter([$city, $region, $country]);
                    $label = implode(',', $parts);
                    if ($lat && $lon) {
                        $label .= " ({$lat}, {$lon})";
                    }
                    return $label ?: $ip;
                }
            }
            

            // Fallback: just store the raw IP
            return $ip; 
        */
        $locationData = [];

        $apiUrl = "http://ip-api.com/json/";
        $response = file_get_contents($apiUrl);
        if($response){
            $data = json_decode($response, true);

            if ($data && $data['status'] === 'success') {
                $city = $data['city'] ?? '';
                $region = $data['regionName'] ?? '';
                $country = $data['country'] ?? '';
                $lat = $data['lat'] ?? '';
                $lon = $data['lon'] ?? '';

                $locationData[] = $city;
                $locationData[] = $region;
                $locationData[] = $country;
                $locationData[] = $lat;
                $locationData[] = $lon;

            } else {
                echo "Could not retrieve location.";
            }
            $label = implode(',', $locationData);
            return $label?:"Location not available";
        }
        return "API Error";

    }

    // ── Clock in / out ────────────────────────────────────────

    /**
     * Clock a user in for today.
     * This is called automatically when the user logs in (in Auth::attempt).
     *
     * The status is set to 'late' if they clock in after work_start_time
     * (configured in system_settings), otherwise 'present'.
     *
     * @param int    $userId   The user's ID
     * @param string $location The user's IP address (recorded for audit)
     * @return bool            false if they are already clocked in today
     */
    public function clockIn(int $userId, string $ip = ''): bool {
        // Don't create a duplicate record if they already clocked in today
        if ($this->getToday($userId)) return false;

        // Resolve the IP to a human-readable city/region/country string
        $location = self::getLocation($ip);

        // Compare current time to the configured work start time
        $workStartTime = Setting::get('work_start_time', '08:30');
        $status        = (date('H:i') > $workStartTime) ? 'late' : 'present';

        $id = $this->db->insert(
            "INSERT INTO attendance (user_id, date, clock_in, clock_in_location, status)
             VALUES (?, CURDATE(), NOW(), ?, ?)",
            [$userId, $location, $status], 'iss'
        );
        return $id !== false;
    }

    /**
     * Clock a user out.
     * Called automatically when they log out (in Auth::logout).
     * Also calculates the total hours worked for the day and records location.
     */
    public function clockOut(int $userId, string $ip = ''): bool {
        $att = $this->getToday($userId);

        // Can't clock out if not clocked in, or already clocked out
        if (!$att || $att['clock_out']) return false;

        // Resolve the IP to a human-readable location
        $location = self::getLocation($ip);

        // Calculate how many hours they worked (minus any break time)
        $totalHours = $this->calculateHours(
            $att['clock_in'],
            date('Y-m-d H:i:s'),
            $att['break_start'],
            $att['break_end']
        );

        return $this->db->execute(
            "UPDATE attendance SET clock_out=NOW(), clock_out_location=?, total_hours=? WHERE id=?",
            [$location, $totalHours, $att['id']], 'sdi'
        );
    }

    // ── Break management ──────────────────────────────────────

    /**
     * Start a break for the given attendance record.
     * Rules:
     *   - Must be clocked in
     *   - Cannot start a second break (only one per day)
     *   - Must be before the break_deadline time (configured in settings)
     *
     * Returns an array: ['ok' => true/false, 'msg' => 'explanation']
     */
    public function startBreak(int $attendanceId): array {
        $att = $this->db->fetchOne("SELECT * FROM attendance WHERE id=?", [$attendanceId], 'i');

        if (!$att) {
            return ['ok' => false, 'msg' => 'Attendance record not found.'];
        }
        if ($att['break_start'] && !$att['break_end']) {
            return ['ok' => false, 'msg' => 'You already have an active break.'];
        }
        if ($att['break_start'] && $att['break_end']) {
            return ['ok' => false, 'msg' => 'Break already taken for today.'];
        }

        // Check if it's past the break deadline time
        $deadline = Setting::get('break_deadline', '15:00');
        if (date('H:i') > $deadline) {
            return ['ok' => false, 'msg' => "Break must be taken before {$deadline}."];
        }

        $this->db->execute("UPDATE attendance SET break_start=NOW() WHERE id=?", [$attendanceId], 'i');
        return ['ok' => true, 'msg' => 'Break started.'];
    }

    /**
     * End the current break.
     * Warns if the break exceeded the maximum allowed duration (from settings).
     *
     * Returns an array: ['ok' => true/false, 'msg' => '...', 'warn' => '...']
     */
    public function endBreak(int $attendanceId): array {
        $att = $this->db->fetchOne("SELECT * FROM attendance WHERE id=?", [$attendanceId], 'i');

        if (!$att)               return ['ok' => false, 'msg' => 'Attendance record not found.'];
        if (!$att['break_start']) return ['ok' => false, 'msg' => 'No active break to end.'];
        if ($att['break_end'])    return ['ok' => false, 'msg' => 'Break already ended.'];

        // Check if break exceeded the allowed maximum (e.g. 1 hour)
        $maxHours   = (float)Setting::get('break_max_hours', '1');
        $elapsed    = (time() - strtotime($att['break_start'])) / 3600; // hours elapsed
        $warning    = '';
        if ($elapsed > ($maxHours + 0.05)) { // +0.05 allows a small grace margin
            $warning = "Warning: break exceeded the {$maxHours}h limit.";
        }

        $this->db->execute("UPDATE attendance SET break_end=NOW() WHERE id=?", [$attendanceId], 'i');
        return [
            'ok'   => true,
            'msg'  => 'Break ended.' . ($warning ? ' ' . $warning : ''),
            'warn' => $warning,
        ];
    }

    // ── Marking absent / on leave ─────────────────────────────

    /**
     * Mark an employee as absent for a given date.
     * Only creates a record if one doesn't exist yet (prevents duplicates).
     * Called by cron.php each evening for employees who never clocked in.
     */
    public function markAbsent(int $userId, string $date): void {
        $existing = $this->db->fetchOne(
            "SELECT id FROM attendance WHERE user_id=? AND date=?",
            [$userId, $date], 'is'
        );
        if (!$existing) {
            $this->db->insert(
                "INSERT INTO attendance (user_id, date, status) VALUES (?, ?, 'absent')",
                [$userId, $date], 'is'
            );
        }
    }

    /**
     * Mark an employee as "on leave" for a specific date.
     * Called when a leave request is approved (for each day in the leave period).
     * Updates an existing record if one exists, or creates a new one.
     */
    public function markOnLeave(int $userId, string $date): void {
        $existing = $this->db->fetchOne(
            "SELECT id FROM attendance WHERE user_id=? AND date=?",
            [$userId, $date], 'is'
        );
        if ($existing) {
            // Update the existing record's status
            $this->db->execute("UPDATE attendance SET status='on_leave' WHERE id=?", [$existing['id']], 'i');
        } else {
            // Create a new record with on_leave status
            $this->db->insert(
                "INSERT INTO attendance (user_id, date, status) VALUES (?, ?, 'on_leave')",
                [$userId, $date], 'is'
            );
        }
    }

    // ── Admin: modify a record ────────────────────────────────

    /**
     * Allow an admin to correct a clock-in or clock-out time.
     * All changes are written to the audit log (attendance_modification_log table)
     * so there is a permanent record of what was changed, by whom, and why.
     *
     * @param int    $attId      The ID of the attendance record to change
     * @param string $newIn      New clock-in datetime (Y-m-d H:i:s)
     * @param string $newOut     New clock-out datetime (Y-m-d H:i:s)
     * @param int    $modifiedBy The admin's user ID (for the audit log)
     * @param string $reason     Why the change was made (required)
     */
    public function modify(int $attId, string $newIn, string $newOut, int $modifiedBy, string $reason): bool {
        $att = $this->db->fetchOne("SELECT * FROM attendance WHERE id=?", [$attId], 'i');
        if (!$att) return false;

        $newHours = $this->calculateHours($newIn, $newOut);

        // Update the attendance record
        // COALESCE keeps the original values the first time a record is modified
        $this->db->execute(
            "UPDATE attendance
             SET clock_in=?, clock_out=?, total_hours=?,
                 modified_by=?, modification_reason=?,
                 original_clock_in  = COALESCE(original_clock_in,  clock_in),
                 original_clock_out = COALESCE(original_clock_out, clock_out)
             WHERE id=?",
            [$newIn, $newOut, $newHours, $modifiedBy, $reason, $attId],
            'ssdiis'
        );

        // Write an entry to the permanent audit log
        $this->db->insert(
            "INSERT INTO attendance_modification_log
             (attendance_id, modified_by, original_clock_in, original_clock_out,
              new_clock_in, new_clock_out, reason)
             VALUES (?, ?, ?, ?, ?, ?, ?)",
            [$attId, $modifiedBy, $att['clock_in'], $att['clock_out'], $newIn, $newOut, $reason],
            'iissss'
        );
        return true;
    }

    // ── Fetching history and stats ────────────────────────────

    /**
     * Get a user's attendance records between two dates (paginated).
     * Used on the "My Attendance" page and in reports.
     */
    public function getHistory(int $userId, string $from, string $to, int $limit = 20, int $offset = 0): array {
        return $this->db->fetch(
            "SELECT * FROM attendance
             WHERE user_id=? AND date BETWEEN ? AND ?
             ORDER BY date DESC
             LIMIT $limit OFFSET $offset",
            [$userId, $from, $to], 'iss'
        );
    }

    /** Count records for pagination purposes. */
    public function countHistory(int $userId, string $from, string $to): int {
        $row = $this->db->fetchOne(
            "SELECT COUNT(*) as cnt FROM attendance WHERE user_id=? AND date BETWEEN ? AND ?",
            [$userId, $from, $to], 'iss'
        );
        return (int)($row['cnt'] ?? 0);
    }

    /**
     * Get attendance statistics for the current user for the CURRENT month.
     * Returns an array with keys: present, late, absent, on_leave, total_hours.
     */
    public function getMonthStats(int $userId): array {
        return $this->db->fetchOne(
            "SELECT
                SUM(CASE WHEN status='present'  THEN 1 ELSE 0 END) as present,
                SUM(CASE WHEN status='late'     THEN 1 ELSE 0 END) as late,
                SUM(CASE WHEN status='absent'   THEN 1 ELSE 0 END) as absent,
                SUM(CASE WHEN status='on_leave' THEN 1 ELSE 0 END) as on_leave,
                SUM(total_hours) as total_hours
             FROM attendance
             WHERE user_id=?
               AND MONTH(date) = MONTH(CURDATE())
               AND YEAR(date)  = YEAR(CURDATE())",
            [$userId], 'i'
        ) ?? [];
    }

    /**
     * Get ALL employees' attendance records between two dates (for admin/HR reports).
     * Supports a search filter across employee name and ID.
     */
    public function getAllRecords(string $from, string $to, string $search = '', int $limit = 20, int $offset = 0): array {
        $where  = "WHERE a.date BETWEEN ? AND ?";
        $params = [$from, $to];
        $types  = 'ss';

        if ($search) {
            $s       = "%$search%";
            $where  .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.employee_id LIKE ?)";
            $params  = array_merge($params, [$s, $s, $s]);
            $types  .= 'sss';
        }

        return $this->db->fetch(
            "SELECT a.*, u.first_name, u.last_name, u.employee_id
             FROM attendance a
             JOIN users u ON a.user_id = u.id
             $where
             ORDER BY a.date DESC, a.clock_in DESC
             LIMIT $limit OFFSET $offset",
            $params, $types
        );
    }

    /** Count all records (for pagination on the admin attendance page). */
    public function countAllRecords(string $from, string $to, string $search = ''): int {
        $where  = "WHERE a.date BETWEEN ? AND ?";
        $params = [$from, $to];
        $types  = 'ss';

        if ($search) {
            $s      = "%$search%";
            $where .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.employee_id LIKE ?)";
            $params = array_merge($params, [$s, $s, $s]);
            $types .= 'sss';
        }

        $row = $this->db->fetchOne(
            "SELECT COUNT(*) as cnt FROM attendance a JOIN users u ON a.user_id=u.id $where",
            $params, $types
        );
        return (int)($row['cnt'] ?? 0);
    }

    /**
     * Count how many employees are present (clocked in) today.
     * Used for the dashboard stat cards.
     */
    public function getPresentCountToday(): int {
        $row = $this->db->fetchOne(
            "SELECT COUNT(*) as cnt FROM attendance WHERE date=CURDATE() AND status IN ('present','late')"
        );
        return (int)($row['cnt'] ?? 0);
    }

    /**
     * Get the latest clock-ins for today (shown on admin dashboard).
     * @param int $limit How many to return
     */
    public function getTodayList(int $limit = 10): array {
        return $this->db->fetch(
            "SELECT a.*, u.first_name, u.last_name, u.employee_id
             FROM attendance a
             JOIN users u ON a.user_id = u.id
             WHERE a.date = CURDATE()
             ORDER BY a.clock_in DESC
             LIMIT $limit"
        );
    }

    /**
     * Get attendance numbers for the past 7 days (for the bar chart on admin dashboard).
     * Returns arrays of day labels, present counts, and absent counts.
     */
    public function getLast7DaysStats(): array {
        $days    = [];
        $present = [];
        $absent  = [];

        // Get total active employee count so we can calculate absent = total - present
        $totalRow        = $this->db->fetchOne("SELECT COUNT(*) as cnt FROM users WHERE is_active=1");
        $totalEmployees  = (int)($totalRow['cnt'] ?? 0);

        // Loop from 6 days ago to today (index 6 = today)
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $days[] = date('D', strtotime($date)); // e.g. "Mon"

            // Count how many were present or late on this day
            $presentRow = $this->db->fetchOne(
                "SELECT COUNT(*) as cnt FROM attendance WHERE date=? AND status IN ('present','late')",
                [$date], 's'
            );
            $p         = (int)($presentRow['cnt'] ?? 0);
            $present[] = $p;
            $absent[]  = max(0, $totalEmployees - $p);
        }

        return ['days' => $days, 'present' => $present, 'absent' => $absent];
    }

    // ── Helper: calculate hours worked ───────────────────────

    /**
     * Calculate the number of hours between clock-in and clock-out.
     * Subtracts break time if break start and end are provided.
     *
     * @param string|null $clockIn    Datetime string (e.g. "2025-01-01 08:30:00")
     * @param string|null $clockOut   Datetime string
     * @param string|null $breakStart Datetime string (optional)
     * @param string|null $breakEnd   Datetime string (optional)
     * @return float Hours worked (rounded to 2 decimal places)
     */
    public function calculateHours(
        ?string $clockIn,
        ?string $clockOut,
        ?string $breakStart = null,
        ?string $breakEnd   = null
    ): float {
        if (!$clockIn || !$clockOut) return 0.0;

        // Calculate total time from clock-in to clock-out in hours
        $totalHours = (strtotime($clockOut) - strtotime($clockIn)) / 3600;

        // Subtract the break time if both break start and end are recorded
        if ($breakStart && $breakEnd) {
            $breakHours = (strtotime($breakEnd) - strtotime($breakStart)) / 3600;
            $totalHours -= $breakHours;
        }

        return max(0, round($totalHours, 2)); // never return a negative value
    }

    /**
     * Format a decimal hours number into a readable string.
     * Example: 7.75 → "7h 45m"
     *
     * Static because it doesn't need any database access.
     */
    public static function formatHours(float $hours): string {
        $h = (int)floor($hours);
        $m = (int)round(($hours - $h) * 60);
        return "{$h}h {$m}m";
    }
}
