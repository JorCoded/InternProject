<?php
/**
 * mail.php
 * ========
 * Gmail SMTP configuration for PHPMailer.
 *
 * HOW TO FILL THIS IN:
 *  1. MAIL_FROM_ADDRESS  → the Gmail address you created for HRMS
 *  2. MAIL_FROM_NAME     → the display name employees will see in their inbox
 *  3. MAIL_PASSWORD      → the 16-character App Password from Google
 *                          (NOT your regular Gmail password)
 *
 * Get an App Password at:
 *   Google Account → Security → 2-Step Verification → App passwords
 */

define('MAIL_HOST',         'smtp.gmail.com');
define('MAIL_PORT',         587);               // TLS port — do not change
define('MAIL_ENCRYPTION',   'tls');             // do not change
define('MAIL_FROM_ADDRESS', 'connectitcutproject0@gmail.com');   // ← change this
define('MAIL_FROM_NAME',    'HRMS Notifications');           // ← change this if you want
define('MAIL_PASSWORD',     'aegi knjj esnf ntwh');          // ← paste your App Password here
