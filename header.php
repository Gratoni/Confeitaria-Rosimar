<?php

if (!isset($title)) $title = "Confeitaria Rosimar";
?>
<!doctype html>
<html lang="pt-BR">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= htmlspecialchars($title) ?></title>
  <link rel="stylesheet" href="css/style.css">
  <meta name="description" content="Confeitaria Rosimar — bolos artesanais e encomendas. Encomende online via WhatsApp.">
</head>

<body>
  <header class="site-header">
    <div class="wrap">
      <a href="index.php" class="brand">
        <img src="assets/logo.png" alt="Logo Confeitaria Rosimar" class="logo-img" onerror="this.style.display='none'">
        <div class="brand-text">
          <span class="brand-name">Confeitaria <strong>Rosimar</strong></span>
          <small class="tagline">Artesanal • Sabor • Acabamento profissional</small>
        </div>
      </a>

      <nav class="main-nav" aria-label="Menu principal">
        <a href="index.php">Início</a>
        <a href="#cardapio">Cardápio</a>
        <a href="checkout.php" class="btn btn-outline">Checkout</a>
      </nav>
    </div>
  </header>
  <main>