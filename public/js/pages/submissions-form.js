(function () {
    'use strict';

    var spark = window.Spark || {};
    var messages = {
        videoUrlError: 'Video URL must be a valid YouTube or Vimeo URL (for example: https://www.youtube.com/embed/VIDEO_ID or https://player.vimeo.com/video/VIDEO_ID).',
        answerTooLong: 'Each answer must be 50 words or fewer.',
        shortTooLong: 'Short Description must be 250 words or fewer.',
        fullTooLong: 'Full Description must be 1000 words or fewer.',
        requiredPhotoTpl: 'Photo {slot} is required. Please upload a JPG image.',
        onlyJpg: 'Only JPG files are allowed.',
        maxImageSize: 'Each image must be 10 MB or less.',
        defaultPhotoTitle: 'Photo'
    };

    var isValidVideoEmbedUrl = function (value) {
        if (spark.videoEmbedUrl && typeof spark.videoEmbedUrl.isValid === 'function') {
            return spark.videoEmbedUrl.isValid(value);
        }
        return true;
    };

    var wordCount = function (text) {
        var normalized = (text || '').trim();
        if (!normalized) {
            return 0;
        }
        return normalized.split(/\s+/).length;
    };

    var upsertInvalidFeedback = function (field, message) {
        if (!field || !field.parentNode) {
            return;
        }

        var group = field.parentNode;
        var feedback = group.querySelector('.invalid-feedback');
        if (!feedback) {
            feedback = document.createElement('div');
            feedback.className = 'invalid-feedback';
            group.appendChild(feedback);
        }
        feedback.textContent = message || '';
    };

    var applyBootstrapErrors = function (form) {
        var controls = form.querySelectorAll('input, textarea, select');
        controls.forEach(function (field) {
            if (field.disabled || field.type === 'hidden' || field.type === 'submit' || field.type === 'button') {
                return;
            }

            if (field.checkValidity()) {
                field.classList.remove('is-invalid');
                return;
            }

            field.classList.add('is-invalid');
            upsertInvalidFeedback(field, field.validationMessage);
        });
    };

    var applyQuestionAnswerLimits = function (form) {
        var answerFields = form.querySelectorAll('textarea[name^="entry_question_"]');
        answerFields.forEach(function (field) {
            field.setCustomValidity(wordCount(field.value) > 50 ? messages.answerTooLong : '');
        });
    };

    var applyRequiredPhotoSlotLimits = function (form) {
        var photoInputs = form.querySelectorAll('input[type="file"][data-photo-slot]');
        photoInputs.forEach(function (input) {
            var isRequired = input.getAttribute('data-photo-required') === '1';
            if (!isRequired) {
                input.setCustomValidity('');
                return;
            }

            var slot = input.getAttribute('data-photo-slot') || '';
            var hasExisting = input.getAttribute('data-photo-has-existing') === '1';
            var hasNewUpload = input.files && input.files.length > 0;
            var isMissingRequired = !hasExisting && !hasNewUpload;

            if (isMissingRequired) {
                input.setCustomValidity(messages.requiredPhotoTpl.replace('{slot}', slot));
            } else {
                input.setCustomValidity('');
            }
        });
    };

    var forms = document.querySelectorAll('.needs-validation');
    Array.prototype.slice.call(forms).forEach(function (form) {
        form.addEventListener('submit', function (event) {
            var shortEl = document.getElementById('short_description');
            var fullEl = document.getElementById('full_description');
            var youtubeEl = document.getElementById('youtube_url');

            if (shortEl) {
                shortEl.setCustomValidity(wordCount(shortEl.value) > 250 ? messages.shortTooLong : '');
            }
            if (fullEl) {
                fullEl.setCustomValidity(wordCount(fullEl.value) > 1000 ? messages.fullTooLong : '');
            }
            if (youtubeEl) {
                youtubeEl.setCustomValidity(isValidVideoEmbedUrl(youtubeEl.value) ? '' : messages.videoUrlError);
            }

            applyQuestionAnswerLimits(form);
            applyRequiredPhotoSlotLimits(form);

            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            applyBootstrapErrors(form);
            form.classList.add('was-validated');
        }, false);

        form.addEventListener('input', function (event) {
            var target = event.target;
            if (!(target instanceof HTMLElement)) {
                return;
            }
            if (!target.matches('input, textarea, select')) {
                return;
            }
            if (target.checkValidity()) {
                target.classList.remove('is-invalid');
            }
        });
    });

    var shortDesc = document.getElementById('short_description');
    var fullDesc = document.getElementById('full_description');
    var youtube = document.getElementById('youtube_url');

    if (shortDesc) {
        shortDesc.addEventListener('input', function () {
            this.setCustomValidity(wordCount(this.value) > 250 ? messages.shortTooLong : '');
        });
    }

    if (fullDesc) {
        fullDesc.addEventListener('input', function () {
            this.setCustomValidity(wordCount(this.value) > 1000 ? messages.fullTooLong : '');
        });
    }

    if (youtube) {
        youtube.addEventListener('input', function () {
            this.setCustomValidity(isValidVideoEmbedUrl(this.value) ? '' : messages.videoUrlError);
        });
    }

    var qaFields = document.querySelectorAll('textarea[name^="entry_question_"]');
    qaFields.forEach(function (field) {
        field.addEventListener('input', function () {
            this.setCustomValidity(wordCount(this.value) > 50 ? messages.answerTooLong : '');
        });
    });

    if (spark.photoPreviewModal && typeof spark.photoPreviewModal.initSimpleImageModal === 'function') {
        spark.photoPreviewModal.initSimpleImageModal({
            modalId: 'photoPreviewModal',
            imageId: 'photoPreviewImage',
            titleId: 'photoPreviewTitle',
            buttonSelector: '.photo-preview-trigger',
            defaultTitle: messages.defaultPhotoTitle
        });
    }

    var maxBytes = 10 * 1024 * 1024;
    var readOnlyInput = document.querySelector('input[name="is_read_only"]');
    var isReadOnly = !!readOnlyInput && readOnlyInput.value === '1';

    if (isReadOnly) {
        var form = document.querySelector('form.needs-validation');
        if (form) {
            var controls = form.querySelectorAll('input, textarea, select, button');
            controls.forEach(function (control) {
                if (control.name === '_token' || control.name === 'is_read_only') {
                    return;
                }
                if (control.classList.contains('photo-preview-trigger')) {
                    return;
                }
                if (control.type === 'hidden') {
                    return;
                }
                control.setAttribute('disabled', 'disabled');
            });
        }
    }

    for (var i = 1; i <= 10; i++) {
        var input = document.getElementById('low_photo_' + i);
        if (!input) {
            continue;
        }

        input.addEventListener('change', function () {
            var file = this.files && this.files[0] ? this.files[0] : null;
            if (!file) {
                var parentForm = this.closest('form');
                if (parentForm) {
                    applyRequiredPhotoSlotLimits(parentForm);
                }
                return;
            }

            var name = (file.name || '').toLowerCase();
            var isJpg = name.endsWith('.jpg') || name.endsWith('.jpeg');
            if (!isJpg) {
                window.alert(messages.onlyJpg);
                this.value = '';
                return;
            }

            if (file.size > maxBytes) {
                window.alert(messages.maxImageSize);
                this.value = '';
            }

            var parentForm = this.closest('form');
            if (parentForm) {
                applyRequiredPhotoSlotLimits(parentForm);
            }
        });
    }
})();
