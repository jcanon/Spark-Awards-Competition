(function (window) {
    if (!window) {
        return;
    }

    var spark = window.Spark || (window.Spark = {});

    var escapeHtml = function (value) {
        var div = document.createElement('div');
        div.textContent = value;
        return div.innerHTML;
    };

    var init = function (config) {
        var modalEl = document.getElementById(config.modalId || '');
        var modalBody = document.getElementById(config.bodyId || '');
        var pdfBtn = config.pdfBtnId ? document.getElementById(config.pdfBtnId) : null;
        var links = document.querySelectorAll(config.linkSelector || '');

        if (!modalEl || !modalBody || links.length === 0) {
            return;
        }

        var loadingHtml = '<div class="text-muted">' + escapeHtml(config.loadingText || 'Loading...') + '</div>';
        var defaultError = config.errorText || 'Unable to load content.';

        links.forEach(function (link) {
            link.addEventListener('click', function (event) {
                event.preventDefault();

                var url = this.getAttribute('data-receipt-url') || '';
                if (!url) {
                    window.location.href = this.getAttribute('href') || '';
                    return;
                }

                modalBody.innerHTML = loadingHtml;

                if (pdfBtn) {
                    var pdfUrl = this.getAttribute('data-receipt-pdf-url') || '';
                    pdfBtn.href = pdfUrl || '#';
                    pdfBtn.classList.toggle('disabled', pdfUrl === '');
                    pdfBtn.setAttribute('aria-disabled', pdfUrl === '' ? 'true' : 'false');
                }

                if (spark.modal && typeof spark.modal.show === 'function') {
                    spark.modal.show(modalEl);
                }

                fetch(url, {headers: {'X-Requested-With': 'XMLHttpRequest'}})
                    .then(function (response) {
                        if (!response.ok) {
                            throw new Error(defaultError);
                        }
                        return response.text();
                    })
                    .then(function (html) {
                        modalBody.innerHTML = html;
                    })
                    .catch(function (error) {
                        modalBody.innerHTML = '<div class="alert alert-danger mb-0">' + escapeHtml(error.message || defaultError) + '</div>';
                    });
            });
        });
    };

    spark.receiptModal = {
        init: init
    };
})(window);
