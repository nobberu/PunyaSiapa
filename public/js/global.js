(function () {
    'use strict';

    function getToastRegion() {
        var region = document.getElementById('toast-region');
        if (region) {
            return region;
        }

        region = document.createElement('div');
        region.id = 'toast-region';
        region.className = 'toast-region';
        region.setAttribute('aria-live', 'polite');
        region.setAttribute('aria-atomic', 'false');
        document.body.appendChild(region);

        return region;
    }

    function toast(message, type, duration) {
        if (!message) {
            return;
        }

        var region = getToastRegion();
        var element = document.createElement('div');
        element.className = 'toast toast-' + (type === 'error' ? 'error' : 'success');
        element.setAttribute('role', type === 'error' ? 'alert' : 'status');
        element.textContent = message;
        region.appendChild(element);

        var remove = function () {
            element.classList.add('is-leaving');
            window.setTimeout(function () {
                element.remove();
            }, 200);
        };

        window.setTimeout(remove, duration || 3500);
    }

    function confirmDialog(message, options) {
        options = options || {};

        var dialog = document.getElementById('confirm-dialog');
        if (!dialog || typeof dialog.showModal !== 'function') {
            return Promise.resolve(window.confirm(message));
        }

        var titleElement = dialog.querySelector('[data-dialog-title]');
        var textElement = dialog.querySelector('[data-dialog-text]');
        var confirmButton = dialog.querySelector('[data-dialog-confirm]');

        if (titleElement) {
            titleElement.textContent = options.title || 'Konfirmasi';
        }
        if (textElement) {
            textElement.textContent = message || 'Lanjutkan tindakan ini?';
        }
        if (confirmButton) {
            confirmButton.textContent = options.confirmLabel || 'Ya, lanjutkan';
            confirmButton.className = options.confirmLabel === 'Ya, hapus'
                ? 'btn btn-danger btn-sm'
                : 'btn btn-sm';
        }

        dialog.returnValue = '';

        return new Promise(function (resolve) {
            var onClose = function () {
                dialog.removeEventListener('close', onClose);
                resolve(dialog.returnValue === 'confirm');
            };

            dialog.addEventListener('close', onClose);
            dialog.showModal();
        });
    }

    window.PunyaUI = {
        toast: toast,
        confirm: confirmDialog
    };

    document.addEventListener('submit', function (event) {
        var form = event.target.closest('form[data-confirm]');
        if (!form) {
            return;
        }

        if (form.dataset.confirmed === '1') {
            delete form.dataset.confirmed;
            return;
        }

        event.preventDefault();

        confirmDialog(form.dataset.confirm, {
            confirmLabel: form.dataset.confirmLabel || 'Ya, hapus'
        }).then(function (confirmed) {
            if (!confirmed) {
                return;
            }

            form.dataset.confirmed = '1';
            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit();
            } else {
                form.submit();
            }
        });
    });

    document.querySelectorAll('textarea[data-counter]').forEach(function (field) {
        var target = document.querySelector(field.dataset.counter);
        if (!target) {
            return;
        }

        var max = field.maxLength > 0 ? field.maxLength : 1000;
        var update = function () {
            target.textContent = field.value.length + '/' + max;
        };

        field.addEventListener('input', update);
        update();
    });

    document.querySelectorAll('[data-autohide]').forEach(function (element) {
        var delay = Number(element.dataset.autohide) || 5000;

        window.setTimeout(function () {
            element.classList.add('is-leaving');
            window.setTimeout(function () {
                element.remove();
            }, 200);
        }, delay);
    });

    document.querySelectorAll('[data-photo-field]').forEach(function (field) {
        var input = field.querySelector('[data-photo-input]');
        var preview = field.querySelector('[data-photo-preview]');
        var placeholder = field.querySelector('[data-photo-placeholder]');

        if (!input || !preview) {
            return;
        }

        input.addEventListener('change', function () {
            var file = input.files && input.files[0];
            if (!file || file.type.indexOf('image/') !== 0) {
                return;
            }

            var url = URL.createObjectURL(file);
            preview.addEventListener('load', function () {
                URL.revokeObjectURL(url);
            }, { once: true });

            preview.src = url;
            preview.hidden = false;
            preview.classList.remove('is-broken');

            if (placeholder) {
                placeholder.hidden = true;
            }
        });
    });

    document.querySelectorAll('img').forEach(function (image) {
        image.addEventListener('error', function () {
            image.classList.add('is-broken');

            var container = image.closest('.postingan-card-media, .detail-hero');
            if (container) {
                container.classList.add('is-missing');
            }
        }, { once: true });
    });
})();
