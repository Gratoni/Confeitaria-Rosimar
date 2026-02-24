<?php

declare(strict_types=1);

include 'db/config.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['carrinho']) || !is_array($_SESSION['carrinho'])) {
    $_SESSION['carrinho'] = [];
}

$acao = $_GET['acao'] ?? $_POST['acao'] ?? '';

function json_response(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
    exit;
}

function normalize_quantity(float $quantidade, string $unidade): float
{
    if ($unidade === 'kg') {
        $quantidade = max(0.5, min(10, $quantidade));
        return round($quantidade * 2) / 2;
    }

    return (float) max(1, min(50, (int) round($quantidade)));
}

switch ($acao) {
    case 'adicionar':
        $id = (int) ($_POST['id'] ?? 0);
        $quantidadeRaw = (float) ($_POST['qtd'] ?? 1);

        if ($id <= 0) {
            json_response(['ok' => false, 'msg' => 'Produto inválido.'], 422);
        }

        $stmt = $conn->prepare('SELECT id, nome, preco, unidade FROM produtos WHERE id = ? LIMIT 1');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $produto = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$produto) {
            json_response(['ok' => false, 'msg' => 'Produto não encontrado.'], 404);
        }

        $quantidade = normalize_quantity($quantidadeRaw, $produto['unidade']);

        if (!isset($_SESSION['carrinho'][$id])) {
            $_SESSION['carrinho'][$id] = [
                'nome' => $produto['nome'],
                'preco' => (float) $produto['preco'],
                'qtd' => 0,
                'unidade' => $produto['unidade']
            ];
        }

        $_SESSION['carrinho'][$id]['qtd'] += $quantidade;

        json_response(['ok' => true, 'msg' => 'Produto adicionado ao carrinho.', 'carrinho' => $_SESSION['carrinho']]);

    case 'remover':
        $id = (int) ($_POST['id'] ?? 0);

        if (isset($_SESSION['carrinho'][$id])) {
            unset($_SESSION['carrinho'][$id]);
            json_response(['ok' => true, 'msg' => 'Item removido.']);
        }

        json_response(['ok' => false, 'msg' => 'Item não encontrado.'], 404);

    case 'listar':
        json_response($_SESSION['carrinho']);

    case 'limpar':
        $_SESSION['carrinho'] = [];
        json_response(['ok' => true, 'msg' => 'Carrinho limpo.']);

    default:
        json_response(['ok' => false, 'msg' => 'Ação inválida.'], 400);
}
