(() => {
  const storageKey = 'sazulis_cart_v2';
  const cartCount = document.getElementById('cart-count');
  const checkoutLines = document.getElementById('checkout-lines');
  const checkoutForm = document.getElementById('checkout-form');
  const feedback = document.getElementById('checkout-feedback');

  const loadCart = () => {
    try {
      return JSON.parse(localStorage.getItem(storageKey) || '[]');
    } catch {
      return [];
    }
  };

  const saveCart = (cart) => localStorage.setItem(storageKey, JSON.stringify(cart));

  const refreshCount = () => {
    const cart = loadCart();
    const qty = cart.reduce((acc, item) => acc + item.qty, 0);
    if (cartCount) cartCount.textContent = String(qty);
  };

  const renderCheckout = () => {
    if (!checkoutLines) return;
    const cart = loadCart();
    if (!cart.length) {
      checkoutLines.innerHTML = '<p>Ton panier est vide. Ajoute des produits avant de commander.</p>';
      return;
    }

    let total = 0;
    checkoutLines.innerHTML = cart
      .map((item) => {
        const lineTotal = item.price * item.qty;
        total += lineTotal;
        return `<div class="product-row"><span>${item.name} x${item.qty}</span><strong>${lineTotal.toFixed(2)} EUR</strong></div>`;
      })
      .join('');

    checkoutLines.innerHTML += `<hr><div class="product-row"><span>Total</span><strong>${total.toFixed(2)} EUR</strong></div>`;
  };

  document.querySelectorAll('.add-to-cart').forEach((button) => {
    button.addEventListener('click', () => {
      const id = Number(button.dataset.id || '0');
      const name = button.dataset.name || 'Product';
      const price = Number(button.dataset.price || '0');
      const cart = loadCart();
      const existing = cart.find((item) => item.id === id);
      if (existing) {
        existing.qty += 1;
      } else {
        cart.push({ id, name, price, qty: 1 });
      }
      saveCart(cart);
      refreshCount();
      button.textContent = 'Ajoute';
      setTimeout(() => {
        button.textContent = 'Ajouter';
      }, 800);
    });
  });

  if (checkoutForm) {
    checkoutForm.addEventListener('submit', async (event) => {
      event.preventDefault();
      const formData = new FormData(checkoutForm);
      const cart = loadCart();

      if (!cart.length) {
        feedback.textContent = 'Panier vide.';
        return;
      }

      const payload = {
        email: String(formData.get('email') || ''),
        name: String(formData.get('name') || ''),
        address: String(formData.get('address') || ''),
        cart,
      };

      try {
        const res = await fetch(`${window.SAZULIS_BASE}/api/orders`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload),
        });
        const data = await res.json();

        if (!res.ok) {
          feedback.textContent = data.message || 'Erreur checkout';
          return;
        }

        const totalInfo = typeof data.total === 'number' ? ` Total ${data.total.toFixed(2)} EUR.` : '';
        feedback.textContent = `Commande validee: ${data.orderId}. ETA ${data.eta}.${totalInfo}`;
        saveCart([]);
        refreshCount();
        renderCheckout();
        if (window.SAZULIS_USER) {
          const addressField = checkoutForm.querySelector('textarea[name="address"]');
          if (addressField) addressField.value = '';
        } else {
          checkoutForm.reset();
        }
      } catch {
        feedback.textContent = 'Service indisponible.';
      }
    });
  }

  document.getElementById('cart-chip')?.addEventListener('click', () => {
    window.location.href = `${window.SAZULIS_BASE}/checkout`;
  });

  const portfolioTrack = document.getElementById('portfolio-track');
  const portfolioPrev = document.getElementById('portfolio-prev');
  const portfolioNext = document.getElementById('portfolio-next');
  if (portfolioTrack && portfolioPrev && portfolioNext) {
    const step = 240;
    portfolioPrev.addEventListener('click', () => {
      portfolioTrack.scrollBy({ left: -step, behavior: 'smooth' });
    });
    portfolioNext.addEventListener('click', () => {
      portfolioTrack.scrollBy({ left: step, behavior: 'smooth' });
    });
  }

  const catalogGrid = document.querySelector('.products-grid');
  const badgeFilter = document.getElementById('catalog-badge-filter');
  const sortSelect = document.getElementById('catalog-sort');
  if (catalogGrid && badgeFilter && sortSelect) {
    const cards = Array.from(catalogGrid.querySelectorAll('.product-card'));

    const sortCards = (visibleCards, mode) => {
      switch (mode) {
        case 'price-asc':
          return visibleCards.sort((a, b) => Number(a.dataset.price || '0') - Number(b.dataset.price || '0'));
        case 'price-desc':
          return visibleCards.sort((a, b) => Number(b.dataset.price || '0') - Number(a.dataset.price || '0'));
        case 'name-asc':
          return visibleCards.sort((a, b) => (a.dataset.name || '').localeCompare(b.dataset.name || '', 'fr'));
        default:
          return visibleCards.sort((a, b) => cards.indexOf(a) - cards.indexOf(b));
      }
    };

    const applyCatalogControls = () => {
      const selectedBadge = (badgeFilter.value || '').trim();
      cards.forEach((card) => {
        const tags = (card.dataset.badges || '').split('|').filter(Boolean);
        const isVisible = selectedBadge === '' || tags.includes(selectedBadge);
        card.style.display = isVisible ? '' : 'none';
      });

      const visibleCards = cards.filter((card) => card.style.display !== 'none');
      sortCards(visibleCards, sortSelect.value || 'default').forEach((card) => {
        catalogGrid.appendChild(card);
      });
    };

    badgeFilter.addEventListener('change', applyCatalogControls);
    sortSelect.addEventListener('change', applyCatalogControls);
  }

  refreshCount();
  renderCheckout();
})();
