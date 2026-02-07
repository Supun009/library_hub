<?php
// auth/logout.php
require_once __DIR__ . '/../includes/url_helper.php';
session_start();
session_destroy();
redirect('login');
?>
