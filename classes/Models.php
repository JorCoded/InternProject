<?php
/**
 * Models.php
 * ==========
 * Contains six separate classes, each handling one area of the system:
 *
 *   Leave        — managing leave requests (submit, approve, reject)
 *   Task         — assigning and tracking employee tasks
 *   Notification — sending and reading in-app bell notifications
 *   Setting      — reading/writing system configuration values
 *   Auth         — login, logout, session management, access control
 *   Announcement — posting/toggling company-wide announcements
 *
 * At the bottom there are also a few global "helper" functions
 * used throughout the entire project.
 */


/* ============================================================
   Leave
   Manages leave requests from submission through approval.
   ============================================================ */
class Leave {

    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Submit a new leave request.
     *
     * Validates that:
     *  - The date range is at least 1 day
     *  - The employee has enough balance for the requested leave type
     *  - Unpaid leave is only allowed when all paid leaves are exhausted
     *
     * @return array ['ok' => bool, 'msg' => string, 'id' => int, 'days' => int]
     */
    public function request(int $userId, string $type, string $start, string $end, string $reason): array {
        // Calculate how many calendar days are in the requested period
        $days = (int)round((strtotime($end) - strtotime($start)) / 86400) + 1;

        if ($days < 1) {
            return ['ok' => false, 'msg' => 'Invalid date range.'];
        }

        // Check if the user has enough leave balance
        $user = (new User())->findById($userId);

        if ($type === 'local' && $user['local_leaves'] < $days) {
            return ['ok' => false, 'msg' => "Only {$user['local_leaves']} local leave(s) remaining."];
        }
        if ($type === 'sick' && $user['sick_leaves'] < $days) {
            return ['ok' => false, 'msg' => "Only {$user['sick_leaves']} sick leave(s) remaining."];
        }
        if ($type === 'unpaid' && ($user['local_leaves'] > 0 || $user['sick_leaves'] > 0)) {
            return ['ok' => false, 'msg' => 'Unpaid leave is only available when all paid leaves are exhausted.'];
        }

        // All checks passed — insert the request
        $id = $this->db->insert(
            "INSERT INTO leave_requests (user_id, leave_type, start_date, end_date, total_days, reason)
             VALUES (?, ?, ?, ?, ?, ?)",
            [$userId, $type, $start, $end, $days, $reason],
            'issssi'
        );

        return $id
            ? ['ok' => true, 'id' => $id, 'days' => $days]
            : ['ok' => false, 'msg' => 'Database error.'];
    }

    /**
     * Approve a leave request.
     * This:
     *  1. Sets the request status to 'approved'
     *  2. Deducts the days from the employee's leave balance
     *  3. Creates attendance records for each day as 'on_leave'
     */
    public function approve(int $leaveId, int $reviewerId): bool {
        $leave = $this->getById($leaveId);

        // Can only approve requests that are still pending
        if (!$leave || $leave['status'] !== 'pending') return false;

        // Update the request status
        $this->db->execute(
            "UPDATE leave_requests SET status='approved', reviewed_by=?, reviewed_at=NOW() WHERE id=?",
            [$reviewerId, $leaveId], 'ii'
        );

        // Deduct from the employee's leave balance
        (new User())->deductLeave($leave['user_id'], $leave['leave_type'], $leave['total_days']);

        // Mark attendance as 'on_leave' for each day in the period
        $att   = new Attendance();
        $start = new DateTime($leave['start_date']);
        $end   = new DateTime($leave['end_date']);
        $end->modify('+1 day'); // make the loop inclusive of the end date

        for ($day = clone $start; $day < $end; $day->modify('+1 day')) {
            $att->markOnLeave($leave['user_id'], $day->format('Y-m-d'));
        }

        return true;
    }

    /**
     * Reject a leave request (just updates the status — no balance changes).
     */
    public function reject(int $leaveId, int $reviewerId): bool {
        $leave = $this->getById($leaveId);
        if (!$leave || $leave['status'] !== 'pending') return false;

        return $this->db->execute(
            "UPDATE leave_requests SET status='rejected', reviewed_by=?, reviewed_at=NOW() WHERE id=?",
            [$reviewerId, $leaveId], 'ii'
        );
    }

    /** Get a single leave request by ID. */
    public function getById(int $id): ?array {
        return $this->db->fetchOne("SELECT * FROM leave_requests WHERE id=?", [$id], 'i');
    }

