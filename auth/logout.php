<?php
// auth/logout.php
require_once '../includes/url_helper.php';
session_start();
session_destroy();
redirect(authUrl('login.php'));
?>
