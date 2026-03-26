<?php
// /conexao.sample.php (Copie como conexao.php e mude as senhas)
ini_set('display_errors', 1);
error_reporting(E_ALL);

$host    = 'localhost';
$dbname  = 'nome_do_banco';
$user    = 'usuario_do_banco';
$pass    = 'senha_do_banco';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
     die("Erro crítico de conexão com o banco de dados: " . $e->getMessage());
}

$script_path = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
if($script_path == "/") $script_path = "";
$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]" . $script_path . "/";
?>
