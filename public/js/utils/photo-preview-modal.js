(function (window) {
    if (!window) {
        return;
    }

    var spark = window.Spark || (window.Spark = {});

    var showModal = function (element) {
        if (spark.modal && typeof spark.modal.show === 'function') {
            spark.modal.show(element);
        }
    };

    var initSimpleImageModal = function (config) {
        var modalEl = document.getElementById(config.modalId || '');
        var imageEl = document.getElementById(config.imageId || '');
        var titleEl = config.titleId ? document.getElementById(config.titleId) : null;
        var buttons = document.querySelectorAll(config.buttonSelector || '');

        if (!modalEl || !imageEl || buttons.length === 0) {
            return;
        }

        buttons.forEach(function (button) {
            button.addEventListener('click', function () {
                var url = this.getAttribute('data-photo-url') || '';
                if (!url) {
                    return;
                }

                var title = this.getAttribute('data-photo-title') || config.defaultTitle || 'Photo';
                imageEl.setAttribute('src', url);
                imageEl.setAttribute('alt', title);
                if (titleEl) {
                    titleEl.textContent = title;
                }

                showModal(modalEl);
            });
        });
    };

    var initMixedMediaModal = function (config) {
        var modalEl = document.getElementById(config.modalId || '');
        var imageEl = document.getElementById(config.imageId || '');
        var pdfEl = config.pdfId ? document.getElementById(config.pdfId) : null;
        var fallbackEl = config.pdfFallbackId ? document.getElementById(config.pdfFallbackId) : null;
        var fallbackLinkEl = config.pdfFallbackLinkId ? document.getElementById(config.pdfFallbackLinkId) : null;
        var titleEl = config.titleId ? document.getElementById(config.titleId) : null;
        var links = document.querySelectorAll(config.linkSelector || '');

        if (!modalEl || !imageEl || links.length === 0) {
            return;
        }

        var showImage = function (url, title) {
            imageEl.src = url;
            imageEl.alt = title;
            imageEl.classList.remove('d-none');
            if (pdfEl) {
                pdfEl.src = '';
                pdfEl.classList.add('d-none');
            }
            if (fallbackEl) {
                fallbackEl.classList.add('d-none');
            }
        };

        var showPdf = function (url) {
            imageEl.src = '';
            imageEl.alt = '';
            imageEl.classList.add('d-none');
            if (pdfEl) {
                pdfEl.src = url + '#toolbar=1&navpanes=0';
                pdfEl.classList.remove('d-none');
            }
            if (fallbackEl && fallbackLinkEl) {
                fallbackLinkEl.href = url;
                fallbackEl.classList.remove('d-none');
            }
        };

        links.forEach(function (link) {
            link.addEventListener('click', function (event) {
                event.preventDefault();
                var mediaUrl = this.getAttribute('data-photo-url') || '';
                if (!mediaUrl) {
                    return;
                }

                var title = this.getAttribute('data-photo-title') || config.defaultTitle || 'Photo Preview';
                var mediaType = (this.getAttribute('data-photo-type') || 'image').toLowerCase();

                if (mediaType === 'pdf') {
                    showPdf(mediaUrl);
                } else {
                    showImage(mediaUrl, title);
                }

                if (titleEl) {
                    titleEl.textContent = title;
                }

                showModal(modalEl);
            });
        });
    };

    var initGalleryModal = function (config) {
        var modalEl = document.getElementById(config.modalId || '');
        var imageEl = document.getElementById(config.imageId || '');
        var titleEl = config.titleId ? document.getElementById(config.titleId) : null;
        var counterEl = config.counterId ? document.getElementById(config.counterId) : null;
        var prevBtn = document.getElementById(config.prevBtnId || '');
        var nextBtn = document.getElementById(config.nextBtnId || '');
        var links = document.querySelectorAll(config.linkSelector || '');
        var activeIndex = -1;

        if (!modalEl || !imageEl || !prevBtn || !nextBtn || links.length === 0) {
            return;
        }

        var setByIndex = function (index) {
            if (index < 0 || index >= links.length) {
                return;
            }

            activeIndex = index;
            var link = links[index];
            var photoUrl = link.getAttribute('data-photo-url') || '';
            if (!photoUrl) {
                return;
            }
            var photoTitle = link.getAttribute('data-photo-title') || config.defaultTitle || 'Entry Photo';

            imageEl.src = photoUrl;
            imageEl.alt = photoTitle;
            if (titleEl) {
                titleEl.textContent = photoTitle;
            }
            if (counterEl) {
                counterEl.textContent = 'Photo ' + (activeIndex + 1) + ' of ' + links.length;
            }

            prevBtn.disabled = activeIndex <= 0;
            nextBtn.disabled = activeIndex >= (links.length - 1);
        };

        links.forEach(function (link, index) {
            link.addEventListener('click', function (event) {
                event.preventDefault();
                setByIndex(index);
                showModal(modalEl);
            });
        });

        prevBtn.addEventListener('click', function () {
            setByIndex(activeIndex - 1);
        });
        nextBtn.addEventListener('click', function () {
            setByIndex(activeIndex + 1);
        });

        document.addEventListener('keydown', function (event) {
            if (!modalEl.classList.contains('show')) {
                return;
            }
            if (event.key === 'ArrowLeft') {
                event.preventDefault();
                setByIndex(activeIndex - 1);
            }
            if (event.key === 'ArrowRight') {
                event.preventDefault();
                setByIndex(activeIndex + 1);
            }
        });
    };

    spark.photoPreviewModal = {
        initSimpleImageModal: initSimpleImageModal,
        initMixedMediaModal: initMixedMediaModal,
        initGalleryModal: initGalleryModal
    };
})(window);
