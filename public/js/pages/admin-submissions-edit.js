(function () {
    'use strict';
    var spark = window.Spark || {};

    var form = document.getElementById('adminSubmissionEditForm');
    if (!form) {
        return;
    }

    var videoUrlError = 'Video URL must be a valid YouTube or Vimeo URL (for example: https://www.youtube.com/embed/VIDEO_ID or https://player.vimeo.com/video/VIDEO_ID).';
    var shortDescriptionError = 'Short Description must be 250 words or fewer.';
    var fullDescriptionError = 'Full Description must be 1000 words or fewer.';
    var maxPhotoBytes = 10 * 1024 * 1024;
    var maxPhotoSizeError = 'Each image must be 10 MB or less.';

    var isValidVideoEmbedUrl = function (value) {
        if (spark.videoEmbedUrl && typeof spark.videoEmbedUrl.isValid === 'function') {
            return spark.videoEmbedUrl.isValid(value);
        }
        return true;
    };

    var isDirty = false;
    var allowExit = false;

    var markDirty = function () {
        if (!allowExit) {
            isDirty = true;
        }
    };

    form.addEventListener('input', markDirty);
    form.addEventListener('change', markDirty);

    var photoInputs = form.querySelectorAll('input[type="file"][data-photo-slot]');

    var isJpgFile = function (fileName) {
        var name = (fileName || '').toLowerCase();
        return name.endsWith('.jpg') || name.endsWith('.jpeg');
    };

    var wordCount = function (text) {
        var normalized = (text || '').trim();
        if (!normalized) {
            return 0;
        }
        return normalized.split(/\s+/).length;
    };

    var syncRequiredPhotoValidity = function () {
        photoInputs.forEach(function (input) {
            var required = input.getAttribute('data-photo-required') === '1';
            var hasUpload = !!(input.files && input.files.length > 0);
            if (hasUpload && !isJpgFile(input.files[0].name || '')) {
                input.setCustomValidity('Only valid JPG images are allowed.');
                input.classList.add('is-invalid');
                return;
            }
            if (hasUpload && input.files[0].size > maxPhotoBytes) {
                input.setCustomValidity(maxPhotoSizeError);
                input.classList.add('is-invalid');
                return;
            }
            if (!required) {
                input.setCustomValidity('');
                input.classList.remove('is-invalid');
                return;
            }
            var hasExisting = input.getAttribute('data-photo-has-existing') === '1';
            var missing = !hasExisting && !hasUpload;
            if (missing) {
                input.setCustomValidity('Photo is required.');
                input.classList.add('is-invalid');
            } else {
                input.setCustomValidity('');
                input.classList.remove('is-invalid');
            }
        });
    };

    form.addEventListener('submit', function (event) {
        var shortDescription = document.getElementById('short_description');
        var fullDescription = document.getElementById('full_description');

        if (shortDescription) {
            shortDescription.setCustomValidity(wordCount(shortDescription.value) > 250 ? shortDescriptionError : '');
        }
        if (fullDescription) {
            fullDescription.setCustomValidity(wordCount(fullDescription.value) > 1000 ? fullDescriptionError : '');
        }

        syncRequiredPhotoValidity();

        var youtubeEl = document.getElementById('youtube_url');
        if (youtubeEl) {
            youtubeEl.setCustomValidity(isValidVideoEmbedUrl(youtubeEl.value) ? '' : videoUrlError);
        }

        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
            form.classList.add('was-validated');
            return;
        }

        allowExit = true;
        isDirty = false;
    });

    photoInputs.forEach(function (input) {
        input.addEventListener('change', syncRequiredPhotoValidity);
    });
    syncRequiredPhotoValidity();

    var youtube = document.getElementById('youtube_url');
    if (youtube) {
        youtube.addEventListener('input', function () {
            this.setCustomValidity(isValidVideoEmbedUrl(this.value) ? '' : videoUrlError);
        });
    }

    var shortDescription = document.getElementById('short_description');
    if (shortDescription) {
        shortDescription.addEventListener('input', function () {
            this.setCustomValidity(wordCount(this.value) > 250 ? shortDescriptionError : '');
        });
    }

    var fullDescription = document.getElementById('full_description');
    if (fullDescription) {
        fullDescription.addEventListener('input', function () {
            this.setCustomValidity(wordCount(this.value) > 1000 ? fullDescriptionError : '');
        });
    }

    var certificateInput = form.querySelector('input[name="certificate_pdf"]');
    var certificateThumbData = document.getElementById('certificate_thumb_data');
    var certificateThumbStatus = document.getElementById('certificate-thumb-status');

    var setCertificateThumbStatus = function (message) {
        if (certificateThumbStatus) {
            certificateThumbStatus.textContent = message || '';
        }
    };

    var readFileAsArrayBuffer = function (file) {
        return new Promise(function (resolve, reject) {
            var reader = new FileReader();
            reader.onload = function () { resolve(reader.result); };
            reader.onerror = function () { reject(new Error('Unable to read PDF file.')); };
            reader.readAsArrayBuffer(file);
        });
    };

    var canvasToJpegDataUrl = function (canvas) {
        return new Promise(function (resolve) {
            if (canvas.toBlob) {
                canvas.toBlob(function (blob) {
                    if (!blob) {
                        resolve('');
                        return;
                    }
                    var reader = new FileReader();
                    reader.onloadend = function () {
                        resolve(typeof reader.result === 'string' ? reader.result : '');
                    };
                    reader.readAsDataURL(blob);
                }, 'image/jpeg', 0.86);
                return;
            }
            resolve(canvas.toDataURL('image/jpeg', 0.86));
        });
    };

    var buildCertificateThumbnail = function (file) {
        if (!window.pdfjsLib || !window.pdfjsLib.getDocument) {
            return Promise.resolve('');
        }

        window.pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js';
        return readFileAsArrayBuffer(file)
            .then(function (buffer) {
                return window.pdfjsLib.getDocument({data: new Uint8Array(buffer)}).promise;
            })
            .then(function (pdfDoc) {
                return pdfDoc.getPage(1);
            })
            .then(function (page) {
                var baseViewport = page.getViewport({scale: 1});
                var maxWidth = 560;
                var scale = baseViewport.width > 0 ? (maxWidth / baseViewport.width) : 1;
                if (scale > 1.5) {
                    scale = 1.5;
                }
                var viewport = page.getViewport({scale: scale});
                var canvas = document.createElement('canvas');
                var context = canvas.getContext('2d', {alpha: false});
                canvas.width = Math.max(1, Math.floor(viewport.width));
                canvas.height = Math.max(1, Math.floor(viewport.height));
                context.fillStyle = '#ffffff';
                context.fillRect(0, 0, canvas.width, canvas.height);
                return page.render({canvasContext: context, viewport: viewport}).promise.then(function () {
                    return canvasToJpegDataUrl(canvas);
                });
            });
    };

    if (certificateInput && certificateThumbData) {
        certificateInput.addEventListener('change', function () {
            certificateThumbData.value = '';
            var file = certificateInput.files && certificateInput.files.length > 0 ? certificateInput.files[0] : null;
            if (!file) {
                setCertificateThumbStatus('');
                return;
            }

            var fileName = (file.name || '').toLowerCase();
            if (!fileName.endsWith('.pdf')) {
                setCertificateThumbStatus('Selected file is not a PDF.');
                return;
            }

            setCertificateThumbStatus('Generating thumbnail from page 1...');
            buildCertificateThumbnail(file)
                .then(function (dataUrl) {
                    if (typeof dataUrl === 'string' && dataUrl.indexOf('data:image/jpeg;base64,') === 0) {
                        certificateThumbData.value = dataUrl;
                        setCertificateThumbStatus('Thumbnail prepared and will be saved.');
                    } else {
                        setCertificateThumbStatus('Browser thumbnail generation failed. Server fallback will be used.');
                    }
                })
                .catch(function () {
                    setCertificateThumbStatus('Browser thumbnail generation failed. Server fallback will be used.');
                });
        });
    }

    window.addEventListener('beforeunload', function (event) {
        if (allowExit || !isDirty) {
            return;
        }
        event.preventDefault();
        event.returnValue = '';
    });

    if (spark.receiptModal && typeof spark.receiptModal.init === 'function') {
        spark.receiptModal.init({
            modalId: 'receiptModal',
            bodyId: 'receiptModalBody',
            pdfBtnId: 'receiptModalPdfBtn',
            linkSelector: '.js-receipt-modal-link',
            loadingText: 'Loading receipt...',
            errorText: 'Unable to load receipt.'
        });
    }

    if (spark.photoPreviewModal && typeof spark.photoPreviewModal.initMixedMediaModal === 'function') {
        spark.photoPreviewModal.initMixedMediaModal({
            modalId: 'photoPreviewModal',
            imageId: 'photoPreviewImage',
            pdfId: 'photoPreviewPdf',
            pdfFallbackId: 'photoPreviewPdfFallback',
            pdfFallbackLinkId: 'photoPreviewPdfFallbackLink',
            titleId: 'photoPreviewModalLabel',
            linkSelector: '.js-photo-modal-link',
            defaultTitle: 'Photo Preview'
        });
    }
})();
