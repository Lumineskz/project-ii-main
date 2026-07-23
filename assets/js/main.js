// =====================================================================
// CampusCanteen — shared front-end behaviour
// =====================================================================

document.addEventListener('DOMContentLoaded', function () {

  // -------------------------------------------------------------
  // Sidebar toggle (mobile)
  // -------------------------------------------------------------
  var toggleBtn = document.getElementById('menuToggle');
  var sidebar = document.querySelector('.sidebar');
  if (toggleBtn && sidebar) {
    toggleBtn.addEventListener('click', function () {
      sidebar.classList.toggle('open');
    });
    document.addEventListener('click', function (e) {
      if (window.innerWidth <= 820 && sidebar.classList.contains('open')) {
        if (!sidebar.contains(e.target) && e.target !== toggleBtn) {
          sidebar.classList.remove('open');
        }
      }
    });
  }

  // -------------------------------------------------------------
  // Auto-dismiss flash alerts
  // -------------------------------------------------------------
  document.querySelectorAll('.alert[data-autohide]').forEach(function (el) {
    setTimeout(function () {
      el.style.transition = 'opacity .4s';
      el.style.opacity = '0';
      setTimeout(function () { el.remove(); }, 400);
    }, 4000);
  });

  // -------------------------------------------------------------
  // Register page: role toggle (student / faculty)
  // -------------------------------------------------------------
  var roleRadios = document.querySelectorAll('input[name="role"]');
  if (roleRadios.length) {
    roleRadios.forEach(function (radio) {
      radio.addEventListener('change', function () {
        document.querySelectorAll('.role-fields').forEach(function (block) {
          block.classList.remove('active');
        });
        var target = document.getElementById('fields-' + radio.value);
        if (target) target.classList.add('active');
      });
    });
  }

  // -------------------------------------------------------------
  // Generic modal open/close via data attributes
  // data-modal-open="modalId"  /  data-modal-close
  // -------------------------------------------------------------
  document.querySelectorAll('[data-modal-open]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var modal = document.getElementById(btn.getAttribute('data-modal-open'));
      if (modal) modal.classList.add('open');
    });
  });
  document.querySelectorAll('[data-modal-close]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      btn.closest('.modal-backdrop').classList.remove('open');
    });
  });
  document.querySelectorAll('.modal-backdrop').forEach(function (backdrop) {
    backdrop.addEventListener('click', function (e) {
      if (e.target === backdrop) backdrop.classList.remove('open');
    });
  });

  // -------------------------------------------------------------
  // Menu item quantity steppers + add to cart (AJAX)
  // -------------------------------------------------------------
  document.querySelectorAll('.menu-card').forEach(function (card) {
    var qtyEl = card.querySelector('.qty-value');
    var minus = card.querySelector('.qty-minus');
    var plus = card.querySelector('.qty-plus');
    var addBtn = card.querySelector('.add-to-cart-btn');
    var maxStock = parseInt(card.getAttribute('data-stock'), 10) || 0;

    if (minus && plus && qtyEl) {
      minus.addEventListener('click', function () {
        var val = parseInt(qtyEl.textContent, 10);
        if (val > 1) qtyEl.textContent = val - 1;
      });
      plus.addEventListener('click', function () {
        var val = parseInt(qtyEl.textContent, 10);
        if (val < maxStock) qtyEl.textContent = val + 1;
      });
    }

    if (addBtn) {
      addBtn.addEventListener('click', function () {
        var itemId = card.getAttribute('data-item-id');
        var scheduleId = document.getElementById('activeScheduleId') ? document.getElementById('activeScheduleId').value : null;
        var qty = qtyEl ? parseInt(qtyEl.textContent, 10) : 1;

        if (!scheduleId) {
          showToast('No active ordering window right now.', 'error');
          return;
        }

        addBtn.disabled = true;
        addBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';

        fetch('cart_process.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: 'action=add&item_id=' + encodeURIComponent(itemId) +
                '&schedule_id=' + encodeURIComponent(scheduleId) +
                '&qty=' + encodeURIComponent(qty)
        })
          .then(function (res) { return res.json(); })
          .then(function (data) {
            addBtn.disabled = false;
            addBtn.innerHTML = '<i class="fa-solid fa-cart-plus"></i> Reserve';
            if (data.success) {
              showToast(data.message || 'Added to cart', 'success');
              var cartBadge = document.getElementById('cartCount');
              if (cartBadge) cartBadge.textContent = data.cart_count;
            } else {
              showToast(data.message || 'Could not add item', 'error');
            }
          })
          .catch(function () {
            addBtn.disabled = false;
            addBtn.innerHTML = '<i class="fa-solid fa-cart-plus"></i> Reserve';
            showToast('Network error, please try again', 'error');
          });
      });
    }
  });

  // -------------------------------------------------------------
  // Cart page: remove item
  // -------------------------------------------------------------
  document.querySelectorAll('.cart-remove-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var row = btn.closest('.cart-row');
      var cartId = btn.getAttribute('data-cart-id');
      fetch('cart_process.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=remove&cart_id=' + encodeURIComponent(cartId)
      })
        .then(function (res) { return res.json(); })
        .then(function (data) {
          if (data.success) {
            row.style.opacity = '0';
            setTimeout(function () { row.remove(); }, 200);
            if (typeof data.new_total !== 'undefined') {
              var totalEl = document.getElementById('cartTotal');
              if (totalEl) totalEl.textContent = 'Rs. ' + data.new_total;
            }
            if (data.empty) setTimeout(function () { location.reload(); }, 250);
          }
        });
    });
  });

  // -------------------------------------------------------------
  // Live countdown for timer pills (data-close="HH:MM:SS")
  // -------------------------------------------------------------
  document.querySelectorAll('[data-close]').forEach(function (pill) {
    function tick() {
      var closeStr = pill.getAttribute('data-close');
      var now = new Date();
      var parts = closeStr.split(':');
      var closeDate = new Date();
      closeDate.setHours(parts[0], parts[1], parts[2] || 0, 0);
      var diffMs = closeDate - now;
      if (diffMs <= 0) {
        pill.classList.add('closed');
        pill.innerHTML = '<i class="fa-solid fa-lock"></i> Ordering closed';
        return;
      }
      var mins = Math.floor(diffMs / 60000);
      var hrs = Math.floor(mins / 60);
      mins = mins % 60;
      var label = hrs > 0 ? (hrs + 'h ' + mins + 'm left') : (mins + 'm left');
      pill.innerHTML = '<i class="fa-regular fa-clock"></i> ' + label;
    }
    tick();
    setInterval(tick, 30000);
  });
});

// -------------------------------------------------------------
// Toast helper
// -------------------------------------------------------------
function showToast(message, type) {
  var existing = document.getElementById('toastBox');
  if (existing) existing.remove();

  var box = document.createElement('div');
  box.id = 'toastBox';
  box.className = 'alert ' + (type === 'error' ? 'alert-error' : 'alert-success');
  box.style.position = 'fixed';
  box.style.bottom = '24px';
  box.style.right = '24px';
  box.style.zIndex = '2000';
  box.style.boxShadow = '0 8px 24px rgba(0,0,0,.15)';
  box.innerHTML = '<i class="fa-solid ' + (type === 'error' ? 'fa-circle-exclamation' : 'fa-circle-check') + '"></i> ' + message;
  document.body.appendChild(box);
  setTimeout(function () {
    box.style.transition = 'opacity .4s';
    box.style.opacity = '0';
    setTimeout(function () { box.remove(); }, 400);
  }, 3000);
}
