/* =========================================================
   E-SHOPPING — UI Enhancements
   Toasts, animated confirm dialogs, cart badge bounce,
   card fade-in, and button ripple effects.

   Usage: drop this file next to style.css and add
       <script src="site-enhancements.js"></script>
   right before </body> on cart.php, products.php and
   product.php (products.php can keep its products.js too —
   just add this as an extra line).
   ========================================================= */
(function () {
  'use strict';

  /* =========================================================
     1. Inject styles (no changes to style.css needed)
     ========================================================= */
  const css = `
    #toast-stack {
      position: fixed;
      bottom: 20px;
      right: 20px;
      display: flex;
      flex-direction: column;
      gap: 10px;
      z-index: 9999;
    }
    .toast {
      display: flex;
      align-items: center;
      gap: 10px;
      background: #fff;
      color: #333;
      padding: 12px 16px;
      border-radius: 10px;
      box-shadow: 0 8px 24px rgba(0,0,0,.15);
      min-width: 220px;
      max-width: 340px;
      font-size: .9rem;
      transform: translateX(120%);
      opacity: 0;
      transition: transform .3s ease, opacity .3s ease;
    }
    .toast.show { transform: translateX(0); opacity: 1; }
    .toast.hide { transform: translateX(120%); opacity: 0; }
    .toast i { font-size: 1.1rem; flex-shrink: 0; }
    .toast-success i { color: #1e9e5a; }
    .toast-error   i { color: #c33; }
    .toast-info    i { color: #3474eb; }
    .toast-close {
      margin-left: auto;
      background: none;
      border: none;
      font-size: 1.1rem;
      line-height: 1;
      cursor: pointer;
      color: #999;
    }
    .toast-close:hover { color: #333; }

    .confirm-overlay {
      position: fixed;
      inset: 0;
      display: flex;
      align-items: center;
      justify-content: center;
      background: rgba(0,0,0,0);
      opacity: 0;
      pointer-events: none;
      transition: background .2s ease, opacity .2s ease;
      z-index: 9998;
    }
    .confirm-overlay.show {
      background: rgba(0,0,0,.45);
      opacity: 1;
      pointer-events: auto;
    }
    .confirm-box {
      background: #fff;
      padding: 24px;
      border-radius: 14px;
      max-width: 320px;
      width: 90%;
      text-align: center;
      transform: scale(.85);
      transition: transform .2s ease;
      box-shadow: 0 20px 50px rgba(0,0,0,.25);
    }
    .confirm-overlay.show .confirm-box { transform: scale(1); }
    .confirm-box p { margin: 0 0 18px; font-size: .95rem; color: #333; }
    .confirm-actions { display: flex; gap: 10px; justify-content: center; }

    .pop { animation: pop .35s ease; }
    @keyframes pop {
      0%   { transform: scale(1); }
      35%  { transform: scale(1.3); }
      100% { transform: scale(1); }
    }

    .fade-init {
      opacity: 0;
      transform: translateY(16px);
      transition: opacity .5s ease, transform .5s ease;
    }
    .fade-init.in-view { opacity: 1; transform: translateY(0); }

    .ripple {
      position: absolute;
      border-radius: 50%;
      background: rgba(255,255,255,.55);
      transform: scale(0);
      animation: ripple-anim .6s ease-out;
      pointer-events: none;
    }
    @keyframes ripple-anim {
      to { transform: scale(2.5); opacity: 0; }
    }

    @media (max-width: 480px) {
      #toast-stack { left: 16px; right: 16px; }
      .toast { max-width: none; }
    }
  `;
  const styleTag = document.createElement('style');
  styleTag.textContent = css;
  document.head.appendChild(styleTag);

  /* =========================================================
     2. Toast system
     ========================================================= */
  const ICONS = {
    success: 'fa-solid fa-circle-check',
    error: 'fa-solid fa-circle-exclamation',
    info: 'fa-solid fa-circle-info',
  };

  function ensureToastStack() {
    let el = document.getElementById('toast-stack');
    if (!el) {
      el = document.createElement('div');
      el.id = 'toast-stack';
      document.body.appendChild(el);
    }
    return el;
  }

  function showToast(message, type = 'success', duration = 3200) {
    const stack = ensureToastStack();
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `
      <i class="${ICONS[type] || ICONS.info}"></i>
      <span>${message}</span>
      <button class="toast-close" aria-label="Dismiss">&times;</button>
    `;
    stack.appendChild(toast);
    requestAnimationFrame(() => toast.classList.add('show'));

    const remove = () => {
      toast.classList.remove('show');
      toast.classList.add('hide');
      setTimeout(() => toast.remove(), 250);
    };
    const timer = setTimeout(remove, duration);
    toast.querySelector('.toast-close').addEventListener('click', () => {
      clearTimeout(timer);
      remove();
    });
  }
  window.showToast = showToast;

  /* Carries a toast message across the full-page reload that
     happens after a cart.php POST redirect. */
  function flashToast(message, type) {
    sessionStorage.setItem('flashToast', JSON.stringify({ message, type }));
  }
  function consumeFlashToast() {
    const raw = sessionStorage.getItem('flashToast');
    if (!raw) return;
    sessionStorage.removeItem('flashToast');
    try {
      const { message, type } = JSON.parse(raw);
      showToast(message, type);
    } catch (e) { /* ignore malformed value */ }
  }

  /* =========================================================
     3. Animated confirm dialog (replaces window.confirm)
     ========================================================= */
  function customConfirm(message) {
    return new Promise((resolve) => {
      const overlay = document.createElement('div');
      overlay.className = 'confirm-overlay';
      overlay.innerHTML = `
        <div class="confirm-box">
          <p>${message}</p>
          <div class="confirm-actions">
            <button class="btn btn-ghost-red" data-choice="cancel">Cancel</button>
            <button class="btn btn-primary" data-choice="ok">Confirm</button>
          </div>
        </div>
      `;
      document.body.appendChild(overlay);
      requestAnimationFrame(() => overlay.classList.add('show'));

      overlay.addEventListener('click', (e) => {
        const choice = e.target.dataset.choice;
        if (!choice) return;
        const result = choice === 'ok';
        overlay.classList.remove('show');
        setTimeout(() => overlay.remove(), 200);
        resolve(result);
      });
    });
  }

  /* =========================================================
     4. Hook up cart forms (add / remove / clear)
     ========================================================= */
  function bounce(el) {
    if (!el) return;
    el.classList.remove('pop');
    void el.offsetWidth; // restart animation
    el.classList.add('pop');
  }

  function hookCartForms() {
    document.querySelectorAll('form[action$="cart.php"], form[action=""]').forEach((form) => {
      const idInput = form.querySelector('input[name="id"]');
      const removeInput = form.querySelector('input[name="remove"]');
      const clearBtn = form.querySelector('button[name="clear"]');

      if (idInput) {
        form.addEventListener('submit', () => {
          flashToast('Added to cart', 'success');
        });
      }

      if (removeInput) {
        const btn = form.querySelector('button[type="submit"]');
        if (btn) btn.removeAttribute('onclick'); // drop native confirm()
        form.addEventListener('submit', async (e) => {
          e.preventDefault();
          const ok = await customConfirm('Remove this item from your cart?');
          if (ok) {
            flashToast('Item removed', 'info');
            form.submit();
          }
        });
      }

      if (clearBtn) {
        clearBtn.removeAttribute('onclick');
        form.addEventListener('submit', async (e) => {
          e.preventDefault();
          const ok = await customConfirm('Clear all items from your cart?');
          if (ok) {
            flashToast('Cart cleared', 'info');
            form.submit();
          }
        });
      }
    });
  }

  /* =========================================================
     5. Cart badge bump when the count changes
     ========================================================= */
  function watchCartBadge() {
    const badge = document.querySelector('.cart-count');
    if (!badge) return;
    const current = parseInt(badge.textContent, 10) || 0;
    const prev = parseInt(sessionStorage.getItem('lastCartCount') || '0', 10);
    if (current !== prev) bounce(badge);
    sessionStorage.setItem('lastCartCount', String(current));
  }

  /* =========================================================
     6. Fade-in product cards / reviews as they scroll in
     ========================================================= */
  function fadeInCards() {
    const cards = document.querySelectorAll('.product-card, .review-item');
    if (!cards.length) return;
    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry, i) => {
        if (entry.isIntersecting) {
          entry.target.style.transitionDelay = `${(i % 8) * 40}ms`;
          entry.target.classList.add('in-view');
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.1 });
    cards.forEach((card) => {
      card.classList.add('fade-init');
      observer.observe(card);
    });
  }

  /* =========================================================
     7. Button ripple effect
     ========================================================= */
  function addRipple(e) {
    const btn = e.currentTarget;
    const rect = btn.getBoundingClientRect();
    const size = Math.max(rect.width, rect.height);
    const ripple = document.createElement('span');
    ripple.className = 'ripple';
    ripple.style.width = ripple.style.height = `${size}px`;
    ripple.style.left = `${e.clientX - rect.left - size / 2}px`;
    ripple.style.top = `${e.clientY - rect.top - size / 2}px`;
    btn.appendChild(ripple);
    setTimeout(() => ripple.remove(), 600);
  }

  function hookRipples() {
    document.querySelectorAll('.btn, .add-to-cart, .filter-chip').forEach((btn) => {
      if (!btn.style.position) btn.style.position = 'relative';
      btn.style.overflow = 'hidden';
      btn.addEventListener('click', addRipple);
    });
  }

  /* =========================================================
     Init
     ========================================================= */
  document.addEventListener('DOMContentLoaded', () => {
    consumeFlashToast();
    hookCartForms();
    watchCartBadge();
    fadeInCards();
    hookRipples();
  });
})();