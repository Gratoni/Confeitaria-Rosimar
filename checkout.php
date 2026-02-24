<?php

declare(strict_types=1);

$title = 'Finalizar pedido - Confeitaria Rosimar';
include 'db/config.php';
include 'header.php';

$carrinho = $_SESSION['carrinho'] ?? [];
$whatsAppLoja = '5511957077345';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($carrinho)) {
    $nome = trim((string) filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_SPECIAL_CHARS));
    $telefoneInput = trim((string) filter_input(INPUT_POST, 'telefone', FILTER_SANITIZE_SPECIAL_CHARS));
    $obs = trim((string) filter_input(INPUT_POST, 'obs', FILTER_SANITIZE_SPECIAL_CHARS));

    $telefoneNumerico = preg_replace('/\D+/', '', $telefoneInput ?? '');

    if (mb_strlen($nome) < 3) {
        $error = 'Informe um nome válido com ao menos 3 caracteres.';
    } elseif (strlen((string) $telefoneNumerico) < 10) {
        $error = 'Informe um telefone/WhatsApp válido com DDD.';
    }

    if ($error === '') {
        date_default_timezone_set('America/Sao_Paulo');
        $dataPedido = date('d/m/Y H:i:s');
        $total = 0.0;

        $mensagem = "*NOVO PEDIDO - CONFEITARIA ROSIMAR*\n";
        $mensagem .= "Data: {$dataPedido}\n";
        $mensagem .= "Cliente: {$nome}\n";
        $mensagem .= "Telefone: {$telefoneInput}\n";
        $mensagem .= 'Observações: ' . ($obs !== '' ? $obs : 'Nenhuma') . "\n\n";
        $mensagem .= "*Itens:*\n";

        foreach ($carrinho as $id => $item) {
            $preco = (float) $item['preco'];
            $qtd = (float) $item['qtd'];
            $subtotal = $preco * $qtd;
            $total += $subtotal;

            $qtdDisplay = $item['unidade'] === 'kg'
                ? number_format($qtd, 1, ',', '.') . 'kg'
                : number_format($qtd, 0, ',', '.') . 'x';

            $mensagem .= "- {$qtdDisplay} {$item['nome']} (R$ " . number_format($subtotal, 2, ',', '.') . ")\n";
        }

        $mensagem .= "\n*Total:* R$ " . number_format($total, 2, ',', '.');

        $stmtPedido = $conn->prepare('INSERT INTO pedidos (nome_cliente, telefone, observacoes) VALUES (?, ?, ?)');
        $stmtPedido->bind_param('sss', $nome, $telefoneInput, $obs);
        $stmtPedido->execute();
        $pedidoId = $stmtPedido->insert_id;
        $stmtPedido->close();

        $stmtItem = $conn->prepare('INSERT INTO pedido_itens (pedido_id, produto_id, quantidade, preco_unitario) VALUES (?, ?, ?, ?)');
        foreach ($carrinho as $id => $item) {
            $produtoId = (int) $id;
            $qtd = (float) $item['qtd'];
            $preco = (float) $item['preco'];
            $stmtItem->bind_param('iidd', $pedidoId, $produtoId, $qtd, $preco);
            $stmtItem->execute();
        }
        $stmtItem->close();

        $_SESSION['carrinho'] = [];
        header('Location: https://wa.me/' . $whatsAppLoja . '?text=' . urlencode($mensagem));
        exit;
    }
}
?>

<section class="wrap checkout">
  <h1>Finalizar pedido</h1>

  <?php if (!empty($error)): ?>
    <div class="form-alert"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <?php if (empty($carrinho)): ?>
    <div class="empty-state">
      <p>Seu carrinho está vazio. Adicione bolos para seguir com a encomenda.</p>
      <a href="index.php#cardapio" class="btn">Ver cardápio</a>
    </div>
  <?php else: ?>
    <div class="checkout-grid">
      <form method="post" class="form">
        <h3>Dados para contato</h3>
        <label for="nome">Nome completo</label>
        <input id="nome" type="text" name="nome" required minlength="3" placeholder="Ex.: Maria da Silva" autocomplete="name">

        <label for="telefone">Telefone / WhatsApp</label>
        <input id="telefone" type="tel" name="telefone" required placeholder="(11) 9XXXX-XXXX" autocomplete="tel">

        <label for="obs">Observações</label>
        <textarea id="obs" name="obs" rows="4" placeholder="Data da entrega, decoração e outras informações"></textarea>

        <div class="form-actions">
          <a href="index.php#cardapio" class="btn btn-outline">Voltar</a>
          <button type="submit" class="btn">Enviar pedido</button>
        </div>
      </form>

      <aside class="cart-preview">
        <h3>Resumo do pedido</h3>
        <?php $total = 0.0; ?>
        <?php foreach ($carrinho as $item): ?>
          <?php
            $subtotal = (float)$item['preco'] * (float)$item['qtd'];
            $total += $subtotal;
            $qtdDisplay = $item['unidade'] === 'kg'
                ? number_format((float)$item['qtd'], 1, ',', '.') . 'kg'
                : number_format((float)$item['qtd'], 0, ',', '.') . 'x';
          ?>
          <div class="preview-item">
            <div>
              <strong><?= $qtdDisplay ?></strong> <?= htmlspecialchars($item['nome']) ?>
            </div>
            <span>R$ <?= number_format($subtotal, 2, ',', '.') ?></span>
          </div>
        <?php endforeach; ?>

        <div class="preview-total">
          <strong>Total</strong>
          <strong>R$ <?= number_format($total, 2, ',', '.') ?></strong>
        </div>
      </aside>
    </div>
  <?php endif; ?>
</section>

<?php include 'footer.php'; ?>
