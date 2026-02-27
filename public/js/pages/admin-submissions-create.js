(function () {
    var searchInput = document.getElementById('entrant_search');
    var results = document.getElementById('entrant_results');
    var userIdInput = document.getElementById('entrant_user_id');
    var selectedText = document.getElementById('entrant_selected');
    if (!searchInput || !results || !userIdInput || !selectedText) {
        return;
    }

    var endpoint = searchInput.getAttribute('data-user-search-url') || '';
    if (!endpoint) {
        return;
    }

    var timer = null;
    var requestId = 0;

    var clearSelection = function () {
        userIdInput.value = '';
        selectedText.textContent = 'No entrant selected.';
        userIdInput.setCustomValidity('Please select an entrant.');
    };

    var setSelection = function (id, label) {
        userIdInput.value = id;
        selectedText.textContent = 'Selected: ' + label;
        userIdInput.setCustomValidity('');
        results.style.display = 'none';
    };

    var renderResults = function (items) {
        results.innerHTML = '';
        if (!items.length) {
            results.style.display = 'none';
            return;
        }

        items.forEach(function (item) {
            var opt = document.createElement('option');
            opt.value = item.id;
            opt.textContent = item.label;
            results.appendChild(opt);
        });
        results.style.display = 'block';
    };

    searchInput.addEventListener('input', function () {
        clearSelection();
        var query = (searchInput.value || '').trim();
        if (query.length < 2) {
            results.style.display = 'none';
            results.innerHTML = '';
            return;
        }

        if (timer) {
            clearTimeout(timer);
        }

        timer = setTimeout(function () {
            requestId++;
            var current = requestId;
            fetch(endpoint + '?q=' + encodeURIComponent(query), {credentials: 'same-origin'})
                .then(function (response) {
                    return response.json();
                })
                .then(function (payload) {
                    if (current !== requestId) {
                        return;
                    }
                    renderResults(Array.isArray(payload.data) ? payload.data : []);
                })
                .catch(function () {
                    results.style.display = 'none';
                    results.innerHTML = '';
                });
        }, 250);
    });

    results.addEventListener('change', function () {
        var selected = results.options[results.selectedIndex];
        if (!selected) {
            return;
        }
        setSelection(selected.value, selected.textContent || '');
    });

    results.addEventListener('dblclick', function () {
        var selected = results.options[results.selectedIndex];
        if (!selected) {
            return;
        }
        setSelection(selected.value, selected.textContent || '');
    });

    clearSelection();
})();
