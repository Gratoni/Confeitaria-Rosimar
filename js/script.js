document.addEventListener('DOMContentLoaded', () => {
    const CARRINHO_URL = 'carrinho.php';
    const miniCartItems = document.getElementById('mini-cart-items');
    const miniCart = document.getElementById('mini-cart');
    const cartBadge = document.getElementById('cart-count');
    const toastContainer = document.getElementById('toast-container');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    // Modal Elements
    const modal = document.getElementById('product-modal');
    const modalContent = modal ? modal.querySelector('.modal-content') : null;
    const modalClose = document.querySelector('.modal-close');
    const modalImg = document.getElementById('modal-img');
    const modalTitle = document.getElementById('modal-title');
    const modalDesc = document.getElementById('modal-desc');
    const modalPrice = document.getElementById('modal-price');
    const modalUnit = document.getElementById('modal-unit');
    const quantityInput = document.getElementById('modal-quantity');
    const quantityLabel = document.getElementById('quantity-label');
    const addToCartBtn = document.getElementById('modal-add-btn');
    const increaseBtn = document.getElementById('qty-plus');
    const decreaseBtn = document.getElementById('qty-minus');

    let currentProduct = null;
    let scrollbarWidth = 0;

    // --- Helper: Calculate Scrollbar Width ---
    function getScrollbarWidth() {
        return window.innerWidth - document.documentElement.clientWidth;
    }

    // --- Toast Notification ---
    function showToast(message) {
        if (!toastContainer) return;
        const toast = document.createElement('div');
        toast.className = 'toast';
        toast.innerHTML = `<span>OK</span> ${message}`;
        toastContainer.appendChild(toast);
        setTimeout(() => toast.remove(), 3500);
    }

    // --- Cart Functions ---
    async function atualizarCarrinho() {
        try {
            const res = await fetch(CARRINHO_URL + '?acao=listar');
            if (!res.ok) throw new Error('Network response was not ok');
            const data = await res.json();

            let count = 0;

            if (!data || !Object.keys(data).length) {
                if (miniCartItems) miniCartItems.innerHTML = '<div class="empty-cart-msg">Seu carrinho está vazio.</div>';
                if (cartBadge) cartBadge.style.display = 'none';
                return;
            }

            let html = '';
            Object.entries(data).forEach(([id, item]) => {
                count += 1;
                const qtdNum = parseFloat(item.qtd);
                const qtdDisplay = item.unidade === 'kg' ? qtdNum.toFixed(1) + 'kg' : parseInt(item.qtd, 10);
                const step = item.unidade === 'kg' ? 0.5 : 1;

                html += `
                    <div class="mini-item">
                        <div class="mini-item-info">
                            <span class="mini-item-name">${item.nome}</span>
                            <span class="mini-item-meta">${qtdDisplay}</span>
                        </div>
                        <div class="mini-item-actions">
                            <button class="mini-qty-btn" data-id="${id}" data-unidade="${item.unidade}" data-qtd="${qtdNum}" data-delta="${-step}">−</button>
                            <button class="mini-qty-btn" data-id="${id}" data-unidade="${item.unidade}" data-qtd="${qtdNum}" data-delta="${step}">+</button>
                            <button class="mini-remove" data-id="${id}">Remover</button>
                        </div>
                    </div>`;
            });

            if (miniCartItems) miniCartItems.innerHTML = html;
            if (cartBadge) {
                cartBadge.innerText = count;
                cartBadge.style.display = 'flex';
            }
        } catch (e) {
            console.error("Erro ao atualizar carrinho", e);
            if (miniCartItems) miniCartItems.innerHTML = '<small>Erro ao carregar carrinho.</small>';
        }
    }

    async function addToCartDirect(product, qtd) {
        const formData = new FormData();
        formData.append('acao', 'adicionar');
        formData.append('id', product.id);
        formData.append('qtd', qtd);
        formData.append('csrf_token', csrfToken);

        try {
            const res = await fetch(CARRINHO_URL, {
                method: 'POST',
                body: formData
            });

            const text = await res.text();
            let data = null;
            try {
                data = text ? JSON.parse(text) : null;
            } catch (e) {
                console.warn('Resposta não-JSON do carrinho:', text);
            }

            if (!res.ok) {
                alert('Erro: ' + (data?.msg || 'Erro no servidor.'));
                return;
            }

            if (data && data.ok) {
                showToast(`${product.nome} adicionado!`);
                atualizarCarrinho();
                if (miniCart) {
                    miniCart.classList.add('active');
                    setTimeout(() => miniCart.classList.remove('active'), 3000);
                }
            } else if (data === null) {
                // Se a resposta veio sem JSON mas o servidor respondeu OK, assume sucesso e atualiza
                showToast(`${product.nome} adicionado!`);
                atualizarCarrinho();
                if (miniCart) {
                    miniCart.classList.add('active');
                    setTimeout(() => miniCart.classList.remove('active'), 3000);
                }
            } else {
                alert('Erro: ' + ((data && data.msg) || 'Erro desconhecido'));
            }
        } catch (err) {
            console.error(err);
            alert('Erro ao adicionar produto.');
        }
    }

    // --- Modal Logic ---
    function openModal(product, isMini = false) {
        if (!modal) return;
        currentProduct = product;
        scrollbarWidth = getScrollbarWidth();

        // Toggle Mini Mode
        if (isMini) {
            modalContent.classList.add('modal-mini');
        } else {
            modalContent.classList.remove('modal-mini');
            // Load image only if not mini
            if (modalImg) {
                modalImg.src = '';
                modalImg.src = product.imagem;
                modalImg.onerror = function() { this.src = 'assets/placeholder.jpg'; };
            }
            if (modalDesc) modalDesc.innerText = product.descricao;
        }

        if (modalTitle) modalTitle.innerText = product.nome;

        const priceFormatted = parseFloat(product.preco).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
        if (modalPrice) modalPrice.innerText = priceFormatted;
        if (modalUnit) modalUnit.innerText = product.unidade === 'kg' ? '/ kg' : '';

        // Reset inputs
        if (product.unidade === 'kg') {
            if (quantityInput) {
                quantityInput.value = "1.0";
                quantityInput.step = "0.5";
                quantityInput.min = "1.0";
            }
            if (quantityLabel) quantityLabel.innerText = "Peso (kg):";
        } else {
            if (quantityInput) {
                quantityInput.value = "1";
                quantityInput.step = "1";
                quantityInput.min = "1";
            }
            if (quantityLabel) quantityLabel.innerText = "Quantidade:";
        }

        // Prevent layout shift
        document.body.style.paddingRight = `${scrollbarWidth}px`;
        document.body.style.overflow = 'hidden';

        modal.classList.add('active');
    }

    function closeModal() {
        if (!modal) return;
        modal.classList.remove('active');

        setTimeout(() => {
            document.body.style.paddingRight = '';
            document.body.style.overflow = '';
            // Reset mini class after closing
            if (modalContent) modalContent.classList.remove('modal-mini');
        }, 300);

        currentProduct = null;
    }

    // Event Delegation
    document.addEventListener('click', e => {
        // 1. Click on "Add" button (Cart Icon)
        const addBtn = e.target.closest('.btn-icon');
        if (addBtn) {
            e.preventDefault();
            e.stopPropagation();

            const card = addBtn.closest('.view-product');
            const product = {
                id: card.dataset.id,
                nome: card.dataset.nome,
                preco: card.dataset.preco,
                unidade: card.dataset.unidade,
                imagem: card.dataset.imagem,
                descricao: card.dataset.descricao
            };

            if (product.unidade === 'kg') {
                openModal(product, true);
            } else {
                addToCartDirect(product, 1);
            }
            return;
        }

        // 2. Click on Card Image or "Ver Detalhes" -> Open Full Modal
        const viewBtn = e.target.closest('.view-product');
        if (viewBtn) {
            e.preventDefault();
            const product = {
                id: viewBtn.dataset.id,
                nome: viewBtn.dataset.nome,
                preco: viewBtn.dataset.preco,
                imagem: viewBtn.dataset.imagem,
                descricao: viewBtn.dataset.descricao,
                unidade: viewBtn.dataset.unidade
            };
            openModal(product, false);
        }

        // 3. Close Modal
        if (e.target === modal || e.target.closest('.modal-close')) {
            closeModal();
        }
    });

    // Quantity Controls
    if (increaseBtn && quantityInput) {
        increaseBtn.addEventListener('click', () => {
            let val = parseFloat(quantityInput.value);
            const step = parseFloat(quantityInput.step);
            quantityInput.value = (val + step).toFixed(step < 1 ? 1 : 0);
        });
    }

    if (decreaseBtn && quantityInput) {
        decreaseBtn.addEventListener('click', () => {
            let val = parseFloat(quantityInput.value);
            const step = parseFloat(quantityInput.step);
            const min = parseFloat(quantityInput.min);
            if (val > min) {
                quantityInput.value = (val - step).toFixed(step < 1 ? 1 : 0);
            }
        });
    }

    // Add to Cart from Modal (Full or Mini)
    if (addToCartBtn) {
        addToCartBtn.addEventListener('click', async () => {
            if (!currentProduct) return;

            const qtd = parseFloat(quantityInput.value);
            const originalText = addToCartBtn.innerHTML;
            addToCartBtn.innerHTML = '<span class="spinner"></span> Adicionando...';
            addToCartBtn.disabled = true;

            try {
                const formData = new FormData();
                formData.append('acao', 'adicionar');
                formData.append('id', currentProduct.id);
                formData.append('qtd', qtd);
                formData.append('csrf_token', csrfToken);

                const res = await fetch(CARRINHO_URL, {
                    method: 'POST',
                    body: formData
                });

                const text = await res.text();
                let data = null;
                try {
                    data = text ? JSON.parse(text) : null;
                } catch (e) {
                    console.warn('Resposta não-JSON do carrinho:', text);
                }

                if (!res.ok) {
                    alert('Erro: ' + (data?.msg || 'Erro no servidor.'));
                    return;
                }

                if (data && data.ok) {
                    closeModal();
                    showToast(`${currentProduct.nome} adicionado!`);
                    atualizarCarrinho();
                    if (miniCart) {
                        miniCart.classList.add('active');
                        setTimeout(() => miniCart.classList.remove('active'), 3000);
                    }
                } else if (data === null) {
                    closeModal();
                    showToast(`${currentProduct.nome} adicionado!`);
                    atualizarCarrinho();
                    if (miniCart) {
                        miniCart.classList.add('active');
                        setTimeout(() => miniCart.classList.remove('active'), 3000);
                    }
                } else {
                    alert('Erro: ' + ((data && data.msg) || 'Erro desconhecido'));
                }
            } catch (err) {
                console.error(err);
                alert('Erro ao adicionar produto.');
            } finally {
                addToCartBtn.innerHTML = originalText;
                addToCartBtn.disabled = false;
            }
        });
    }

    // Clear Cart
    const clearCartBtn = document.getElementById('clear-cart');
    if (clearCartBtn) {
        clearCartBtn.addEventListener('click', async () => {
            if (confirm("Deseja realmente limpar o carrinho?")) {
                const formData = new FormData();
                formData.append('acao', 'limpar');
                formData.append('csrf_token', csrfToken);
                await fetch(CARRINHO_URL, {
                    method: 'POST',
                    body: formData
                });
                atualizarCarrinho();
            }
        });
    }

    // Toggle Cart (all sizes)
    const cartLink = document.querySelector('.cart-link');
    if (cartLink && miniCart) {
        cartLink.addEventListener('click', (e) => {
            e.preventDefault();
            miniCart.classList.toggle('active');
        });
    }

    // Init
    atualizarCarrinho();

    // Checkout phone mask
    const checkoutPhone = document.querySelector('form#form-order input[name="telefone"]');
    if (checkoutPhone) {
        checkoutPhone.addEventListener('input', () => {
            let v = checkoutPhone.value.replace(/\D/g, '').slice(0, 11);
            if (v.length <= 2) {
                checkoutPhone.value = v;
                return;
            }
            if (v.length <= 7) {
                checkoutPhone.value = `(${v.slice(0, 2)}) ${v.slice(2)}`;
                return;
            }
            checkoutPhone.value = `(${v.slice(0, 2)}) ${v.slice(2, 7)}-${v.slice(7)}`;
        });
    }

    // Mini-cart actions
    if (miniCartItems) {
        miniCartItems.addEventListener('click', async (e) => {
            const removeBtn = e.target.closest('.mini-remove');
            const updateBtn = e.target.closest('.mini-qty-btn');

            if (removeBtn) {
                const id = removeBtn.dataset.id;
                const formData = new FormData();
                formData.append('acao', 'remover');
                formData.append('id', id);
                formData.append('csrf_token', csrfToken);
                await fetch(CARRINHO_URL, { method: 'POST', body: formData });
                atualizarCarrinho();
                return;
            }

            if (updateBtn) {
                const id = updateBtn.dataset.id;
                const unidade = updateBtn.dataset.unidade;
                const atual = parseFloat(updateBtn.dataset.qtd);
                const delta = parseFloat(updateBtn.dataset.delta);
                let novo = atual + delta;

                if (unidade !== 'kg') {
                    novo = Math.max(1, Math.round(novo));
                } else {
                    novo = Math.max(0.5, novo);
                }

                const formData = new FormData();
                formData.append('acao', 'atualizar');
                formData.append('id', id);
                formData.append('qtd', novo);
                formData.append('csrf_token', csrfToken);
                await fetch(CARRINHO_URL, { method: 'POST', body: formData });
                atualizarCarrinho();
            }
        });
    }

    // Checkout clear cart
    const clearCheckoutBtn = document.getElementById('clear-cart-checkout');
    if (clearCheckoutBtn) {
        clearCheckoutBtn.addEventListener('click', async () => {
            if (confirm("Deseja realmente limpar o carrinho?")) {
                const formData = new FormData();
                formData.append('acao', 'limpar');
                formData.append('csrf_token', csrfToken);
                await fetch(CARRINHO_URL, { method: 'POST', body: formData });
                window.location.reload();
            }
        });
    }
});
