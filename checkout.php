<?php
// checkout.php
$title = "Checkout - Confeitaria Rosimar";
include 'db/config.php';
include 'header.php';

$carrinho = $_SESSION['carrinho'] ?? [];
$SEU_TELEFONE_WHATSAPP = "5511957077345";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $carrinho) {

  date_default_timezone_set('America/Sao_Paulo');

  $nome = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
  $telefone = filter_input(INPUT_POST, 'telefone', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
  $obs = filter_input(INPUT_POST, 'obs', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
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

  // --- 2. Salvar no Banco (Opcional, mas recomendado) ---
  // Verifica tabelas antes de tentar inserir
  $checkTable = $conn->query("SHOW TABLES LIKE 'pedidos'");
  if ($checkTable->num_rows > 0) {
      $stmt = $conn->prepare("INSERT INTO pedidos (nome_cliente, telefone, observacoes) VALUES (?, ?, ?)");
      if ($stmt) {
          $stmt->bind_param("sss", $nome, $telefone, $obs);
          $stmt->execute();
          $pedido_id = $stmt->insert_id;
          $stmt->close();

          $stmtItem = $conn->prepare("INSERT INTO pedido_itens (pedido_id, produto_id, quantidade, preco_unitario) VALUES (?, ?, ?, ?)");
          if ($stmtItem) {
              foreach ($carrinho as $id => $item) {
                $stmtItem->bind_param("iiid", $pedido_id, $id, $item['qtd'], $item['preco']);
                $stmtItem->execute();
              }
              $stmtItem->close();
          }
      }
  }

  $_SESSION['carrinho'] = [];

  $mensagem_url = urlencode($mensagem);
  $whatsapp_url = "https://wa.me/{$SEU_TELEFONE_WHATSAPP}?text={$mensagem_url}";

  header("Location: " . $whatsapp_url);
  exit;
}

if (empty($carrinho)) {
  $error = "Seu carrinho está vazio. Adicione bolos para finalizar o pedido.";
}
?>

<section class="wrap checkout">
  <h1>Finalizar Pedido</h1>

  <?php if (!empty($error)): ?>
    <div class="empty-state">
        <p><?= htmlspecialchars($error) ?></p>
        <a href="index.php" class="btn">Voltar ao Cardápio</a>
    </div>
  <?php else: ?>

  <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 40px; align-items: start;">
      <form id="form-order" method="post" class="form">
        <h3>Seus dados</h3>
        <label>Nome completo
          <input type="text" name="nome" required placeholder="Ex: Maria Silva">
        </label>
        <label>Telefone / WhatsApp
          <input type="tel" name="telefone" required placeholder="(11) 9xxxx-xxxx">
        </label>
        <label>Observações (data da festa, decoração, etc)
          <textarea name="obs" rows="4" placeholder="Escreva aqui detalhes importantes..."></textarea>
        </label>

        <div class="form-actions">
          <a href="index.php" class="btn btn-outline" style="border:none; color: #666;">Cancelar</a>
          <button type="submit" class="btn" style="width: 100%;">Enviar Pedido pelo WhatsApp</button>
        </div>
      </form>

      <div class="cart-preview">
        <h3>Resumo do Pedido</h3>
        <div id="cart-preview-items">
          <?php
          $total = 0;
          foreach ($carrinho as $id => $item):
            $subtotal = $item['preco'] * $item['qtd'];
            $total += $subtotal;
          ?>
              <div class="preview-item">
                <div style="display:flex; justify-content:space-between;">
                    <span><strong><?= $item['qtd'] ?>x</strong> <?= htmlspecialchars($item['nome']) ?></span>
                    <span>R$ <?= number_format($subtotal, 2, ',', '.') ?></span>
                </div>
                <small style="color:#888;">Unit: R$ <?= number_format($item['preco'], 2, ',', '.') ?></small>
              </div>
          <?php endforeach; ?>

          <div class="preview-item" style="border-top: 2px solid #ddd; border-bottom: none; margin-top: 10px; padding-top: 15px;">
            <div style="display:flex; justify-content:space-between; font-size: 1.2em;">
                <strong>TOTAL:</strong>
                <strong style="color: var(--accent-dark);">R$ <?= number_format($total, 2, ',', '.') ?></strong>
            </div>
          </div>
        </div>
        <p style="font-size: 13px; color: #666; margin-top: 20px; line-height: 1.4;">
            Ao clicar em "Enviar Pedido", você será redirecionado para o WhatsApp para confirmar os detalhes com nossa equipe.
        </p>
      </div>
  </div>

  <?php endif; ?>
</section>

<style>
    @media (max-width: 768px) {
        .checkout > div {
            grid-template-columns: 1fr !important;
        }
        .form, .cart-preview {
            width: 100%;
        }
        .cart-preview {
            order: -1;
        }
    }
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.05);
    }
    .empty-state p {
        font-size: 18px;
        color: #666;
        margin-bottom: 20px;
    }
</style>

<?php include 'footer.php'; ?>
