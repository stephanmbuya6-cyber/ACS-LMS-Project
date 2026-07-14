<?php
// ACS LMS Login Proxy
// Inashughulikia login kutoka landing page na kupeleka mtu mahali pake

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /acs/');
    exit;
}

$username = strtolower(trim($_POST['username'] ?? ''));
$password = $_POST['password'] ?? '';

if (!$username || !$password) {
    header('Location: /acs/?error=empty');
    exit;
}

// Pata logintoken kutoka Moodle
$ch = curl_init('http://localhost/moodle/login/index.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, sys_get_temp_dir() . '/moodle_cookie_' . session_id() . '.txt');
curl_setopt($ch, CURLOPT_COOKIEFILE, sys_get_temp_dir() . '/moodle_cookie_' . session_id() . '.txt');
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
$html = curl_exec($ch);
curl_close($ch);

// Toa logintoken
preg_match('/name="logintoken"\s+value="([^"]+)"/', $html, $matches);
$logintoken = $matches[1] ?? '';

// Fanya login
$ch = curl_init('http://localhost/moodle/login/index.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'username'   => $username,
    'password'   => $password,
    'logintoken' => $logintoken,
    'anchor'     => ''
]));
curl_setopt($ch, CURLOPT_COOKIEJAR, sys_get_temp_dir() . '/moodle_cookie_' . session_id() . '.txt');
curl_setopt($ch, CURLOPT_COOKIEFILE, sys_get_temp_dir() . '/moodle_cookie_' . session_id() . '.txt');
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
curl_setopt($ch, CURLOPT_HEADER, true);
$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Angalia kama login imefaulu
if ($httpcode === 303 || $httpcode === 302) {
    // Login imefaulu — peleka mtumiaji kwenye session ya Moodle
    // Soma cookies na uzipeleke browser
    $cookiefile = sys_get_temp_dir() . '/moodle_cookie_' . session_id() . '.txt';
    if (file_exists($cookiefile)) {
        $cookies = file_get_contents($cookiefile);
        preg_match_all('/^[^\s]+\s+[^\s]+\s+[^\s]+\s+[^\s]+\s+[^\s]+\s+([^\s]+)\s+(.+)$/m', $cookies, $cm);
        foreach ($cm[1] as $i => $name) {
            $name = trim($name);
            $value = trim($cm[2][$i]);
            if ($name && $value && strpos($name, 'Moodle') !== false) {
                setcookie($name, $value, 0, '/', 'localhost', false, true);
            }
        }
        @unlink($cookiefile);
    }

    // Redirect kulingana na role
    $admins   = ['admin'];
    $teachers = ['teacher', 'mwalimu', 'stephan.mbuya'];

    if (in_array($username, $admins)) {
        header('Location: http://localhost/moodle/admin/index.php');
    } elseif (in_array($username, $teachers)) {
        header('Location: http://localhost/moodle/my/courses.php');
    } else {
        header('Location: http://localhost/moodle/my/courses.php');
    }
    exit;
} else {
    // Login imeshindwa
    header('Location: /acs/?error=invalid');
    exit;
}
