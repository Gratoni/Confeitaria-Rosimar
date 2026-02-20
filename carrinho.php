<?php

include 'db/config.php';
require_once __DIR__ . '/lib/cart.php';
require_once __DIR__ . '/lib/logger.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['carrinho'])) {
    $_SESSION['carrinho'] = [];
}

$acao = $_GET['acao'] ?? $_POST['acao'] ?? '';

$response = ['ok' => false, 'msg' => 'Ação inválida'];

function getCsrfFromRequest(): ?string
{
    $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? null;
    if ($token) {
        return $token;
    }
    if (isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
        return $_SERVER['HTTP_X_CSRF_TOKEN'];
    }
    return null;
}

try {
    switch ($acao) {
        case 'adicionar':
            if (!validateCsrfToken(getCsrfFromRequest())) {
                $response = ['ok' => false, 'msg' => 'Token CSRF inválido'];
                logError('CSRF invalid on carrinho adicionar', ['ip' => $_SERVER['REMOTE_ADDR'] ?? '']);
                break;
            }
            $id = (int)($_POST['id'] ?? 0);
            $qtdRaw = $_POST['qtd'] ?? 1;
            $qtdRaw = is_string($qtdRaw) ? str_replace(',', '.', $qtdRaw) : $qtdRaw;
            if (!is_numeric($qtdRaw)) {
                $response = ['ok' => false, 'msg' => 'Quantidade inválida'];
                break;
            }
            $quantidade = (float)$qtdRaw;

            if ($id <= 0) {
                $response = ['ok' => false, 'msg' => 'ID inválido'];
                break;
            }

            $stmt = $conn->prepare("SELECT id, nome, preco, unidade FROM produtos WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $produto = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$produto) {
                $response = ['ok' => false, 'msg' => 'Produto não encontrado'];
                break;
            }

            $nome = $produto['nome'];
            $preco = (float)$produto['preco'];
            $unidade = $produto['unidade'] ?? 'un';

            $quantidade = normalizeQuantity($quantidade, $unidade);

            if (!isset($_SESSION['carrinho'][$id])) {
                $_SESSION['carrinho'][$id] = [
                    'nome' => $nome,
                    'preco' => $preco,
                    'qtd' => 0,
                    'unidade' => $unidade
                ];
            }

            $_SESSION['carrinho'][$id]['qtd'] += $quantidade;
            $response = ['ok' => true, 'msg' => 'Adicionado com sucesso', 'carrinho' => $_SESSION['carrinho']];
            break;

        case 'atualizar':
            if (!validateCsrfToken(getCsrfFromRequest())) {
                $response = ['ok' => false, 'msg' => 'Token CSRF inválido'];
                logError('CSRF invalid on carrinho atualizar', ['ip' => $_SERVER['REMOTE_ADDR'] ?? '']);
                break;
            }
            $id = (int)($_POST['id'] ?? 0);
            $qtdRaw = $_POST['qtd'] ?? 0;
            $qtdRaw = is_string($qtdRaw) ? str_replace(',', '.', $qtdRaw) : $qtdRaw;
            if (!is_numeric($qtdRaw)) {
                $response = ['ok' => false, 'msg' => 'Quantidade inválida'];
                break;
            }
            $quantidade = (float)$qtdRaw;

            if ($id <= 0) {
                $response = ['ok' => false, 'msg' => 'ID inválido'];
                break;
            }

            $stmt = $conn->prepare("SELECT id, nome, preco, unidade FROM produtos WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $produto = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$produto) {
                $response = ['ok' => false, 'msg' => 'Produto não encontrado'];
                break;
            }

            $unidade = $produto['unidade'] ?? 'un';
            $quantidade = normalizeQuantity($quantidade, $unidade);

            if ($quantidade <= 0) {
                unset($_SESSION['carrinho'][$id]);
                $response = ['ok' => true, 'msg' => 'Item removido'];
                break;
            }

            if (!isset($_SESSION['carrinho'][$id])) {
                $_SESSION['carrinho'][$id] = [
                    'nome' => $produto['nome'],
                    'preco' => (float)$produto['preco'],
                    'qtd' => 0,
                    'unidade' => $unidade
                ];
            }

            $_SESSION['carrinho'][$id]['qtd'] = $quantidade;
            $response = ['ok' => true, 'msg' => 'Item atualizado', 'carrinho' => $_SESSION['carrinho']];
            break;

        case 'remover':
            if (!validateCsrfToken(getCsrfFromRequest())) {
                $response = ['ok' => false, 'msg' => 'Token CSRF inválido'];
                logError('CSRF invalid on carrinho remover', ['ip' => $_SERVER['REMOTE_ADDR'] ?? '']);
                break;
            }
            $id = (int)($_POST['id'] ?? 0);
            if (isset($_SESSION['carrinho'][$id])) {
                unset($_SESSION['carrinho'][$id]);
                $response = ['ok' => true, 'msg' => 'Item removido'];
            } else {
                $response = ['ok' => false, 'msg' => 'Item não encontrado'];
            }
            break;

        case 'listar':
            $response = $_SESSION['carrinho'];
            break;

        case 'limpar':
            if (!validateCsrfToken(getCsrfFromRequest())) {
                $response = ['ok' => false, 'msg' => 'Token CSRF inválido'];
                logError('CSRF invalid on carrinho limpar', ['ip' => $_SERVER['REMOTE_ADDR'] ?? '']);
                break;
            }
            $_SESSION['carrinho'] = [];
            $response = ['ok' => true, 'msg' => 'Carrinho limpo'];
            break;
    }
} catch (Exception $e) {
    logError('Carrinho error', ['message' => $e->getMessage()]);
    http_response_code(500);
    $response = ['ok' => false, 'msg' => 'Erro interno no carrinho.'];
}

// Ensure clean output
if (ob_get_length()) ob_clean();

$json = json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);

if ($json === false) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => 'Erro interno na codificação JSON: ' . json_last_error_msg()]);
} else {
    echo $json;
}
exit;
