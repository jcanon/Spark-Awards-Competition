<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
    $entryId = (string)($entry['entry_id'] ?? '');
    $backFilters = array_merge($filters, ['phase' => $phase]);
    $oldScores = old('scores');
    if (!is_array($oldScores)) {
        $oldScores = [];
    }
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1 text-gray-800">Edit Scores</h1>
            <p class="mb-0 text-muted">
                <?= esc((string)($entry['design_name'] ?? '')) ?>
                &middot;
                <?= esc((string)($entry['comp_type_name'] ?? '')) ?>
                <?= esc((string)($entry['comp_year'] ?? '')) ?>
                &middot;
                Phase <?= esc((string)$phase) ?>
            </p>
        </div>
        <a class="btn btn-sm btn-secondary" href="<?= site_url('admin/score-results?' . http_build_query($backFilters)) ?>">Back to Score Results</a>
    </div>

    <?= view('partials/flash') ?>

    <form method="post" action="<?= site_url('admin/score-results/edit/' . rawurlencode($entryId)) ?>" class="needs-validation" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="phase" value="<?= esc((string)$phase) ?>">
        <input type="hidden" name="year" value="<?= (int)($filters['year'] ?? date('Y')) ?>">
        <input type="hidden" name="type" value="<?= (int)($filters['type'] ?? 0) ?>">
        <input type="hidden" name="userType" value="<?= esc((string)($filters['userType'] ?? 'all')) ?>">
        <input type="hidden" name="status" value="<?= esc((string)($filters['status'] ?? 'All')) ?>">

        <div class="card">
            <div class="card-body table-responsive">
                <?php if ($scores === []): ?>
                    <p class="mb-0 text-muted">No judge scores were found for this entry and phase.</p>
                <?php else: ?>
                    <table class="table table-bordered table-hover">
                        <thead>
                        <tr>
                            <th>Judge</th>
                            <th class="spark-score-col">Score</th>
                            <th>Comments</th>
                            <th>Judged</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($scores as $index => $score): ?>
                            <?php
                                $judgeName = trim((string)($score['first_name'] ?? '') . ' ' . (string)($score['last_name'] ?? ''));
                                if ($judgeName === '') {
                                    $judgeName = (string)($score['email_address'] ?? $score['user_id'] ?? 'Unknown Judge');
                                }

                                $postedScore = $oldScores[$index]['entry_score'] ?? $score['entry_score'] ?? '';
                                $postedComments = $oldScores[$index]['entry_comments'] ?? $score['entry_comments'] ?? '';
                            ?>
                            <tr>
                                <td>
                                    <strong><?= esc($judgeName) ?></strong>
                                    <?php if ((string)($score['email_address'] ?? '') !== ''): ?>
                                        <div class="small text-muted"><?= esc((string)$score['email_address']) ?></div>
                                    <?php endif; ?>
                                    <?php if ((string)($score['company_name'] ?? '') !== ''): ?>
                                        <div class="small text-muted"><?= esc((string)$score['company_name']) ?></div>
                                    <?php endif; ?>
                                    <input type="hidden" name="scores[<?= (int)$index ?>][user_id]" value="<?= esc((string)$score['user_id']) ?>">
                                </td>
                                <td>
                                    <select name="scores[<?= (int)$index ?>][entry_score]" class="form-control">
                                        <?php foreach ([0, 1, 2] as $option): ?>
                                            <option value="<?= (int)$option ?>" <?= (string)$postedScore === (string)$option ? 'selected' : '' ?>><?= (int)$option ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                    <textarea name="scores[<?= (int)$index ?>][entry_comments]" class="form-control" rows="3"><?= esc((string)$postedComments) ?></textarea>
                                </td>
                                <td class="text-nowrap"><?= esc(format_datetime_ui((string)($score['date_judged'] ?? ''))) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($scores !== []): ?>
            <div class="mt-3">
                <button type="submit" class="btn btn-primary">Save Scores</button>
                <a class="btn btn-secondary ml-2" href="<?= site_url('admin/score-results?' . http_build_query($backFilters)) ?>">Cancel</a>
            </div>
        <?php endif; ?>
    </form>
</div>

<?= $this->endSection() ?>
