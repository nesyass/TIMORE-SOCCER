(() => {
    const system = window.matchMedia('(prefers-color-scheme: dark)');
    const key = 'timore-theme';
    const read = () => {
        try {
            const value = localStorage.getItem(key);
            return ['light', 'dark', 'system'].includes(value) ? value : 'dark';
        } catch {
            return 'dark';
        }
    };
    function applyTheme() {
        const theme = choice === 'system'
            ? (system.matches ? 'dark' : 'light')
            : choice;
        document.documentElement.dataset.theme = theme;
        if (toggle) {
            const dark = theme === 'dark';
            toggle.setAttribute('aria-pressed', String(dark));
            toggle.title = dark ? 'Gunakan mode terang' : 'Gunakan mode gelap';
        }
    }
    let choice = read();
    let toggle;
    applyTheme();
    system.addEventListener('change', applyTheme);
    window.addEventListener('storage', (event) => {
        if (event.key !== key && event.key !== null) return;
        choice = read();
        applyTheme();
    });

    document.addEventListener('DOMContentLoaded', () => {
        const account = document.querySelector('.account');
        if (account) {
            document.addEventListener('click', (event) => {
                if (account.open && !account.contains(event.target)) account.open = false;
            });
            account.addEventListener('keydown', (event) => {
                if (event.key !== 'Escape' || !account.open) return;
                account.open = false;
                account.querySelector('summary').focus();
            });
        }

        document.querySelectorAll('[data-upload]').forEach((area) => {
            const input = area.querySelector('input[type="file"]');
            const image = area.querySelector('img');
            const error = area.querySelector('[data-upload-error]');
            const original = image.getAttribute('src');
            const remove = area.querySelector('input[type="checkbox"]');
            const clear = area.querySelector('[data-upload-clear]');
            const acceptedTypes = input.accept.split(',');
            const fileTypeLabel = acceptedTypes.includes('application/pdf') ? 'JPG, PNG, WebP, atau PDF' : 'JPG, PNG, atau WebP';
            let preview;

            function updatePreview() {
                if (preview) URL.revokeObjectURL(preview);
                const file = input.files[0];
                const invalid = file && (
                    (file.type && !acceptedTypes.includes(file.type)) || file.size > 2 * 1024 * 1024
                );
                const message = invalid ? `Pilih file ${fileTypeLabel} maksimal 2 MB.` : '';
                input.setCustomValidity(message);
                error.textContent = message;
                clear.hidden = !file;
                preview = !invalid && file?.type.startsWith('image/') ? URL.createObjectURL(file) : null;
                if (preview && remove) remove.checked = false;
                const source = preview ?? original;
                if (source) image.src = source;
                else image.removeAttribute('src');
                image.hidden = !source;
            }

            input.addEventListener('change', updatePreview);
            clear.addEventListener('click', () => {
                input.value = '';
                updatePreview();
                input.focus();
            });
            area.addEventListener('dragover', (event) => {
                if (!event.dataTransfer.types.includes('Files')) return;
                event.preventDefault();
                area.classList.add('drag-over');
            });
            area.addEventListener('dragleave', (event) => {
                if (!area.contains(event.relatedTarget)) area.classList.remove('drag-over');
            });
            area.addEventListener('drop', (event) => {
                event.preventDefault();
                area.classList.remove('drag-over');
                if (event.dataTransfer.files.length !== 1) {
                    error.textContent = 'Seret satu file saja.';
                    return;
                }
                input.files = event.dataTransfer.files;
                updatePreview();
            });
        });

        const confirmation = document.querySelector('#confirm-action');
        let pendingForm = null;
        const submitStatus = document.createElement('div');
        submitStatus.className = 'sr-only';
        submitStatus.setAttribute('role', 'status');
        document.body.append(submitStatus);

        document.addEventListener('click', async (event) => {
            const opener = event.target.closest('[data-open-dialog]');
            if (opener) document.getElementById(opener.dataset.openDialog)?.showModal();
            if (event.target.closest('[data-close-dialog]')) event.target.closest('dialog')?.close();
            const repeatButton = event.target.closest('[data-add-repeat], [data-remove-repeat]');
            if (repeatButton) {
                const field = repeatButton.closest('[data-repeat]');
                const items = field.querySelector('[data-repeat-items]');
                const add = field.querySelector('[data-add-repeat]');
                const status = field.querySelector('[data-repeat-feedback]');
                if (repeatButton === add) {
                    if (items.children.length >= 10) return;
                    const row = field.querySelector('template').content.cloneNode(true);
                    const next = Number(field.dataset.next);
                    field.dataset.next = String(next + 1);
                    row.querySelectorAll('[id], [for], [name]').forEach((element) => {
                        for (const attribute of ['id', 'for', 'name']) {
                            const value = element.getAttribute(attribute);
                            if (value?.includes('__ROW__')) element.setAttribute(attribute, value.replace('__ROW__', String(next)));
                        }
                    });
                    const firstInput = row.querySelector('input, select');
                    items.append(row);
                    firstInput.focus();
                    status.textContent = 'Baris ditambahkan.';
                } else {
                    const current = repeatButton.closest('[data-repeat-item]');
                    const next = current.nextElementSibling ?? current.previousElementSibling;
                    current.remove();
                    add.disabled = false;
                    (next?.querySelector('input, select') ?? add).focus();
                    status.textContent = 'Baris dihapus.';
                }
                [...items.children].forEach((row, index) => {
                    row.querySelectorAll('[data-repeat-ordinal]').forEach((label) => { label.textContent = index + 1; });
                });
                add.disabled = items.children.length >= 10;
            }
            const copy = event.target.closest('[data-copy-text]');
            if (copy) {
                const field = document.getElementById(copy.dataset.copyText);
                const feedback = copy.parentElement.querySelector('[data-copy-status]');
                if (!field || !feedback) return;
                try {
                    await navigator.clipboard.writeText(field.value);
                    feedback.textContent = 'Akses disalin.';
                } catch {
                    field.focus();
                    field.select();
                    feedback.textContent = 'Teks dipilih. Salin dari perangkatmu.';
                }
            }
        });

        document.querySelector('dialog[data-restore-form]')?.showModal();
        document.querySelector('dialog[open] [aria-invalid="true"]')?.focus();

        document.addEventListener('submit', (event) => {
            const form = event.target;
            if (form.dataset.submitting !== undefined) {
                event.preventDefault();
                return;
            }
            if (confirmation && form.hasAttribute('data-confirm-title') && form !== pendingForm) {
                event.preventDefault();
                const button = document.querySelector('#confirm-button');
                document.querySelector('#confirm-title').textContent = form.dataset.confirmTitle;
                document.querySelector('#confirm-description').textContent = form.dataset.confirmText;
                button.textContent = form.dataset.confirmAction;
                button.className = form.hasAttribute('data-confirm-danger') ? 'btn danger' : 'btn primary';
                pendingForm = form;
                confirmation.returnValue = '';
                confirmation.showModal();
                return;
            }

            if (!form.matches('.data-form, .login-card, .filters, .inline-filter, [data-confirm-title], .account-menu form')) return;
            form.dataset.submitting = '';
            form.setAttribute('aria-busy', 'true');
            const button = event.submitter ?? form.querySelector('button[type="submit"]');
            if (button && !button.disabled) {
                button.dataset.submitting = '';
                button.disabled = true;
            }
            submitStatus.textContent = form.method === 'get' ? 'Memuat hasil…' : 'Memproses…';
        });

        confirmation?.addEventListener('close', () => {
            if (confirmation.returnValue === 'confirm') pendingForm?.requestSubmit(pendingForm.querySelector('button[type="submit"]'));
            pendingForm = null;
        });

        window.addEventListener('pageshow', () => {
            submitStatus.textContent = '';
        });
    });

    window.addEventListener('pageshow', () => {
        document.querySelectorAll('[data-submitting]').forEach((element) => {
            if (element instanceof HTMLButtonElement) element.disabled = false;
            element.removeAttribute('aria-busy');
            delete element.dataset.submitting;
        });
    });

    document.addEventListener('DOMContentLoaded', () => {
        toggle = document.querySelector('[data-theme-toggle]');
        if (toggle) {
            applyTheme();
            toggle.hidden = false;
            toggle.addEventListener('click', () => {
                choice = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
                applyTheme();
                try { localStorage.setItem(key, choice); } catch {}
            });
        }

        const drawer = document.querySelector('#navigation-drawer');
        const launcher = document.querySelector('#navigation-launcher');
        if (!drawer || !launcher) return;
        const breakpoint = window.matchMedia('(min-width: 1024px)');
        launcher.addEventListener('click', () => {
            drawer.showModal();
            launcher.setAttribute('aria-expanded', 'true');
            document.body.classList.add('drawer-open');
        });
        drawer.addEventListener('click', (event) => {
            if (event.target === drawer || event.target.closest('a[href]')) drawer.close();
        });
        drawer.addEventListener('close', () => {
            launcher.setAttribute('aria-expanded', 'false');
            document.body.classList.remove('drawer-open');
            launcher.focus();
        });
        breakpoint.addEventListener('change', () => { if (breakpoint.matches) drawer.close(); });
    });
})();
