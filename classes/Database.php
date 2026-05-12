<?php
/**
 * Database.php
 * ============
 * This class handles the MySQL database connection.
 *
 * It uses the "Singleton" pattern, which means only ONE connection
 * is ever created for the whole page load. Every other file that
 * needs the database calls Database::getInstance() to get that
 * single shared connection.
 *
 * How to use from any other file:
 *   $db = Database::getInstance();
 *   $rows = $db->fetch("SELECT * FROM users");
 */

class Database {

    // ── Singleton storage ─────────────────────────────────────
    // This holds the one and only Database object once it is created.
    private static ?Database $instance = null;

    // The actual MySQL connection object
    private mysqli $conn;

    /**
     * Constructor — runs ONCE when the first getInstance() is called.
     * It reads the DB credentials from the constants defined in bootstrap.php
     * and opens the connection.
     *
     * Private because you must use getInstance(), not "new Database()".
     */
    private function __construct() {
        $this->conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

        // Stop everything if the connection failed
        if ($this->conn->connect_error) {
            die('Database connection failed: ' . $this->conn->connect_error);
        }

        // Use UTF-8 so accented characters work correctly
        $this->conn->set_charset('utf8mb4');
    }

    /**
     * Get (or create) the single Database instance.
     * Every file calls this to get the database object.
     */
    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    /** Get the raw mysqli connection object (rarely needed directly). */
    public function getConnection(): mysqli {
        return $this->conn;
    }

    /**
     * Run a SELECT and return ALL matching rows as an array of arrays.
     *
     * @param string $sql    SQL with ? placeholders for values
     * @param array  $params Values to fill the ? placeholders
     * @param string $types  One letter per param: 's'=string, 'i'=int, 'd'=float
     * @return array         All rows (empty array if none found)
     *
     * Example:
     *   $rows = $db->fetch("SELECT * FROM users WHERE role=?", ['employee'], 's');
     */
    public function fetch(string $sql, array $params = [], string $types = ''): array {
        // Simple query with no parameters — run directly
        if (empty($params)) {
            $result = $this->conn->query($sql);
            if (!$result) return [];
            return $result->fetch_all(MYSQLI_ASSOC);
        }

        // Prepared statement — values replace ? safely (prevents SQL injection)
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return [];
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Run a SELECT and return ONE row (or null if nothing found).
     *
     * Example:
     *   $user = $db->fetchOne("SELECT * FROM users WHERE id=?", [5], 'i');
     */
    public function fetchOne(string $sql, array $params = [], string $types = ''): ?array {
        $rows = $this->fetch($sql, $params, $types);
        return $rows ? $rows[0] : null;
    }

    /**
     * Run an INSERT and return the new row's auto-increment ID (or false on failure).
     *
     * Example:
     *   $newId = $db->insert("INSERT INTO users (name) VALUES (?)", ['John'], 's');
     */
    public function insert(string $sql, array $params = [], string $types = ''): int|false {
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return false;
        if (!empty($params)) $stmt->bind_param($types, ...$params);
        if (!$stmt->execute()) return false;
        return $this->conn->insert_id;
    }

    /**
     * Run an UPDATE or DELETE and return true/false.
     *
     * Example:
     *   $ok = $db->execute("UPDATE users SET is_active=0 WHERE id=?", [5], 'i');
     */
    public function execute(string $sql, array $params = [], string $types = ''): bool {
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return false;
        if (!empty($params)) $stmt->bind_param($types, ...$params);
        return $stmt->execute();
    }

    // Prevent cloning and un-serializing the singleton
    private function __clone() {}
    public function __wakeup() { throw new \Exception('Cannot unserialize singleton.'); }
}
