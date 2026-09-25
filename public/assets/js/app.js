(() => {
    'use strict';

    const body = document.body;
    const scrim = document.querySelector('.drawer-scrim');
    const toastRegion = document.getElementById('toast-region');
    let activePanel = null;
    let previousFocus = null;

    const focusableSelector = [
        'a[href]',
        'button:not([disabled])',
        'input:not([disabled]):not([type="hidden"])',
        'select:not([disabled])',
        'textarea:not([disabled])',
        '[tabindex]:not([tabindex="-1"])'
    ].join(',');

    const visibleFocusable = (panel) => Array.from(panel.querySelectorAll(focusableSelector)).filter((element) => {
        return !element.hidden && element.getClientRects().length > 0;
    });

    const showScrim = () => {
        if (!scrim) {
            return;
        }

        scrim.hidden = false;
        body.classList.add('drawer-open');
    };

    const hideScrim = () => {
        if (!scrim) {
            return;
        }

        scrim.hidden = true;
        body.classList.remove('drawer-open');
    };

    const openPanel = (panel, trigger) => {
        if (!panel) {
            return;
        }

        if (activePanel && activePanel !== panel) {
            closePanel(activePanel);
        }

        previousFocus = trigger instanceof HTMLElement ? trigger : document.activeElement;
        activePanel = panel;
        panel.classList.add('is-open');
        panel.removeAttribute('aria-hidden');
        showScrim();

        if (trigger instanceof HTMLElement) {
            trigger.setAttribute('aria-expanded', 'true');
        }

        window.requestAnimationFrame(() => {
            const focusable = visibleFocusable(panel);
            if (focusable.length > 0) {
                focusable[0].focus();
            } else {
                panel.focus();
            }
        });
    };

    const closePanel = (panel) => {
        if (!panel) {
            return;
        }

        panel.classList.remove('is-open');
        panel.setAttribute('aria-hidden', 'true');
        document.querySelectorAll(`[data-drawer-open="${panel.id}"], [data-modal-open="${panel.id}"]`).forEach((trigger) => {
            trigger.setAttribute('aria-expanded', 'false');
        });

        if (activePanel === panel) {
            activePanel = null;
            hideScrim();

            if (previousFocus instanceof HTMLElement && document.contains(previousFocus)) {
                previousFocus.focus();
            }

            previousFocus = null;
        }
    };

    const toast = (message, type = 'info', duration = 4500) => {
        if (!toastRegion || typeof message !== 'string' || message.trim() === '') {
            return;
        }

        const allowedTypes = ['info', 'success', 'danger', 'warning'];
        const toastType = allowedTypes.includes(type) ? type : 'info';
        const element = document.createElement('div');
        const indicator = document.createElement('span');
        const content = document.createElement('span');
        const close = document.createElement('button');

        element.className = `toast toast-${toastType}`;
        element.setAttribute('role', toastType === 'danger' ? 'alert' : 'status');
        indicator.className = 'toast-indicator';
        indicator.setAttribute('aria-hidden', 'true');
        content.textContent = message.trim();
        close.className = 'toast-close';
        close.type = 'button';
        close.setAttribute('aria-label', 'Dismiss notification');
        close.textContent = '×';
        element.append(indicator, content, close);
        toastRegion.appendChild(element);

        const dismiss = () => {
            element.classList.remove('is-visible');
            window.setTimeout(() => element.remove(), 220);
        };

        close.addEventListener('click', dismiss);
        element.addEventListener('mouseenter', () => window.clearTimeout(closeTimer));
        element.addEventListener('mouseleave', () => window.clearTimeout(closeTimer));

        let closeTimer = window.setTimeout(dismiss, Math.max(1500, Number(duration) || 4500));
        window.requestAnimationFrame(() => element.classList.add('is-visible'));
    };

    document.addEventListener('click', (event) => {
        const drawerTrigger = event.target.closest('[data-drawer-open]');
        const modalTrigger = event.target.closest('[data-modal-open]');
        const closeTrigger = event.target.closest('[data-drawer-close], [data-modal-close]');
        const confirmTrigger = event.target.closest('[data-confirm]');
        const passwordTrigger = event.target.closest('[data-password-toggle]');

        if (drawerTrigger) {
            event.preventDefault();
            openPanel(document.getElementById(drawerTrigger.dataset.drawerOpen), drawerTrigger);
            return;
        }

        if (modalTrigger) {
            event.preventDefault();
            openPanel(document.getElementById(modalTrigger.dataset.modalOpen), modalTrigger);
            return;
        }

        if (closeTrigger) {
            event.preventDefault();
            const panel = closeTrigger.closest('.drawer, .modal, .sidebar') || activePanel;
            closePanel(panel);
            return;
        }

        if (confirmTrigger) {
            const message = confirmTrigger.dataset.confirm || 'Are you sure you want to continue?';
            if (!window.confirm(message)) {
                event.preventDefault();
            }
            return;
        }

        if (passwordTrigger) {
            event.preventDefault();
            const field = document.getElementById(passwordTrigger.dataset.passwordToggle);
            if (field instanceof HTMLInputElement) {
                const reveal = field.type === 'password';
                field.type = reveal ? 'text' : 'password';
                passwordTrigger.setAttribute('aria-label', reveal ? 'Hide password' : 'Show password');
                passwordTrigger.textContent = reveal ? 'Hide' : 'Show';
            }
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && activePanel) {
            event.preventDefault();
            closePanel(activePanel);
            return;
        }

        if (event.key !== 'Tab' || !activePanel) {
            return;
        }

        const focusable = visibleFocusable(activePanel);
        if (focusable.length === 0) {
            event.preventDefault();
            return;
        }

        const first = focusable[0];
        const last = focusable[focusable.length - 1];

        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    });

    const describeSize = (bytes) => {
        if (bytes < 1024) {
            return `${bytes} B`;
        }

        if (bytes < 1024 * 1024) {
            return `${Math.round(bytes / 1024)} KB`;
        }

        return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
    };

    const initCoverInputs = () => {
        document.querySelectorAll('[data-cover-input]').forEach((input) => {
            if (!(input instanceof HTMLInputElement)) {
                return;
            }

            const field = input.closest('.cover-upload');
            const readout = field ? field.querySelector('[data-cover-filename]') : null;
            const accepted = (input.getAttribute('accept') || '')
                .split(',')
                .map((type) => type.trim())
                .filter((type) => type.length > 0);

            const reset = () => {
                if (readout instanceof HTMLElement) {
                    readout.textContent = '';
                }
            };

            input.addEventListener('change', () => {
                const file = input.files && input.files[0];

                if (!(file instanceof File) || file.size === 0) {
                    reset();
                    return;
                }

                if (file.size > 5 * 1024 * 1024) {
                    input.value = '';
                    reset();
                    toast(`${file.name} is larger than the 5 MB limit.`, 'danger');
                    return;
                }

                if (accepted.length > 0 && accepted.indexOf(file.type) === -1) {
                    input.value = '';
                    reset();
                    toast(`${file.name} is not a supported image type.`, 'danger');
                    return;
                }

                if (readout instanceof HTMLElement) {
                    readout.textContent = `${file.name} · ${describeSize(file.size)}`;
                }
            });

            input.addEventListener('input', reset);
        });
    };

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('[data-toast-message]').forEach((element) => {
            toast(element.dataset.toastMessage || '', element.dataset.toastType || 'info');
            element.remove();
        });

        initCoverInputs();
    });

    window.AuraLib = Object.freeze({
        closeDrawer: closePanel,
        openDrawer: openPanel,
        toast
    });
})();
