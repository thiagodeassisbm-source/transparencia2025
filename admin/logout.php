<?php
// /admin/logout.php
session_start();
require_once '../conexao.php';
require_once 'functions_logs.php';

if (isset($_SESSION['admin_user_id'])) {
    registrar_log($pdo, 'LOGOUT', 'usuarios_admin', "Usuário saiu do sistema.");
}

session_unset();
session_destroy();
header("Location: login.php");
exit;