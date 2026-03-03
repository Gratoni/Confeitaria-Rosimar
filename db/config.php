<?php

require_once __DIR__ . '/../lib/logger.php';
require_once __DIR__ . '/../lib/security.php';
require_once __DIR__ . '/../lib/privacy.php';

$isHttps = isHttpsRequest();
if (session_status() === PHP_SESSION_NONE) {
  ini_set('session.use_strict_mode', '1');
  ini_set('session.use_only_cookies', '1');
  ini_set('session.cookie_httponly', '1');
  ini_set('session.cookie_secure', $isHttps ? '1' : '0');
  ini_set('session.cookie_samesite', 'Lax');

  session_name('crsid');
  session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax',
  ]);
  session_start();
}

if (empty($_SESSION['session_started_at'])) {
  $_SESSION['session_started_at'] = time();
}
if (empty($_SESSION['session_regenerated_at'])) {
  $_SESSION['session_regenerated_at'] = time();
}
if ((time() - (int)$_SESSION['session_regenerated_at']) > 1800) {
  session_regenerate_id(true);
  $_SESSION['session_regenerated_at'] = time();
}

if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

date_default_timezone_set('America/Sao_Paulo');
applySecurityHeaders();

$host = getenv('DB_HOST') ?: 'localhost';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';
$db = getenv('DB_NAME') ?: 'confeitaria_rosimar';
if (preg_match('/^[A-Za-z0-9_]+$/', $db) !== 1) {
  http_response_code(500);
  logError('Invalid DB_NAME value');
  echo '<h2>Configuracao invalida do banco de dados.</h2>';
  exit;
}

$appEnv = strtolower((string)(getenv('APP_ENV') ?: 'production'));
$isDebug = in_array($appEnv, ['local', 'development', 'dev', 'test'], true);

// Optional local analytics config file (do not commit real IDs).
$analyticsConfig = __DIR__ . '/../config/analytics.php';
if (is_file($analyticsConfig)) {
  require_once $analyticsConfig;
}

if (!defined('GTM_CONTAINER_ID')) {
  $gtmId = getenv('GTM_CONTAINER_ID');
  define('GTM_CONTAINER_ID', is_string($gtmId) ? trim($gtmId) : '');
}

if (!defined('GA4_MEASUREMENT_ID')) {
  $ga4Id = getenv('GA4_MEASUREMENT_ID');
  define('GA4_MEASUREMENT_ID', is_string($ga4Id) ? trim($ga4Id) : '');
}

if (!defined('STORE_WHATSAPP_NUMBER')) {
  $storePhone = getenv('STORE_WHATSAPP_NUMBER');
  define('STORE_WHATSAPP_NUMBER', is_string($storePhone) && trim($storePhone) !== '' ? trim($storePhone) : '5511957077345');
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function tableExists(mysqli $conn, string $table): bool
{
  $tableEscaped = $conn->real_escape_string($table);
  $result = $conn->query("SHOW TABLES LIKE '{$tableEscaped}'");
  return $result && $result->num_rows > 0;
}

function getCsrfToken(): string
{
  return $_SESSION['csrf_token'] ?? '';
}

function validateCsrfToken(?string $token): bool
{
  $sessionToken = $_SESSION['csrf_token'] ?? '';
  if ($token === null || $sessionToken === '') {
    return false;
  }
  return hash_equals($sessionToken, $token);
}

function ensurePedidosSecuritySchema(mysqli $conn): void
{
  if (!tableExists($conn, 'pedidos')) {
    return;
  }

  $columns = [
    'nome_cliente' => 'NOT NULL',
    'telefone' => 'NOT NULL',
    'observacoes' => 'NULL',
  ];
  foreach ($columns as $column => $nullability) {
    $columnCheck = $conn->query("SHOW COLUMNS FROM pedidos LIKE '{$column}'");
    $columnInfo = $columnCheck ? $columnCheck->fetch_assoc() : null;
    if (!$columnInfo) {
      continue;
    }

    $currentType = strtolower((string)($columnInfo['Type'] ?? ''));
    if (!str_contains($currentType, 'text')) {
      $conn->query("ALTER TABLE pedidos MODIFY COLUMN {$column} TEXT {$nullability}");
    }
  }

  $consentCheck = $conn->query("SHOW COLUMNS FROM pedidos LIKE 'consentimento_lgpd'");
  if ($consentCheck && $consentCheck->num_rows === 0) {
    $conn->query("ALTER TABLE pedidos ADD COLUMN consentimento_lgpd TINYINT(1) NOT NULL DEFAULT 0");
  }
}

try {
  $conn = new mysqli($host, $user, $pass);
  $conn->set_charset('utf8mb4');

  $escapedDbName = $conn->real_escape_string($db);
  $checkDb = $conn->query("SHOW DATABASES LIKE '{$escapedDbName}'");
  if ($checkDb->num_rows === 0) {
    $conn->query("CREATE DATABASE `{$db}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
  }

  $conn->select_db($db);
  $conn->set_charset('utf8mb4');

  if (!tableExists($conn, 'produtos')) {
    $sqlFile = __DIR__ . '/database.sql';
    if (is_file($sqlFile)) {
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

  if (tableExists($conn, 'produtos')) {
    $checkCol = $conn->query("SHOW COLUMNS FROM produtos LIKE 'unidade'");
    if ($checkCol->num_rows === 0) {
      $conn->query("ALTER TABLE produtos ADD COLUMN unidade VARCHAR(10) DEFAULT 'un'");
      $conn->query("UPDATE produtos SET unidade = 'kg' WHERE preco >= 65");
    }
  }

  if (tableExists($conn, 'pedido_itens')) {
    $checkColQtd = $conn->query("SHOW COLUMNS FROM pedido_itens LIKE 'quantidade'");
    $row = $checkColQtd->fetch_assoc();
    if ($row && strpos(strtolower((string)$row['Type']), 'int') !== false) {
      $conn->query("ALTER TABLE pedido_itens MODIFY COLUMN quantidade DECIMAL(10,3) NOT NULL");
    }
  }

  ensurePedidosSecuritySchema($conn);
  if (!isDataProtectionEnabled() && empty($_SESSION['missing_data_key_logged'])) {
    $_SESSION['missing_data_key_logged'] = true;
    logError('APP_DATA_KEY ausente/invalida. Criptografia de dados pessoais indisponivel.');
  }

  require_once __DIR__ . '/../lib/catalog.php';
  ensureDefaultCatalogProducts($conn);
} catch (Throwable $e) {
  http_response_code(500);
  logError('DB connection error', ['message' => $e->getMessage()]);

  if ($isDebug) {
    echo '<h2>Erro ao conectar ao banco de dados</h2>';
    echo '<p>Verifique as configuracoes em <code>db/config.php</code> e se o MySQL esta ativo.</p>';
    echo '<pre>' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</pre>';
  } else {
    echo '<h2>Nao foi possivel carregar a loja agora.</h2>';
    echo '<p>Tente novamente em alguns minutos.</p>';
  }
  exit;
}
