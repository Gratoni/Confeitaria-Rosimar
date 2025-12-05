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
    <h1>Bolos artesanais com acabamento profissional</h1>
    <p class="lead">Para festejar seus melhores momentos com sabor e elegância.</p>
    <a href="#cardapio" class="btn">Ver cardápio</a>
  </div>
</section>

<section id="cardapio" class="cardapio wrap">
  <h2>Cardápio de Bolos</h2>
  <p class="intro">Escolha o sabor ideal. Todos os nossos bolos são feitos com ingredientes selecionados e muito carinho.</p>

  <div class="bolos-grid">
    <?php if (!$bolos): ?>
      <div class="empty" style="grid-column: 1/-1; text-align: center; padding: 40px; background: #fff; border-radius: 12px;">
        <h3>Nenhum produto encontrado.</h3>
        <p>Parece que o banco de dados ainda não foi populado. Recarregue a página para tentar a inicialização automática.</p>
      </div>
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

<!-- Mini Cart -->
<aside class="mini-cart" id="mini-cart" aria-live="polite">
  <h4>Carrinho</h4>
  <div id="mini-cart-items"><small>Seu carrinho está vazio.</small></div>
  <div class="mini-cart-actions">
    <a href="checkout.php" class="btn btn-outline">Finalizar</a>
    <button id="clear-cart" class="btn btn-outline" style="border: 1px solid #ddd; color: #666;">Limpar</button>
  </div>
</aside>

<!-- Toast Container -->
<div class="toast-container" id="toast-container"></div>

<script>
  const CARRINHO_URL = 'carrinho.php';

  function showToast(message) {
      const container = document.getElementById('toast-container');
      const toast = document.createElement('div');
      toast.className = 'toast';
      toast.innerHTML = `<span>✓</span> ${message}`;
      container.appendChild(toast);
      setTimeout(() => {
          toast.remove();
      }, 3500);
  }

  async function atualizarCarrinho() {
    try {
        const res = await fetch(CARRINHO_URL + '?acao=listar');
        const data = await res.json();
        const el = document.getElementById('mini-cart-items');
        const miniCart = document.getElementById('mini-cart');
        const badge = document.getElementById('cart-count');

        let count = 0;

        if (!data || !Object.keys(data).length) {
          el.innerHTML = '<small>Seu carrinho está vazio.</small>';
          miniCart.classList.remove('active');
          if(badge) badge.style.display = 'none';
          return;
        }

        let html = '';
        Object.values(data).forEach(i => {
            count += i.qtd;
            html += `<div class="mini-item"><span>${i.nome}</span> <span>x${i.qtd}</span></div>`;
        });

        el.innerHTML = html;
        if(badge) {
            badge.innerText = count;
            badge.style.display = 'block';
        }

        // Show mini cart if it has items (optional, maybe only on hover or add)
        // miniCart.classList.add('active');
    } catch (e) {
        console.error("Erro ao atualizar carrinho", e);
    }
  }

  document.addEventListener('click', async e => {
    if (e.target.matches('.add-cart')) {
      const btn = e.target;

      // Visual feedback on button
      const originalText = btn.textContent;
      btn.textContent = '...';
      btn.disabled = true;

      const formData = new FormData();
      formData.append('acao', 'adicionar');
      formData.append('id', btn.dataset.id);
      formData.append('nome', btn.dataset.nome);
      formData.append('preco', btn.dataset.preco);

      try {
          const res = await fetch(CARRINHO_URL, {
            method: 'POST',
            body: formData
          });

          const data = await res.json();
          if (data.ok) {
              showToast(`${btn.dataset.nome} adicionado ao carrinho!`);
              document.getElementById('mini-cart').classList.add('active');
              // Auto hide mini cart after 3 seconds if user doesn't hover
              setTimeout(() => {
                  // logic to hide if not hovering could go here
              }, 3000);
          }
      } catch (err) {
          console.error(err);
      } finally {
          btn.textContent = originalText;
          btn.disabled = false;
          atualizarCarrinho();
      }
    }
  });

  document.getElementById('clear-cart').addEventListener('click', async () => {
    await fetch(CARRINHO_URL + '?acao=limpar');
    atualizarCarrinho();
  });

  // Toggle mini cart on cart icon click (mobile mostly)
  const cartLink = document.querySelector('.cart-link');
  if(cartLink) {
      cartLink.addEventListener('click', (e) => {
          if(window.innerWidth > 900) return; // Allow normal navigation on desktop if desired, or toggle
          e.preventDefault();
          document.getElementById('mini-cart').classList.toggle('active');
      });
  }

  atualizarCarrinho();
</script>

<?php include 'footer.php'; ?>
