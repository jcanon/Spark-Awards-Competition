(function () {
    var handlers = {
        alpha2: function (value) {
            return String(value || '').replace(/[^A-Za-z]/g, '').toUpperCase().slice(0, 2);
        },
        digits: function (value) {
            return String(value || '').replace(/[^0-9]/g, '');
        }
    };

    document.querySelectorAll('input[data-filter]').forEach(function (input) {
        if (input.dataset.filterBound === '1') {
            return;
        }
        var key = input.getAttribute('data-filter') || '';
        if (!handlers[key]) {
            return;
        }
        input.dataset.filterBound = '1';
        input.addEventListener('input', function () {
            this.value = handlers[key](this.value);
        });
    });
})();
