<?php
/**
 * includes/auth.php
 * =================
 * This file is included at the TOP of every authenticated page.
 *
 * All it does is:
 *  1. Load bootstrap.php  → connects the database and loads all classes
 *  2. Provide short "wrapper" functions that older pages call directly
 *     (e.g. requireRole(), flash(), sanitize())
 *     The actual logic lives in classes/Models.php.
 *
 * Every protected page starts with one of these two lines:
 *   require_once '../includes/auth.php';  (inside a subfolder like admin/)
 *   require_once 'includes/auth.php';     (from the root like profile.php)
 *
 * Then immediately restricts access:
 *   requireRole('admin');              // admins only
 *   requireRole(['admin', 'hr']);      // admins or HR
 */
require_once __DIR__ . '/../config/bootstrap.php';

// ── Procedural wrappers (thin aliases to class methods) ───────
// These exist so pages can call requireRole(), flash(), etc. directly
// without needing to write Auth::requireRole() or Setting::getAll() etc.
// The real code is in classes/Models.php.

function isLoggedIn(): bool { return Auth::isLoggedIn(); }
function requireLogin(string $r='../index.php'): void { Auth::requireLogin($r); }
function requireRole(string|array $roles, string $r='../index.php'): void { Auth::requireRole($roles, $r); }
function currentUser(): ?array { return Auth::currentUser(); }
function getSettings(): array { return Setting::getAll(); }
function getSetting(string $k, string $d=''): string { return Setting::get($k,$d); }

// Notification helpers
function createNotification(int $u, string $t, string $ti, string $m, ?int $ri=null): void {
    (new Notification())->create($u,$t,$ti,$m,$ri);
}
function notifyAdmins(string $t, string $ti, string $m, ?int $ri=null): void {
    (new Notification())->notifyAdmins($t,$ti,$m,$ri);
}
function getUnreadNotifications(int $u): array { return (new Notification())->getUnread($u); }
function getUnreadCount(int $u): int           { return (new Notification())->countUnread($u); }

// Attendance helpers
function getTodayAttendance(int $u): ?array { return (new Attendance())->getToday($u); }
function calculateHours(?string $in, ?string $out, ?string $bs=null, ?string $be=null): float {
    return (new Attendance())->calculateHours($in,$out,$bs,$be);
}
function formatHours(float $h): string { return Attendance::formatHours($h); }

// IP helper
function getIPLocation(): string {
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    return trim(explode(',',$ip)[0]);
}

// Direct database wrappers (used in hr/reports.php, admin/reports.php, etc.)
function dbFetch(string $sql, array $p=[], string $t=''): array    { return Database::getInstance()->fetch($sql,$p,$t); }
function dbFetchOne(string $sql, array $p=[], string $t=''): ?array { return Database::getInstance()->fetchOne($sql,$p,$t); }
function dbInsert(string $sql, array $p=[], string $t=''): int|false { return Database::getInstance()->insert($sql,$p,$t); }
function dbUpdate(string $sql, array $p=[], string $t=''): bool     { return Database::getInstance()->execute($sql,$p,$t); }
