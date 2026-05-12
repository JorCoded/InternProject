<?php
/**
 * User.php
 * ========
 * Handles everything related to user accounts:
 *   - Finding users by ID or email
 *   - Creating / updating / deactivating users
 *   - Checking if an email or employee ID already exists
 *   - Managing leave balances
 *   - Verifying and updating passwords
 *
 * Usage example:
 *   $userObj = new User();
 *   $user = $userObj->findById(3);
 *   echo $user['first_name'];
 */

class User {

    // The shared database connection
    private Database $db;

    /** Constructor — get the database connection */
    public function __construct() {
        $this->db = Database::getInstance();
    }

    // ── Finding users ─────────────────────────────────────────

    /** Get one user by their numeric ID. Returns null if not found. */
    public function findById(int $id): ?array {
        return $this->db->fetchOne("SELECT * FROM users WHERE id=?", [$id], 'i');
    }

    /**
     * Get one ACTIVE user by their email address.
     * Used during login to find the account.
     */
    public function findByEmail(string $email): ?array {
        return $this->db->fetchOne(
            "SELECT * FROM users WHERE email=? AND is_active=1",
            [$email], 's'
        );
    }

    /**
     * Get a paginated list of users with optional search and role filter.
     *
     * @param string $search   Text to search across name, email, employee_id
     * @param string $role     Filter by role (e.g. 'employee', 'hr') — '' for all
     * @param int    $limit    Number of rows to return (for pagination)
     * @param int    $offset   How many rows to skip (for pagination)
     */
    public function getAll(string $search = '', string $role = '', int $limit = 15, int $offset = 0): array {
        // Start with a condition that is always true so we can append AND conditions cleanly
        $where  = "WHERE 1=1";
        $params = [];
        $types  = '';

        // Add search filter if provided
        if ($search) {
            $s      = "%$search%"; // wrap in % for LIKE matching
            $where .= " AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR employee_id LIKE ?)";
            $params = [$s, $s, $s, $s];
            $types  = 'ssss';
        }

        // Add role filter if provided
        if ($role) {
            $where   .= " AND role=?";
            $params[] = $role;
            $types   .= 's';
        }

        return $this->db->fetch(
            "SELECT * FROM users $where ORDER BY first_name LIMIT $limit OFFSET $offset",
            $params, $types
        );
    }

    /** Count users matching the same search/role filter (used for pagination). */
    public function countAll(string $search = '', string $role = ''): int {
        $where  = "WHERE 1=1";
        $params = [];
        $types  = '';

        if ($search) {
            $s      = "%$search%";
            $where .= " AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR employee_id LIKE ?)";
            $params = [$s, $s, $s, $s];
            $types  = 'ssss';
        }
        if ($role) {
            $where   .= " AND role=?";
            $params[] = $role;
            $types   .= 's';
        }

        $row = $this->db->fetchOne("SELECT COUNT(*) as cnt FROM users $where", $params, $types);
        return (int)($row['cnt'] ?? 0);
    }

    // ── Creating and updating users ───────────────────────────

