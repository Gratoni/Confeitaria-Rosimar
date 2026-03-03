<?php

$title = 'Checkout - Confeitaria Rosimar';
include 'db/config.php';
require_once __DIR__ . '/lib/cart.php';
require_once __DIR__ . '/lib/catalog.php';
require_once __DIR__ . '/lib/validation.php';
require_once __DIR__ . '/lib/whatsapp.php';
require_once __DIR__ . '/lib/logger.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('X-Robots-Tag: noindex, nofollow');

function buildCartPreview(mysqli $conn, array $carrinho): array
{
  $items = [];
  $total = 0.0;

  foreach ($carrinho as $id => $item) {
    $produto = getCatalogProductById($conn, (int)$id);
    if (!$produto) {
      continue;
    }

    $precoUnit = (float)$produto['preco'];
    $unidade = normalizeUnit((string)($produto['unidade'] ?? 'un'));
    $qtd = normalizeQuantity((float)($item['qtd'] ?? 1), $unidade);
    $subtotal = calculateSubtotal($precoUnit, $qtd);
    $total += $subtotal;

    $items[] = [
      'id' => (int)$produto['id'],
      'nome' => (string)$produto['nome'],
      'qtd' => $qtd,
      'qtdDisplay' => formatQuantityDisplay($qtd, $unidade),
      'precoUnit' => $precoUnit,
      'subtotal' => $subtotal,
      'unidade' => $unidade,
      'imagem' => (string)$produto['imagem'],
    ];
  }

  return [
    'items' => $items,
    'total' => $total,
    'count' => count($items),
  ];
}

function isCheckoutRateLimited(int $windowSeconds = 8): bool
{
  $now = time();
  $lastSubmitAt = (int)($_SESSION['checkout_last_submit_at'] ?? 0);
  if ($lastSubmitAt > 0 && ($now - $lastSubmitAt) < $windowSeconds) {
    return true;
  }

  $_SESSION['checkout_last_submit_at'] = $now;
  return false;
}

$carrinho = $_SESSION['carrinho'] ?? [];
$preview = buildCartPreview($conn, is_array($carrinho) ? $carrinho : []);
$previewItems = $preview['items'];
$totalPreview = $preview['total'];
$itemCount = $preview['count'];

$error = '';
$formData = [
  'nome' => '',
  'telefone' => '',
  'obs' => '',
  'lgpd' => false,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!validateCsrfToken($_POST['csrf_token'] ?? null)) {
    http_response_code(400);
    $error = 'Token CSRF invalido. Recarregue a pagina e tente novamente.';
    logError('CSRF invalid on checkout', ['ip' => $_SERVER['REMOTE_ADDR'] ?? '']);
  } else {
    $honeypot = trim((string)($_POST['website'] ?? ''));
    if ($honeypot !== '') {
      http_response_code(400);
      $error = 'Nao foi possivel processar o pedido.';
      logError('Checkout bot suspicion', ['ip' => $_SERVER['REMOTE_ADDR'] ?? '']);
    } elseif (isCheckoutRateLimited()) {
      http_response_code(429);
      $error = 'Aguarde alguns segundos antes de enviar novamente.';
    } else {
      $formData['nome'] = sanitizeCheckoutText((string)($_POST['nome'] ?? ''), 80);
      $formData['telefone'] = sanitizeCheckoutText((string)($_POST['telefone'] ?? ''), 30);
      $formData['obs'] = sanitizeCheckoutText((string)($_POST['obs'] ?? ''), 500);
      $formData['lgpd'] = hasLgpdConsent($_POST['lgpd_consent'] ?? '');

      $validationErrors = validateCheckoutInput($formData['nome'], $formData['telefone'], $formData['obs']);
      if (!$formData['lgpd']) {
        $validationErrors[] = 'Para continuar, aceite o tratamento de dados conforme a LGPD.';
      }
      if ($validationErrors) {
        http_response_code(400);
        $error = implode(' ', $validationErrors);
        logError('Checkout validation failed', ['error_count' => count($validationErrors)]);
      } elseif ($itemCount <= 0) {
        http_response_code(400);
        $error = 'Seu carrinho esta vazio. Volte ao cardapio para adicionar bolos.';
      } else {
        $orderDate = date('d/m/Y H:i');
        $phoneDigits = sanitizePhone($formData['telefone']);

        $messageItems = [];
        foreach ($previewItems as $item) {
          $messageItems[] = [
            'qty' => $item['qtdDisplay'],
            'name' => $item['nome'],
            'unit_price' => $item['precoUnit'],
          ];
        }

        $mensagem = buildWhatsappOrderMessage([
          'client_name' => $formData['nome'],
          'client_phone' => $phoneDigits,
          'notes' => $formData['obs'],
          'order_date' => $orderDate,
          'items' => $messageItems,
          'total' => $totalPreview,
        ]);
        $whatsappUrl = buildWhatsappOrderUrl((string)STORE_WHATSAPP_NUMBER, $mensagem);

        if (tableExists($conn, 'pedidos') && tableExists($conn, 'pedido_itens')) {
          try {
            if (!isDataProtectionEnabled()) {
              throw new RuntimeException('APP_DATA_KEY ausente/invalida');
            }

            $conn->begin_transaction();

            $encryptedName = encryptSensitiveData($formData['nome']);
            $encryptedPhone = encryptSensitiveData($phoneDigits);
            $encryptedNotes = encryptSensitiveData($formData['obs']);
            $lgpdConsent = 1;

            $stmt = $conn->prepare('INSERT INTO pedidos (nome_cliente, telefone, observacoes, consentimento_lgpd) VALUES (?, ?, ?, ?)');
            $stmt->bind_param('sssi', $encryptedName, $encryptedPhone, $encryptedNotes, $lgpdConsent);
            $stmt->execute();
            $pedidoId = (int)$stmt->insert_id;
            $stmt->close();

            $stmtItem = $conn->prepare('INSERT INTO pedido_itens (pedido_id, produto_id, quantidade, preco_unitario) VALUES (?, ?, ?, ?)');
            foreach ($previewItems as $item) {
              $produtoId = (int)$item['id'];
              $qtd = (float)$item['qtd'];
              $precoUnit = (float)$item['precoUnit'];
              $stmtItem->bind_param('iidd', $pedidoId, $produtoId, $qtd, $precoUnit);
              $stmtItem->execute();
            }
            $stmtItem->close();

            $conn->commit();
          } catch (Throwable $e) {
            $conn->rollback();
            logError('Checkout DB save failed', ['message' => $e->getMessage()]);
          }
        }

        $_SESSION['carrinho'] = [];
        header('Location: ' . $whatsappUrl, true, 303);
        exit;
      }
    }
  }
}

