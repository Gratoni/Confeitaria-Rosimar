<?php

session_start();
$title = "Confeitaria Rosimar - Início";
include 'db/config.php';
include 'header.php';

$stmt = $conn->prepare("SELECT id, nome, descricao, preco, imagem FROM produtos ORDER BY id ASC");
$stmt->execute();
$result = $stmt->get_result();
$bolos = $result->fetch_all(MYSQLI_ASSOC);
?>
<section class="hero wrap">
    <div class="hero-left">
        <p class="lead">Bolos artesanais com acabamento profissional.
      <br>para festejar seus melhores momentos.
    </p>
        <a href="#cardapio" class="btn btn-primary">Ver cardápio</a>
      </div>
</section>

<section id="cardapio" class="cardapio wrap">
  <h2>Cardápio de Bolos</h2>
  <p class="intro">Escolha o sabor ideal. Clique em “Adicionar” para montar sua encomenda.</p>

  <div class="bolos-grid">
    <?php if (!$bolos): ?>
      <div class="empty">Nenhum produto encontrado. Importe o arquivo <code>database.sql</code> ou crie produtos no banco.</div>
    <?php else: ?>
      <?php foreach ($bolos as $b): ?>
        <article class="bolo-card" data-id="<?= $b['id'] ?>">
          <figure class="thumb">
            <img src="<?= htmlspecialchars($b['imagem']) ?>" alt="<?= htmlspecialchars($b['nome']) ?>" onerror="this.src='assets/placeholder.jpg'">
          </figure>
          <div class="info">
            <h3><?= htmlspecialchars($b['nome']) ?></h3>
            <p class="desc"><?= htmlspecialchars($b['descricao']) ?></p>
            <div class="actions">
              <div class="price">R$ <?= number_format($b['preco'], 2, ',', '.') ?></div>
              <button class="btn add-cart"
                data-id="<?= $b['id'] ?>"
                data-nome="<?= htmlspecialchars($b['nome']) ?>"
                data-preco="<?= $b['preco'] ?>">Adicionar</button>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</section>

<aside class="mini-cart" id="mini-cart" aria-live="polite">
  <h4>Carrinho</h4>
  <div id="mini-cart-items"><small>Seu carrinho está vazio.</small></div>
  <div class="mini-cart-actions">
    <a href="checkout.php" class="btn btn-outline">Finalizar</a>
    <button id="clear-cart" class="btn">Limpar</button>
  </div>
</aside>

<script>
  const CARRINHO_URL = '/confeitaria_rosimar/carrinho.php';

  async function atualizarCarrinho() {
    const res = await fetch(CARRINHO_URL + '?acao=listar');
    const data = await res.json();
    const el = document.getElementById('mini-cart-items');
    if (!Object.keys(data).length) {
      el.innerHTML = '<small>Seu carrinho está vazio.</small>';
      return;
    }
    el.innerHTML = Object.values(data)
      .map(i => `<div class="mini-item">${i.nome} x${i.qtd}</div>`)
      .join('');
  }

  document.addEventListener('click', async e => {
    if (e.target.matches('.add-cart')) {
      const btn = e.target;
      const formData = new FormData();
      formData.append('acao', 'adicionar');
      formData.append('id', btn.dataset.id);
      formData.append('nome', btn.dataset.nome);
      formData.append('preco', btn.dataset.preco);

      const res = await fetch(CARRINHO_URL, {
        method: 'POST',
        body: formData
      });

      const data = await res.json();
      if (data.ok) btn.textContent = 'Adicionado ✓';
      setTimeout(() => (btn.textContent = 'Adicionar'), 1000);
      atualizarCarrinho();
    }
  });

  document.getElementById('clear-cart').addEventListener('click', async () => {
    await fetch(CARRINHO_URL + '?acao=limpar');
    atualizarCarrinho();
  });

  atualizarCarrinho();
</script>

<?php include 'footer.php'; ?>