<?php
/**
 * cron.php — Scheduled Task Runner
 * ==================================
 * This script should be run automatically once a day by the server.
 *
 * HOW TO SET IT UP (Linux/Mac cron job):
 *   0 17 * * * php /var/www/html/hrms/cron.php >> /var/log/hrms_cron.log 2>&1
 *   (This runs at 5pm every day and logs the output)
 *
 * WHAT IT DOES:
 *   Task 1 — Annual leave reset: on January 1st, resets all employees' leave balances
 *   Task 2 — Monthly report archiving: on the 1st of each month, archives the previous month
 *   Task 3 — Mark absentees: after work hours, marks employees with no record as absent
 *   Task 4 — Task notifications: alerts admins about overdue or due-today tasks
 */

require_once __DIR__ . '/config/bootstrap.php';

// Get common date/time values we'll use throughout
$today    = date('Y-m-d');
$month    = date('m');   // e.g. "01" = January
$day      = date('d');   // e.g. "01" = 1st
$hour     = (int)date('H'); // e.g. 17 = 5pm

$db    = Database::getInstance();
$notif = new Notification();
$att   = new Attendance();
$user  = new User();

/** Helper: print a timestamped log message to stdout */
function clog(string $message): void {
    echo '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;
}

// ─────────────────────────────────────────────────────────────
// TASK 1: Annual Leave Reset
// Only runs on January 1st each year.
// Resets every employee's leave balance back to the annual defaults.
// ─────────────────────────────────────────────────────────────
if ($month === '01' && $day === '01') {
    $localLeaves = (int)Setting::get('annual_local_leaves', '22');
    $sickLeaves  = (int)Setting::get('annual_sick_leaves',  '15');

    $user->resetAllLeaves($localLeaves, $sickLeaves);

    clog("Annual leave reset done: local={$localLeaves}, sick={$sickLeaves}, unpaid=0");
}

// ─────────────────────────────────────────────────────────────
// TASK 2: Monthly Report Archiving
// On the 1st of each month, create a record in report_archives
// for the previous month's attendance and task data.
// ─────────────────────────────────────────────────────────────
if ($day === '01') {
    $prevMonth   = date('Y-m', strtotime('-1 month')); // e.g. "2025-04"
    $periodStart = $prevMonth . '-01';
    $periodEnd   = date('Y-m-t', strtotime($periodStart)); // last day of that month

    // Only archive once — check if we've already done this month
    $alreadyDone = $db->fetchOne(
        "SELECT id FROM report_archives WHERE report_type='attendance' AND period_start=?",
        [$periodStart], 's'
    );

    if (!$alreadyDone) {
        $db->insert(
            "INSERT INTO report_archives (report_type, period_start, period_end, generated_by) VALUES ('attendance',?,?,1)",
            [$periodStart, $periodEnd], 'ss'
        );
        $db->insert(
            "INSERT INTO report_archives (report_type, period_start, period_end, generated_by) VALUES ('tasks',?,?,1)",
            [$periodStart, $periodEnd], 'ss'
        );
        clog("Monthly reports archived for {$prevMonth}");
    } else {
        clog("Monthly archive for {$prevMonth} already exists — skipped");
    }
}

// ─────────────────────────────────────────────────────────────
// TASK 3: Mark Absent Employees
// Runs after the work end time (e.g. 5pm).
// Any active employee with no attendance record today is marked absent,
// UNLESS they have an approved leave for today (then mark as on_leave).
// ─────────────────────────────────────────────────────────────
$workEndHour = (int)substr(Setting::get('work_end_time', '17:00'), 0, 2);

if ($hour >= $workEndHour) {
    $employees = $db->fetch("SELECT id FROM users WHERE is_active=1");
    $marked    = 0;

    foreach ($employees as $e) {
        // Check if there's already an attendance record for today
        $existingRecord = $db->fetchOne(
            "SELECT id FROM attendance WHERE user_id=? AND date=?",
            [$e['id'], $today], 'is'
        );

        if (!$existingRecord) {
            // No record — check if they have an approved leave for today
            $onLeave = $db->fetchOne(
                "SELECT id FROM leave_requests
                 WHERE user_id=? AND status='approved'
                   AND ? BETWEEN start_date AND end_date",
                [$e['id'], $today], 'is'
            );

            // Mark as on_leave if they have approved leave, otherwise absent
            $status = $onLeave ? 'on_leave' : 'absent';
            $db->insert(
                "INSERT INTO attendance (user_id, date, status) VALUES (?, ?, ?)",
                [$e['id'], $today, $status], 'iss'
            );
            $marked++;
        }
    }

    clog("Marked {$marked} employee(s) as absent or on_leave.");
} else {
    clog("Skipping absent marking — work end time ({$workEndHour}:00) not reached yet.");
}

// ─────────────────────────────────────────────────────────────
// TASK 4: Overdue / Due-Today Task Notifications
// Checks for pending tasks that are overdue or due today.
// Sends a notification to each admin — but only once per task per day.
// ─────────────────────────────────────────────────────────────
$overdueTasks = (new Task())->getOverdue(); // returns pending tasks where due_date <= today
$admins       = $user->getAdmins();
$notified     = 0;

foreach ($overdueTasks as $task) {
    $isOverdue = ($task['due_date'] < $today); // true = overdue, false = due today
    $label     = $isOverdue ? 'Overdue' : 'Due Today';

    foreach ($admins as $admin) {
        // Skip if we already sent a notification for this task today
        $alreadySent = $db->fetchOne(
            "SELECT id FROM notifications
             WHERE user_id=? AND related_id=? AND type='task_due' AND DATE(created_at)=CURDATE()",
            [$admin['id'], $task['id']], 'ii'
        );

        if (!$alreadySent) {
            $notif->create(
                $admin['id'],
                'task_due',
                "Task {$label}",
                "{$task['first_name']} {$task['last_name']}'s task \"{$task['title']}\" is {$label}.",
                $task['id']
            );
            $notified++;
        }
    }
}

clog("Task notifications: " . count($overdueTasks) . " task(s) found, {$notified} notification(s) created.");
clog("Cron completed successfully.");
