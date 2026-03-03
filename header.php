<?php

if (!isset($title)) {
  $title = 'Confeitaria Rosimar';
}

$carrinhoCount = 0;
if (!empty($_SESSION['carrinho']) && is_array($_SESSION['carrinho'])) {
  $carrinhoCount = count($_SESSION['carrinho']);
}

$gtmContainerId = defined('GTM_CONTAINER_ID') ? trim((string)GTM_CONTAINER_ID) : '';
$ga4MeasurementId = defined('GA4_MEASUREMENT_ID') ? trim((string)GA4_MEASUREMENT_ID) : '';
$hasGtm = preg_match('/^GTM-[A-Z0-9]+$/', $gtmContainerId) === 1;
$hasGa4 = preg_match('/^G-[A-Z0-9]+$/', $ga4MeasurementId) === 1;

$requestPath = (string)parse_url($_SERVER['REQUEST_URI'] ?? 'index.php', PHP_URL_PATH);
$currentFile = basename($requestPath);
$isHome = ($currentFile === '' || $currentFile === 'index.php');
$logoPath = __DIR__ . '/assets/logo.png';
$hasLogo = is_file($logoPath);
$cspNonce = function_exists('getCspNonce') ? getCspNonce() : '';
$nonceAttr = $cspNonce !== '' ? ' nonce="' . htmlspecialchars($cspNonce, ENT_QUOTES, 'UTF-8') . '"' : '';
?>
<!doctype html>
<html lang="pt-BR">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></title>
  <meta name="description" content="Confeitaria Rosimar - bolos artesanais, caseiros e de festa. Encomende online pelo WhatsApp.">
  <meta name="csrf-token" content="<?= htmlspecialchars(getCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
  <meta name="theme-color" content="#f7efe8">
  <link rel="stylesheet" href="css/style.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Bree+Serif&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">

  <?php if ($hasGtm): ?>
    <script<?= $nonceAttr ?>>
      window.dataLayer = window.dataLayer || [];
      (function(w, d, s, l, i) {
        w[l] = w[l] || [];
        w[l].push({
          'gtm.start': new Date().getTime(),
          event: 'gtm.js'
        });
        var f = d.getElementsByTagName(s)[0],
          j = d.createElement(s),
          dl = l !== 'dataLayer' ? '&l=' + l : '';
        j.async = true;
        j.src = 'https://www.googletagmanager.com/gtm.js?id=' + i + dl;
        f.parentNode.insertBefore(j, f);
      })(window, document, 'script', 'dataLayer', '<?= htmlspecialchars($gtmContainerId, ENT_QUOTES, 'UTF-8') ?>');
    </script>
  <?php else: ?>
    <script<?= $nonceAttr ?>>
      window.dataLayer = window.dataLayer || [];
    </script>
    <?php if ($hasGa4): ?>
      <script async src="https://www.googletagmanager.com/gtag/js?id=<?= htmlspecialchars($ga4MeasurementId, ENT_QUOTES, 'UTF-8') ?>"></script>
      <script<?= $nonceAttr ?>>
        function gtag() {
          dataLayer.push(arguments);
        }
        gtag('js', new Date());
        gtag('config', '<?= htmlspecialchars($ga4MeasurementId, ENT_QUOTES, 'UTF-8') ?>');
      </script>
    <?php endif; ?>
  <?php endif; ?>
</head>

<body>
  <?php if ($hasGtm): ?>
    <noscript>
      <iframe src="https://www.googletagmanager.com/ns.html?id=<?= htmlspecialchars($gtmContainerId, ENT_QUOTES, 'UTF-8') ?>" height="0" width="0" style="display:none;visibility:hidden"></iframe>
    </noscript>
  <?php endif; ?>

  <a class="skip-link" href="#main-content">Pular para o conteudo</a>

  <header class="site-header">
    <div class="wrap">
      <a href="index.php" class="brand" aria-label="Ir para a pagina inicial da Confeitaria Rosimar">
        <?php if ($hasLogo): ?>
          <img src="assets/logo.png" alt="Logo Confeitaria Rosimar" class="logo-img">
        <?php else: ?>
          <span class="logo-fallback" aria-hidden="true">CR</span>
        <?php endif; ?>

        <div class="brand-text">
          <span class="brand-name">Confeitaria <strong>Rosimar</strong></span>
          <small class="tagline">Bolos caseiros e de festa sob encomenda</small>
        </div>
      </a>

      <nav class="main-nav" aria-label="Menu principal">
        <a href="index.php" class="<?= $isHome ? 'is-active' : '' ?>">Inicio</a>
        <a href="index.php#diferenciais">Diferenciais</a>
        <a href="index.php#cardapio">Cardapio</a>
        <a href="index.php#como-encomendar">Como encomendar</a>
        <a href="checkout.php" class="cart-link <?= $currentFile === 'checkout.php' ? 'is-active' : '' ?>" aria-label="Abrir checkout" aria-controls="mini-cart" aria-expanded="false">
          <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="9" cy="21" r="1"></circle>
            <circle cx="20" cy="21" r="1"></circle>
            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
          </svg>
          <span id="cart-count" class="badge" aria-live="polite" aria-atomic="true" style="<?= $carrinhoCount > 0 ? '' : 'display:none' ?>"><?= (int)$carrinhoCount ?></span>
        </a>
      </nav>
    </div>
  </header>

  <main id="main-content" tabindex="-1">
