(() => {
    'use strict';

    const form = document.querySelector('[data-catalog-filter]');

    if (!(form instanceof HTMLFormElement)) {
        return;
    }

    const search = form.querySelector('[data-catalog-search]');
    const results = document.querySelector('[data-catalog-results]');
    const total = document.querySelector('[data-catalog-total]');
    const copies = document.querySelector('[data-catalog-copies]');
    const available = document.querySelector('[data-catalog-available]');
    const liveStatus = form.querySelector('[data-catalog-search-status]');
    const endpoint = form.dataset.catalogEndpoint;

    if (!(search instanceof HTMLInputElement) || !(results instanceof HTMLElement) || typeof endpoint !== 'string') {
        return;
    }

    let debounceTimer = 0;
    let activeController = null;
    let requestSequence = 0;

    const number = (value) => Number(value || 0).toLocaleString();

    const setBusy = (busy) => {
        results.setAttribute('aria-busy', busy ? 'true' : 'false');
    };

    const announce = (message) => {
        if (liveStatus instanceof HTMLElement) {
            liveStatus.textContent = message;
        }
    };

    const updateAddress = (parameters) => {
        const address = new URL(window.location.href);
        address.search = parameters.toString();
        window.history.replaceState({}, '', address);
    };

    const render = (payload) => {
        if (!payload || payload.minimum || typeof payload.html !== 'string') {
            return;
        }

        results.innerHTML = payload.html;

        if (total instanceof HTMLElement) {
            total.textContent = number(payload.total);
        }

        if (copies instanceof HTMLElement) {
            copies.textContent = number(payload.copies);
        }

        if (available instanceof HTMLElement) {
            available.textContent = number(payload.available);
        }

        const resultStatus = results.querySelector('[data-catalog-result-status]');

        if (resultStatus instanceof HTMLElement) {
            resultStatus.textContent = payload.total > 0
                ? `${number(payload.total)} matching ${payload.total === 1 ? 'title' : 'titles'}`
                : 'No titles found';
        }

        announce(`${payload.total} matching ${payload.total === 1 ? 'title' : 'titles'}`);
    };

    const searchCatalog = async () => {
        const parameters = new URLSearchParams(new FormData(form));
        parameters.delete('page');
        parameters.set('page', '1');
        updateAddress(parameters);

        if (activeController) {
            activeController.abort();
        }

        activeController = new AbortController();
        const sequence = ++requestSequence;
        setBusy(true);
        announce('Searching catalog');

        try {
            const response = await fetch(`${endpoint}?${parameters.toString()}`, {
                method: 'GET',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin',
                signal: activeController.signal
            });

            if (!response.ok) {
                throw new Error('Search request failed');
            }

            const payload = await response.json();

            if (sequence !== requestSequence) {
                return;
            }

            render(payload);
        } catch (error) {
            if (error instanceof DOMException && error.name === 'AbortError') {
                return;
            }

            announce('Live search is temporarily unavailable');
        } finally {
            if (sequence === requestSequence) {
                setBusy(false);
            }
        }
    };

    search.addEventListener('input', () => {
        window.clearTimeout(debounceTimer);
        const length = search.value.trim().length;

        if (length === 1) {
            if (activeController) {
                activeController.abort();
            }

            requestSequence += 1;
            setBusy(false);
            announce('Type one more character to search');
            return;
        }

        debounceTimer = window.setTimeout(searchCatalog, 280);
    });

    form.addEventListener('submit', (event) => {
        const length = search.value.trim().length;

        if (length === 1) {
            event.preventDefault();
            announce('Enter at least two characters to search');
            return;
        }

        if (length === 0) {
            return;
        }

        event.preventDefault();
        window.clearTimeout(debounceTimer);
        searchCatalog();
    });
})();
