(function () {
  "use strict";

  function getItems(container) {
    return Array.from(container.querySelectorAll('.pdf-gallery-item'));
  }

  function sortDomItems(container, by, dir) {
    const items = getItems(container);
    if (!items.length) return;

    // Restore server order
    if (!by || by === 'none') {
      items
        .sort((a, b) => {
          const ia = parseInt(a.getAttribute('data-original-index') || '0', 10);
          const ib = parseInt(b.getAttribute('data-original-index') || '0', 10);
          return ia - ib;
        })
        .forEach((it) => container.appendChild(it));
      return;
    }

    const factor = (dir || 'asc').toLowerCase() === 'desc' ? -1 : 1;
    const keyFn = (el) => {
      if (by === 'title') {
        const t = el.querySelector('.pdf-title');
        return (t && (t.getAttribute('title') || t.textContent) || '').trim().toLowerCase();
      }
      if (by === 'filename') {
        const u = el.getAttribute('data-url') || '';
        return u.split('/').pop().toLowerCase();
      }
      return '';
    };

    items
      .sort((a, b) => {
        const ka = keyFn(a);
        const kb = keyFn(b);
        if (ka < kb) return -1 * factor;
        if (ka > kb) return 1 * factor;
        // stable fallback
        const ia = parseInt(a.getAttribute('data-original-index') || '0', 10);
        const ib = parseInt(b.getAttribute('data-original-index') || '0', 10);
        return ia - ib;
      })
      .forEach((it) => container.appendChild(it));
  }

  // Wires HTML5 drag & drop reordering + the "Salva ordine" button to the
  // pdf_gallery_save_order AJAX endpoint. Only called when the current user
  // is allowed to edit (server only renders .pdf-gallery-save/manual-only
  // controls in that case).
  function setupManualOrdering(container, selBy, saveBtn) {
    const i18n = (window.PDFGalleryViewer && PDFGalleryViewer.i18n) || {};
    let draggedEl = null;
    let dirty = false;

    function isManualMode() {
      return selBy && selBy.value === 'manual';
    }

    function updateDraggableState() {
      const manual = isManualMode();
      container.classList.toggle('is-manual', manual);
      getItems(container).forEach((it) => it.setAttribute('draggable', manual ? 'true' : 'false'));
    }

    function setSaveLabel(label) {
      if (saveBtn) saveBtn.textContent = label;
    }

    function markDirty() {
      dirty = true;
      if (!saveBtn) return;
      saveBtn.style.display = '';
      saveBtn.disabled = false;
      setSaveLabel(i18n.save || 'Save order');
    }

    function clearDirty() {
      dirty = false;
      if (saveBtn) saveBtn.style.display = 'none';
    }

    container.addEventListener('dragstart', function (e) {
      const item = e.target.closest('.pdf-gallery-item');
      if (!item || item.getAttribute('draggable') !== 'true') return;
      draggedEl = item;
      item.classList.add('is-dragging');
      if (e.dataTransfer) {
        e.dataTransfer.effectAllowed = 'move';
        try { e.dataTransfer.setData('text/plain', ''); } catch (err) { /* Firefox requires setData to enable drag */ }
      }
    });

    container.addEventListener('dragover', function (e) {
      if (!draggedEl) return;
      e.preventDefault();
      const target = e.target.closest('.pdf-gallery-item');
      getItems(container).forEach((it) => it.classList.remove('drop-hint'));
      if (target && target !== draggedEl) target.classList.add('drop-hint');
    });

    container.addEventListener('drop', function (e) {
      if (!draggedEl) return;
      e.preventDefault();
      const target = e.target.closest('.pdf-gallery-item');
      getItems(container).forEach((it) => it.classList.remove('drop-hint'));

      if (target && target !== draggedEl) {
        const items = getItems(container);
        const draggedIndex = items.indexOf(draggedEl);
        const targetIndex = items.indexOf(target);
        if (draggedIndex < targetIndex) {
          target.after(draggedEl);
        } else {
          target.before(draggedEl);
        }
        markDirty();
      }
    });

    container.addEventListener('dragend', function () {
      if (draggedEl) draggedEl.classList.remove('is-dragging');
      getItems(container).forEach((it) => it.classList.remove('drop-hint'));
      draggedEl = null;
    });

    if (selBy) selBy.addEventListener('change', updateDraggableState);
    updateDraggableState();

    if (saveBtn) {
      saveBtn.addEventListener('click', function () {
        if (!dirty || !window.PDFGalleryViewer || !PDFGalleryViewer.canEdit) return;

        const order = getItems(container).map((it) => it.getAttribute('data-url') || '');
        const body = new URLSearchParams();
        body.set('action', 'pdf_gallery_save_order');
        body.set('nonce', PDFGalleryViewer.nonce);
        body.set('postId', PDFGalleryViewer.postId);
        body.set('galleryId', container.getAttribute('data-gallery-id') || '');
        order.forEach((url) => body.append('order[]', url));

        saveBtn.disabled = true;
        setSaveLabel(i18n.save || 'Save order');

        fetch(PDFGalleryViewer.ajaxUrl, {
          method: 'POST',
          credentials: 'same-origin',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: body.toString(),
        })
          .then((res) => res.json())
          .then((data) => {
            if (data && data.success) {
              getItems(container).forEach((it, idx) => it.setAttribute('data-original-index', String(idx)));
              clearDirty();
            } else {
              setSaveLabel(i18n.saveError || 'Could not save order');
              saveBtn.disabled = false;
            }
          })
          .catch(function () {
            setSaveLabel(i18n.saveError || 'Could not save order');
            saveBtn.disabled = false;
          });
      });
    }

    return { clearDirty: clearDirty, updateDraggableState: updateDraggableState };
  }

  function initContainer(wrapper) {
    const container = wrapper.querySelector('.pdf-gallery-container');
    if (!container) return;

    const controls = wrapper.querySelector('.pdf-gallery-controls');
    if (!controls) return;

    const selBy = controls.querySelector('.pdf-gallery-sort-by');
    const selOrder = controls.querySelector('.pdf-gallery-sort-order');
    const btnReset = controls.querySelector('.pdf-gallery-reset');
    const btnSave = controls.querySelector('.pdf-gallery-save');

    // Tag original order (server-authoritative) so "Reset" can restore it.
    getItems(container).forEach((it, idx) => {
      if (!it.hasAttribute('data-original-index')) {
        it.setAttribute('data-original-index', String(idx));
      }
    });

    const initialBy = (container.getAttribute('data-order-by') || 'none').toLowerCase();
    const initialDir = (container.getAttribute('data-order') || 'asc').toLowerCase();

    if (selBy) selBy.value = initialBy;
    if (selOrder) selOrder.value = initialDir;

    // Apply initial sorting (if any)
    sortDomItems(container, initialBy, initialDir);

    const manualOrdering = (window.PDFGalleryViewer && PDFGalleryViewer.canEdit)
      ? setupManualOrdering(container, selBy, btnSave)
      : null;

    function applyFromControls() {
      const by = (selBy && selBy.value) ? selBy.value : 'none';
      const dir = (selOrder && selOrder.value) ? selOrder.value : 'asc';
      sortDomItems(container, by, dir);
    }

    if (selBy) selBy.addEventListener('change', applyFromControls);
    if (selOrder) selOrder.addEventListener('change', applyFromControls);

    if (btnReset) {
      btnReset.addEventListener('click', () => {
        if (selBy) selBy.value = initialBy;
        if (selOrder) selOrder.value = initialDir;
        sortDomItems(container, initialBy, initialDir);
        if (manualOrdering) {
          manualOrdering.clearDirty();
          manualOrdering.updateDraggableState();
        }
      });
    }
  }

  document.addEventListener('DOMContentLoaded', function () {
    const wrappers = document.querySelectorAll('.pdf-gallery-wrapper');
    wrappers.forEach(initContainer);
  });
})();