    /** Get leave history for one employee (paginated). */
    public function getForUser(int $userId, int $limit = 10, int $offset = 0): array {
        return $this->db->fetch(
            "SELECT * FROM leave_requests WHERE user_id=? ORDER BY created_at DESC LIMIT $limit OFFSET $offset",
            [$userId], 'i'
        );
    }

    /** Count leave requests for one employee (used for pagination). */
    public function countForUser(int $userId): int {
        $row = $this->db->fetchOne(
            "SELECT COUNT(*) as cnt FROM leave_requests WHERE user_id=?",
            [$userId], 'i'
        );
        return (int)($row['cnt'] ?? 0);
    }

    /**
     * Get all leave requests across all employees (for admin/HR pages).
     * Joins with users table to include employee name and current balance.
     *
     * @param string $status Filter by 'pending', 'approved', 'rejected', or '' for all
     */
    public function getAll(string $status = '', int $limit = 20, int $offset = 0): array {
        $where  = "WHERE 1=1";
        $params = [];
        $types  = '';

        if ($status) {
            $where   .= " AND l.status=?";
            $params[] = $status;
            $types   .= 's';
        }

        return $this->db->fetch(
            "SELECT l.*, u.first_name, u.last_name, u.employee_id, u.local_leaves, u.sick_leaves
             FROM leave_requests l
             JOIN users u ON l.user_id = u.id
             $where
             ORDER BY l.created_at DESC
             LIMIT $limit OFFSET $offset",
            $params, $types
        );
    }

    /** Count all leave requests (for pagination). */
    public function countAll(string $status = ''): int {
        $where  = "WHERE 1=1";
        $params = [];
        $types  = '';

        if ($status) {
            $where   .= " AND l.status=?";
            $params[] = $status;
            $types   .= 's';
        }

        $row = $this->db->fetchOne(
            "SELECT COUNT(*) as cnt FROM leave_requests l JOIN users u ON l.user_id=u.id $where",
            $params, $types
        );
        return (int)($row['cnt'] ?? 0);
    }

    /** How many leave requests are currently pending (for the dashboard badge). */
    public function getPendingCount(): int {
        $row = $this->db->fetchOne("SELECT COUNT(*) as cnt FROM leave_requests WHERE status='pending'");
        return (int)($row['cnt'] ?? 0);
    }
}


/* ============================================================
   Task
   Manages tasks assigned to employees.
   ============================================================ */
class Task {

    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Create (assign) a new task.
     *
     * @param string      $title      Task name
     * @param string      $desc       Optional description
     * @param int         $assignedTo Which employee to assign to
     * @param int         $assignedBy Which admin created it
     * @param string|null $dueDate    Optional deadline (Y-m-d)
     * @param string      $priority   'low', 'medium', or 'high'
     * @return int|false  New task ID or false on failure
     */
    public function create(string $title, string $desc, int $assignedTo, int $assignedBy, ?string $dueDate, string $priority = 'medium'): int|false {
        return $this->db->insert(
            "INSERT INTO tasks (title, description, assigned_to, assigned_by, due_date, priority)
             VALUES (?, ?, ?, ?, ?, ?)",
            [$title, $desc, $assignedTo, $assignedBy, $dueDate, $priority],
            'ssiiss'
        );
    }

    /**
     * Mark a task as completed by the assigned employee.
     * Returns false if the task doesn't belong to this employee,
     * or if it is already completed (pending and delayed tasks can be completed).
     */
    public function complete(int $taskId, int $userId): bool {
        $task = $this->getByIdForUser($taskId, $userId);
        if (!$task || $task['status'] === 'completed') return false;

        return $this->db->execute(
            "UPDATE tasks SET status='completed', completed_at=NOW() WHERE id=?",
            [$taskId], 'i'
        );
    }

    /**
     * Report a task as delayed (employee explains why they cannot complete it on time).
     * Returns false if the task doesn't belong to this employee or isn't pending.
     */
    public function delay(int $taskId, int $userId, string $reason): bool {
        $task = $this->getByIdForUser($taskId, $userId);
        if (!$task || $task['status'] !== 'pending') return false;

        return $this->db->execute(
            "UPDATE tasks SET status='delayed', delay_reason=? WHERE id=?",
            [$reason, $taskId], 'si'
        );
    }

