(function () {
    'use strict';
    var sourceForm = document.querySelector('form.needs-validation');
    var existingTypeIdsByYear = {};
    if (sourceForm) {
        try {
            existingTypeIdsByYear = JSON.parse(sourceForm.getAttribute('data-existing-type-ids-by-year') || '{}');
        } catch (error) {
            existingTypeIdsByYear = {};
        }
    }

    function syncTypeOptionsForYear(form) {
        var yearInput = form.querySelector('input[name="comp_year"]');
        var multiTypeSelect = form.querySelector('select[name="comp_type_ids[]"]');
        if (!yearInput || !multiTypeSelect) {
            return;
        }

        var year = parseInt(yearInput.value, 10);
        var takenTypeIds = Number.isNaN(year) ? [] : (existingTypeIdsByYear[String(year)] || existingTypeIdsByYear[year] || []);
        var takenMap = {};
        Array.prototype.forEach.call(takenTypeIds, function (id) {
            takenMap[String(parseInt(id, 10))] = true;
        });

        Array.prototype.forEach.call(multiTypeSelect.options, function (option) {
            var optionId = String(parseInt(option.value, 10));
            var isTaken = !!takenMap[optionId];
            var baseLabel = option.getAttribute('data-base-label') || option.text;
            option.disabled = isTaken;
            if (isTaken && option.selected) {
                option.selected = false;
            }
            option.text = isTaken ? (baseLabel + ' (Already exists for selected year)') : baseLabel;
        });
    }

    var forms = document.querySelectorAll('.needs-validation');
    Array.prototype.slice.call(forms).forEach(function (form) {
        var yearInput = form.querySelector('input[name="comp_year"]');
        if (yearInput) {
            yearInput.addEventListener('change', function () { syncTypeOptionsForYear(form); });
            yearInput.addEventListener('input', function () { syncTypeOptionsForYear(form); });
        }

        syncTypeOptionsForYear(form);

        form.addEventListener('submit', function () {
            var multiTypeSelect = form.querySelector('select[name="comp_type_ids[]"]');
            if (multiTypeSelect) {
                var hasSelection = Array.prototype.some.call(multiTypeSelect.options, function (option) { return option.selected; });
                multiTypeSelect.setCustomValidity(hasSelection ? '' : 'Please select at least one competition category.');
            }
        }, false);
    });
})();
