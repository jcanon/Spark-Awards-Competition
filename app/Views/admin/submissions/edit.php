<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Edit Submission</h1>
        <div>
            <button class="btn btn-sm btn-primary" type="submit" form="adminSubmissionEditForm">Update Submission</button>
            <a class="btn btn-sm btn-info" href="<?= site_url('admin/submissions/copy/' . rawurlencode((string)$row['entry_id'])) ?>">Copy Submission</a>
            <a class="btn btn-sm btn-secondary" href="<?= site_url('admin/submissions') ?>">Back to Submissions</a>
        </div>
    </div>

    <?= view('partials/flash') ?>
    <?= view('partials/form-errors', ['heading' => 'Please fix the following:']) ?>

    <?php
    $selectedDesignTypes = [];
    $rawDesignTypeList = (string)($row['design_type_list'] ?? '');
    if ($rawDesignTypeList !== '') {
        $decoded = json_decode($rawDesignTypeList, true);
        if (is_array($decoded)) {
            $selectedDesignTypes = array_map('strval', $decoded);
        } else {
            $selectedDesignTypes = array_map('trim', explode(',', $rawDesignTypeList));
        }
    }

    $lowPhotosByOrder = [];
    $certificate = null;
    $winnerBadge = null;
    foreach (($photos ?? []) as $photo) {
        if (($photo['entry_photo_res'] ?? '') === 'Low') {
            $lowPhotosByOrder[(int)($photo['entry_photo_order'] ?? 0)] = $photo;
        } elseif (($photo['entry_photo_res'] ?? '') === 'PDF') {
            $certificate = $photo;
        } elseif (($photo['entry_photo_res'] ?? '') === 'Badge') {
            $winnerBadge = $photo;
        }
    }

    $fmtDate = static function (?string $dt): string {
        return format_datetime_ui($dt);
    };

    $deletePhotoIds = [];
    $formErrors = session('errors');
    $formErrors = is_array($formErrors) ? $formErrors : [];
    $selectedWinnerLevelId = (string)((int)($row['winner_level'] ?? 0));
    ?>
    <link href="/css/components/photo-slot.css" rel="stylesheet">

    <form id="adminSubmissionEditForm" action="<?= site_url('admin/submissions/update/' . rawurlencode((string)$row['entry_id'])) ?>" method="post" enctype="multipart/form-data" class="needs-validation" novalidate>
        <?= csrf_field() ?>

        <div class="card mb-4">
            <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">Entry Overview</h6></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 form-group mb-2">
                        <label class="form-label mb-1">Date Created</label>
                        <div class="form-control-plaintext"><?= esc($fmtDate((string)($row['date_created'] ?? ''))) ?></div>
                    </div>
                    <div class="col-md-4 form-group mb-2">
                        <label class="form-label mb-1">Last Updated</label>
                        <div class="form-control-plaintext"><?= esc($fmtDate((string)($row['last_updated'] ?? ''))) ?></div>
                    </div>
                    <div class="col-md-4 form-group mb-2">
                        <label class="form-label mb-1">Submitted By</label>
                        <div class="form-control-plaintext">
                            <a href="<?= site_url('admin/users/edit/' . rawurlencode((string)$row['user_id'])) ?>">
                                <?= esc(trim((string)($row['last_name'] ?? '') . ', ' . (string)($row['first_name'] ?? ''))) ?>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 form-group mb-0">
                        <label class="form-label" for="comp_id">Competition <span class="text-danger">*</span></label>
                        <select id="comp_id" name="comp_id" class="form-control" required>
                            <?php foreach ($types as $comp): ?>
                                <option value="<?= (int)$comp->comp_id ?>" <?= (int)$row['comp_id'] === (int)$comp->comp_id ? 'selected' : '' ?>>
                                    <?= esc((string)$comp->comp_type_name . ' ' . (string)$comp->comp_year) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">Competition is required.</div>
                    </div>
                    <div class="col-md-4 form-group mb-0">
                        <label class="form-label" for="gallery_hide">Hide From Gallery</label>
                        <select id="gallery_hide" name="gallery_hide" class="form-control">
                            <option value="No" <?= (($row['gallery_hide'] ?? 'No') === 'No') ? 'selected' : '' ?>>No</option>
                            <option value="Yes" <?= (($row['gallery_hide'] ?? 'No') === 'Yes') ? 'selected' : '' ?>>Yes</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">Payment & Status</h6></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 form-group">
                        <label class="form-label" for="entry_status">Entry Status <span class="text-danger">*</span></label>
                        <select id="entry_status" name="entry_status" class="form-control" required>
                            <option value="Draft" <?= ($row['entry_status'] ?? '') === 'Draft' ? 'selected' : '' ?>>Draft</option>
                            <option value="Entrant" <?= ($row['entry_status'] ?? '') === 'Entrant' ? 'selected' : '' ?>>Entrant</option>
                            <option value="Finalist" <?= ($row['entry_status'] ?? '') === 'Finalist' ? 'selected' : '' ?>>Finalist</option>
                            <option value="Winner" <?= ($row['entry_status'] ?? '') === 'Winner' ? 'selected' : '' ?>>Winner</option>
                        </select>
                        <div class="invalid-feedback">Entry status is required.</div>
                    </div>
                    <div class="col-md-4 form-group">
                        <label class="form-label" for="winner_level">Winner Award Level</label>
                        <select id="winner_level" name="winner_level" class="form-control">
                            <option value="0">Select Winner Level</option>
                            <?php foreach ($winnerLevels as $level): ?>
                                <?php $levelId = (string)((int)($level['winner_level_id'] ?? 0)); ?>
                                <option value="<?= esc($levelId) ?>" <?= $selectedWinnerLevelId === $levelId ? 'selected' : '' ?>>
                                    <?= esc($level['winner_level_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 form-group mb-0">
                        <label class="form-label" for="entry_non_finalist">Exclude Non-Finalist</label>
                        <select id="entry_non_finalist" name="entry_non_finalist" class="form-control">
                            <option value="No" <?= (($row['entry_non_finalist'] ?? 'No') === 'No') ? 'selected' : '' ?>>No</option>
                            <option value="Yes" <?= (($row['entry_non_finalist'] ?? 'No') === 'Yes') ? 'selected' : '' ?>>Yes</option>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 form-group mb-0">
                        <label class="form-label" for="phase_1_payment">Phase 1 Payment</label>
                        <select id="phase_1_payment" name="phase_1_payment" class="form-control">
                            <option value="Paid" <?= ($row['phase_1_payment'] ?? '') === 'Paid' ? 'selected' : '' ?>>Paid</option>
                            <option value="Unpaid" <?= ($row['phase_1_payment'] ?? 'Unpaid') === 'Unpaid' ? 'selected' : '' ?>>Unpaid</option>
                        </select>
                    </div>
                    <div class="col-md-6 form-group mb-0">
                        <label class="form-label" for="phase_2_payment">Phase 2 Payment</label>
                        <select id="phase_2_payment" name="phase_2_payment" class="form-control">
                            <option value="Paid" <?= ($row['phase_2_payment'] ?? '') === 'Paid' ? 'selected' : '' ?>>Paid</option>
                            <option value="Unpaid" <?= ($row['phase_2_payment'] ?? 'Unpaid') === 'Unpaid' ? 'selected' : '' ?>>Unpaid</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">Payment Receipts</h6></div>
            <div class="card-body">
                <?php if (!empty($payments)): ?>
                    <?php foreach ($payments as $p): ?>
                        <p class="mb-1">
                            <strong>
                                <a
                                    href="<?= site_url('admin/submissions/receipt/' . (int)$p['payment_id'] . '/' . rawurlencode((string)$row['entry_id'])) ?>"
                                    data-receipt-url="<?= site_url('admin/submissions/receipt-content/' . (int)$p['payment_id'] . '/' . rawurlencode((string)$row['entry_id'])) ?>"
                                    data-receipt-pdf-url="<?= site_url('admin/submissions/receipt-pdf/' . (int)$p['payment_id'] . '/' . rawurlencode((string)$row['entry_id'])) ?>"
                                    class="js-receipt-modal-link"
                                >
                                    Phase <?= esc((string)$p['payment_phase']) ?> Receipt
                                    <?php if ((string)($p['payment_total'] ?? '') !== ''): ?>
                                        ($<?= number_format((float)$p['payment_total'], 2) ?>)
                                    <?php endif; ?>
                                </a>
                            </strong>
                            <span class="text-muted"> - <?= esc($fmtDate((string)($p['payment_date'] ?? ''))) ?></span>
                        </p>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="mb-0 text-muted">No payments have been made for this submission.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">Design Information</h6></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label class="form-label" for="design_name">Design Name <span class="text-danger">*</span></label>
                        <input id="design_name" name="design_name" class="form-control" maxlength="100" required value="<?= esc((string)($row['design_name'] ?? '')) ?>">
                        <div class="invalid-feedback">Design name is required.</div>
                    </div>
                    <div class="col-md-6 form-group">
                        <label class="form-label" for="company_name">Organization <span class="text-danger">*</span></label>
                        <input id="company_name" name="company_name" class="form-control" maxlength="100" required value="<?= esc((string)($row['company_name'] ?? '')) ?>">
                        <div class="invalid-feedback">Company / Organization is required.</div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 form-group">
                        <label class="form-label" for="design_type_list">Design Types (you can select more than one)</label>
                        <select id="design_type_list" name="design_type_list[]" class="form-control" multiple>
                            <?php foreach ($designTypes as $designType): ?>
                                <?php $id = (string)$designType['design_type_id']; ?>
                                <option value="<?= esc($id) ?>" <?= in_array($id, $selectedDesignTypes, true) ? 'selected' : '' ?>>
                                    <?= esc((string)$designType['design_type_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 form-group">
                        <label class="form-label" for="design_type">Other Design Type</label>
                        <input id="design_type" name="design_type" class="form-control" maxlength="100" value="<?= esc((string)($row['design_type'] ?? '')) ?>">
                    </div>
                    <div class="col-md-3 form-group">
                        <label class="form-label" for="design_stage">Design Stage</label>
                        <select id="design_stage" name="design_stage" class="form-control">
                            <option value="">Select design stage</option>
                            <option value="Concept" <?= (($row['design_stage'] ?? '') === 'Concept') ? 'selected' : '' ?>>A Concept</option>
                            <option value="Produced/Published/Built" <?= (($row['design_stage'] ?? '') === 'Produced/Published/Built') ? 'selected' : '' ?>>Produced/Published/Built</option>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 form-group mb-0">
                        <label class="form-label" for="client_brandname">Client Brandname (If Applicable)</label>
                        <input id="client_brandname" name="client_brandname" class="form-control" maxlength="100" value="<?= esc((string)($row['client_brandname'] ?? '')) ?>">
                    </div>
                    <div class="col-md-4 form-group mb-0">
                        <label class="form-label" for="series">Is this design part of a series or campaign?</label>
                        <select id="series" name="series" class="form-control">
                            <option value="No" <?= (($row['series'] ?? 'No') === 'No') ? 'selected' : '' ?>>No</option>
                            <option value="Yes" <?= (($row['series'] ?? 'No') === 'Yes') ? 'selected' : '' ?>>Yes</option>
                        </select>
                    </div>
                    <div class="col-md-4 form-group mb-0">
                        <label class="form-label" for="referred_by">Referred By</label>
                        <input id="referred_by" name="referred_by" class="form-control" maxlength="200" value="<?= esc((string)($row['referred_by'] ?? '')) ?>">
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">Design Photos</h6></div>
            <div class="card-body">
                <p class="text-muted mb-3"><em>Upload at least three (max 1 MB each) JPG images. At least one image should be free of overlays and ideally on a white background.</em></p>

                <?php for ($i = 1; $i <= 10; $i++): ?>
                    <?php $photo = $lowPhotosByOrder[$i] ?? null; ?>
                    <div class="photo-slot mb-3">
                        <div class="photo-slot-label">Photo <?= $i ?><?= ($i <= 3) ? ' <span class="text-danger">*</span>' : '' ?></div>
                        <div class="row g-3 align-items-start">
                            <div class="col-md-2 col-sm-4">
                                <div class="photo-thumb-wrap">
                                    <?php if ($photo): ?>
                                        <button
                                            type="button"
                                            class="btn p-0 border-0 bg-transparent w-100 h-100 js-photo-modal-link"
                                            data-photo-url="<?= site_url('media/photo/' . (int)$photo['entry_photo_id']) ?>"
                                            data-photo-title="Photo <?= $i ?>"
                                            aria-label="Photo <?= $i ?>"
                                        >
                                            <img src="<?= site_url('media/photo/' . (int)$photo['entry_photo_id']) ?>" alt="Photo <?= $i ?>">
                                        </button>
                                    <?php else: ?>
                                        <div class="photo-thumb-empty">No photo</div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="col-md-5 col-sm-8">
                                <label class="form-label mb-1"><?= $photo ? 'Replace File' : 'Upload File' ?> <span class="text-muted">(JPG, max 1 MB)</span></label>
                                <input
                                    type="file"
                                    id="low_photo_<?= $i ?>"
                                    name="low_photo_<?= $i ?>"
                                    class="form-control<?= isset($formErrors['low_photo_' . $i]) ? ' is-invalid' : '' ?>"
                                    data-photo-slot="<?= $i ?>"
                                    data-photo-required="<?= $i <= 3 ? '1' : '0' ?>"
                                    data-photo-has-existing="<?= $photo ? '1' : '0' ?>"
                                    accept=".jpg,.jpeg,image/jpeg"
                                >
                                <div class="invalid-feedback"><?= esc((string)($formErrors['low_photo_' . $i] ?? ('Photo ' . $i . ' is required.'))) ?></div>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label mb-1">Caption / Credit</label>
                                <input type="text" name="low_caption_<?= $i ?>" maxlength="200" class="form-control" value="<?= esc((string)($photo['entry_photo_caption'] ?? '')) ?>">
                            </div>

                            <div class="col-md-1 text-md-right text-left">
                                <?php if ($photo && $canDelete): ?>
                                    <?php $photoId = (int)$photo['entry_photo_id']; ?>
                                    <?php $deletePhotoIds[$photoId] = true; ?>
                                    <button
                                        type="submit"
                                        class="btn btn-outline-danger btn-sm mt-md-4 mt-2"
                                        form="delete-photo-<?= $photoId ?>"
                                        formnovalidate
                                        onclick="return confirm('Delete this photo? This cannot be undone.');"
                                    >
                                        Delete
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endfor; ?>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">Certificate & Winner Badge Files</h6></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-2 form-group">
                        <label class="form-label">Certificate PDF Thumbnail</label>
                        <div class="photo-thumb-wrap mb-2">
                            <?php if ($certificate && !empty($certificate['entry_certificate'])): ?>
                                <button
                                    type="button"
                                    class="btn p-0 border-0 bg-transparent w-100 h-100 js-photo-modal-link"
                                    data-photo-url="<?= esc((string)$certificate['entry_certificate']) ?>"
                                    data-photo-title="Certificate PDF"
                                    data-photo-type="pdf"
                                    aria-label="Certificate PDF"
                                >
                                    <?php if (!empty($certificate['entry_photo'])): ?>
                                        <img src="<?= esc((string)$certificate['entry_photo']) ?>" alt="Certificate PDF Thumbnail">
                                    <?php else: ?>
                                        <div class="photo-thumb-empty">PDF preview</div>
                                    <?php endif; ?>
                                </button>
                            <?php else: ?>
                                <div class="photo-thumb-empty">No PDF</div>
                            <?php endif; ?>
                        </div>
                        <div class="small text-muted">Thumbnail is auto-generated from the uploaded PDF.</div>
                    </div>
                    <div class="col-md-3 form-group">
                        <label class="form-label">Certificate PDF</label>
                        <input type="file" name="certificate_pdf" class="form-control" accept=".pdf,application/pdf">
                        <input type="hidden" name="certificate_thumb_data" id="certificate_thumb_data" value="">
                        <div id="certificate-thumb-status" class="small text-muted mt-1"></div>
                        <?php if ($certificate && !empty($certificate['entry_certificate'])): ?>
                            <div class="small mt-2">
                                <button
                                    type="button"
                                    class="btn btn-link p-0 align-baseline js-photo-modal-link"
                                    data-photo-url="<?= esc((string)$certificate['entry_certificate']) ?>"
                                    data-photo-title="Certificate PDF"
                                    data-photo-type="pdf"
                                >View</button>
                                <?php if ($canDelete): ?>
                                    <button
                                        type="submit"
                                        class="btn btn-link text-danger p-0 ml-2"
                                        form="delete-certificate-<?= (int)$certificate['entry_photo_id'] ?>"
                                        formnovalidate
                                        onclick="return confirm('Delete this certificate PDF reference? This cannot be undone.');"
                                    >
                                        Delete
                                    </button>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-2 form-group"></div>
                    <div class="col-md-2 form-group">
                        <label class="form-label">Winner Badge Preview</label>
                        <div class="photo-thumb-wrap mb-2">
                            <?php if ($winnerBadge && !empty($winnerBadge['entry_photo'])): ?>
                                <button
                                    type="button"
                                    class="btn p-0 border-0 bg-transparent w-100 h-100 js-photo-modal-link"
                                    data-photo-url="<?= site_url('media/photo/' . (int)$winnerBadge['entry_photo_id']) ?>"
                                    data-photo-title="Winner Badge"
                                    aria-label="Winner Badge"
                                >
                                    <img src="<?= site_url('media/photo/' . (int)$winnerBadge['entry_photo_id']) ?>" alt="Winner Badge">
                                </button>
                            <?php else: ?>
                                <div class="photo-thumb-empty">No Badge</div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-3 form-group">
                        <label class="form-label">Winner Badge</label>
                        <?php if ((string)session('role') === 'admin'): ?>
                            <input type="file" name="winner_badge" class="form-control" accept="image/*,.jpg,.jpeg,.png,.tif,.tiff">
                        <?php else: ?>
                            <div class="small text-muted mt-2">Only admins can upload a Winner Badge.</div>
                        <?php endif; ?>
                        <?php if ($winnerBadge): ?>
                            <div class="small mt-2">
                                <button
                                    type="button"
                                    class="btn btn-link p-0 align-baseline js-photo-modal-link"
                                    data-photo-url="<?= site_url('media/photo/' . (int)$winnerBadge['entry_photo_id']) ?>"
                                    data-photo-title="Winner Badge"
                                >View</button>
                                <?php if ($canDelete): ?>
                                    <?php $badgePhotoId = (int)$winnerBadge['entry_photo_id']; ?>
                                    <?php $deletePhotoIds[$badgePhotoId] = true; ?>
                                    <button
                                        type="submit"
                                        class="btn btn-link text-danger p-0 ml-2"
                                        form="delete-photo-<?= $badgePhotoId ?>"
                                        formnovalidate
                                        onclick="return confirm('Delete this winner badge? This cannot be undone.');"
                                    >
                                        Delete
                                    </button>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">Design Description</h6></div>
            <div class="card-body">
                <div class="form-group">
                    <label class="form-label" for="short_description">Short Description <span class="text-danger">*</span></label>
                    <div class="text-muted small mb-1">50 words maximum. Simple and clear language, please.</div>
                    <textarea id="short_description" name="short_description" class="form-control" rows="3" maxlength="600" required><?= esc((string)($row['short_description'] ?? '')) ?></textarea>
                    <div class="invalid-feedback">Short description is required.</div>
                </div>
                <div class="form-group">
                    <label class="form-label" for="full_description">Full Description <span class="text-danger">*</span></label>
                    <div class="text-muted small mb-1">200 words maximum. Simple and clear language, please.</div>
                    <textarea id="full_description" name="full_description" class="form-control" rows="5" maxlength="2400" required><?= esc((string)($row['full_description'] ?? '')) ?></textarea>
                    <div class="invalid-feedback">Full description is required.</div>
                </div>
                <div class="form-group mb-0">
                    <label class="form-label d-inline-flex align-items-center" for="youtube_url">
                        Video Embed URL (YouTube or Vimeo)
                        <button
                            type="button"
                            class="btn btn-link btn-sm p-0 ml-2 align-baseline"
                            data-toggle="modal"
                            data-target="#videoEmbedHelpModal"
                            aria-label="How to find a YouTube or Vimeo embed URL"
                            title="How to find embed URL"
                        >
                            <i class="fas fa-question-circle" aria-hidden="true"></i>
                        </button>
                    </label>
                    <input id="youtube_url" name="youtube_url" class="form-control" maxlength="255" value="<?= esc((string)(old('youtube_url') ?? ($videoEmbedUrl ?? ''))) ?>">
                    <small class="text-muted">Enter one video URL only. Accepted providers: YouTube or Vimeo.</small><br>
                    <small class="text-muted">Examples: <strong>https://www.youtube.com/embed/VIDEO_ID</strong> or <strong>https://player.vimeo.com/video/123456789</strong></small>
                </div>
            </div>
        </div>

        <?php if (!empty($questions)): ?>
            <div class="card mb-4">
                <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">Questions & Answers</h6></div>
                <div class="card-body">
                    <p class="text-muted mb-3"><em>Please answer as many questions as possible. Keep responses concise and factual.</em></p>
                    <?php foreach ($questions as $q): ?>
                        <?php $qid = (int)$q['entry_question_id']; ?>
                        <div class="form-group">
                            <label class="form-label"><?= (int)$q['entry_question_order'] ?>. <?= esc($q['entry_question']) ?></label>
                            <textarea class="form-control" name="entry_question_<?= $qid ?>" rows="3" maxlength="600"><?= esc((string)($answersMap[$qid] ?? '')) ?></textarea>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">Contact Person</h6></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 form-group">
                        <label class="form-label" for="designer_first_name">First Name <span class="text-danger">*</span></label>
                        <input id="designer_first_name" name="designer_first_name" class="form-control" maxlength="100" required value="<?= esc((string)($row['designer_first_name'] ?? '')) ?>">
                        <div class="invalid-feedback">First name is required.</div>
                    </div>
                    <div class="col-md-3 form-group">
                        <label class="form-label" for="designer_last_name">Last Name <span class="text-danger">*</span></label>
                        <input id="designer_last_name" name="designer_last_name" class="form-control" maxlength="100" required value="<?= esc((string)($row['designer_last_name'] ?? '')) ?>">
                        <div class="invalid-feedback">Last name is required.</div>
                    </div>
                    <div class="col-md-3 form-group">
                        <label class="form-label" for="designer_email_address">Email Address <span class="text-danger">*</span></label>
                        <input id="designer_email_address" name="designer_email_address" type="email" class="form-control" maxlength="100" required value="<?= esc((string)($row['designer_email_address'] ?? '')) ?>">
                        <div class="invalid-feedback">A valid email address is required.</div>
                    </div>
                    <div class="col-md-3 form-group">
                        <label class="form-label" for="designer_phone">Telephone <span class="text-danger">*</span></label>
                        <input id="designer_phone" name="designer_phone" class="form-control" maxlength="25" required value="<?= esc((string)($row['designer_phone'] ?? '')) ?>">
                        <div class="invalid-feedback">Telephone is required.</div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12 form-group mb-0">
                        <label class="form-label" for="additional_team_members">Designers</label>
                        <textarea id="additional_team_members" name="additional_team_members" class="form-control" rows="3"><?= esc((string)($row['additional_team_members'] ?? '')) ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">Internal Notes</h6></div>
            <div class="card-body">
                <textarea name="internal_notes" class="form-control" rows="5"><?= esc((string)($row['internal_notes'] ?? '')) ?></textarea>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">Judges Comments</h6></div>
            <div class="card-body">
                <textarea name="judges_comments" class="form-control" rows="5"><?= esc((string)($row['judges_comments'] ?? '')) ?></textarea>
            </div>
        </div>

        <div class="d-flex mb-4">
            <button class="btn btn-primary mr-2" type="submit">Update Submission</button>
            <a class="btn btn-outline-secondary" href="<?= site_url('admin/submissions') ?>">Back to Submissions</a>
        </div>
    </form>

    <?php foreach (array_keys($deletePhotoIds) as $deletePhotoId): ?>
        <form id="delete-photo-<?= (int)$deletePhotoId ?>" action="<?= site_url('admin/submissions/photo/delete/' . (int)$deletePhotoId) ?>" method="post" class="d-none">
            <?= csrf_field() ?>
        </form>
    <?php endforeach; ?>

    <?php if ($certificate): ?>
        <form id="delete-certificate-<?= (int)$certificate['entry_photo_id'] ?>" action="<?= site_url('admin/submissions/certificate/delete/' . (int)$certificate['entry_photo_id']) ?>" method="post" class="d-none">
            <?= csrf_field() ?>
        </form>
    <?php endif; ?>

    <div class="modal fade" id="photoPreviewModal" tabindex="-1" role="dialog" aria-labelledby="photoPreviewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="photoPreviewModalLabel">Photo Preview</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body text-center">
                    <img id="photoPreviewImage" src="" alt="" class="img-fluid rounded border spark-max-h-70vh">
                    <iframe id="photoPreviewPdf" src="" title="PDF Preview" class="w-100 border rounded d-none spark-h-70vh"></iframe>
                    <div id="photoPreviewPdfFallback" class="d-none mt-2 small">
                        If the PDF does not display here,
                        <a id="photoPreviewPdfFallbackLink" href="" target="_blank" rel="noopener">Open PDF in a new tab</a>.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="receiptModal" tabindex="-1" role="dialog" aria-labelledby="receiptModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="receiptModalLabel">Payment Receipt</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="receiptModalBody">
                    <div class="text-muted">Loading receipt...</div>
                </div>
                <div class="modal-footer">
                    <a id="receiptModalPdfBtn" class="btn btn-sm btn-primary" href="#" target="_blank" rel="noopener">Save as PDF</a>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="videoEmbedHelpModal" tabindex="-1" role="dialog" aria-labelledby="videoEmbedHelpModalTitle" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="videoEmbedHelpModalTitle">How To Get A Video Embed URL</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p class="mb-2"><strong>YouTube:</strong> Open your video, choose <em>Share</em> then <em>Embed</em>, and copy the iframe <code>src</code> URL (starts with <code>https://www.youtube.com/embed/</code>).</p>
                    <p class="mb-0"><strong>Vimeo:</strong> Open your video, choose <em>Share</em> or <em>Embed</em>, and copy the iframe <code>src</code> URL (starts with <code>https://player.vimeo.com/video/</code>).</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="/js/utils/video-embed-url.js"></script>
<script src="/js/utils/modal.js"></script>
<script src="/js/utils/receipt-modal.js"></script>
<script src="/js/utils/photo-preview-modal.js"></script>
<script src="/js/pages/admin-submissions-edit.js"></script>

<?= $this->endSection() ?>
