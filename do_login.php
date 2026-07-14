<?php
// ACS LMS - Login Handler
// Inashughulikia login na kupeleka mtu mahali pake

$moodle_path = 'C:/xampp/htdocs/moodle';

$username = strtolower(trim($_POST['username'] ?? ''));
$password = $_POST['password'] ?? '';

if (!$username || !$password) {
    header('Location: /acs/?err=1');
    exit;
}

// Tumia Moodle authentication moja kwa moja
define('CLI_SCRIPT', false);
define('AJAX_SCRIPT', false);
define('NO_DEBUG_DISPLAY', true);

chdir($moodle_path);
require_once($moodle_path . '/config.php');
require_once($moodle_path . '/lib/authlib.php');

// Thibitisha credentials
$user = authenticate_user_login($username, $password);

if (!$user) {
    // Credentials mbaya
    header('Location: /acs/?err=invalid');
    exit;
}

// Login imefaulu — weka session ya Moodle
complete_user_login($user);

// Peleka kulingana na role
$admins   = ['admin'];
$teachers = ['teacher', 'mwalimu', 'stephan.mbuya'];

if (is_siteadmin($user)) {
    redirect(new moodle_url('/admin/index.php'));
} elseif (in_array($username, $teachers)) {
    redirect(new moodle_url('/my/courses.php'));
} else {
    redirect(new moodle_url('/my/courses.php'));
}
