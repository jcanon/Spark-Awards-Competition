(function () {
    var modalEl = document.getElementById('receiptModal');
    var modalBody = document.getElementById('receiptModalBody');
    var pdfBtn = document.getElementById('receiptModalPdfBtn');
    var links = document.querySelectorAll('.js-receipt-modal-link');

    var showModal = function (element) {
        if (window.bootstrap && window.bootstrap.Modal) {
            if (typeof window.bootstrap.Modal.getOrCreateInstance === 'function') {
                window.bootstrap.Modal.getOrCreateInstance(element).show();
                return;
            }
            try {
                (new window.bootstrap.Modal(element)).show();
                return;
            } catch (e) {
                // fall through
            }
        }
        if (window.jQuery && window.jQuery.fn && window.jQuery.fn.modal) {
            window.jQuery(element).modal('show');
        }
    };

    var escapeHtml = function (value) {
        var div = document.createElement('div');
        div.textContent = value;
        return div.innerHTML;
    };

    if (!modalEl || !modalBody || links.length === 0) {
        return;
    }

    links.forEach(function (link) {
        link.addEventListener('click', function (event) {
            event.preventDefault();
            var url = this.getAttribute('data-receipt-url') || '';
            if (!url) {
                window.location.href = this.getAttribute('href') || '';
                return;
            }

            modalBody.innerHTML = '<div class="text-muted">Loading receipt...</div>';
            var pdfUrl = this.getAttribute('data-receipt-pdf-url') || '';
            if (pdfBtn) {
                pdfBtn.href = pdfUrl || '#';
                pdfBtn.classList.toggle('disabled', pdfUrl === '');
                pdfBtn.setAttribute('aria-disabled', pdfUrl === '' ? 'true' : 'false');
            }
            showModal(modalEl);

            fetch(url, {headers: {'X-Requested-With': 'XMLHttpRequest'}})
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('Unable to load receipt.');
                    }
                    return response.text();
                })
                .then(function (html) {
                    modalBody.innerHTML = html;
                })
                .catch(function (err) {
                    modalBody.innerHTML = '<div class="alert alert-danger mb-0">' + escapeHtml(err.message || 'Unable to load receipt.') + '</div>';
                });
        });
    });
})();
