<?php
// checkout.php
$title = "Checkout - Confeitaria Rosimar";
include 'db/config.php';
include 'header.php';

// O PHP lê diretamente da Sessão
$carrinho = $_SESSION['carrinho'] ?? [];
$SEU_TELEFONE_WHATSAPP = "5511957077345"; // 📞 

// Se POST: monta a mensagem do WhatsApp
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $carrinho) {

  date_default_timezone_set('America/Sao_Paulo');

  $nome = $_POST['nome'];
  $telefone = $_POST['telefone'];
  $obs = $_POST['obs'];
  $data_pedido = date('d/m/Y H:i:s');
  $total = 0;

  // --- 1. Geração da Mensagem ---
  $mensagem = "*NOVO PEDIDO ROSIMAR* (Feito em: $data_pedido)\n";
  $mensagem .= "---------------------------------------------------\n";
  $mensagem .= "🙋‍♂️ *Cliente:* " . $nome . "\n";
  $mensagem .= "📞 *Contato:* " . $telefone . "\n";
  $mensagem .= "📝 *Obs:* " . ($obs ?: "Nenhuma.") . "\n\n";
  $mensagem .= "🛒 *ITENS DO PEDIDO:*\n";

  foreach ($carrinho as $id => $item) {
    $subtotal = $item['preco'] * $item['qtd'];
    $total += $subtotal;
    $mensagem .= " • {$item['qtd']}x {$item['nome']} (R$ " . number_format($item['preco'], 2, ',', '.') . ")\n";
  }

  $mensagem .= "---------------------------------------------------\n";
  $mensagem .= "💵 *TOTAL GERAL:* R$ " . number_format($total, 2, ',', '.') . "\n\n";
  $mensagem .= "Aguardamos sua confirmação para prosseguir!";

  // --- 2. Redirecionamento para WhatsApp ---

  // Codifica a mensagem para URL
  $mensagem_url = urlencode($mensagem);

  // Cria o link do WhatsApp
  $whatsapp_url = "https://wa.me/{$SEU_TELEFONE_WHATSAPP}?text={$mensagem_url}";

  // O código de banco de dados foi comentado, mas mantido caso queira reativar

  $stmt = $conn->prepare("INSERT INTO pedidos (nome_cliente, telefone, observacoes) VALUES (?, ?, ?)");
  $stmt->bind_param("sss", $nome, $telefone, $obs);
  $stmt->execute();
  $pedido_id = $stmt->insert_id;

  $stmtItem = $conn->prepare("INSERT INTO pedido_itens (pedido_id, produto_id, quantidade, preco_unitario) VALUES (?, ?, ?, ?)");
  foreach ($carrinho as $id => $item) {
    $stmtItem->bind_param("iiid", $pedido_id, $id, $item['qtd'], $item['preco']);
    $stmtItem->execute();
  }


  $_SESSION['carrinho'] = []; // Limpa o carrinho após gerar o pedido

  // Redireciona o usuário para o link do WhatsApp
  header("Location: " . $whatsapp_url);
  exit;
}

// Verifica se o carrinho está vazio para exibir uma mensagem
if (empty($carrinho)) {
  $error = "Seu carrinho está vazio. Adicione bolos para finalizar o pedido.";
}
?>

<section class="wrap checkout">
  <h1>Finalizar pedido</h1>
  <?php if (!empty($error)): ?>
    <div class="alert"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form id="form-order" method="post" class="form">
    <label>Nome completo
      <input type="text" name="nome" required>
    </label>
    <label>Telefone / WhatsApp
      <input type="tel" name="telefone" required placeholder="(11) 9xxxx-xxxx">
    </label>
    <label>Observações (data, decoração)
      <textarea name="obs"></textarea>
    </label>

    <div class="cart-preview">
      <h3>Itens no carrinho (<?= count($carrinho) ?>)</h3>
      <div id="cart-preview-items">
        <?php
        $total = 0;
        if (!empty($carrinho)):
          foreach ($carrinho as $id => $item):
            $subtotal = $item['preco'] * $item['qtd'];
            $total += $subtotal;
        ?>
            <div class="preview-item">
              <?= htmlspecialchars($item['nome']) ?> x<?= $item['qtd'] ?>
              (R$ <?= number_format($item['preco'], 2, ',', '.') ?> cada)
              <strong style="float:right;">R$ <?= number_format($subtotal, 2, ',', '.') ?></strong>
            </div>
        <?php
          endforeach;
        endif;
        ?>
        <div class="preview-item">
          <strong>TOTAL DO PEDIDO:</strong>
          <strong style="float:right;">R$ <?= number_format($total, 2, ',', '.') ?></strong>
        </div>
      </div>
    </div>

    <div class="form-actions">
      <a href="index.php" class="btn btn-outline">Continuar comprando</a>
      <button type="submit" class="btn" <?= empty($carrinho) ? 'disabled' : '' ?>>Enviar pedido</button>
    </div>
  </form>
</section>

<?php include 'footer.php'; ?>