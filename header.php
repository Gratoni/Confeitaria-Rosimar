<?php

if (!isset($title)) {
    $title = 'Confeitaria Rosimar';
}

$carrinho_count = 0;
if (isset($_SESSION['carrinho']) && is_array($_SESSION['carrinho'])) {
    foreach ($_SESSION['carrinho'] as $item) {
        $carrinho_count += (float) ($item['qtd'] ?? 0);
    }
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($title) ?></title>
  <meta name="description" content="Confeitaria Rosimar — bolos artesanais, caseiros e por quilo. Monte seu pedido online e finalize no WhatsApp.">
  <link rel="stylesheet" href="css/style.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Lato:wght@400;700&display=swap" rel="stylesheet">
</head>
<body>
  <header class="site-header">
    <div class="wrap header-inner">
      <a href="index.php" class="brand" aria-label="Ir para a página inicial">
        <img src="assets/logo.png" alt="Logo Confeitaria Rosimar" class="logo-img" onerror="this.style.display='none'">
        <div class="brand-text">
          <span class="brand-name">Confeitaria <strong>Rosimar</strong></span>
          <small class="tagline">Bolos artesanais • Encomenda fácil • Atendimento humanizado</small>
        </div>
      </a>

      <nav class="main-nav" aria-label="Menu principal">
        <a href="index.php#cardapio">Cardápio</a>
        <a href="index.php#diferenciais">Diferenciais</a>
        <a href="checkout.php" class="cart-link" aria-label="Abrir carrinho">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
          <span id="cart-count" class="badge" style="<?= $carrinho_count > 0 ? '' : 'display:none' ?>"><?= rtrim(rtrim(number_format($carrinho_count, 1, '.', ''), '0'), '.') ?></span>
        </a>
      </nav>
    </div>
  </header>
  <main>