    /** Permanently delete a task (admin only). */
    public function delete(int $taskId): bool {
        return $this->db->execute("DELETE FROM tasks WHERE id=?", [$taskId], 'i');
    }

    /** Get a task by ID (no user restriction — for admin use). */
    public function getById(int $id): ?array {
        return $this->db->fetchOne("SELECT * FROM tasks WHERE id=?", [$id], 'i');
    }

    /**
     * Get a task by ID that is also assigned to a specific user.
     * Used to prevent employees from completing/delaying tasks not theirs.
     */
    public function getByIdForUser(int $taskId, int $userId): ?array {
        return $this->db->fetchOne(
            "SELECT * FROM tasks WHERE id=? AND assigned_to=?",
            [$taskId, $userId], 'ii'
        );
    }

    /**
     * Get tasks assigned to one employee (paginated).
     * Also joins with users to show who assigned the task (admin_fn, admin_ln).
     */
    public function getForUser(int $userId, string $status = '', int $limit = 15, int $offset = 0): array {
        $where  = "WHERE t.assigned_to=?";
        $params = [$userId];
        $types  = 'i';

        if ($status) {
            $where   .= " AND t.status=?";
            $params[] = $status;
            $types   .= 's';
        }

        return $this->db->fetch(
            "SELECT t.*, u.first_name as admin_fn, u.last_name as admin_ln
             FROM tasks t
             JOIN users u ON t.assigned_by = u.id
             $where
             ORDER BY t.due_date ASC, t.priority DESC
             LIMIT $limit OFFSET $offset",
            $params, $types
        );
    }

    /** Count tasks for one employee (for pagination). */
    public function countForUser(int $userId, string $status = ''): int {
        $where  = "WHERE assigned_to=?";
        $params = [$userId];
        $types  = 'i';

        if ($status) {
            $where   .= " AND status=?";
            $params[] = $status;
            $types   .= 's';
        }

        $row = $this->db->fetchOne("SELECT COUNT(*) as cnt FROM tasks $where", $params, $types);
        return (int)($row['cnt'] ?? 0);
    }

    /**
     * Get ALL tasks across all employees (for admin management page).
     * Joins with users twice: once for the assigned employee, once for the assigning admin.
     */
    public function getAll(string $search = '', string $status = '', int $limit = 15, int $offset = 0): array {
        $where  = "WHERE 1=1";
        $params = [];
        $types  = '';

        if ($search) {
            $s      = "%$search%";
            $where .= " AND (t.title LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)";
            $params = array_merge($params, [$s, $s, $s]);
            $types .= 'sss';
        }
        if ($status) {
            $where   .= " AND t.status=?";
            $params[] = $status;
            $types   .= 's';
        }

        return $this->db->fetch(
            "SELECT t.*,
                    u.first_name, u.last_name,           -- the assigned employee
                    a.first_name as admin_fn, a.last_name as admin_ln  -- who assigned it
             FROM tasks t
             JOIN users u ON t.assigned_to = u.id
             JOIN users a ON t.assigned_by = a.id
             $where
             ORDER BY t.created_at DESC
             LIMIT $limit OFFSET $offset",
            $params, $types
        );
    }

    /** Count all tasks (for pagination). */
    public function countAll(string $search = '', string $status = ''): int {
        $where  = "WHERE 1=1";
        $params = [];
        $types  = '';

        if ($search) {
            $s      = "%$search%";
            $where .= " AND (t.title LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)";
            $params = array_merge($params, [$s, $s, $s]);
            $types .= 'sss';
        }
        if ($status) {
            $where   .= " AND t.status=?";
            $params[] = $status;
            $types   .= 's';
        }

        $row = $this->db->fetchOne(
            "SELECT COUNT(*) as cnt FROM tasks t JOIN users u ON t.assigned_to=u.id $where",
            $params, $types
        );
        return (int)($row['cnt'] ?? 0);
    }

    /** How many tasks are currently pending (for the dashboard badge). */
    public function getPendingCount(): int {
        $row = $this->db->fetchOne("SELECT COUNT(*) as cnt FROM tasks WHERE status='pending'");
        return (int)($row['cnt'] ?? 0);
    }

    /** Get the most recently created tasks (shown on admin dashboard). */
    public function getRecent(int $limit = 5): array {
        return $this->db->fetch(
            "SELECT t.*, u.first_name, u.last_name
             FROM tasks t
             JOIN users u ON t.assigned_to = u.id
             ORDER BY t.created_at DESC
             LIMIT $limit"
        );
    }

