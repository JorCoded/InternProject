<?php
/**
 * executive/attendance.php
 * Executive's personal attendance history — same view as employee.
 */
require_once '../includes/auth.php';
requireRole('executive');

// Reuse the employee attendance view
// (role check above ensures only executives reach this)
require_once '../employee/attendance.php';
