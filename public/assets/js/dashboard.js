(function () {
    'use strict';

    const cards = Array.from(document.querySelectorAll('[data-metric-card]'));

    if (cards.length === 0) {
        return;
    }

    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const formatter = new Intl.NumberFormat();
    const visualProfile = [0.38, 0.64, 0.48, 0.82, 0.58, 1, 0.72];
    const values = cards.map((card) => Math.max(0, Number.parseInt(card.dataset.metricValue || '0', 10) || 0));
    const maximum = Math.max.apply(Math, values.concat([1]));
    const visualArrays = cards.map((card, index) => {
        const valueRatio = values[index] / maximum;
        const progress = Math.max(0, Math.min(100, Number.parseInt(card.dataset.metricProgress || '0', 10) || 0));
        const weightedRatio = valueRatio * 0.78 + progress / 100 * 0.22;

        return visualProfile.map((point) => Math.max(3, Math.round(weightedRatio * point * 100)));
    });

    const renderVisualArray = (target, series, label) => {
        target.textContent = '';
        series.forEach((height, index) => {
            const bar = document.createElement('i');
            bar.style.setProperty('--bar-height', String(height) + '%');
            bar.title = label + ' sample ' + (index + 1) + ': ' + height + '%';
            target.appendChild(bar);
        });
    };

    const animateCounter = (element, target) => {
        if (reduceMotion) {
            element.textContent = formatter.format(target);
            return;
        }

        const startTime = performance.now();
        const duration = 760;
        element.textContent = formatter.format(0);

        const frame = (timestamp) => {
            const progress = Math.min(1, (timestamp - startTime) / duration);
            const eased = 1 - Math.pow(1 - progress, 3);
            element.textContent = formatter.format(Math.round(target * eased));

            if (progress < 1) {
                window.requestAnimationFrame(frame);
            }
        };

        window.requestAnimationFrame(frame);
    };

    const revealCard = (card, index) => {
        if (card.dataset.dashboardReady === 'true') {
            return;
        }

        card.dataset.dashboardReady = 'true';
        const target = Math.max(0, Number.parseInt(card.dataset.metricValue || '0', 10) || 0);
        const display = card.querySelector('[data-metric-display]');
        const visual = card.querySelector('[data-metric-visual]');
        renderVisualArray(visual, visualArrays[index], card.dataset.metricKey || 'Metric');
        animateCounter(display, target);
        window.setTimeout(() => card.classList.add('is-ready'), reduceMotion ? 0 : 70 + index * 45);
    };

    if (reduceMotion || !('IntersectionObserver' in window)) {
        cards.forEach(revealCard);
    } else {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) {
                    return;
                }

                const index = cards.indexOf(entry.target);
                revealCard(entry.target, index);
                observer.unobserve(entry.target);
            });
        }, { threshold: 0.28 });

        cards.forEach((card) => observer.observe(card));
    }

    const progressBars = Array.from(document.querySelectorAll('.progress-track span'));
    const trendBars = Array.from(document.querySelectorAll('.trend-bar'));
    const targets = progressBars.map((bar) => bar.style.width).concat(trendBars.map((bar) => bar.style.height));

    if (!reduceMotion) {
        progressBars.forEach((bar) => { bar.style.width = '0%'; });
        trendBars.forEach((bar) => { bar.style.height = '3px'; });
    }

    window.requestAnimationFrame(() => {
        window.setTimeout(() => {
            progressBars.forEach((bar, index) => { bar.style.width = targets[index]; });
            trendBars.forEach((bar, index) => { bar.style.height = targets[progressBars.length + index]; });
        }, reduceMotion ? 0 : 90);
    });
}());