if ($itemCount <= 0 && $error === '') {
  $error = 'Seu carrinho esta vazio. Adicione bolos para finalizar o pedido.';
}

include 'header.php';
?>

<section class="wrap checkout checkout-page">
  <div class="checkout-steps" aria-label="Etapas do pedido">
    <div class="step-item is-done">1. Cardapio</div>
    <div class="step-item is-active">2. Dados e resumo</div>
    <div class="step-item">3. Confirmacao no WhatsApp</div>
  </div>

  <h1>Finalizar Pedido</h1>

  <?php if ($error !== ''): ?>
    <div class="empty-state">
      <p><?= htmlspecialchars($error) ?></p>
      <a href="index.php" class="btn">Voltar ao cardapio</a>
    </div>
  <?php else: ?>

    <div class="checkout-layout">
      <form id="form-order" method="post" class="form checkout-form">
        <h3>Seus dados para contato</h3>

        <label for="nome">Nome completo</label>
        <input id="nome" type="text" name="nome" required minlength="3" maxlength="80" autocomplete="name" placeholder="Ex: Maria Silva" value="<?= htmlspecialchars($formData['nome']) ?>">

        <label for="telefone">Telefone / WhatsApp</label>
        <input id="telefone" type="tel" name="telefone" required inputmode="tel" autocomplete="tel" maxlength="16" placeholder="(11) 9XXXX-XXXX" value="<?= htmlspecialchars($formData['telefone']) ?>">

        <label for="obs">Observacoes (data da festa, decoracao, tema)</label>
        <textarea id="obs" name="obs" rows="4" maxlength="500" placeholder="Conte detalhes importantes para o pedido."><?= htmlspecialchars($formData['obs']) ?></textarea>

        <label class="checkbox-field" for="lgpd-consent">
          <input
            id="lgpd-consent"
            type="checkbox"
            name="lgpd_consent"
            value="1"
            required
            <?= $formData['lgpd'] ? 'checked' : '' ?>>
          <span>Autorizo o uso dos meus dados para processar este pedido, conforme a LGPD.</span>
        </label>

        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(getCsrfToken()) ?>">
        <input type="text" name="website" class="hp-field" tabindex="-1" autocomplete="off" aria-hidden="true">

        <div class="form-actions">
          <a href="index.php" class="btn btn-outline btn-neutral">Voltar</a>
          <button type="submit" class="btn btn-full">Enviar pedido para WhatsApp</button>
        </div>
      </form>

      <aside class="cart-preview">
        <h3>Resumo do pedido</h3>
        <p class="preview-meta"><?= (int)$itemCount ?> <?= $itemCount === 1 ? 'item selecionado' : 'itens selecionados' ?></p>

        <div id="cart-preview-items">
          <?php foreach ($previewItems as $item): ?>
            <div class="preview-item">
              <div class="preview-item-row">
                <span><strong><?= htmlspecialchars($item['qtdDisplay']) ?></strong> <?= htmlspecialchars($item['nome']) ?></span>
                <span>R$ <?= number_format($item['subtotal'], 2, ',', '.') ?></span>
              </div>
              <small class="preview-item-unit">Unitario: R$ <?= number_format($item['precoUnit'], 2, ',', '.') ?></small>
            </div>
          <?php endforeach; ?>

          <div class="preview-item preview-total">
            <div class="preview-total-row">
              <strong>Total:</strong>
              <strong class="preview-total-value">R$ <?= number_format($totalPreview, 2, ',', '.') ?></strong>
            </div>
          </div>
        </div>

        <div class="checkout-side-actions">
          <button type="button" id="clear-cart-checkout" class="btn btn-outline">Limpar carrinho</button>
        </div>

        <p class="checkout-note">
          Depois de enviar, voce sera redirecionado para o WhatsApp da confeitaria para confirmar os detalhes.
        </p>
      </aside>
    </div>

  <?php endif; ?>
</section>

<?php include 'footer.php'; ?>
