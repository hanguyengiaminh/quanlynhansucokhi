<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once "db.php";
function check_login() {
    if(!isset($_SESSION['user_id'])) {
        header("Location: index.php");
        exit;
    }
}

function check_role($roles = []) {
    if(!in_array($_SESSION['role'], $roles)) {
        echo "<div class='alert alert-danger'>Bạn không có quyền truy cập!</div>";
        exit;
    }
}
?>