    /**
     * Create a new user account.
     * The password is hashed automatically before storing.
     *
     * @param array $data  Must include: employee_id, first_name, last_name,
     *                     email, password, role
     *                     Optional: department, position, phone,
     *                               local_leaves, sick_leaves
     * @return int|false   The new user's ID, or false on failure
     */
    public function create(array $data): int|false {
        return $this->db->insert(
            "INSERT INTO users
             (employee_id, first_name, last_name, email, password,
              role, department, position, phone, local_leaves, sick_leaves)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $data['employee_id'],
                $data['first_name'],
                $data['last_name'],
                $data['email'],
                password_hash($data['password'], PASSWORD_DEFAULT), // never store plain text passwords
                $data['role'],
                $data['department']   ?? '',
                $data['position']     ?? '',
                $data['phone']        ?? '',
                (int)($data['local_leaves'] ?? 22),
                (int)($data['sick_leaves']  ?? 15),
            ],
            'sssssssssii'
        );
    }

    /**
     * Update an existing user's information (everything except password).
     *
     * @param int   $id    The user's ID
     * @param array $data  Fields to update
     */
    public function update(int $id, array $data): bool {
        return $this->db->execute(
            "UPDATE users
             SET first_name=?, last_name=?, email=?, role=?,
                 department=?, position=?, phone=?,
                 local_leaves=?, sick_leaves=?
             WHERE id=?",
            [
                $data['first_name'],
                $data['last_name'],
                $data['email'],
                $data['role'],
                $data['department'] ?? '',
                $data['position']   ?? '',
                $data['phone']      ?? '',
                (int)$data['local_leaves'],
                (int)$data['sick_leaves'],
                $id,
            ],
            'ssssssssii'
        );
    }

    /**
     * Update only personal profile fields (for the user themselves on profile.php).
     * Email and role are intentionally NOT included here — only admin can change those.
     */
    public function updateProfile(int $id, array $data): bool {
        return $this->db->execute(
            "UPDATE users SET first_name=?, last_name=?, phone=?, address=? WHERE id=?",
            [$data['first_name'], $data['last_name'], $data['phone'] ?? '', $data['address'] ?? '', $id],
            'ssssi'
        );
    }

    // ── Password management ───────────────────────────────────

    /**
     * Check if a given plain-text password matches what is stored for a user.
     * password_verify() compares against the bcrypt hash in the database.
     */
    public function verifyPassword(int $id, string $plainPassword): bool {
        $user = $this->findById($id);
        return $user && password_verify($plainPassword, $user['password']);
    }

    /**
     * Hash and store a new password for a user.
     * Always hash passwords — never store them as plain text.
     */
    public function updatePassword(int $id, string $newPassword): bool {
        return $this->db->execute(
            "UPDATE users SET password=? WHERE id=?",
            [password_hash($newPassword, PASSWORD_DEFAULT), $id],
            'si'
        );
    }

    // ── Account status ────────────────────────────────────────

    /** Soft-delete: mark user as inactive (they keep their data but cannot log in). */
    public function deactivate(int $id): bool {
        return $this->db->execute("UPDATE users SET is_active=0 WHERE id=?", [$id], 'i');
    }

    /** Re-enable a deactivated user account. */
    public function activate(int $id): bool {
        return $this->db->execute("UPDATE users SET is_active=1 WHERE id=?", [$id], 'i');
    }

    // ── Uniqueness checks ─────────────────────────────────────

    /**
     * Check whether an email address is already in use.
     * Pass $excludeId to ignore a specific user (useful when editing).
     */
    public function emailExists(string $email, int $excludeId = 0): bool {
        $row = $this->db->fetchOne(
            "SELECT id FROM users WHERE email=? AND id!=?",
            [$email, $excludeId], 'si'
        );
        return (bool)$row;
    }

    /**
     * Check whether an employee ID is already taken.
     * Pass $excludeId to ignore the user being edited.
     */
    public function employeeIdExists(string $empId, int $excludeId = 0): bool {
        $row = $this->db->fetchOne(
            "SELECT id FROM users WHERE employee_id=? AND id!=?",
            [$empId, $excludeId], 'si'
        );
        return (bool)$row;
    }

    // ── Leave balance management ──────────────────────────────

    /**
     * Deduct leave days from a user's balance when a leave is approved.
     * For unpaid leave it ADDS to the used count instead of deducting.
     *
     * @param int    $id   User ID
     * @param string $type 'local', 'sick', or 'unpaid'
     * @param int    $days Number of days
     */
    public function deductLeave(int $id, string $type, int $days): bool {
        if ($type === 'local') {
            // Reduce the local leave balance
            return $this->db->execute(
                "UPDATE users SET local_leaves = local_leaves - ? WHERE id=?",
                [$days, $id], 'ii'
            );
        } elseif ($type === 'sick') {
            return $this->db->execute(
                "UPDATE users SET sick_leaves = sick_leaves - ? WHERE id=?",
                [$days, $id], 'ii'
            );
        } elseif ($type === 'unpaid') {
            // Unpaid leave counter goes UP (it tracks how many unpaid days were taken)
            return $this->db->execute(
                "UPDATE users SET unpaid_leaves = unpaid_leaves + ? WHERE id=?",
                [$days, $id], 'ii'
            );
        }
        return false;
    }

    /**
     * Reset ALL employees' leave balances to the annual defaults.
     * Called once a year (Jan 1) via cron.php, or manually from settings.
     */
    public function resetAllLeaves(int $local = 22, int $sick = 15): bool {
        return $this->db->execute(
            "UPDATE users SET local_leaves=?, sick_leaves=?, unpaid_leaves=0",
            [$local, $sick], 'ii'
        );
    }

    // ── Utility queries ───────────────────────────────────────

    /** Get all active users — used for dropdowns and notifications. */
    public function getAllActive(): array {
        return $this->db->fetch(
            "SELECT id, first_name, last_name, employee_id, role
             FROM users WHERE is_active=1 ORDER BY first_name"
        );
    }

    /** Get only active employees (role='employee') — for task assignment dropdowns. */
    public function getEmployees(): array {
        return $this->db->fetch(
            "SELECT id, first_name, last_name, employee_id
             FROM users WHERE role='employee' AND is_active=1 ORDER BY first_name"
        );
    }

    /** Get all admin and HR user IDs — used to send notifications. */
    public function getAdmins(): array {
        return $this->db->fetch(
            "SELECT id FROM users WHERE role IN ('admin','hr') AND is_active=1"
        );
    }
}
