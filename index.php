<?php

declare(strict_types=1);

$title = 'Confeitaria Rosimar - Bolos artesanais e caseiros';
include 'db/config.php';
include 'header.php';

$stmt = $conn->prepare('SELECT id, nome, descricao, preco, imagem, unidade FROM produtos ORDER BY unidade DESC, nome ASC');
$stmt->execute();
$bolos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<section class="hero wrap">
  <div class="hero-content">
    <p class="hero-tag">Confeitaria artesanal em São Paulo</p>
    <h1>Bolos para celebrações e para o café da tarde.</h1>
    <p>Escolha sabores por quilo ou bolos caseiros. Monte o pedido em minutos e confirme pelo WhatsApp.</p>
    <div class="hero-actions">
      <a href="#cardapio" class="btn">Ver cardápio</a>
      <a href="#diferenciais" class="btn btn-outline">Como funciona</a>
    </div>
  </div>
</section>

<section id="cardapio" class="cardapio wrap">
  <div class="section-heading">
    <h2>Cardápio de bolos</h2>
    <p>Selecionamos sabores clássicos e caseiros com imagens reais para facilitar sua escolha.</p>
  </div>

  <div class="bolos-grid">
    <?php if (!$bolos): ?>
      <div class="empty-state-box">
        <h3>Cardápio indisponível no momento.</h3>
        <p>Atualize a página em instantes.</p>
      </div>
    <?php else: ?>
      <?php foreach ($bolos as $b):
        $isKg = $b['unidade'] === 'kg';
        $priceDisplay = 'R$ ' . number_format((float) $b['preco'], 2, ',', '.') . ($isKg ? ' <small>/kg</small>' : '');
      ?>
      <article class="bolo-card view-product"
        data-id="<?= (int) $b['id'] ?>"
        data-nome="<?= htmlspecialchars($b['nome']) ?>"
        data-descricao="<?= htmlspecialchars($b['descricao'] ?? '') ?>"
        data-preco="<?= (float) $b['preco'] ?>"
        data-imagem="<?= htmlspecialchars($b['imagem'] ?: 'assets/placeholder.jpg') ?>"
        data-unidade="<?= htmlspecialchars($b['unidade']) ?>"
        role="button"
        tabindex="0"
        aria-label="Ver detalhes de <?= htmlspecialchars($b['nome']) ?>">
        <figure class="thumb">
          <img src="<?= htmlspecialchars($b['imagem'] ?: 'assets/placeholder.jpg') ?>" alt="<?= htmlspecialchars($b['nome']) ?>" onerror="this.src='assets/placeholder.jpg'; this.onerror=null;">
        </figure>
        <div class="info">
          <h3><?= htmlspecialchars($b['nome']) ?></h3>
          <p class="desc"><?= htmlspecialchars($b['descricao'] ?? '') ?></p>
          <div class="actions">
            <div class="price"><?= $priceDisplay ?></div>
            <button type="button" class="btn-icon" aria-label="Adicionar <?= htmlspecialchars($b['nome']) ?> ao carrinho">+</button>
          </div>
        </div>
      </article>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</section>

<section id="diferenciais" class="wrap benefits">
  <article>
    <h3>Produção fresca</h3>
    <p>Bolos preparados sob encomenda para manter sabor e textura ideais.</p>
  </article>
  <article>
    <h3>Fluxo de pedido simples</h3>
    <p>Cliente escolhe, confere o resumo e finaliza por WhatsApp com poucos cliques.</p>
  </article>
  <article>
    <h3>Catálogo consistente</h3>
    <p>Descrições claras, preços por unidade e por kg, e imagens alinhadas aos sabores.</p>
  </article>
</section>

<div class="modal-backdrop" id="product-modal" aria-hidden="true">
  <div class="modal-content" role="dialog" aria-modal="true" aria-labelledby="modal-title">
    <button class="modal-close" type="button" aria-label="Fechar">&times;</button>
    <div class="modal-body">
      <div class="modal-image"><img id="modal-img" src="" alt="Produto"></div>
      <div class="modal-details">
        <h3 id="modal-title"></h3>
        <p id="modal-desc"></p>
        <div class="modal-price-tag"><span id="modal-price"></span> <span id="modal-unit"></span></div>
        <div class="quantity-control">
          <label id="quantity-label" for="modal-quantity">Quantidade</label>
          <div class="qty-group">
            <button type="button" id="qty-minus">−</button>
            <input type="number" id="modal-quantity" value="1" min="1" step="1" readonly>
            <button type="button" id="qty-plus">+</button>
          </div>
        </div>
        <button id="modal-add-btn" class="btn btn-full" type="button">Adicionar ao carrinho</button>
      </div>
    </div>
  </div>
</div>

<aside class="mini-cart" id="mini-cart">
  <h4>Meu pedido</h4>
  <div id="mini-cart-items"></div>
  <div class="mini-cart-actions">
    <a href="checkout.php" class="btn btn-primary">Finalizar</a>
    <button id="clear-cart" type="button" class="btn btn-outline">Limpar</button>
  </div>
</aside>

<div class="toast-container" id="toast-container"></div>

<script src="js/script.js"></script>
<?php include 'footer.php'; ?>
