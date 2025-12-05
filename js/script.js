document.addEventListener('DOMContentLoaded', () => {
    const CARRINHO_URL = 'carrinho.php';
    const miniCartItems = document.getElementById('mini-cart-items');
    const miniCart = document.getElementById('mini-cart');
    const cartBadge = document.getElementById('cart-count');
    const toastContainer = document.getElementById('toast-container');

    // Modal Elements
    const modal = document.getElementById('product-modal');
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

    // --- Toast Notification ---
    function showToast(message) {
        const toast = document.createElement('div');
        toast.className = 'toast';
        toast.innerHTML = `<span>✓</span> ${message}`;
        toastContainer.appendChild(toast);
        setTimeout(() => toast.remove(), 3500);
    }

    // --- Cart Functions ---
    async function atualizarCarrinho() {
        try {
            const res = await fetch(CARRINHO_URL + '?acao=listar');
            const data = await res.json();

            let count = 0;
            let total = 0;

            if (!data || !Object.keys(data).length) {
                miniCartItems.innerHTML = '<div class="empty-cart-msg">Seu carrinho está vazio.</div>';
                if (cartBadge) cartBadge.style.display = 'none';
                return;
            }

            let html = '';
            Object.values(data).forEach(item => {
                count += 1; // Count distinct items or use item.qtd if preferred (but item.qtd can be float now)
                const qtdDisplay = item.unidade === 'kg' ? item.qtd.toFixed(1) + 'kg' : parseInt(item.qtd);
                const subtotal = item.price * item.qtd; // Note: Ensure backend sends price or calculation is done there.
                // Actually backend sends what was stored.

                html += `
                    <div class="mini-item">
                        <div class="mini-item-info">
                            <span class="mini-item-name">${item.nome}</span>
                            <span class="mini-item-meta">${qtdDisplay}</span>
                        </div>
                    </div>`;
            });

            miniCartItems.innerHTML = html;
            if (cartBadge) {
                cartBadge.innerText = count;
                cartBadge.style.display = 'flex';
            }
        } catch (e) {
            console.error("Erro ao atualizar carrinho", e);
        }
    }

    // --- Modal Logic ---
    function openModal(product) {
        currentProduct = product;

        modalImg.src = product.imagem;
        modalTitle.innerText = product.nome;
        modalDesc.innerText = product.descricao;

        const priceFormatted = parseFloat(product.preco).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
        modalPrice.innerText = priceFormatted;
        modalUnit.innerText = product.unidade === 'kg' ? '/ kg' : '';

        // Reset inputs
        if (product.unidade === 'kg') {
            quantityInput.value = "1.0";
            quantityInput.step = "0.5";
            quantityInput.min = "1.0";
            quantityLabel.innerText = "Peso (kg):";
        } else {
            quantityInput.value = "1";
            quantityInput.step = "1";
            quantityInput.min = "1";
            quantityLabel.innerText = "Quantidade:";
        }

        modal.classList.add('active');
        document.body.style.overflow = 'hidden'; // Prevent background scrolling
    }

    function closeModal() {
        modal.classList.remove('active');
        document.body.style.overflow = '';
        currentProduct = null;
    }

    // Event Delegation for "View/Add" buttons
    document.addEventListener('click', e => {
        const btn = e.target.closest('.view-product');
        if (btn) {
            e.preventDefault();
            const product = {
                id: btn.dataset.id,
                nome: btn.dataset.nome,
                preco: btn.dataset.preco,
                imagem: btn.dataset.imagem,
                descricao: btn.dataset.descricao,
                unidade: btn.dataset.unidade
            };
            openModal(product);
        }

        if (e.target === modal || e.target.closest('.modal-close')) {
            closeModal();
        }
    });

    // Quantity Controls
    increaseBtn.addEventListener('click', () => {
        let val = parseFloat(quantityInput.value);
        const step = parseFloat(quantityInput.step);
        quantityInput.value = (val + step).toFixed(step < 1 ? 1 : 0);
    });

    decreaseBtn.addEventListener('click', () => {
        let val = parseFloat(quantityInput.value);
        const step = parseFloat(quantityInput.step);
        const min = parseFloat(quantityInput.min);
        if (val > min) {
            quantityInput.value = (val - step).toFixed(step < 1 ? 1 : 0);
        }
    });

    // Add to Cart from Modal
    addToCartBtn.addEventListener('click', async () => {
        if (!currentProduct) return;

        const qtd = parseFloat(quantityInput.value);
        const formData = new FormData();
        formData.append('acao', 'adicionar');
        formData.append('id', currentProduct.id);
        formData.append('nome', currentProduct.nome);
        formData.append('preco', currentProduct.preco);
        formData.append('qtd', qtd);
        formData.append('unidade', currentProduct.unidade);

        const originalText = addToCartBtn.innerHTML;
        addToCartBtn.innerHTML = '<span class="spinner"></span> Adicionando...';
        addToCartBtn.disabled = true;

        try {
            const res = await fetch(CARRINHO_URL, {
                method: 'POST',
                body: formData
            });
            const data = await res.json();

            if (data.ok) {
                closeModal();
                showToast(`${currentProduct.nome} adicionado ao carrinho!`);
                atualizarCarrinho();

                // Show mini cart briefly
                miniCart.classList.add('active');
                setTimeout(() => miniCart.classList.remove('active'), 3000);
            }
        } catch (err) {
            console.error(err);
            alert('Erro ao adicionar produto.');
        } finally {
            addToCartBtn.innerHTML = originalText;
            addToCartBtn.disabled = false;
        }
    });

    // Clear Cart
    const clearCartBtn = document.getElementById('clear-cart');
    if (clearCartBtn) {
        clearCartBtn.addEventListener('click', async () => {
            if(confirm("Deseja realmente limpar o carrinho?")) {
                await fetch(CARRINHO_URL + '?acao=limpar');
                atualizarCarrinho();
            }
        });
    }

    // Toggle Mobile Cart
    const cartLink = document.querySelector('.cart-link');
    if(cartLink) {
        cartLink.addEventListener('click', (e) => {
            if(window.innerWidth <= 900) {
                e.preventDefault();
                miniCart.classList.toggle('active');
            }
        });
    }

    // Init
    atualizarCarrinho();
});
