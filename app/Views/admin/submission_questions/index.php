<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Submission Questions</h1>
    </div>

    <?= view('partials/flash') ?>

    <div class="card mb-4">
        <div class="card-header">
            <h6 class="m-0 font-weight-bold text-primary">Add New Question</h6>
        </div>
        <div class="card-body">
            <form action="<?= site_url('admin/submission-questions/store') ?>" method="post" class="needs-validation" novalidate>
                <?= csrf_field() ?>
                <div class="row">
                    <div class="col-md-8 form-group">
                        <label for="entry_question">Question <span class="text-danger">*</span></label>
                        <input id="entry_question" type="text" name="entry_question" class="form-control" maxlength="200" value="<?= esc((string)old('entry_question')) ?>" required>
                        <div class="invalid-feedback">Question is required.</div>
                    </div>
                    <div class="col-md-2 form-group">
                        <label for="entry_question_order">Order <span class="text-danger">*</span></label>
                        <input id="entry_question_order" type="number" min="1" step="1" name="entry_question_order" class="form-control" value="<?= esc((string)(old('entry_question_order') ?? (count($rows) + 1))) ?>" required>
                        <div class="invalid-feedback">Order is required.</div>
                    </div>
                    <div class="col-md-2 form-group d-flex align-items-end">
                        <button type="submit" class="btn btn-primary btn-block">Add Question</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h6 class="m-0 font-weight-bold text-primary">Manage Questions</h6>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-hover">
                <thead>
                <tr>
                    <th class="spark-w-120">Order</th>
                    <th>Question</th>
                    <th class="spark-w-220">Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($rows)): ?>
                    <tr>
                        <td colspan="3" class="text-center text-muted">No questions found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rows as $row): ?>
                        <?php $formId = 'question_form_' . (int)$row['entry_question_id']; ?>
                        <tr>
                            <td>
                                <input form="<?= esc($formId) ?>" type="number" min="1" step="1" name="entry_question_order" class="form-control form-control-sm" value="<?= (int)$row['entry_question_order'] ?>" required>
                            </td>
                            <td>
                                <input form="<?= esc($formId) ?>" type="text" name="entry_question" class="form-control form-control-sm" maxlength="200" value="<?= esc((string)$row['entry_question']) ?>" required>
                            </td>
                            <td class="text-nowrap">
                                <form id="<?= esc($formId) ?>" action="<?= site_url('admin/submission-questions/update/' . (int)$row['entry_question_id']) ?>" method="post" class="needs-validation d-inline mb-0" novalidate>
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-primary">Save</button>
                                </form>
                                <?php if ($canDelete): ?>
                                    <form action="<?= site_url('admin/submission-questions/delete/' . (int)$row['entry_question_id']) ?>" method="post" class="d-inline" onsubmit="return confirm('Delete this question and ALL answers tied to it across submissions? This action is irreversible.');">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

