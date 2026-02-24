<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Add Submission</h1>
        <a class="btn btn-sm btn-secondary" href="<?= site_url('admin/submissions?compYear=' . (int)$filters['compYear']) ?>">Back to Submissions</a>
    </div>

    <?php if ($msg = session('error')): ?><div class="alert alert-danger"><?= esc($msg) ?></div><?php endif; ?>

    <form action="<?= site_url('admin/submissions/store') ?>" method="post" class="needs-validation" novalidate>
        <?= csrf_field() ?>

        <div class="card mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label>Competition <span class="text-danger">*</span></label>
                        <select name="comp_id" class="form-control" required>
                            <option value="">Select Competition</option>
                            <?php foreach ($types as $type): ?>
                                <option value="<?= (int)$type['comp_id'] ?>"><?= esc($type['comp_type_name']) ?> (<?= (int)$filters['compYear'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Entrant <span class="text-danger">*</span></label>
                        <input id="entrant_search" type="text" class="form-control" placeholder="Type name or email (minimum 2 chars)" autocomplete="off">
                        <input id="entrant_user_id" type="hidden" name="user_id" required>
                        <select id="entrant_results" class="form-control mt-2" size="6" style="display:none;"></select>
                        <small id="entrant_selected" class="form-text text-muted">No entrant selected.</small>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label>Design Name <span class="text-danger">*</span></label>
                        <input type="text" name="design_name" class="form-control" maxlength="100" required>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Referred By</label>
                        <input type="text" name="referred_by" class="form-control" maxlength="200">
                    </div>
                </div>
            </div>
        </div>

        <button class="btn btn-primary" type="submit">Create Submission</button>
    </form>
</div>

<script>
    (function () {
        'use strict';
        var forms = document.querySelectorAll('.needs-validation');
        Array.prototype.slice.call(forms).forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });

        var searchInput = document.getElementById('entrant_search');
        var results = document.getElementById('entrant_results');
        var userIdInput = document.getElementById('entrant_user_id');
        var selectedText = document.getElementById('entrant_selected');
        if (!searchInput || !results || !userIdInput || !selectedText) {
            return;
        }

        var endpoint = <?= json_encode(site_url('admin/submissions/user-search')) ?>;
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
            var q = (searchInput.value || '').trim();
            if (q.length < 2) {
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
                fetch(endpoint + '?q=' + encodeURIComponent(q), { credentials: 'same-origin' })
                    .then(function (r) { return r.json(); })
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
</script>

<?= $this->endSection() ?>
