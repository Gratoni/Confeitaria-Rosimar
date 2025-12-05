<?php

include 'db/config.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['carrinho'])) {
    $_SESSION['carrinho'] = [];
}

$acao = $_GET['acao'] ?? $_POST['acao'] ?? '';

$response = ['ok' => false, 'msg' => 'Ação inválida'];

switch ($acao) {
    case 'adicionar':
        $id = (int)($_POST['id'] ?? 0);
        $nome = $_POST['nome'] ?? 'Produto';
        $preco = (float)($_POST['preco'] ?? 0);
        // Allow float for quantity (weight)
        $quantidade = (float)($_POST['qtd'] ?? 1);
        $unidade = $_POST['unidade'] ?? 'un';

        if ($id > 0) {
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
        } else {
             $response = ['ok' => false, 'msg' => 'ID inválido'];
        }
        break;

    case 'remover':
        $id = (int)($_POST['id'] ?? 0);
        if (isset($_SESSION['carrinho'][$id])) {
            unset($_SESSION['carrinho'][$id]);
            $response = ['ok' => true, 'msg' => 'Item removido'];
        } else {
            $response = ['ok' => false, 'msg' => 'Item não encontrado'];
        }
        break;

    case 'listar':
        $response = $_SESSION['carrinho']; // Directly return the array (which JS expects as object map)
        break;

    case 'limpar':
        $_SESSION['carrinho'] = [];
        $response = ['ok' => true, 'msg' => 'Carrinho limpo'];
        break;
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