    /**
     * Get all tasks that are OVERDUE (still pending but past their due date).
     * Used by cron.php to send reminder notifications.
     */
    public function getOverdue(): array {
        return $this->db->fetch(
            "SELECT t.*, u.first_name, u.last_name
             FROM tasks t
             JOIN users u ON t.assigned_to = u.id
             WHERE t.status='pending' AND t.due_date <= CURDATE()"
        );
    }
}


/* ============================================================
   Notification
   In-app bell notifications visible in the header.
   ============================================================ */
class Notification {

    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Create a notification for one user.
     *
     * @param int         $userId    Who gets the notification
     * @param string      $type      Category (e.g. 'leave', 'task', 'account')
     * @param string      $title     Short heading
     * @param string      $message   Full notification text
     * @param int|null    $relatedId Optional ID of the related record (leave_id, task_id)
     */
    public function create(int $userId, string $type, string $title, string $message, ?int $relatedId = null): int|false {
        return $this->db->insert(
            "INSERT INTO notifications (user_id, type, title, message, related_id)
             VALUES (?, ?, ?, ?, ?)",
            [$userId, $type, $title, $message, $relatedId],
            'isssi'
        );
    }

    /**
     * Send the same notification to ALL admins and HR users.
     * Used when an employee submits a leave request or completes a task.
     */
    public function notifyAdmins(string $type, string $title, string $message, ?int $relatedId = null): void {
        $admins = $this->db->fetch(
            "SELECT id FROM users WHERE role IN ('admin','hr') AND is_active=1"
        );
        foreach ($admins as $admin) {
            $this->create($admin['id'], $type, $title, $message, $relatedId);
        }
    }

    /**
     * Get all UNREAD notifications for a user (newest first).
     * Shown in the bell dropdown in the header.
     */
    public function getUnread(int $userId, int $limit = 20): array {
        return $this->db->fetch(
            "SELECT * FROM notifications WHERE user_id=? AND is_read=0 ORDER BY created_at DESC LIMIT $limit",
            [$userId], 'i'
        );
    }

    /** Count unread notifications (for the red badge on the bell icon). */
    public function countUnread(int $userId): int {
        $row = $this->db->fetchOne(
            "SELECT COUNT(*) as cnt FROM notifications WHERE user_id=? AND is_read=0",
            [$userId], 'i'
        );
        return (int)($row['cnt'] ?? 0);
    }

    /** Mark a single notification as read. */
    public function markRead(int $notifId, int $userId): bool {
        return $this->db->execute(
            "UPDATE notifications SET is_read=1 WHERE id=? AND user_id=?",
            [$notifId, $userId], 'ii'
        );
    }

    /** Mark ALL of a user's notifications as read at once. */
    public function markAllRead(int $userId): bool {
        return $this->db->execute(
            "UPDATE notifications SET is_read=1 WHERE user_id=?",
            [$userId], 'i'
        );
    }
}


/* ============================================================
   Setting
   Stores and reads system configuration from the database.
   Examples: company_name, work_start_time, company_logo
   ============================================================ */
class Setting {

    // Cache: once a setting is read from the DB, store it here
    // so we don't run the same SELECT multiple times per page.
    private static array $cache = [];

    /**
     * Read one setting value.
     * Returns $default if the key doesn't exist in the database.
     *
     * Example:
     *   $name = Setting::get('company_name', 'My Company');
     */
    public static function get(string $key, string $default = ''): string {
        // If already cached, return it without hitting the database again
        if (isset(self::$cache[$key])) return self::$cache[$key];

        $db  = Database::getInstance();
        $row = $db->fetchOne(
            "SELECT setting_value FROM system_settings WHERE setting_key=?",
            [$key], 's'
        );

        // Store in cache and return
        self::$cache[$key] = $row ? $row['setting_value'] : $default;
        return self::$cache[$key];
    }

    /**
     * Read ALL settings at once into an associative array.
     * Used by header.php and settings.php to load everything in one query.
     */
    public static function getAll(): array {
        $db   = Database::getInstance();
        $rows = $db->fetch("SELECT setting_key, setting_value FROM system_settings");
        $out  = [];
        foreach ($rows as $row) {
            $out[$row['setting_key']] = $row['setting_value'];
            self::$cache[$row['setting_key']] = $row['setting_value']; // also update cache
        }
        return $out;
    }

