(function () {
    function toMinutes(value) {
        var match = /^(\d{1,2}):(\d{2})/.exec(value || '');
        return match ? parseInt(match[1], 10) * 60 + parseInt(match[2], 10) : null;
    }

    function updateDay(row, i18n) {
        var active = row.querySelector('[data-psch-toggle]').checked;
        var startInput = row.querySelector('[data-psch-start]');
        var endInput = row.querySelector('[data-psch-end]');
        var interval = Math.max(parseInt(row.querySelector('[data-psch-interval]').value, 10) || 0, 4);
        var range = row.querySelector('[data-psch-range]');
        var summary = row.querySelector('[data-psch-summary]');
        var start = toMinutes(startInput.value);
        var end = toMinutes(endInput.value);
        var invalid = active && (start === null || end === null || end <= start);

        row.classList.toggle('is-off', !active);
        row.classList.toggle('is-invalid', invalid);

        if (!active) {
            range.style.width = '0';
            summary.textContent = i18n.off;
            return;
        }
        if (invalid) {
            range.style.width = '0';
            summary.textContent = i18n.invalid;
            return;
        }

        // Same rule as the server: only whole slots that fit before closing time.
        var slots = Math.floor((end - start) / interval);
        range.style.left = (start / 1440 * 100) + '%';
        range.style.width = ((end - start) / 1440 * 100) + '%';
        summary.textContent = slots + ' ' + (slots === 1 ? i18n.slot : i18n.slots) + ' · ' + startInput.value + '–' + endInput.value;
    }

    function init() {
        var root = document.querySelector('.psch');
        if (!root) {
            return;
        }

        var i18n = {
            slot: root.getAttribute('data-i18n-slot'),
            slots: root.getAttribute('data-i18n-slots'),
            invalid: root.getAttribute('data-i18n-invalid'),
            off: root.getAttribute('data-i18n-off')
        };

        Array.prototype.forEach.call(root.querySelectorAll('[data-psch-day]'), function (row) {
            var update = function () { updateDay(row, i18n); };
            Array.prototype.forEach.call(row.querySelectorAll('input'), function (input) {
                input.addEventListener('input', update);
                input.addEventListener('change', update);
            });
            update();
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
