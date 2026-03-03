document.addEventListener('DOMContentLoaded', () => {
    const CARRINHO_URL = 'carrinho.php';
    const miniCartItems = document.getElementById('mini-cart-items');
    const miniCart = document.getElementById('mini-cart');
    const cartLink = document.querySelector('.cart-link');
    const cartBadge = document.getElementById('cart-count');
    let toastContainer = document.getElementById('toast-container');
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
    const catalogGrid = document.querySelector('.bolos-grid');
    const catalogCards = Array.from(document.querySelectorAll('.bolo-card'));
    const catalogSearchInput = document.getElementById('catalog-search');
    const catalogUnitFilter = document.getElementById('catalog-unit-filter');
    const catalogCategoryFilter = document.getElementById('catalog-category-filter');
    const catalogSort = document.getElementById('catalog-sort');
    const catalogCount = document.getElementById('catalog-count');
    const catalogEmptyState = document.getElementById('catalog-empty-state');
    const checkoutForm = document.getElementById('form-order');

    const focusableSelector = [
        'a[href]',
        'button:not([disabled])',
        'input:not([disabled])',
        'select:not([disabled])',
        'textarea:not([disabled])',
        '[tabindex]:not([tabindex="-1"])'
    ].join(', ');

    let currentProduct = null;
    let scrollbarWidth = 0;
    let lastFocusedElement = null;
    let miniCartTimer = null;
    let catalogInputTimer = null;
    let confirmBackdrop = null;
    let pendingConfirmResolve = null;
    let lastConfirmFocusedElement = null;
    let cartSnapshot = {};
    let checkoutBeginTracked = false;
    let catalogListTracked = false;

    function getScrollbarWidth() {
        return window.innerWidth - document.documentElement.clientWidth;
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function ensureToastContainer() {
        if (toastContainer) return toastContainer;
        toastContainer = document.createElement('div');
        toastContainer.id = 'toast-container';
        toastContainer.className = 'toast-container';
        toastContainer.setAttribute('aria-live', 'polite');
        toastContainer.setAttribute('aria-atomic', 'true');
        document.body.appendChild(toastContainer);
        return toastContainer;
    }

    function setupImageFallbacks() {
        const images = document.querySelectorAll('img[data-fallback-src]');
        images.forEach(image => {
            if (!(image instanceof HTMLImageElement)) return;
            image.addEventListener('error', () => {
                const fallbackSrc = image.getAttribute('data-fallback-src');
                if (!fallbackSrc || image.dataset.fallbackApplied === '1') return;
                image.dataset.fallbackApplied = '1';
                image.src = fallbackSrc;
            });
        });
    }

    function trackEvent(eventName, payload = {}) {
        if (!eventName) return;
        window.dataLayer = window.dataLayer || [];
        window.dataLayer.push({
            event: eventName,
            ...payload,
            ts: new Date().toISOString()
        });
    }

    function toNumber(value, fallback = 0) {
        const raw = typeof value === 'string' ? value.replace(',', '.') : value;
        const parsed = Number(raw);
        return Number.isFinite(parsed) ? parsed : fallback;
    }

    function buildEcommerceItem(product, quantity = 1, index = 1) {
        const unit = String(product?.unidade || 'un');
        const category = String(product?.categoria || 'Bolos');
        return {
            item_id: String(product?.id ?? ''),
            item_name: String(product?.nome || 'Produto'),
            item_brand: 'Confeitaria Rosimar',
            item_category: category,
            item_variant: unit === 'kg' ? 'Por kg' : 'Por unidade',
            index,
            price: toNumber(product?.preco, 0),
            quantity: toNumber(quantity, 1)
        };
    }

    function getEcommerceItemsFromCartSnapshot() {
        return Object.entries(cartSnapshot || {}).map(([id, item], index) => buildEcommerceItem({
            id,
            nome: item?.nome,
            preco: item?.preco,
            unidade: item?.unidade,
            categoria: item?.categoria || 'Bolos'
        }, item?.qtd, index + 1));
    }

    function calculateItemsValue(items) {
        const value = (items || []).reduce((sum, item) => {
            return sum + (toNumber(item?.price, 0) * toNumber(item?.quantity, 0));
        }, 0);
        return Number(value.toFixed(2));
    }

    function pushEcommerceEvent(eventName, ecommercePayload, extraPayload = {}) {
        window.dataLayer = window.dataLayer || [];
        window.dataLayer.push({ ecommerce: null });
        window.dataLayer.push({
            event: eventName,
            ecommerce: ecommercePayload,
            ...extraPayload
        });
    }

    function pushEcommerceItemsEvent(eventName, items, options = {}) {
        const validItems = (items || []).filter(item => item && item.item_id);
        if (!validItems.length) return;

        const ecommerce = {
            currency: 'BRL',
            items: validItems
        };

        if (options.includeValue !== false) {
            ecommerce.value = calculateItemsValue(validItems);
        }
        if (options.itemListName) {
            ecommerce.item_list_name = options.itemListName;
        }
        if (options.itemListId) {
            ecommerce.item_list_id = options.itemListId;
        }

        pushEcommerceEvent(eventName, ecommerce, options.extraPayload || {});
    }

    function trackCatalogListViewIfNeeded() {
        if (catalogListTracked || !catalogCards.length) return;
        const visibleCards = catalogCards.filter(card => !card.hidden);
        if (!visibleCards.length) return;

        const items = visibleCards.slice(0, 20).map((card, index) => {
            const product = getProductFromCard(card);
            return buildEcommerceItem(product, 1, index + 1);
        });

        pushEcommerceItemsEvent('view_item_list', items, {
            includeValue: false,
            itemListName: 'Cardapio de Bolos',
            itemListId: 'menu-bolos'
        });
        catalogListTracked = true;
    }

    function trackBeginCheckoutIfNeeded() {
        if (checkoutBeginTracked || !checkoutForm) return;
        const items = getEcommerceItemsFromCartSnapshot();
        if (!items.length) return;
        pushEcommerceItemsEvent('begin_checkout', items);
        checkoutBeginTracked = true;
    }

    function ensureConfirmDialog() {
        if (confirmBackdrop) return confirmBackdrop;

        const wrapper = document.createElement('div');
        wrapper.className = 'confirm-backdrop';
        wrapper.setAttribute('aria-hidden', 'true');
        wrapper.innerHTML = `
            <div class="confirm-dialog" role="dialog" aria-modal="true" aria-labelledby="confirm-title" aria-describedby="confirm-message">
                <h3 id="confirm-title" class="confirm-title">Confirmar acao</h3>
                <p id="confirm-message" class="confirm-message"></p>
                <div class="confirm-actions">
                    <button type="button" class="btn btn-outline confirm-btn" data-confirm-action="cancel">Cancelar</button>
                    <button type="button" class="btn confirm-btn" data-confirm-action="ok">Confirmar</button>
                </div>
            </div>
        `;

        document.body.appendChild(wrapper);
        confirmBackdrop = wrapper;

        wrapper.addEventListener('click', event => {
            const target = event.target instanceof Element ? event.target : null;
            if (!target) return;

            if (target === wrapper) {
                closeConfirmDialog(false);
                return;
            }

            const actionBtn = target.closest('[data-confirm-action]');
            if (!actionBtn) return;
            closeConfirmDialog(actionBtn.getAttribute('data-confirm-action') === 'ok');
        });

        return confirmBackdrop;
    }

    function closeConfirmDialog(result) {
        if (!confirmBackdrop) return;
        confirmBackdrop.classList.remove('active');
        confirmBackdrop.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';

        if (pendingConfirmResolve) {
            pendingConfirmResolve(Boolean(result));
            pendingConfirmResolve = null;
        }

        if (lastConfirmFocusedElement && document.contains(lastConfirmFocusedElement)) {
            lastConfirmFocusedElement.focus();
        }
        lastConfirmFocusedElement = null;
    }

    function showConfirmDialog(message) {
        const backdrop = ensureConfirmDialog();
        const title = backdrop.querySelector('#confirm-title');
        const text = backdrop.querySelector('#confirm-message');
        const primaryBtn = backdrop.querySelector('[data-confirm-action="ok"]');

        if (title) title.textContent = 'Confirmar acao';
        if (text) text.textContent = message;

        lastConfirmFocusedElement = document.activeElement instanceof HTMLElement ? document.activeElement : null;
        backdrop.classList.add('active');
        backdrop.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        window.requestAnimationFrame(() => {
            if (primaryBtn instanceof HTMLElement) primaryBtn.focus();
        });

        return new Promise(resolve => {
            pendingConfirmResolve = resolve;
        });
    }

    function normalizeText(value) {
        return String(value ?? '')
            .toLocaleLowerCase('pt-BR')
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '');
    }

    function applyCatalogControls() {
        if (!catalogGrid || !catalogCards.length) return;

        const query = normalizeText(catalogSearchInput?.value || '');
        const unit = catalogUnitFilter?.value || 'all';
        const category = catalogCategoryFilter?.value || 'all';
        const sort = catalogSort?.value || 'featured';
        const visibleCards = [];

        catalogCards.forEach(card => {
            const name = normalizeText(card.dataset.nome);
            const description = normalizeText(card.dataset.descricao);
            const cardUnit = card.dataset.unidade || 'un';
            const cardCategory = card.dataset.categoria || 'tradicional';
            const matchesQuery = !query || name.includes(query) || description.includes(query);
            const matchesUnit = unit === 'all' || cardUnit === unit;
            const matchesCategory = category === 'all' || cardCategory === category;
            const isVisible = matchesQuery && matchesUnit && matchesCategory;

            card.hidden = !isVisible;
            card.setAttribute('aria-hidden', String(!isVisible));
            if (isVisible) visibleCards.push(card);
        });

        const sortedCards = [...visibleCards].sort((cardA, cardB) => {
            const priceA = parseFloat(cardA.dataset.preco || '0');
            const priceB = parseFloat(cardB.dataset.preco || '0');
            const nameA = String(cardA.dataset.nome || '');
            const nameB = String(cardB.dataset.nome || '');

            if (sort === 'price-asc') return priceA - priceB;
            if (sort === 'price-desc') return priceB - priceA;
            if (sort === 'name-asc') return nameA.localeCompare(nameB, 'pt-BR');
            return 0;
        });

        sortedCards.forEach(card => catalogGrid.appendChild(card));

        if (catalogCount) {
            const total = visibleCards.length;
            catalogCount.textContent = `${total} ${total === 1 ? 'bolo encontrado' : 'bolos encontrados'}`;
        }

        if (catalogEmptyState) {
            catalogEmptyState.hidden = visibleCards.length > 0;
        }
    }

    function isModalOpen() {
        return Boolean(modal && modal.classList.contains('active'));
    }

    function isMiniCartOpen() {
        return Boolean(miniCart && miniCart.classList.contains('active'));
    }

    function setMiniCartOpen(open) {
        if (!miniCart) return;
        miniCart.classList.toggle('active', open);
        miniCart.setAttribute('aria-hidden', String(!open));
        if (cartLink) {
            cartLink.setAttribute('aria-expanded', String(open));
        }
    }

    function showMiniCartTemporarily(durationMs = 3000) {
        if (!miniCart) return;
        setMiniCartOpen(true);
        if (miniCartTimer) clearTimeout(miniCartTimer);
        miniCartTimer = window.setTimeout(() => {
            setMiniCartOpen(false);
        }, durationMs);
    }

    function getModalFocusableElements() {
        if (!modalContent) return [];
        return Array.from(modalContent.querySelectorAll(focusableSelector))
            .filter(el => !el.hasAttribute('disabled') && el.getAttribute('aria-hidden') !== 'true');
    }

    function trapModalFocus(event) {
        if (!isModalOpen()) return;
        const focusables = getModalFocusableElements();
        if (!focusables.length) {
            event.preventDefault();
            modalContent?.focus();
            return;
        }

        const first = focusables[0];
        const last = focusables[focusables.length - 1];
        const active = document.activeElement;

        if (event.shiftKey && active === first) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && active === last) {
            event.preventDefault();
            first.focus();
        }
    }

    function showToast(message, type = 'success') {
        const container = ensureToastContainer();
        const toast = document.createElement('div');
        toast.className = type === 'success' ? 'toast' : `toast toast--${type}`;
        toast.setAttribute('role', 'status');

        const icon = document.createElement('span');
        icon.setAttribute('aria-hidden', 'true');
        icon.textContent = type === 'error' ? 'ER' : 'OK';
        toast.appendChild(icon);
        toast.appendChild(document.createTextNode(` ${message}`));

        container.appendChild(toast);
        setTimeout(() => toast.remove(), 3500);
    }

    function getProductFromCard(card) {
        return {
            id: card.dataset.id,
            nome: card.dataset.nome,
            preco: card.dataset.preco,
            imagem: card.dataset.imagem,
            descricao: card.dataset.descricao,
            unidade: card.dataset.unidade,
            categoria: card.dataset.categoria || 'Bolos'
        };
    }

    async function atualizarCarrinho() {
        try {
            const res = await fetch(CARRINHO_URL + '?acao=listar');
            if (!res.ok) throw new Error('Network response was not ok');
            const data = await res.json();
            cartSnapshot = (data && typeof data === 'object') ? data : {};

            let count = 0;

            if (!Object.keys(cartSnapshot).length) {
                cartSnapshot = {};
                if (miniCartItems) miniCartItems.innerHTML = '<div class="empty-cart-msg">Seu carrinho está vazio.</div>';
                if (cartBadge) {
                    cartBadge.style.display = 'none';
                    cartBadge.setAttribute('aria-label', 'Carrinho vazio');
                }
                trackBeginCheckoutIfNeeded();
                return;
            }

            let html = '';
            Object.entries(cartSnapshot).forEach(([id, item]) => {
                count += 1;
                const qtdNum = parseFloat(item.qtd);
                const qtdDisplay = item.unidade === 'kg' ? `${qtdNum.toFixed(1)}kg` : parseInt(item.qtd, 10);
                const step = item.unidade === 'kg' ? 0.5 : 1;
                const safeNome = escapeHtml(item.nome);

                html += `
                    <div class="mini-item">
                        <div class="mini-item-info">
                            <span class="mini-item-name">${safeNome}</span>
                            <span class="mini-item-meta">${qtdDisplay}</span>
                        </div>
                        <div class="mini-item-actions">
                            <button type="button" class="mini-qty-btn" aria-label="Diminuir quantidade de ${safeNome}" data-id="${id}" data-unidade="${item.unidade}" data-qtd="${qtdNum}" data-delta="${-step}">−</button>
                            <button type="button" class="mini-qty-btn" aria-label="Aumentar quantidade de ${safeNome}" data-id="${id}" data-unidade="${item.unidade}" data-qtd="${qtdNum}" data-delta="${step}">+</button>
                            <button type="button" class="mini-remove" aria-label="Remover ${safeNome} do carrinho" data-id="${id}">Remover</button>
                        </div>
                    </div>`;
            });

            if (miniCartItems) miniCartItems.innerHTML = html;
            if (cartBadge) {
                cartBadge.innerText = String(count);
                cartBadge.style.display = 'flex';
                cartBadge.setAttribute('aria-label', `${count} ${count === 1 ? 'item' : 'itens'} no carrinho`);
                cartBadge.classList.remove('pulse');
                void cartBadge.offsetWidth;
                cartBadge.classList.add('pulse');
            }
            trackBeginCheckoutIfNeeded();
        } catch (e) {
            console.error('Erro ao atualizar carrinho', e);
            if (miniCartItems) miniCartItems.innerHTML = '<small>Erro ao carregar carrinho.</small>';
        }
    }

    async function cartApi(acao, payload = {}) {
        const formData = new FormData();
        formData.append('acao', acao);
        formData.append('csrf_token', csrfToken);
        Object.entries(payload).forEach(([key, val]) => formData.append(key, val));

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
                return { success: false, msg: data?.msg || 'Erro no servidor.', data: data };
            }

            if (data && data.ok) {
                return { success: true, msg: data.msg };
            } else if (data === null) {
                return { success: true, msg: 'Operação concluída' };
            }
            return { success: false, msg: data?.msg || 'Erro desconhecido', data: data };
        } catch (err) {
            console.error(err);
            return { success: false, msg: 'Erro de conexão.' };
        }
    }

    async function addToCartDirect(product, qtd) {
        const result = await cartApi('adicionar', { id: product.id, qtd: qtd });
        if (result.success) {
            showToast(`${product.nome} adicionado!`);
            pushEcommerceItemsEvent('add_to_cart', [
                buildEcommerceItem(product, qtd, 1)
            ], {
                extraPayload: {
                    source: 'quick_add'
                }
            });
            await atualizarCarrinho();
            showMiniCartTemporarily();
        } else {
            showToast(`Erro: ${result.msg}`, 'error');
        }
    }

    function openModal(product, isMini = false) {
        if (!modal || !modalContent) return;
        lastFocusedElement = document.activeElement instanceof HTMLElement ? document.activeElement : null;
        currentProduct = product;
        scrollbarWidth = getScrollbarWidth();
        pushEcommerceItemsEvent('view_item', [
            buildEcommerceItem(product, 1, 1)
        ], {
            includeValue: false,
            extraPayload: {
                mode: isMini ? 'quick_quantity' : 'full'
            }
        });

        if (isMini) {
            modalContent.classList.add('modal-mini');
        } else {
            modalContent.classList.remove('modal-mini');
            if (modalImg) {
                modalImg.src = '';
                modalImg.src = product.imagem;
                modalImg.onerror = function () {
                    this.src = 'assets/placeholder.jpg';
                };
            }
            if (modalDesc) modalDesc.innerText = product.descricao;
        }

        if (modalTitle) modalTitle.innerText = product.nome;

        const priceFormatted = parseFloat(product.preco).toLocaleString('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        });
        if (modalPrice) modalPrice.innerText = priceFormatted;
        if (modalUnit) modalUnit.innerText = product.unidade === 'kg' ? '/ kg' : '';

        if (product.unidade === 'kg') {
            if (quantityInput) {
                quantityInput.value = '1.0';
                quantityInput.step = '0.5';
                quantityInput.min = '0.5';
            }
            if (quantityLabel) quantityLabel.innerText = 'Peso (kg):';
        } else {
            if (quantityInput) {
                quantityInput.value = '1';
                quantityInput.step = '1';
                quantityInput.min = '1';
            }
            if (quantityLabel) quantityLabel.innerText = 'Quantidade:';
        }

        document.body.style.paddingRight = `${scrollbarWidth}px`;
        document.body.style.overflow = 'hidden';

        modal.classList.add('active');
        modal.setAttribute('aria-hidden', 'false');
        modalContent.setAttribute('aria-hidden', 'false');

        if (isMiniCartOpen()) {
            setMiniCartOpen(false);
        }

        window.requestAnimationFrame(() => {
            (modalClose || addToCartBtn || modalContent).focus();
        });
    }

    function closeModal(options = {}) {
        if (!modal) return;
        const { restoreFocus = true } = options;

        modal.classList.remove('active');
        modal.setAttribute('aria-hidden', 'true');
        if (modalContent) modalContent.setAttribute('aria-hidden', 'true');

        setTimeout(() => {
            document.body.style.paddingRight = '';
            document.body.style.overflow = '';
            if (modalContent) modalContent.classList.remove('modal-mini');

            if (restoreFocus && lastFocusedElement && document.contains(lastFocusedElement)) {
                lastFocusedElement.focus();
            }
            lastFocusedElement = null;
        }, 300);

        currentProduct = null;
    }

    document.addEventListener('click', e => {
        const target = e.target instanceof Element ? e.target : null;
        if (!target) return;

        const addBtn = target.closest('.btn-icon');
        if (addBtn) {
            e.preventDefault();
            e.stopPropagation();

            const card = addBtn.closest('.view-product');
            if (!card) return;
            const product = getProductFromCard(card);
            pushEcommerceItemsEvent('select_item', [
                buildEcommerceItem(product, 1, 1)
            ], {
                includeValue: false,
                itemListName: 'Cardapio de Bolos',
                itemListId: 'menu-bolos',
                extraPayload: {
                    source: 'card_add_button'
                }
            });

            if (product.unidade === 'kg') {
                openModal(product, true);
            } else {
                addToCartDirect(product, 1);
            }
            return;
        }

        if (target === modal || target.closest('.modal-close')) {
            closeModal();
            return;
        }

        const viewBtn = target.closest('.view-product');
        if (viewBtn) {
            e.preventDefault();
            const product = getProductFromCard(viewBtn);
            pushEcommerceItemsEvent('select_item', [
                buildEcommerceItem(product, 1, 1)
            ], {
                includeValue: false,
                itemListName: 'Cardapio de Bolos',
                itemListId: 'menu-bolos',
                extraPayload: {
                    source: 'card_open_details'
                }
            });
            openModal(product, false);
            return;
        }

        if (miniCart && isMiniCartOpen() && !target.closest('#mini-cart') && !target.closest('.cart-link')) {
            setMiniCartOpen(false);
        }
    });

    document.addEventListener('keydown', e => {
        if (confirmBackdrop && confirmBackdrop.classList.contains('active') && e.key === 'Escape') {
            e.preventDefault();
            closeConfirmDialog(false);
            return;
        }

        if (isModalOpen()) {
            if (e.key === 'Escape') {
                e.preventDefault();
                closeModal();
                return;
            }
            if (e.key === 'Tab') {
                trapModalFocus(e);
                return;
            }
        } else if (e.key === 'Escape' && isMiniCartOpen()) {
            e.preventDefault();
            setMiniCartOpen(false);
            return;
        }

        const target = e.target instanceof Element ? e.target : null;
        const card = target?.closest('.view-product');
        if (!card) return;

        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            const product = getProductFromCard(card);
            pushEcommerceItemsEvent('select_item', [
                buildEcommerceItem(product, 1, 1)
            ], {
                includeValue: false,
                itemListName: 'Cardapio de Bolos',
                itemListId: 'menu-bolos',
                extraPayload: {
                    source: 'keyboard_open_details'
                }
            });
            openModal(product, false);
        }
    });

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

    if (addToCartBtn) {
        addToCartBtn.addEventListener('click', async () => {
            if (!currentProduct || !quantityInput) return;

            const qtd = parseFloat(quantityInput.value);
            const originalText = addToCartBtn.innerHTML;
            addToCartBtn.innerHTML = '<span class="spinner"></span> Adicionando...';
            addToCartBtn.disabled = true;

            try {
                const result = await cartApi('adicionar', { id: currentProduct.id, qtd: qtd });
                if (result.success) {
                    closeModal();
                    showToast(`${currentProduct.nome} adicionado!`);
                    pushEcommerceItemsEvent('add_to_cart', [
                        buildEcommerceItem(currentProduct, qtd, 1)
                    ], {
                        extraPayload: {
                            source: 'modal'
                        }
                    });
                    await atualizarCarrinho();
                    showMiniCartTemporarily();
                } else {
                    showToast(`Erro: ${result.msg}`, 'error');
                }
            } catch (err) {
                console.error(err);
                showToast('Erro ao adicionar produto.', 'error');
            } finally {
                addToCartBtn.innerHTML = originalText;
                addToCartBtn.disabled = false;
            }
        });
    }

    const clearCartBtn = document.getElementById('clear-cart');
    if (clearCartBtn) {
        clearCartBtn.addEventListener('click', async () => {
            const confirmed = await showConfirmDialog('Deseja realmente limpar o carrinho?');
            if (confirmed) {
                const previousItems = getEcommerceItemsFromCartSnapshot();
                const result = await cartApi('limpar');
                if (result.success) {
                    if (previousItems.length) {
                        pushEcommerceItemsEvent('remove_from_cart', previousItems);
                    }
                    await atualizarCarrinho();
                    setMiniCartOpen(false);
                    showToast('Carrinho limpo com sucesso.');
                    trackEvent('clear_cart', { context: 'mini_cart' });
                } else {
                    showToast(`Erro: ${result.msg}`, 'error');
                }
            }
        });
    }

    if (cartLink && miniCart) {
        cartLink.addEventListener('click', e => {
            e.preventDefault();
            const nextState = !isMiniCartOpen();
            setMiniCartOpen(nextState);
            trackEvent('mini_cart_toggle', { open: nextState });
            if (nextState) {
                const items = getEcommerceItemsFromCartSnapshot();
                if (items.length) {
                    pushEcommerceItemsEvent('view_cart', items);
                }
            }
        });
    }

    setupImageFallbacks();
    setMiniCartOpen(false);
    atualizarCarrinho();

    if (catalogSearchInput || catalogUnitFilter || catalogCategoryFilter || catalogSort) {
        applyCatalogControls();
        trackCatalogListViewIfNeeded();

        if (catalogSearchInput) {
            catalogSearchInput.addEventListener('input', () => {
                if (catalogInputTimer) clearTimeout(catalogInputTimer);
                catalogInputTimer = window.setTimeout(() => {
                    applyCatalogControls();
                    trackEvent('catalog_search', {
                        query_length: catalogSearchInput.value.trim().length
                    });
                }, 100);
            });
        }

        if (catalogUnitFilter) {
            catalogUnitFilter.addEventListener('change', () => {
                applyCatalogControls();
                trackEvent('catalog_filter_unit', { unit: catalogUnitFilter.value });
            });
        }

        if (catalogCategoryFilter) {
            catalogCategoryFilter.addEventListener('change', () => {
                applyCatalogControls();
                trackEvent('catalog_filter_category', { category: catalogCategoryFilter.value });
            });
        }

        if (catalogSort) {
            catalogSort.addEventListener('change', () => {
                applyCatalogControls();
                trackEvent('catalog_sort', { sort: catalogSort.value });
            });
        }
    }

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

    if (checkoutForm) {
        checkoutForm.addEventListener('submit', () => {
            trackBeginCheckoutIfNeeded();
            trackEvent('checkout_submit', { source: 'checkout_form' });
            pushEcommerceEvent('generate_lead', {
                currency: 'BRL',
                value: calculateItemsValue(getEcommerceItemsFromCartSnapshot()),
                items: getEcommerceItemsFromCartSnapshot()
            }, {
                lead_type: 'whatsapp_order'
            });
            const submitBtn = checkoutForm.querySelector('button[type="submit"]');
            if (submitBtn instanceof HTMLButtonElement) {
                submitBtn.disabled = true;
                submitBtn.innerText = 'Enviando...';
            }
        });
    }

    if (miniCartItems) {
        miniCartItems.addEventListener('click', async e => {
            const target = e.target instanceof Element ? e.target : null;
            if (!target) return;

            const removeBtn = target.closest('.mini-remove');
            const updateBtn = target.closest('.mini-qty-btn');

            if (removeBtn) {
                const id = removeBtn.dataset.id;
                const removedItem = cartSnapshot[id];
                const result = await cartApi('remover', { id: id });
                if (result.success) {
                    if (removedItem) {
                        pushEcommerceItemsEvent('remove_from_cart', [
                            buildEcommerceItem({
                                id,
                                nome: removedItem.nome,
                                preco: removedItem.preco,
                                unidade: removedItem.unidade
                            }, removedItem.qtd, 1)
                        ]);
                    }
                    await atualizarCarrinho();
                } else {
                    showToast(`Erro: ${result.msg}`, 'error');
                }
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

                const snapshotItem = cartSnapshot[id];
                const result = await cartApi('atualizar', { id: id, qtd: novo });
                if (result.success) {
                    const deltaQty = Number((novo - atual).toFixed(3));
                    const baseItem = buildEcommerceItem({
                        id,
                        nome: snapshotItem?.nome || 'Produto',
                        preco: snapshotItem?.preco || 0,
                        unidade: unidade
                    }, Math.abs(deltaQty), 1);

                    if (deltaQty > 0) {
                        pushEcommerceItemsEvent('add_to_cart', [baseItem], {
                            extraPayload: {
                                source: 'mini_cart_quantity'
                            }
                        });
                    } else if (deltaQty < 0) {
                        pushEcommerceItemsEvent('remove_from_cart', [baseItem], {
                            extraPayload: {
                                source: 'mini_cart_quantity'
                            }
                        });
                    }

                    await atualizarCarrinho();
                } else {
                    showToast(`Erro: ${result.msg}`, 'error');
                }
            }
        });
    }

    const clearCheckoutBtn = document.getElementById('clear-cart-checkout');
    if (clearCheckoutBtn) {
        clearCheckoutBtn.addEventListener('click', async () => {
            const confirmed = await showConfirmDialog('Deseja realmente limpar o carrinho?');
            if (confirmed) {
                const previousItems = getEcommerceItemsFromCartSnapshot();
                const result = await cartApi('limpar');
                if (result.success) {
                    if (previousItems.length) {
                        pushEcommerceItemsEvent('remove_from_cart', previousItems);
                    }
                    trackEvent('clear_cart', { context: 'checkout' });
                    window.location.reload();
                } else {
                    showToast(`Erro: ${result.msg}`, 'error');
                }
            }
        });
    }
});
