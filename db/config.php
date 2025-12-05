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
