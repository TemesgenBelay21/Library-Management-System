(() => {
    'use strict';

    const MINIMUM_QUERY = 2;
    const DEBOUNCE_MS = 280;
    const EMPTY_LABEL = 'No matches';

    const placeholders = {
        members: 'Choose an active member',
        books: 'Choose an available book'
    };

    const formatters = {
        members: (record) => `${record.name} · ${record.email}`,
        books: (record) => `${record.title} · ${record.author} · ${record.available_copies} available`
    };

    const build = (input) => {
        const select = document.getElementById(input.dataset.issueTarget);
        const field = input.closest('.form-field');
        const status = field ? field.querySelector('[data-issue-status]') : null;

        if (!(select instanceof HTMLSelectElement) || typeof input.dataset.issueEndpoint !== 'string') {
            return null;
        }

        return {
            input,
            select,
            status: status instanceof HTMLElement ? status : null,
            endpoint: input.dataset.issueEndpoint,
            collection: input.dataset.issueCollection || '',
            format: formatters[input.dataset.issueCollection] || formatters.members
        };
    };

    const announce = (context, message) => {
        if (context.status) {
            context.status.textContent = message;
        }
    };

    const setOptions = (context, records) => {
        const label = placeholders[context.collection] || EMPTY_LABEL;
        const fragment = document.createDocumentFragment();
        const placeholder = document.createElement('option');

        placeholder.value = '';
        placeholder.textContent = label;
        fragment.appendChild(placeholder);

        records.forEach((record) => {
            if (!record || typeof record.id === 'undefined') {
                return;
            }

            const option = document.createElement('option');

            option.value = String(record.id);
            option.textContent = context.format(record);
            fragment.appendChild(option);
        });

        context.select.replaceChildren(fragment);
    };

    const run = async (context, sequence) => {
        const query = context.input.value.trim();

        try {
            const response = await fetch(`${context.endpoint}?q=${encodeURIComponent(query)}`, {
                method: 'GET',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin',
                signal: context.controller.signal
            });

            if (!response.ok) {
                throw new Error('Lookup failed');
            }

            const payload = await response.json();
            const records = Array.isArray(payload[context.collection]) ? payload[context.collection] : [];

            if (sequence !== context.sequence) {
                return;
            }

            setOptions(context, records);
            announce(
                context,
                records.length === 0
                    ? `No ${context.collection} match "${query}"`
                    : `${records.length} matching ${context.collection}`
            );
        } catch (error) {
            if (error instanceof DOMException && error.name === 'AbortError') {
                return;
            }

            announce(context, 'Live lookup is temporarily unavailable');
        }
    };

    const search = (context) => {
        const query = context.input.value.trim();
        const length = query.length;

        if (context.timer) {
            window.clearTimeout(context.timer);
        }

        if (context.controller) {
            context.controller.abort();
        }

        context.sequence += 1;
        const sequence = context.sequence;

        if (length < MINIMUM_QUERY) {
            context.input.setAttribute('aria-busy', 'false');
            announce(
                context,
                length === 0 ? '' : `Type ${MINIMUM_QUERY - length} more character to search`
            );
            return;
        }

        context.controller = new AbortController();
        context.input.setAttribute('aria-busy', 'true');
        context.timer = window.setTimeout(() => run(context, sequence), DEBOUNCE_MS);
    };

    document.querySelectorAll('[data-issue-search]').forEach((input) => {
        const context = input instanceof HTMLInputElement ? build(input) : null;

        if (context === null) {
            return;
        }

        context.sequence = 0;
        context.timer = 0;
        context.controller = null;
        input.addEventListener('input', () => search(context));
        input.addEventListener('search', () => search(context));
    });
})();
