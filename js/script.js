document.addEventListener('DOMContentLoaded', () => {
  const CARRINHO_URL = 'carrinho.php';

  const els = {
    miniCartItems: document.getElementById('mini-cart-items'),
    miniCart: document.getElementById('mini-cart'),
    cartBadge: document.getElementById('cart-count'),
    clearCart: document.getElementById('clear-cart'),
    toastContainer: document.getElementById('toast-container'),
    modal: document.getElementById('product-modal'),
    modalContent: document.querySelector('#product-modal .modal-content'),
    modalImg: document.getElementById('modal-img'),
    modalTitle: document.getElementById('modal-title'),
    modalDesc: document.getElementById('modal-desc'),
    modalPrice: document.getElementById('modal-price'),
    modalUnit: document.getElementById('modal-unit'),
    modalQty: document.getElementById('modal-quantity'),
    quantityLabel: document.getElementById('quantity-label'),
    addBtn: document.getElementById('modal-add-btn'),
    plusBtn: document.getElementById('qty-plus'),
    minusBtn: document.getElementById('qty-minus')
  };

  let currentProduct = null;

  const money = value => Number(value).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });

  const toast = msg => {
    if (!els.toastContainer) return;
    const item = document.createElement('div');
    item.className = 'toast';
    item.textContent = msg;
    els.toastContainer.appendChild(item);
    setTimeout(() => item.remove(), 2500);
  };

  const loadCart = async () => {
    const res = await fetch(`${CARRINHO_URL}?acao=listar`);
    const data = await res.json();

    if (!data || !Object.keys(data).length) {
      if (els.miniCartItems) els.miniCartItems.innerHTML = '<p class="empty-cart-msg">Carrinho vazio.</p>';
      if (els.cartBadge) els.cartBadge.style.display = 'none';
      return;
    }

    let count = 0;
    let html = '';

    Object.entries(data).forEach(([id, item]) => {
      count += Number(item.qtd);
      const qtdLabel = item.unidade === 'kg' ? `${Number(item.qtd).toFixed(1)}kg` : `${parseInt(item.qtd, 10)}x`;
      html += `<button class="mini-item-remove" data-remove-id="${id}" type="button">${item.nome} <small>${qtdLabel}</small></button>`;
    });

    if (els.miniCartItems) els.miniCartItems.innerHTML = html;
    if (els.cartBadge) {
      els.cartBadge.style.display = 'flex';
      els.cartBadge.textContent = count % 1 === 0 ? count : count.toFixed(1);
    }
  };

  const cartAction = async (payload) => {
    const formData = new FormData();
    Object.entries(payload).forEach(([k, v]) => formData.append(k, v));
    const res = await fetch(CARRINHO_URL, { method: 'POST', body: formData });
    return res.json();
  };

  const addToCart = async (product, qtd) => {
    const response = await cartAction({ acao: 'adicionar', id: product.id, qtd });
    if (!response.ok) {
      toast(response.msg || 'Erro ao adicionar item.');
      return;
    }
    await loadCart();
    if (els.miniCart) {
      els.miniCart.classList.add('active');
      setTimeout(() => els.miniCart.classList.remove('active'), 2200);
    }
    toast(`${product.nome} adicionado ao carrinho.`);
  };

  const openModal = (product) => {
    if (!els.modal) return;
    currentProduct = product;
    els.modalImg.src = product.imagem || 'assets/placeholder.jpg';
    els.modalTitle.textContent = product.nome;
    els.modalDesc.textContent = product.descricao || 'Delicioso bolo artesanal.';
    els.modalPrice.textContent = money(product.preco);
    els.modalUnit.textContent = product.unidade === 'kg' ? '/kg' : '';

    if (product.unidade === 'kg') {
      els.modalQty.value = '1.0';
      els.modalQty.step = '0.5';
      els.modalQty.min = '0.5';
      els.quantityLabel.textContent = 'Peso (kg)';
    } else {
      els.modalQty.value = '1';
      els.modalQty.step = '1';
      els.modalQty.min = '1';
      els.quantityLabel.textContent = 'Quantidade';
    }

    els.modal.classList.add('active');
    els.modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
  };

  const closeModal = () => {
    if (!els.modal) return;
    els.modal.classList.remove('active');
    els.modal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
    currentProduct = null;
  };

  document.addEventListener('click', async (event) => {
    const addBtn = event.target.closest('.btn-icon');
    const card = event.target.closest('.view-product');
    const removeBtn = event.target.closest('[data-remove-id]');

    if (removeBtn) {
      await cartAction({ acao: 'remover', id: removeBtn.dataset.removeId });
      await loadCart();
      return;
    }

    if (addBtn && card) {
      event.stopPropagation();
      const product = card.dataset;
      if (product.unidade === 'kg') {
        openModal(product);
      } else {
        await addToCart(product, 1);
      }
      return;
    }

    if (card) {
      openModal(card.dataset);
      return;
    }

    if (event.target === els.modal || event.target.closest('.modal-close')) {
      closeModal();
    }
  });

  els.plusBtn?.addEventListener('click', () => {
    const step = parseFloat(els.modalQty.step);
    const value = parseFloat(els.modalQty.value);
    els.modalQty.value = (value + step).toFixed(step < 1 ? 1 : 0);
  });

  els.minusBtn?.addEventListener('click', () => {
    const step = parseFloat(els.modalQty.step);
    const min = parseFloat(els.modalQty.min);
    const value = parseFloat(els.modalQty.value);
    if (value > min) els.modalQty.value = (value - step).toFixed(step < 1 ? 1 : 0);
  });

  els.addBtn?.addEventListener('click', async () => {
    if (!currentProduct) return;
    await addToCart(currentProduct, parseFloat(els.modalQty.value));
    closeModal();
  });

  els.clearCart?.addEventListener('click', async () => {
    await cartAction({ acao: 'limpar' });
    await loadCart();
    toast('Carrinho limpo.');
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeModal();
    if ((e.key === 'Enter' || e.key === ' ') && e.target.classList.contains('view-product')) {
      e.preventDefault();
      openModal(e.target.dataset);
    }
  });

  loadCart().catch(() => toast('Não foi possível carregar o carrinho.'));
});