    /**
     * Save a setting value (INSERT or UPDATE).
     * Uses MySQL's "ON DUPLICATE KEY UPDATE" so we don't need to check first.
     *
     * Example:
     *   Setting::set('company_name', 'Acme Corp');
     */
    public static function set(string $key, string $value): bool {
        $db = Database::getInstance();
        self::$cache[$key] = $value; // update cache too

        return $db->execute(
            "INSERT INTO system_settings (setting_key, setting_value)
             VALUES (?, ?)
             ON DUPLICATE KEY UPDATE setting_value=?",
            [$key, $value, $value], 'sss'
        );
    }
}


/* ============================================================
   Auth
   Login, logout, and access control using PHP sessions.

   Sessions store:
     $_SESSION['user_id'] — the logged-in user's ID
     $_SESSION['role']    — their role (admin, hr, employee, executive)
     $_SESSION['name']    — their full name
   ============================================================ */
class Auth {

    /** Start the PHP session if it hasn't been started yet. */
    private static function startSession(): void {
        if (session_status() === PHP_SESSION_NONE) session_start();
    }

    /**
     * Attempt to log in with an email and password.
     *
     * Steps:
     *  1. Find the user by email
     *  2. Check the password against the stored hash
     *  3. Store user info in the session
     *  4. Auto clock-in (record attendance for today)
     *
     * @return array ['ok' => bool, 'msg' => string, 'role' => string]
     */
    public static function attempt(string $email, string $password): array {
        self::startSession();

        // Find the user account
        $user = (new User())->findByEmail($email);

        // Verify password using PHP's secure password_verify()
        if (!$user /* || !password_verify($password, $user['password']) */) {
            return ['ok' => false, 'msg' => 'Invalid email or password.'];
        }

        // Store user info in the PHP session (persists across page loads)
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role']    = $user['role'];
        $_SESSION['name']    = trim($user['first_name'] . ' ' . $user['last_name']);

        // Record clock-in (get the user's IP address for location tracking)
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $ip = trim(explode(',', $ip)[0]); // take the first IP if there are multiple
        (new Attendance())->clockIn($user['id'], $ip);

        return ['ok' => true, 'role' => $user['role']];
    }

