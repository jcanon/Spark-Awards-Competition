<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Add Submission</h1>
        <a class="btn btn-sm btn-secondary" href="<?= site_url('admin/submissions?compYear=' . (int)$filters['compYear']) ?>">Back to Submissions</a>
    </div>

    <?= view('partials/flash', ['showSuccess' => false]) ?>

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
                        <input id="entrant_search" type="text" class="form-control" placeholder="Type name or email (minimum 2 chars)" autocomplete="off" data-user-search-url="<?= esc(site_url('admin/submissions/user-search')) ?>">
                        <input id="entrant_user_id" type="hidden" name="user_id" required>
                        <select id="entrant_results" class="form-control mt-2 d-none" size="6"></select>
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

<script src="/js/pages/admin-submissions-create.js"></script>

<?= $this->endSection() ?>

