<?php

include 'db/config.php';
require_once __DIR__ . '/lib/cart.php';
require_once __DIR__ . '/lib/catalog.php';
require_once __DIR__ . '/lib/logger.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('X-Robots-Tag: noindex, nofollow');

if (!isset($_SESSION['carrinho']) || !is_array($_SESSION['carrinho'])) {
  $_SESSION['carrinho'] = [];
}

$acao = $_GET['acao'] ?? $_POST['acao'] ?? '';
$response = ['ok' => false, 'msg' => 'Acao invalida'];
$requiresPost = ['adicionar', 'atualizar', 'remover', 'limpar'];

function getCsrfFromRequest(): ?string
{
  $token = $_POST['csrf_token'] ?? null;
  if ($token) {
    return $token;
  }

  if (isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
    return $_SERVER['HTTP_X_CSRF_TOKEN'];
  }

  return null;
}

function parseQuantityValue($rawValue): ?float
{
  if (is_string($rawValue)) {
    $rawValue = str_replace(',', '.', $rawValue);
  }

  if (!is_numeric($rawValue)) {
    return null;
  }

  return (float)$rawValue;
}

try {
  if (in_array($acao, $requiresPost, true) && ($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    $response = ['ok' => false, 'msg' => 'Metodo nao permitido'];
  } else {
    switch ($acao) {
      case 'adicionar':
        if (!validateCsrfToken(getCsrfFromRequest())) {
          http_response_code(400);
          $response = ['ok' => false, 'msg' => 'Token CSRF invalido'];
          logError('CSRF invalid on carrinho adicionar', ['ip' => $_SERVER['REMOTE_ADDR'] ?? '']);
          break;
        }

        $id = (int)($_POST['id'] ?? 0);
        $quantityRaw = parseQuantityValue($_POST['qtd'] ?? 1);
        if ($id <= 0 || $quantityRaw === null || $quantityRaw <= 0) {
          http_response_code(400);
          $response = ['ok' => false, 'msg' => 'Dados de item invalidos'];
          break;
        }

        $produto = getCatalogProductById($conn, $id);
        if (!$produto) {
          http_response_code(404);
          $response = ['ok' => false, 'msg' => 'Produto nao encontrado'];
          break;
        }

        $unidade = normalizeUnit((string)($produto['unidade'] ?? 'un'));
        $quantidade = normalizeQuantity($quantityRaw, $unidade);

        if (!isset($_SESSION['carrinho'][$id]) && count($_SESSION['carrinho']) >= CART_MAX_DISTINCT_ITEMS) {
          http_response_code(400);
          $response = ['ok' => false, 'msg' => 'Carrinho cheio. Remova itens para continuar.'];
          break;
        }

        if (!isset($_SESSION['carrinho'][$id])) {
          $_SESSION['carrinho'][$id] = [
            'nome' => (string)$produto['nome'],
            'preco' => (float)$produto['preco'],
            'qtd' => 0.0,
            'unidade' => $unidade,
            'categoria' => (string)($produto['categoria'] ?? 'Bolos'),
          ];
        }

        $nextQty = (float)$_SESSION['carrinho'][$id]['qtd'] + $quantidade;
        $maxQty = getMaxQuantityForUnit($unidade);
        if ($nextQty > $maxQty) {
          http_response_code(400);
          $response = [
            'ok' => false,
            'msg' => $unidade === 'kg'
              ? 'Limite por item: 10kg.'
              : 'Limite por item: 20 unidades.',
          ];
          break;
        }

        $_SESSION['carrinho'][$id]['qtd'] = normalizeQuantity($nextQty, $unidade);
        $response = ['ok' => true, 'msg' => 'Item adicionado ao carrinho', 'carrinho' => $_SESSION['carrinho']];
        break;

      case 'atualizar':
        if (!validateCsrfToken(getCsrfFromRequest())) {
          http_response_code(400);
          $response = ['ok' => false, 'msg' => 'Token CSRF invalido'];
          logError('CSRF invalid on carrinho atualizar', ['ip' => $_SERVER['REMOTE_ADDR'] ?? '']);
          break;
        }

        $id = (int)($_POST['id'] ?? 0);
        $quantityRaw = parseQuantityValue($_POST['qtd'] ?? 0);
        if ($id <= 0 || $quantityRaw === null) {
          http_response_code(400);
          $response = ['ok' => false, 'msg' => 'Dados de item invalidos'];
          break;
        }

        $produto = getCatalogProductById($conn, $id);
        if (!$produto) {
          http_response_code(404);
          $response = ['ok' => false, 'msg' => 'Produto nao encontrado'];
          break;
        }

        $unidade = normalizeUnit((string)($produto['unidade'] ?? 'un'));
        if ($quantityRaw <= 0) {
          unset($_SESSION['carrinho'][$id]);
          $response = ['ok' => true, 'msg' => 'Item removido'];
          break;
        }

        $quantidade = normalizeQuantity($quantityRaw, $unidade);
        if ($quantidade > getMaxQuantityForUnit($unidade)) {
          http_response_code(400);
          $response = ['ok' => false, 'msg' => 'Quantidade acima do permitido para esse item.'];
          break;
        }

        $_SESSION['carrinho'][$id] = [
          'nome' => (string)$produto['nome'],
          'preco' => (float)$produto['preco'],
          'qtd' => $quantidade,
          'unidade' => $unidade,
          'categoria' => (string)($produto['categoria'] ?? 'Bolos'),
        ];

        $response = ['ok' => true, 'msg' => 'Item atualizado', 'carrinho' => $_SESSION['carrinho']];
        break;

      case 'remover':
        if (!validateCsrfToken(getCsrfFromRequest())) {
          http_response_code(400);
          $response = ['ok' => false, 'msg' => 'Token CSRF invalido'];
          logError('CSRF invalid on carrinho remover', ['ip' => $_SERVER['REMOTE_ADDR'] ?? '']);
          break;
        }

        $id = (int)($_POST['id'] ?? 0);
        if (isset($_SESSION['carrinho'][$id])) {
          unset($_SESSION['carrinho'][$id]);
          $response = ['ok' => true, 'msg' => 'Item removido'];
        } else {
          http_response_code(404);
          $response = ['ok' => false, 'msg' => 'Item nao encontrado no carrinho'];
        }
        break;

      case 'listar':
        $response = $_SESSION['carrinho'];
        break;

      case 'limpar':
        if (!validateCsrfToken(getCsrfFromRequest())) {
          http_response_code(400);
          $response = ['ok' => false, 'msg' => 'Token CSRF invalido'];
          logError('CSRF invalid on carrinho limpar', ['ip' => $_SERVER['REMOTE_ADDR'] ?? '']);
          break;
        }
        $_SESSION['carrinho'] = [];
        $response = ['ok' => true, 'msg' => 'Carrinho limpo'];
        break;

      default:
        http_response_code(400);
        $response = ['ok' => false, 'msg' => 'Acao invalida'];
        break;
    }
  }
} catch (Throwable $e) {
  logError('Carrinho error', ['message' => $e->getMessage()]);
  http_response_code(500);
  $response = ['ok' => false, 'msg' => 'Erro interno no carrinho'];
}

if (ob_get_length()) {
  ob_clean();
}

$json = json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
if ($json === false) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'msg' => 'Erro interno de serializacao']);
  exit;
}

echo $json;
exit;