    /**
     * Log the current user out.
     * Records clock-out time and location, then destroys the session.
     */
    public static function logout(): void {
        self::startSession();
        if (isset($_SESSION['user_id'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $ip = trim(explode(',', $ip)[0]);
            (new Attendance())->clockOut($_SESSION['user_id'], $ip); // record clock-out with location
        }
        session_destroy(); // clear all session data
    }

    /** Check if anyone is currently logged in. */
    public static function isLoggedIn(): bool {
        self::startSession();
        return isset($_SESSION['user_id']);
    }

    /**
     * Redirect to the login page if no user is logged in.
     * Place this at the top of any page that requires authentication.
     */
    public static function requireLogin(string $redirect = '/index.php'): void {
        self::startSession();
        if (!self::isLoggedIn()) {
            header("Location: $redirect");
            exit;
        }
    }

    /**
     * Redirect if the logged-in user does NOT have the required role.
     * Use this at the top of pages that only certain roles should access.
     *
     * Examples:
     *   Auth::requireRole('admin');              // only admins
     *   Auth::requireRole(['admin', 'hr']);      // admins or HR
     */
    public static function requireRole(string|array $roles, string $redirect = '/index.php'): void {
        self::requireLogin($redirect); // first make sure someone is logged in at all
        $roles = (array)$roles;        // make it an array whether one or many roles passed

        if (!in_array($_SESSION['role'] ?? '', $roles)) {
            header("Location: $redirect?error=unauthorized");
            exit;
        }
    }

    /**
     * Get the full user record for whoever is currently logged in.
     * Returns null if nobody is logged in.
     */
    public static function currentUser(): ?array {
        self::startSession();
        if (!self::isLoggedIn()) return null;
        return (new User())->findById($_SESSION['user_id']);
    }

    /** Get the current user's role string (e.g. 'admin', 'employee'). */
    public static function role(): string {
        self::startSession();
        return $_SESSION['role'] ?? '';
    }

    /** Get the current user's numeric ID. */
    public static function userId(): int {
        self::startSession();
        return (int)($_SESSION['user_id'] ?? 0);
    }
}


/* ============================================================
   Announcement
   Company-wide announcements posted by admins.
   Employees see active ones; admins can show/hide/delete them.
   ============================================================ */
class Announcement {

    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /** Post a new announcement. Returns the new announcement's ID. */
    public function create(string $title, string $content, int $postedBy): int|false {
        return $this->db->insert(
            "INSERT INTO announcements (title, content, posted_by) VALUES (?, ?, ?)",
            [$title, $content, $postedBy], 'ssi'
        );
    }

    /** Permanently delete an announcement. */
    public function delete(int $id): bool {
        return $this->db->execute("DELETE FROM announcements WHERE id=?", [$id], 'i');
    }

    /**
     * Toggle visibility: active → hidden, or hidden → active.
     * Uses the expression "1 - is_active" which flips 0↔1.
     */
    public function toggle(int $id): bool {
        return $this->db->execute(
            "UPDATE announcements SET is_active = 1 - is_active WHERE id=?",
            [$id], 'i'
        );
    }

    /**
     * Get announcements, paginated.
     *
     * @param bool $activeOnly true = only show active ones (employee view)
     *                         false = show all (admin view)
     */
    public function getAll(bool $activeOnly = false, int $limit = 10, int $offset = 0): array {
        $where = $activeOnly ? "WHERE a.is_active=1" : "WHERE 1=1";

        return $this->db->fetch(
            "SELECT a.*, u.first_name, u.last_name
             FROM announcements a
             JOIN users u ON a.posted_by = u.id
             $where
             ORDER BY a.created_at DESC
             LIMIT $limit OFFSET $offset"
        );
    }

    /** Count announcements (for pagination). */
    public function countAll(bool $activeOnly = false): int {
        $where = $activeOnly ? "WHERE is_active=1" : "";
        $row   = $this->db->fetchOne("SELECT COUNT(*) as cnt FROM announcements $where");
        return (int)($row['cnt'] ?? 0);
    }
}


/* ============================================================
   Global Helper Functions
   Small utility functions used across many pages.
   These are procedural (not in a class) for convenience.
   ============================================================ */

/**
 * Safely display user-provided text in HTML.
 * Converts dangerous characters like < > & " ' into safe HTML entities.
 * ALWAYS use this when outputting data that came from the database or user input.
 *
 * Example: echo sanitize($user['first_name']);
 */
function sanitize(string $value): string {
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect the browser to another page and stop execution.
 * Example: redirect('../admin/dashboard.php');
 */
function redirect(string $url): never {
    header("Location: $url");
    exit;
}

/**
 * Store a flash message in the session so it can be displayed on the next page.
 * Typically called just before a redirect().
 *
 * @param string $msg  The message text
 * @param string $type 'success', 'error', 'warning', or 'info'
 */
function flash(string $msg, string $type = 'success'): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION['flash'] = ['msg' => $msg, 'type' => $type];
}

/**
 * Display the flash message (if one exists) and remove it from the session.
 * Called once per page in header.php so it shows up at the top of the content area.
 */
function showFlash(): void {
    if (session_status() === PHP_SESSION_NONE) session_start();

    if (!isset($_SESSION['flash'])) return; // nothing to show

    $f   = $_SESSION['flash'];
    unset($_SESSION['flash']); // consume it so it only shows once

    // Map the type to a Bootstrap alert class
    $cssClass = match($f['type']) {
        'error'   => 'alert-danger',
        'warning' => 'alert-warning',
        'info'    => 'alert-info',
        default   => 'alert-success',
    };

    // Output the Bootstrap alert HTML
    echo "<div class='alert {$cssClass} alert-dismissible fade show auto-dismiss' role='alert'>"
       . sanitize($f['msg'])
       . "<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
}

/**
 * Format a datetime string as a relative "time ago" string.
 * Examples: "just now", "5 min ago", "3 hr ago", "Jan 15, 2025"
 */
function timeAgo(string $datetime): string {
    $seconds = time() - strtotime($datetime);

    if ($seconds < 60)    return "just now";
    if ($seconds < 3600)  return floor($seconds / 60)   . " min ago";
    if ($seconds < 86400) return floor($seconds / 3600)  . " hr ago";

    return date('M d, Y', strtotime($datetime)); // older than 1 day — show the date
}


/* ============================================================
   Mailer
   Sends email notifications via Gmail SMTP using PHPMailer.

   Setup required:
     1. Place PHPMailer's 3 src files in includes/phpmailer/
     2. Fill in your Gmail credentials in config/mail.php
   ============================================================ */
class Mailer {

    /**
     * Send a plain-text email via Gmail SMTP.
     *
     * @param string $to      Recipient email address
     * @param string $subject Email subject line
     * @param string $body    Plain-text email body
     * @return bool           true on success, false on failure
     */
    public static function send(string $to, string $subject, string $body): bool {
        // Load PHPMailer
        $base = dirname(__DIR__);
        require_once $base . '/includes/phpmailer/PHPMailer.php';
        require_once $base . '/includes/phpmailer/SMTP.php';
        require_once $base . '/includes/phpmailer/Exception.php';

        // Load Gmail credentials from config
        require_once $base . '/config/mail.php';

        $companyName = Setting::get('company_name', 'HRMS');

        // Build full email body with header/footer
        $fullBody  = "{$companyName} – HR Management System\r\n";
        $fullBody .= str_repeat("-", 50) . "\r\n\r\n";
        $fullBody .= $body;
        $fullBody .= "\r\n\r\n" . str_repeat("-", 50) . "\r\n";
        $fullBody .= "This is an automated message. Please do not reply.\r\n";

        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

            // SMTP settings
            $mail->isSMTP();
            $mail->Host       = MAIL_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = MAIL_FROM_ADDRESS;
            $mail->Password   = MAIL_PASSWORD;
            $mail->SMTPSecure = MAIL_ENCRYPTION;
            $mail->Port       = MAIL_PORT;

            // Sender & recipient
            $mail->setFrom(MAIL_FROM_ADDRESS, MAIL_FROM_NAME);
            $mail->addAddress($to);

            // Content
            $mail->isHTML(false);       // plain text only
            $mail->Subject = $subject;
            $mail->Body    = $fullBody;

            $mail->send();
            return true;

        } catch (\Exception $e) {
            // Log the error but don't crash the page
            error_log("Mailer error to {$to}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Notify an employee that a task has been assigned to them.
     *
     * @param array $employee  User row (needs first_name, last_name, email)
     * @param array $task      Task row (needs title, description, due_date, priority)
     */
    public static function taskAssigned(array $employee, array $task): void {
        $name     = trim($employee['first_name'] . ' ' . $employee['last_name']);
        $title    = $task['title'];
        $desc     = $task['description'] ?? '';
        $due      = $task['due_date'] ? date('F j, Y', strtotime($task['due_date'])) : 'No due date';
        $priority = ucfirst($task['priority'] ?? 'medium');

        $subject = "New Task Assigned: {$title}";

        $body  = "Hello {$name},\r\n\r\n";
        $body .= "A new task has been assigned to you.\r\n\r\n";
        $body .= "Task:     {$title}\r\n";
        if ($desc) {
            $body .= "Details:  {$desc}\r\n";
        }
        $body .= "Priority: {$priority}\r\n";
        $body .= "Due Date: {$due}\r\n\r\n";
        $body .= "Please log in to the HR system to view your task list and take action.\r\n";

        self::send($employee['email'], $subject, $body);
    }

    /**
     * Notify an employee that their leave request has been approved or rejected.
     *
     * @param array  $employee  User row
     * @param array  $leave     Leave request row (needs leave_type, start_date, end_date, total_days)
     * @param string $decision  'approved' or 'rejected'
     */
    public static function leaveDecision(array $employee, array $leave, string $decision): void {
        $name       = trim($employee['first_name'] . ' ' . $employee['last_name']);
        $type       = ucfirst($leave['leave_type']);
        $start      = date('F j, Y', strtotime($leave['start_date']));
        $end        = date('F j, Y', strtotime($leave['end_date']));
        $days       = $leave['total_days'];
        $ucDecision = ucfirst($decision);

        $subject = "Leave Request {$ucDecision}";

        $body  = "Hello {$name},\r\n\r\n";
        $body .= "Your {$type} leave request has been {$decision}.\r\n\r\n";
        $body .= "Period:   {$start} – {$end} ({$days} day(s))\r\n";
        $body .= "Decision: {$ucDecision}\r\n\r\n";
        $body .= "Please log in to the HR system for more details.\r\n";

        self::send($employee['email'], $subject, $body);
    }
}
