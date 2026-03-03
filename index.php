<?php

$title = 'Confeitaria Rosimar - Bolos Artesanais';
include 'db/config.php';
require_once __DIR__ . '/lib/catalog.php';
include 'header.php';

$bolos = fetchCatalogProducts($conn);
$caseiros = array_values(array_filter($bolos, static function ($bolo) {
  return ($bolo['categoria'] ?? '') === 'caseiro';
}));
?>

<section class="hero wrap">
  <div class="hero-left">
    <p class="hero-kicker">Confeitaria artesanal para encomendas</p>
    <h1>Bolos que combinam sabor caseiro e acabamento profissional</h1>
    <p class="lead">
      Monte seu pedido em minutos, escolha o sabor ideal e confirme tudo direto no WhatsApp.
    </p>
    <div class="hero-actions">
      <a href="#cardapio" class="btn">Ver cardapio</a>
      <a href="#como-encomendar" class="btn btn-outline hero-secondary">Como encomendar</a>
    </div>
    <ul class="hero-highlights">
      <li>Producao sob encomenda</li>
      <li>Receitas frescas todos os dias</li>
      <li>Atendimento rapido no WhatsApp</li>
    </ul>
  </div>
</section>

<section class="wrap trust-strip" id="diferenciais">
  <article class="trust-card">
    <h3>Ingredientes selecionados</h3>
    <p>Receitas com massa leve, recheios equilibrados e cobertura feita no mesmo dia.</p>
  </article>
  <article class="trust-card">
    <h3>Catalogo completo</h3>
    <p>Opcoes por unidade e por kg para festas, aniversarios e cafe da tarde.</p>
  </article>
  <article class="trust-card">
    <h3>Fluxo simples de compra</h3>
    <p>Escolha os bolos, revise no checkout e confirme sem burocracia.</p>
  </article>
</section>

<?php if (!empty($caseiros)): ?>
  <section class="wrap caseiros" id="caseiros">
    <div class="section-heading">
      <h2>Bolos caseiros em destaque</h2>
      <p>Novas opcoes para cafe da tarde e encomendas do dia a dia.</p>
    </div>

    <div class="caseiro-grid">
      <?php foreach (array_slice($caseiros, 0, 4) as $bolo): ?>
        <article class="caseiro-card">
          <img src="<?= htmlspecialchars($bolo['imagem']) ?>" alt="<?= htmlspecialchars($bolo['nome']) ?>" loading="lazy" decoding="async" data-fallback-src="assets/placeholder.jpg">
          <div>
            <h3><?= htmlspecialchars($bolo['nome']) ?></h3>
            <p><?= htmlspecialchars($bolo['descricao']) ?></p>
            <span>R$ <?= number_format((float)$bolo['preco'], 2, ',', '.') ?></span>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </section>
<?php endif; ?>

