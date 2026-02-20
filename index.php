<?php

session_start();
$title = "Confeitaria Rosimar - Início";
include 'db/config.php';
include 'header.php';

$stmt = $conn->prepare("SELECT id, nome, descricao, preco, imagem, unidade FROM produtos ORDER BY id ASC");
$stmt->execute();
$result = $stmt->get_result();
$bolos = $result->fetch_all(MYSQLI_ASSOC);
?>
<section class="hero wrap">
  <div class="hero-left">
    <h1>Bolos artesanais com acabamento profissional</h1>
    <p class="lead">Para festejar seus melhores momentos com sabor e elegância.</p>
    <a href="#cardapio" class="btn">Ver cardápio</a>
  </div>
</section>

<section id="cardapio" class="cardapio wrap">
  <h2>Cardápio de Bolos</h2>
  <p class="intro">Escolha o sabor ideal. Clique no produto para ver detalhes e encomendar.</p>

  <div class="bolos-grid">
    <?php if (!$bolos): ?>
      <div class="empty-state-box">
        <h3>Nenhum produto encontrado.</h3>
        <p>Parece que o banco de dados ainda não foi populado. Recarregue a página.</p>
      </div>
    <?php else: ?>
      <?php foreach ($bolos as $b):
        $isKg = ($b['unidade'] === 'kg');
        $priceDisplay = 'R$ ' . number_format($b['preco'], 2, ',', '.') . ($isKg ? ' <small>/ kg</small>' : '');
      ?>
        <article class="bolo-card view-product"
          data-id="<?= $b['id'] ?>"
          data-nome="<?= htmlspecialchars($b['nome']) ?>"
          data-descricao="<?= htmlspecialchars($b['descricao']) ?>"
          data-preco="<?= $b['preco'] ?>"
          data-imagem="<?= htmlspecialchars($b['imagem']) ?>"
          data-unidade="<?= $b['unidade'] ?>"
          role="button"
          tabindex="0">
          <figure class="thumb">
            <img src="<?= htmlspecialchars($b['imagem']) ?>" alt="<?= htmlspecialchars($b['nome']) ?>" onerror="this.src='assets/placeholder.jpg'; this.onerror=null;">
            <div class="overlay"><span class="btn-sm">Ver Detalhes</span></div>
          </figure>
          <div class="info">
            <h3><?= htmlspecialchars($b['nome']) ?></h3>
            <p class="desc"><?= htmlspecialchars($b['descricao']) ?></p>
            <div class="actions">
              <div class="price"><?= $priceDisplay ?></div>
              <button class="btn-icon" aria-label="Adicionar">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <line x1="12" y1="5" x2="12" y2="19"></line>
                  <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
              </button>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</section>

<!-- Product Modal -->
<div class="modal-backdrop" id="product-modal">
  <div class="modal-content">
    <button class="modal-close">&times;</button>
    <div class="modal-body">
      <div class="modal-image">
        <img id="modal-img" src="" alt="Produto">
      </div>
      <div class="modal-details">
        <h3 id="modal-title">Nome do Bolo</h3>
        <p id="modal-desc">Descrição do bolo...</p>
        <div class="modal-price-tag">
          <span id="modal-price">R$ 0,00</span>
          <span id="modal-unit"></span>
        </div>

        <div class="quantity-control">
          <label id="quantity-label">Quantidade:</label>
          <div class="qty-group">
            <button type="button" id="qty-minus">−</button>
            <input type="number" id="modal-quantity" value="1" min="1" step="1" readonly>
            <button type="button" id="qty-plus">+</button>
          </div>
        </div>

        <button id="modal-add-btn" class="btn btn-full">Adicionar ao Carrinho</button>
      </div>
    </div>
  </div>
</div>

<!-- Mini Cart -->
<aside class="mini-cart" id="mini-cart">
  <h4>Meu Pedido</h4>
  <div id="mini-cart-items"></div>
  <div class="mini-cart-actions">
    <a href="checkout.php" class="btn btn-primary">Finalizar</a>
    <button id="clear-cart" class="btn btn-outline">Limpar</button>
  </div>
</aside>

<div class="toast-container" id="toast-container"></div>

<script src="js/script.js"></script>
<?php include 'footer.php'; ?>