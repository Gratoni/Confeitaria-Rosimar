<?php

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
$host = "localhost";
$user = "root";
$pass = "";
$db   = "confeitaria_rosimar";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
  $conn = new mysqli($host, $user, $pass);
  $conn->set_charset("utf8mb4");

  // Check if database exists
  $checkDb = $conn->query("SHOW DATABASES LIKE '" . $conn->real_escape_string($db) . "'");
  if ($checkDb->num_rows === 0) {
    $sqlCreate = "CREATE DATABASE `" . $db . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    $conn->query($sqlCreate);
  }

  $conn->select_db($db);
  $conn->set_charset("utf8mb4");

  // Migration: Add 'unidade' column if it doesn't exist
  $checkCol = $conn->query("SHOW COLUMNS FROM produtos LIKE 'unidade'");
  if ($checkCol->num_rows === 0) {
      $conn->query("ALTER TABLE produtos ADD COLUMN unidade VARCHAR(10) DEFAULT 'un'");
      // Update existing records based on price logic (Migration logic)
      $conn->query("UPDATE produtos SET unidade = 'kg' WHERE preco >= 65");
  }

  // Migration: Change 'quantidade' in 'pedido_itens' to DECIMAL if it is INT
  $checkColQtd = $conn->query("SHOW COLUMNS FROM pedido_itens LIKE 'quantidade'");
  $row = $checkColQtd->fetch_assoc();
  if (strpos(strtolower($row['Type']), 'int') !== false) {
       $conn->query("ALTER TABLE pedido_itens MODIFY COLUMN quantidade DECIMAL(10,3) NOT NULL");
  }

  // Auto-initialize tables if 'produtos' table doesn't exist
  $checkTable = $conn->query("SHOW TABLES LIKE 'produtos'");
  if ($checkTable->num_rows === 0) {
      $sqlFile = __DIR__ . '/database.sql';
      if (file_exists($sqlFile)) {
          $sqlContent = file_get_contents($sqlFile);
          if ($conn->multi_query($sqlContent)) {
              do {
                  if ($result = $conn->store_result()) {
                      $result->free();
                  }
              } while ($conn->more_results() && $conn->next_result());
          }
      }
  }

} catch (Exception $e) {
  http_response_code(500);
  echo "<h2>Erro ao conectar ao banco de dados</h2>";
  echo "<p>Verifique as configurações em <code>config.php</code> e se o MySQL está ativo.</p>";
  echo "<pre>" . $e->getMessage() . "</pre>";
  exit;
}