<section id="cardapio" class="cardapio wrap">
  <div class="section-heading">
    <h2>Cardapio de Bolos</h2>
    <p>Filtre por tipo, unidade e preco para encontrar o sabor ideal para cada ocasiao.</p>
  </div>

  <?php if (!empty($bolos)): ?>
    <div class="catalog-toolbar" aria-label="Controles de busca do cardapio">
      <label class="catalog-field catalog-field-search" for="catalog-search">
        <span class="sr-only">Buscar sabor ou descricao</span>
        <input type="search" id="catalog-search" placeholder="Buscar sabor (ex: chocolate)" autocomplete="off">
      </label>

      <label class="catalog-field" for="catalog-unit-filter">
        <span>Unidade</span>
        <select id="catalog-unit-filter">
          <option value="all">Todas</option>
          <option value="un">Por unidade</option>
          <option value="kg">Por kg</option>
        </select>
      </label>

      <label class="catalog-field" for="catalog-category-filter">
        <span>Tipo</span>
        <select id="catalog-category-filter">
          <option value="all">Todos</option>
          <option value="caseiro">Caseiro</option>
          <option value="tradicional">Tradicional</option>
          <option value="festa">Festa</option>
        </select>
      </label>

      <label class="catalog-field" for="catalog-sort">
        <span>Ordenar</span>
        <select id="catalog-sort">
          <option value="featured">Relevancia</option>
          <option value="price-asc">Menor preco</option>
          <option value="price-desc">Maior preco</option>
          <option value="name-asc">Nome (A-Z)</option>
        </select>
      </label>
    </div>
    <p id="catalog-count" class="catalog-count" aria-live="polite"></p>
  <?php endif; ?>

  <div class="bolos-grid">
    <?php if (empty($bolos)): ?>
      <div class="empty-state-box">
        <h3>Nenhum produto encontrado.</h3>
        <p>O cardapio ainda nao foi populado. Recarregue a pagina.</p>
      </div>
    <?php else: ?>
      <?php foreach ($bolos as $b):
        $isKg = ($b['unidade'] === 'kg');
        $priceDisplay = 'R$ ' . number_format((float)$b['preco'], 2, ',', '.') . ($isKg ? ' <small>/ kg</small>' : '');
      ?>
        <article class="bolo-card view-product"
          data-id="<?= (int)$b['id'] ?>"
          data-nome="<?= htmlspecialchars((string)$b['nome']) ?>"
          data-descricao="<?= htmlspecialchars((string)$b['descricao']) ?>"
          data-preco="<?= (float)$b['preco'] ?>"
          data-imagem="<?= htmlspecialchars((string)$b['imagem']) ?>"
          data-unidade="<?= htmlspecialchars((string)$b['unidade']) ?>"
          data-categoria="<?= htmlspecialchars((string)$b['categoria']) ?>"
          role="button"
          aria-label="Ver detalhes de <?= htmlspecialchars((string)$b['nome']) ?>"
          tabindex="0">
          <figure class="thumb">
            <img src="<?= htmlspecialchars((string)$b['imagem']) ?>" alt="<?= htmlspecialchars((string)$b['nome']) ?>" loading="lazy" decoding="async" data-fallback-src="assets/placeholder.jpg">
            <div class="overlay"><span class="btn-sm">Ver detalhes</span></div>
          </figure>

          <div class="info">
            <div class="card-badges">
              <span class="badge-chip"><?= $isKg ? 'Venda por kg' : 'Venda por unidade' ?></span>
              <?php if (($b['categoria'] ?? '') === 'caseiro'): ?>
                <span class="badge-chip badge-chip-soft">Caseiro</span>
              <?php elseif (($b['categoria'] ?? '') === 'festa'): ?>
                <span class="badge-chip badge-chip-soft">Festa</span>
              <?php else: ?>
                <span class="badge-chip badge-chip-soft">Tradicional</span>
              <?php endif; ?>
            </div>

            <h3><?= htmlspecialchars((string)$b['nome']) ?></h3>
            <p class="desc"><?= htmlspecialchars((string)$b['descricao']) ?></p>
            <div class="actions">
              <div class="price"><?= $priceDisplay ?></div>
              <button type="button" class="btn-icon" aria-label="Adicionar <?= htmlspecialchars((string)$b['nome']) ?> ao carrinho">
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

  <?php if (!empty($bolos)): ?>
    <div id="catalog-empty-state" class="empty-state-box catalog-empty" hidden>
      <h3>Nenhum bolo encontrado para este filtro.</h3>
      <p>Tente limpar a busca ou selecionar outro tipo de bolo.</p>
    </div>
  <?php endif; ?>
</section>

<section id="como-encomendar" class="wrap flow">
  <div class="section-heading">
    <h2>Como encomendar</h2>
    <p>Fluxo simples para voce fechar seu pedido em poucos minutos.</p>
  </div>
  <div class="flow-grid">
    <article>
      <span>1</span>
      <h3>Escolha os bolos</h3>
      <p>Abra os detalhes, ajuste quantidade e adicione ao carrinho.</p>
    </article>
    <article>
      <span>2</span>
      <h3>Revise no checkout</h3>
      <p>Confira itens, total e preencha seus dados para contato.</p>
    </article>
    <article>
      <span>3</span>
      <h3>Confirme no WhatsApp</h3>
      <p>Voce e redirecionado com o pedido pronto para a confirmacao final.</p>
    </article>
  </div>
</section>

<div class="modal-backdrop" id="product-modal" aria-hidden="true">
  <div class="modal-content" role="dialog" aria-modal="true" aria-hidden="true" aria-labelledby="modal-title" aria-describedby="modal-desc" tabindex="-1">
    <button type="button" class="modal-close" aria-label="Fechar detalhes do produto">&times;</button>
    <div class="modal-body">
      <div class="modal-image">
        <img id="modal-img" src="" alt="Produto">
      </div>
      <div class="modal-details">
        <h3 id="modal-title">Nome do bolo</h3>
        <p id="modal-desc">Descricao do bolo.</p>
        <div class="modal-price-tag">
          <span id="modal-price">R$ 0,00</span>
          <span id="modal-unit"></span>
        </div>

        <div class="quantity-control">
          <label id="quantity-label">Quantidade:</label>
          <div class="qty-group">
            <button type="button" id="qty-minus">-</button>
            <input type="number" id="modal-quantity" value="1" min="1" step="1" readonly>
            <button type="button" id="qty-plus">+</button>
          </div>
        </div>

        <button id="modal-add-btn" class="btn btn-full">Adicionar ao carrinho</button>
      </div>
    </div>
  </div>
</div>

<aside class="mini-cart" id="mini-cart" aria-labelledby="mini-cart-title" aria-hidden="true">
  <h4 id="mini-cart-title">Meu pedido</h4>
  <div id="mini-cart-items" aria-live="polite" aria-atomic="true"></div>
  <div class="mini-cart-actions">
    <a href="checkout.php" class="btn btn-primary">Finalizar</a>
    <button type="button" id="clear-cart" class="btn btn-outline">Limpar</button>
  </div>
</aside>

<div class="toast-container" id="toast-container" aria-live="polite" aria-atomic="true"></div>

<?php include 'footer.php'; ?>
