(function () {
    'use strict';

    var page = document.getElementById('detail-page');
    if (!page) {
        return;
    }

    var ui = window.PunyaUI || {
        toast: function () { },
        confirm: function (message) {
            return Promise.resolve(window.confirm(message));
        }
    };

    var base = page.dataset.base || '';
    var root = page.dataset.root || '';
    var csrf = page.dataset.csrf || '';
    var id = page.dataset.id || '';

    var list = document.getElementById('daftar-komentar');
    var form = document.getElementById('form-komentar');
    var input = document.getElementById('input-komentar');
    var submitButton = document.getElementById('btn-kirim');
    var claimButton = document.getElementById('btn-klaim');
    var claimBox = document.getElementById('kotak-klaim');
    var countLabel = document.getElementById('diskusi-count');

    function updateCount() {
        if (!list || !countLabel) {
            return;
        }

        var total = list.querySelectorAll('.komentar-item').length;
        countLabel.textContent = total + ' komentar';

        if (total === 0 && !document.getElementById('pesan-kosong')) {
            var kosong = document.createElement('p');
            kosong.className = 'empty-state empty-state-inline';
            kosong.id = 'pesan-kosong';
            kosong.textContent = 'Belum ada diskusi untuk laporan ini.';
            list.appendChild(kosong);
        }
    }

    function initial(name) {
        var trimmed = (name || '').trim();
        return trimmed ? trimmed.charAt(0).toUpperCase() : '?';
    }

    function buildDeleteForm(commentId) {
        var deleteForm = document.createElement('form');
        deleteForm.method = 'post';
        deleteForm.action = root + '/komentar/' + commentId + '/delete';
        deleteForm.className = 'inline-form komentar-delete form-hapus-komentar';
        deleteForm.dataset.ajaxConfirm = 'Hapus komentar ini?';

        var token = document.createElement('input');
        token.type = 'hidden';
        token.name = '_csrf';
        token.value = csrf;

        var button = document.createElement('button');
        button.type = 'submit';
        button.className = 'btn-text btn-text-sm';
        button.textContent = 'Hapus';

        deleteForm.append(token, button);

        return deleteForm;
    }

    function appendComment(comment) {
        if (!list) {
            return;
        }

        var emptyState = document.getElementById('pesan-kosong');
        if (emptyState) {
            emptyState.remove();
        }

        var item = document.createElement('div');
        item.className = 'komentar-item';
        item.id = 'komentar-' + comment.id_komentar;

        var avatar = document.createElement('span');
        avatar.className = 'komentar-avatar';
        avatar.setAttribute('aria-hidden', 'true');
        avatar.textContent = initial(comment.nama_pengguna);

        var body = document.createElement('div');
        body.className = 'komentar-body';

        var meta = document.createElement('div');
        meta.className = 'komentar-meta';

        var name = document.createElement('span');
        name.className = 'komentar-name';
        name.textContent = comment.nama_pengguna || 'Pengguna';

        var date = document.createElement('span');
        date.className = 'komentar-date';
        date.textContent = comment.waktu_komentar || '';

        var text = document.createElement('p');
        text.className = 'komentar-text';
        text.textContent = comment.isi_komentar || '';

        meta.append(name, date);
        body.append(meta, text);
        item.append(avatar, body, buildDeleteForm(comment.id_komentar));
        list.appendChild(item);
    }

    function requestJson(url, options) {
        return fetch(url, options).then(function (response) {
            return response.json().then(function (data) {
                return { ok: response.ok, data: data };
            });
        });
    }

    if (form && input && submitButton) {
        form.addEventListener('submit', function (event) {
            event.preventDefault();

            var isi = input.value.trim();
            if (!isi) {
                input.focus();
                return;
            }

            submitButton.disabled = true;
            submitButton.textContent = 'Mengirim...';

            requestJson(form.action, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: new FormData(form)
            }).then(function (result) {
                if (!result.ok || !result.data.success) {
                    throw new Error(result.data.message || 'Gagal mengirim komentar.');
                }

                appendComment(result.data.data);
                form.reset();
                input.dispatchEvent(new Event('input'));
                updateCount();
                ui.toast('Komentar terkirim.', 'success');
            }).catch(function (error) {
                ui.toast(error.message || 'Terjadi kesalahan jaringan.', 'error');
            }).finally(function () {
                submitButton.disabled = false;
                submitButton.textContent = 'Kirim komentar';
            });
        });
    }

    if (list) {
        list.addEventListener('submit', function (event) {
            var deleteForm = event.target.closest('.form-hapus-komentar');
            if (!deleteForm) {
                return;
            }

            event.preventDefault();

            ui.confirm(deleteForm.dataset.ajaxConfirm || 'Hapus komentar ini?', {
                confirmLabel: 'Ya, hapus'
            }).then(function (confirmed) {
                if (!confirmed) {
                    return null;
                }

                return requestJson(deleteForm.action, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: new FormData(deleteForm)
                });
            }).then(function (result) {
                if (!result) {
                    return;
                }

                if (!result.ok || !result.data.success) {
                    throw new Error(result.data.message || 'Gagal menghapus komentar.');
                }

                var deletedId = result.data.data ? result.data.data.id_komentar : result.data.id_komentar;
                var item = document.getElementById('komentar-' + deletedId);
                if (item) {
                    item.remove();
                }

                updateCount();
                ui.toast('Komentar dihapus.', 'success');
            }).catch(function (error) {
                ui.toast(error.message || 'Terjadi kesalahan jaringan.', 'error');
            });
        });
    }

    // --- Handler Modal Klaim Privat (Bukti Teks + Foto Opsional) ---
    var claimDialog = document.getElementById('claim-dialog');
    var claimForm = document.getElementById('form-claim');
    var claimCancelBtn = document.getElementById('btn-batal-claim');
    var claimSubmitBtn = document.getElementById('btn-kirim-claim');
    var claimHint = document.getElementById('hint-klaim');

    if (claimButton && claimDialog && claimForm) {
        claimButton.addEventListener('click', function () {
            claimForm.reset();
            if (typeof claimDialog.showModal === 'function') {
                claimDialog.showModal();
            } else {
                claimDialog.setAttribute('open', 'open');
            }
        });

        if (claimCancelBtn) {
            claimCancelBtn.addEventListener('click', function () {
                if (typeof claimDialog.close === 'function') {
                    claimDialog.close();
                } else {
                    claimDialog.removeAttribute('open');
                }
            });
        }

        claimForm.addEventListener('submit', function (event) {
            event.preventDefault();

            if (claimSubmitBtn) {
                claimSubmitBtn.disabled = true;
                claimSubmitBtn.textContent = 'Mengirim...';
            }

            requestJson(claimForm.action, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: new FormData(claimForm)
            }).then(function (result) {
                if (!result.ok || !result.data.success) {
                    throw new Error(result.data.message || 'Gagal mengajukan klaim.');
                }

                if (typeof claimDialog.close === 'function') {
                    claimDialog.close();
                } else {
                    claimDialog.removeAttribute('open');
                }

                if (claimBox) {
                    claimBox.hidden = true;
                }
                if (claimHint) {
                    claimHint.remove();
                }
                if (input) {
                    input.disabled = false;
                    input.placeholder = 'Tulis info atau pertanyaan untuk penemu...';
                }
                if (submitButton) {
                    submitButton.disabled = false;
                }

                ui.toast(result.data.message || 'Klaim berhasil dikirim.', 'success');

                if (input) {
                    input.focus();
                }
            }).catch(function (error) {
                ui.toast(error.message || 'Terjadi kesalahan koneksi.', 'error');
            }).finally(function () {
                if (claimSubmitBtn) {
                    claimSubmitBtn.disabled = false;
                    claimSubmitBtn.textContent = 'Kirim Klaim';
                }
            });
        });
    }
    // --- Handler Modal Report (Postingan & Komentar) ---
    var reportDialog = document.getElementById('report-dialog');
    var reportForm = document.getElementById('form-report');
    var reportTargetLabel = document.getElementById('report-dialog-target');
    var reportKategori = document.getElementById('report-kategori');
    var reportAlasan = document.getElementById('report-alasan');
    var reportAlasanReq = document.getElementById('report-alasan-req');
    var reportCancelBtn = document.getElementById('btn-batal-report');
    var reportSubmitBtn = document.getElementById('btn-kirim-report');

    function syncAlasanRequirement() {
        if (!reportKategori || !reportAlasan) {
            return;
        }
        var isOther = reportKategori.value === 'other';
        reportAlasan.required = isOther;
        if (reportAlasanReq) {
            reportAlasanReq.textContent = isOther ? '(Wajib diisi)' : '(Opsional)';
        }
    }

    if (reportDialog && reportForm) {
        page.addEventListener('click', function (event) {
            var trigger = event.target.closest('[data-report-trigger]');
            if (!trigger) {
                return;
            }

            reportForm.reset();
            reportForm.action = trigger.dataset.reportUrl || '';
            if (reportTargetLabel) {
                reportTargetLabel.textContent = trigger.dataset.reportLabel || 'Laporkan konten ini';
            }
            syncAlasanRequirement();

            if (typeof reportDialog.showModal === 'function') {
                reportDialog.showModal();
            } else {
                reportDialog.setAttribute('open', 'open');
            }
        });

        if (reportKategori) {
            reportKategori.addEventListener('change', syncAlasanRequirement);
        }

        if (reportCancelBtn) {
            reportCancelBtn.addEventListener('click', function () {
                if (typeof reportDialog.close === 'function') {
                    reportDialog.close();
                } else {
                    reportDialog.removeAttribute('open');
                }
            });
        }

        reportForm.addEventListener('submit', function (event) {
            event.preventDefault();

            if (!reportForm.action) {
                return;
            }

            if (reportSubmitBtn) {
                reportSubmitBtn.disabled = true;
                reportSubmitBtn.textContent = 'Mengirim...';
            }

            requestJson(reportForm.action, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: new FormData(reportForm)
            }).then(function (result) {
                if (!result.ok || !result.data.success) {
                    throw new Error(result.data.message || 'Gagal mengirim laporan.');
                }

                if (typeof reportDialog.close === 'function') {
                    reportDialog.close();
                } else {
                    reportDialog.removeAttribute('open');
                }

                ui.toast(result.data.message || 'Laporan berhasil dikirim.', 'success');
            }).catch(function (error) {
                ui.toast(error.message || 'Terjadi kesalahan jaringan.', 'error');
            }).finally(function () {
                if (reportSubmitBtn) {
                    reportSubmitBtn.disabled = false;
                    reportSubmitBtn.textContent = 'Kirim Laporan';
                }
            });
        });
    }
    updateCount();
})();
