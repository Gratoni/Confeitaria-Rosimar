<?php
// checkout.php
$title = "Checkout - Confeitaria Rosimar";
include 'db/config.php';
include 'header.php';
require_once __DIR__ . '/lib/cart.php';
require_once __DIR__ . '/lib/validation.php';
require_once __DIR__ . '/lib/logger.php';

$carrinho = $_SESSION['carrinho'] ?? [];
$SEU_TELEFONE_WHATSAPP = "5511957077345";

function getProdutoById(mysqli $conn, int $id): ?array
{
  $stmt = $conn->prepare("SELECT id, nome, preco, unidade FROM produtos WHERE id = ?");
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $produto = $stmt->get_result()->fetch_assoc();
  $stmt->close();
  return $produto ?: null;
}

$previewItems = [];
$totalPreview = 0;
if ($carrinho) {
  foreach ($carrinho as $id => $item) {
    $produto = getProdutoById($conn, (int)$id);
    $nome = $produto['nome'] ?? ($item['nome'] ?? 'Produto');
    $precoUnit = (float)($produto['preco'] ?? $item['preco'] ?? 0);
    $unidade = $produto['unidade'] ?? ($item['unidade'] ?? 'un');

    $qtd = normalizeQuantity((float)($item['qtd'] ?? 1), $unidade);

    $subtotal = calculateSubtotal($precoUnit, $qtd);
    $totalPreview += $subtotal;

    $qtdDisplay = formatQuantityDisplay($qtd, $unidade);

    $previewItems[] = [
      'nome' => $nome,
      'qtdDisplay' => $qtdDisplay,
      'precoUnit' => $precoUnit,
      'subtotal' => $subtotal
    ];
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $carrinho) {
  if (!validateCsrfToken($_POST['csrf_token'] ?? null)) {
    http_response_code(400);
    $error = "Token CSRF inválido. Recarregue a página e tente novamente.";
    logError('CSRF invalid on checkout', ['ip' => $_SERVER['REMOTE_ADDR'] ?? '']);
  } else {

  date_default_timezone_set('America/Sao_Paulo');

  $nome = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '';
  $telefone = filter_input(INPUT_POST, 'telefone', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '';
  $obs = filter_input(INPUT_POST, 'obs', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '';

  $validationErrors = validateCheckoutInput($nome, $telefone, $obs);
  if ($validationErrors) {
    http_response_code(400);
    $error = implode(' ', $validationErrors);
    logError('Checkout validation failed', ['errors' => $validationErrors]);
  } else {
  $data_pedido = date('d/m/Y H:i:s');
  $total = 0;

  // --- 1. Geração da Mensagem ---
  $mensagem = "*NOVO PEDIDO ROSIMAR* (Feito em: $data_pedido)\n";
  $mensagem .= "---------------------------------------------------\n";
  $mensagem .= "Cliente: " . $nome . "\n";
  $mensagem .= "Contato: " . $telefone . "\n";
  $mensagem .= "Obs: " . ($obs ?: "Nenhuma.") . "\n\n";
  $mensagem .= "ITENS DO PEDIDO:\n";

  foreach ($carrinho as $id => $item) {
    $produto = getProdutoById($conn, (int)$id);
    if (!$produto) {
      continue;
    }

    $precoUnit = (float)$produto['preco'];
    $unidade = $produto['unidade'] ?? 'un';
    $nomeProduto = $produto['nome'];

    $qtd = normalizeQuantity((float)($item['qtd'] ?? 1), $unidade);

    $subtotal = calculateSubtotal($precoUnit, $qtd);
    $total += $subtotal;

    $qtdDisplay = formatQuantityDisplay($qtd, $unidade);

    $mensagem .= " • {$qtdDisplay} {$nomeProduto} (R$ " . number_format($precoUnit, 2, ',', '.') . ")\n";
  }

  $mensagem .= "---------------------------------------------------\n";
  $mensagem .= "TOTAL GERAL: R$ " . number_format($total, 2, ',', '.') . "\n\n";
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
          $produto = getProdutoById($conn, (int)$id);
          if (!$produto) {
            continue;
          }

          $precoUnit = (float)$produto['preco'];
          $unidade = $produto['unidade'] ?? 'un';
          $qtd = normalizeQuantity((float)($item['qtd'] ?? 1), $unidade);

          $stmtItem->bind_param("iidd", $pedido_id, $id, $qtd, $precoUnit);
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
  }
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
          <input type="text" name="nome" required minlength="3" maxlength="80" placeholder="Ex: Maria Silva">
        </label>
        <label>Telefone / WhatsApp
          <input type="tel" name="telefone" required inputmode="tel" maxlength="16" placeholder="(11) 9xxxx-xxxx">
        </label>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(getCsrfToken()) ?>">
        <label>Observações (data da festa, decoração, etc)
          <textarea name="obs" rows="4" maxlength="500" placeholder="Escreva aqui detalhes importantes..."></textarea>
        </label>

        <div class="form-actions">
          <a href="index.php" class="btn btn-outline" style="border:none; color: #666;">Cancelar</a>
          <button type="submit" class="btn" style="width: 100%;">Enviar Pedido pelo WhatsApp</button>
        </div>
      </form>

      <div class="cart-preview">
        <h3>Resumo do Pedido</h3>
        <div id="cart-preview-items">
          <?php foreach ($previewItems as $item): ?>
            <div class="preview-item">
              <div style="display:flex; justify-content:space-between;">
                <span><strong><?= htmlspecialchars($item['qtdDisplay']) ?></strong> <?= htmlspecialchars($item['nome']) ?></span>
                <span>R$ <?= number_format($item['subtotal'], 2, ',', '.') ?></span>
              </div>
              <small style="color:#888;">Unit: R$ <?= number_format($item['precoUnit'], 2, ',', '.') ?></small>
            </div>
          <?php endforeach; ?>

          <div class="preview-item" style="border-top: 2px solid #ddd; border-bottom: none; margin-top: 10px; padding-top: 15px;">
            <div style="display:flex; justify-content:space-between; font-size: 1.2em;">
              <strong>TOTAL:</strong>
              <strong style="color: var(--accent-dark);">R$ <?= number_format($totalPreview, 2, ',', '.') ?></strong>
            </div>
          </div>
        </div>
        <div style="margin-top: 14px; display:flex; justify-content:flex-end;">
          <button type="button" id="clear-cart-checkout" class="btn btn-outline">Limpar carrinho</button>
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
    .checkout>div {
      grid-template-columns: 1fr !important;
    }

    .form,
    .cart-preview {
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
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
  }

  .empty-state p {
    font-size: 18px;
    color: #666;
    margin-bottom: 20px;
  }
</style>

<?php include 'footer.php'; ?>
