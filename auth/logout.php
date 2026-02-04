<?php
// auth/logout.php
session_start();
session_destroy();
header("Location: /lib_system/library_system/auth/login.php");
exit();
?>
