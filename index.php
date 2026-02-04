<?php
// index.php
require_once 'includes/auth_middleware.php';

if (isLoggedIn()) {
    if (hasRole('admin')) {
        header("Location: /lib_system/library_system/admin/dashboard.php");
    } else {
        header("Location: /lib_system/library_system/member/index.php");
    }
} else {
    header("Location: /lib_system/library_system/auth/login.php");
}
exit();
?>
