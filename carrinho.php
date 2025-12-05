<?php

include 'db/config.php';

if (!isset($_SESSION['carrinho'])) {
    $_SESSION['carrinho'] = [];
}

$acao = $_GET['acao'] ?? $_POST['acao'] ?? '';

switch ($acao) {
    case 'adicionar':
        $id = (int)$_POST['id'];
        $nome = $_POST['nome'];
        $preco = (float)$_POST['preco'];
        // Allow float for quantity (weight)
        $quantidade = (float)($_POST['qtd'] ?? 1);
        $unidade = $_POST['unidade'] ?? 'un';

        if (!isset($_SESSION['carrinho'][$id])) {
            $_SESSION['carrinho'][$id] = [
                'nome' => $nome,
                'preco' => $preco,
                'qtd' => 0,
                'unidade' => $unidade
            ];
        }
        $_SESSION['carrinho'][$id]['qtd'] += $quantidade;

        echo json_encode(['ok' => true, 'msg' => 'Adicionado com sucesso', 'carrinho' => $_SESSION['carrinho']]);
        break;

    case 'remover':
        $id = (int)$_POST['id'];
        unset($_SESSION['carrinho'][$id]);
        echo json_encode(['ok' => true, 'msg' => 'Item removido']);
        break;

    case 'listar':
        echo json_encode($_SESSION['carrinho']);
        break;

    case 'limpar':
        $_SESSION['carrinho'] = [];
        echo json_encode(['ok' => true, 'msg' => 'Carrinho limpo']);
        break;

    default:
        echo json_encode(['ok' => false, 'msg' => 'Ação inválida']);
}
