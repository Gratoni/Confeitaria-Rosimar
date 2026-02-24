<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'confeitaria_rosimar';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($host, $user, $pass);
    $conn->set_charset('utf8mb4');

    $safeDb = $conn->real_escape_string($db);
    $checkDb = $conn->query("SHOW DATABASES LIKE '{$safeDb}'");

    if ($checkDb->num_rows === 0) {
        $conn->query("CREATE DATABASE `{$db}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    }

    $conn->select_db($db);
    $conn->set_charset('utf8mb4');

    $hasProdutos = $conn->query("SHOW TABLES LIKE 'produtos'");
    if ($hasProdutos->num_rows === 0) {
        $sqlFile = __DIR__ . '/database.sql';
        if (file_exists($sqlFile)) {
            $sqlContent = file_get_contents($sqlFile);
            if ($sqlContent !== false && $conn->multi_query($sqlContent)) {
                do {
                    if ($result = $conn->store_result()) {
                        $result->free();
                    }
                } while ($conn->more_results() && $conn->next_result());
            }
        }
    }

    $checkColQtd = $conn->query("SHOW COLUMNS FROM pedido_itens LIKE 'quantidade'");
    if ($checkColQtd->num_rows > 0) {
        $row = $checkColQtd->fetch_assoc();
        if (isset($row['Type']) && strpos(strtolower((string) $row['Type']), 'int') !== false) {
            $conn->query('ALTER TABLE pedido_itens MODIFY COLUMN quantidade DECIMAL(10,3) NOT NULL');
        }
    }

    $conn->query("UPDATE produtos SET imagem = 'assets/bolos/chocolate.jpg' WHERE LOWER(nome) LIKE '%chocolate%' AND (imagem IS NULL OR imagem = '' OR imagem LIKE 'assets/bolo-%')");
    $conn->query("UPDATE produtos SET imagem = 'assets/bolos/morango.jpg' WHERE LOWER(nome) LIKE '%morango%' AND (imagem IS NULL OR imagem = '' OR imagem LIKE 'assets/bolo-%')");
    $conn->query("UPDATE produtos SET imagem = 'assets/bolos/cenoura.jpg' WHERE LOWER(nome) LIKE '%cenoura%' AND (imagem IS NULL OR imagem = '' OR imagem LIKE 'assets/bolo-%')");
    $conn->query("UPDATE produtos SET imagem = 'assets/bolos/limao.jpg' WHERE LOWER(nome) LIKE '%limão%' OR LOWER(nome) LIKE '%limao%'");

} catch (Throwable $e) {
    http_response_code(500);
    echo '<h2>Erro ao conectar ao banco de dados</h2>';
    echo '<p>Verifique as configurações em <code>db/config.php</code> e se o MySQL está ativo.</p>';
    echo '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
    exit;
}
