<?php
/**
 * hr/my_leaves.php
 * HR staff can also request and view their own leaves.
 * Identical to employee leaves page — role checked below.
 */
require_once '../includes/auth.php';
requireRole('hr');

// Reuse the employee leave request page
require_once '../employee/leaves.php';
