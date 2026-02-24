<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
helper(['form', 'url']);

$isEdit = (bool) ($isEdit ?? isset($entry));
$entryId = $isEdit ? (string) ($entry->entry_id ?? '') : '';
$formAction = $isEdit
    ? site_url('submissions/update/' . urlencode($entryId))
    : site_url('submissions/store');
$pageTitle = $isEdit
    ? lang('Entrant.update_submission_title')
    : lang('Entrant.create_submission');

$selectedTypes = old('design_type_list');
if ($selectedTypes === null) {
    $selectedTypes = $entry->design_type_list ?? [];
    if (! is_array($selectedTypes) && is_string($selectedTypes)) {
        $decoded = json_decode($selectedTypes, true);
        $selectedTypes = is_array($decoded) ? $decoded : array_filter(array_map('trim', explode(',', $selectedTypes)));
    }
}
$selectedTypes = array_map('strval', (array) $selectedTypes);

$answersMap = is_array($answersMap ?? null) ? $answersMap : [];
$byOrder = [];
foreach (($photosLow ?? []) as $ph) {
    $byOrder[(int) $ph->entry_photo_order] = $ph;
}
$deletePhotoIds = [];
?>

<style>
    .photo-slot {
        border: 1px solid #e3e6f0;
        border-radius: 0.5rem;
        background: #f8f9fc;
        padding: 0.9rem;
    }
    .photo-slot-label {
        font-weight: 600;
        color: #008080;
        margin-bottom: 0.5rem;
    }
    .photo-thumb-wrap {
        width: 100%;
        max-width: 140px;
        aspect-ratio: 4 / 3;
        border: 1px dashed #cfd5e2;
        border-radius: 0.35rem;
        background: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }
    .photo-thumb-wrap img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .photo-thumb-empty {
        color: #9aa0ac;
        font-size: 0.8rem;
        text-align: center;
        padding: 0.5rem;
    }
</style>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= esc($pageTitle) ?></h1>
    </div>

    <?php if ($msg = session()->getFlashdata('success')): ?>
        <div class="alert alert-success"><?= esc($msg) ?></div>
    <?php endif; ?>
    <?php if ($errs = session()->get('errors')): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errs as $e): ?>
                    <li><?= is_array($e) ? implode(', ', $e) : esc($e) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form action="<?= $formAction ?>" method="post" enctype="multipart/form-data" class="needs-validation" novalidate>
        <?= csrf_field() ?>
        <?php if (! $isEdit): ?>
            <input type="hidden" name="comp_id" value="<?= esc((string) ($compId ?? (int) ($competition->comp_id ?? 0))) ?>">
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold text-primary"><?= esc(lang('Entrant.competition')) ?></h6>
            </div>
            <div class="card-body">
                <div><strong><?= esc(($competition->comp_type_name ?? '') . ' ' . ($competition->comp_year ?? '')) ?></strong></div>
                <div class="text-muted small">
                    <?= esc(lang('Entrant.opens')) ?>: <?= esc(format_datetime_ui((string) $competition->comp_phase_1_open, '-')) ?> |
                    <?= esc(lang('Entrant.closes')) ?>: <?= esc(format_datetime_ui((string) $competition->comp_phase_1_close, '-')) ?>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold text-primary"><?= esc(lang('Entrant.design_information')) ?></h6>
            </div>
            <div class="card-body">
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label" for="design_name"><?= esc(lang('Entrant.design_name')) ?> <span class="text-danger">*</span></label>
                        <input id="design_name" name="design_name" type="text" class="form-control" maxlength="100" required value="<?= old('design_name') ?? ($entry->design_name ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="company_name"><?= esc(lang('Entrant.organization')) ?> <span class="text-danger">*</span></label>
                        <input id="company_name" name="company_name" type="text" class="form-control" maxlength="100" required value="<?= old('company_name') ?? ($entry->company_name ?? $user->company_name ?? '') ?>">
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label" for="design_type_list"><?= esc(lang('Entrant.design_types_multi')) ?></label>
                        <select id="design_type_list" name="design_type_list[]" class="form-control" multiple>
                            <?php foreach (($designTypes ?? []) as $dt): ?>
                                <option value="<?= (int) $dt['design_type_id'] ?>" <?= in_array((string) $dt['design_type_id'], $selectedTypes, true) ? 'selected' : '' ?>>
                                    <?= esc($dt['design_type_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="design_type"><?= esc(lang('Entrant.other_type')) ?></label>
                        <input id="design_type" name="design_type" type="text" class="form-control" maxlength="100" value="<?= old('design_type') ?? ($entry->design_type ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="design_stage"><?= esc(lang('Entrant.design_stage')) ?></label>
                        <?php $stage = old('design_stage') ?? ($entry->design_stage ?? ''); ?>
                        <select id="design_stage" name="design_stage" class="form-control">
                            <option value=""><?= esc(lang('Entrant.select_design_stage')) ?></option>
                            <option value="Concept" <?= $stage === 'Concept' ? 'selected' : '' ?>><?= esc(lang('Entrant.design_stage_concept')) ?></option>
                            <option value="Produced/Published/Built" <?= $stage === 'Produced/Published/Built' ? 'selected' : '' ?>><?= esc(lang('Entrant.design_stage_produced')) ?></option>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 form-group mb-0">
                        <label class="form-label" for="client_brandname"><?= esc(lang('Entrant.client_brandname_if_applicable')) ?></label>
                        <input id="client_brandname" name="client_brandname" type="text" class="form-control" maxlength="100" value="<?= old('client_brandname') ?? ($entry->client_brandname ?? '') ?>">
                    </div>
                    <div class="col-md-4 form-group mb-0">
                        <label class="form-label" for="series"><?= esc(lang('Entrant.series_question')) ?></label>
                        <?php $series = old('series') ?? ($entry->series ?? 'No'); ?>
                        <select id="series" name="series" class="form-control">
                            <option value="No" <?= $series === 'No' ? 'selected' : '' ?>><?= esc(lang('Entrant.no')) ?></option>
                            <option value="Yes" <?= $series === 'Yes' ? 'selected' : '' ?>><?= esc(lang('Entrant.yes')) ?></option>
                        </select>
                    </div>
                    <div class="col-md-4 form-group mb-0">
                        <label class="form-label" for="referred_by"><?= esc(lang('Entrant.referred_by')) ?></label>
                        <input id="referred_by" name="referred_by" type="text" class="form-control" maxlength="200" value="<?= old('referred_by') ?? ($entry->referred_by ?? '') ?>">
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold text-primary">
                    <?= esc($isEdit ? lang('Entrant.design_photos_low') : lang('Entrant.design_entry_photo_upload')) ?>
                </h6>
            </div>
            <div class="card-body">
                <p class="text-muted mb-3"><em><?= esc(lang('Entrant.photo_upload_instructions')) ?></em></p>

                <?php for ($i = 1; $i <= 10; $i++): ?>
                    <?php $ph = $byOrder[$i] ?? null; ?>
                    <div class="photo-slot mb-3">
                        <div class="photo-slot-label"><?= esc(lang('Entrant.photo')) ?> <?= $i ?><?= ($i <= 3) ? ' <span class="text-danger">*</span>' : '' ?></div>
                        <div class="row g-3 align-items-start">
                            <?php if ($isEdit): ?>
                                <div class="col-md-2 col-sm-4">
                                    <div class="photo-thumb-wrap">
                                        <?php if ($ph): ?>
                                            <button
                                                type="button"
                                                class="btn p-0 border-0 bg-transparent w-100 h-100 photo-preview-trigger"
                                                data-photo-url="<?= site_url('media/photo/' . (int) $ph->entry_photo_id) ?>"
                                                data-photo-title="<?= esc(lang('Entrant.photo')) ?> <?= $i ?>"
                                                aria-label="<?= esc(lang('Entrant.photo')) ?> <?= $i ?>"
                                            >
                                                <img src="<?= site_url('media/photo/' . (int) $ph->entry_photo_id) ?>" alt="<?= esc(lang('Entrant.photo')) ?> <?= $i ?>">
                                            </button>
                                        <?php else: ?>
                                            <div class="photo-thumb-empty">No photo</div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="<?= $isEdit ? 'col-md-5 col-sm-8' : 'col-md-6' ?>">
                                <label class="form-label mb-1" for="low_photo_<?= $i ?>">
                                    <?= esc($isEdit ? ($ph ? lang('Entrant.replace_file') : lang('Entrant.upload_file')) : lang('Entrant.upload_file')) ?>
                                    <span class="text-muted"><?= esc(lang('Entrant.jpg_max_1mb')) ?></span>
                                </label>
                                <input
                                    type="file"
                                    class="form-control"
                                    id="low_photo_<?= $i ?>"
                                    name="low_photo_<?= $i ?>"
                                    data-photo-slot="<?= $i ?>"
                                    data-photo-required="<?= $i <= 3 ? '1' : '0' ?>"
                                    data-photo-has-existing="<?= ($isEdit && $ph) ? '1' : '0' ?>"
                                    accept=".jpg,.jpeg,image/jpeg"
                                    <?= (!$isEdit && $i <= 3) ? 'required' : '' ?>
                                >
                            </div>

                            <div class="<?= $isEdit ? 'col-md-4' : 'col-md-6' ?>">
                                <label class="form-label mb-1" for="low_caption_<?= $i ?>"><?= esc(lang('Entrant.photo_caption_credit')) ?></label>
                                <input id="low_caption_<?= $i ?>" type="text" class="form-control" name="low_caption_<?= $i ?>" maxlength="200" value="<?= old('low_caption_' . $i) ?? ($ph->entry_photo_caption ?? '') ?>">
                            </div>

                            <?php if ($isEdit): ?>
                                <div class="col-md-1 text-md-right text-left">
                                    <?php if ($ph): ?>
                                        <?php $photoId = (int) $ph->entry_photo_id; ?>
                                        <?php $deletePhotoIds[$photoId] = true; ?>
                                        <button
                                            type="submit"
                                            class="btn btn-outline-danger btn-sm mt-md-4 mt-2"
                                            form="delete-photo-<?= $photoId ?>"
                                            formnovalidate
                                            onclick="return confirm('<?= esc(lang('Entrant.delete_photo_confirm'), 'js') ?>');"
                                        >
                                            <?= esc(lang('Entrant.delete')) ?>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endfor; ?>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold text-primary"><?= esc(lang('Entrant.design_description')) ?></h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label" for="short_description"><?= esc(lang('Entrant.short_description')) ?> <span class="text-danger">*</span></label>
                    <div class="text-muted small mb-1"><?= esc(lang('Entrant.short_description_hint')) ?></div>
                    <textarea id="short_description" name="short_description" class="form-control" rows="3" required><?= old('short_description') ?? ($entry->short_description ?? '') ?></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="full_description"><?= esc(lang('Entrant.full_description')) ?> <span class="text-danger">*</span></label>
                    <div class="text-muted small mb-1"><?= esc(lang('Entrant.full_description_hint')) ?></div>
                    <textarea id="full_description" name="full_description" class="form-control" rows="4" required><?= old('full_description') ?? ($entry->full_description ?? '') ?></textarea>
                </div>
                <div class="mb-0">
                    <label class="form-label" for="youtube_url"><?= esc(lang('Entrant.youtube_video')) ?></label>
                    <input id="youtube_url" name="youtube_url" type="text" class="form-control" maxlength="255" value="<?= old('youtube_url') ?? ($entry->youtube_url ?? '') ?>">
                    <small class="text-muted"><?= esc(lang('Entrant.youtube_share_hint')) ?> <strong>https://youtu.be/1D_YL9LwKnc</strong></small>
                </div>
            </div>
        </div>

        <?php if (! empty($questions)): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary"><?= esc(lang('Entrant.questions_answers')) ?></h6>
                </div>
                <div class="card-body">
                    <p class="text-muted"><em><?= esc(lang('Entrant.questions_answers_hint')) ?></em></p>
                    <?php foreach ($questions as $q): ?>
                        <?php $qid = (int) $q->entry_question_id; ?>
                        <div class="mb-3">
                            <label class="form-label" for="entry_question_<?= $qid ?>"><?= (int) $q->entry_question_order ?>. <?= esc($q->entry_question) ?></label>
                            <textarea id="entry_question_<?= $qid ?>" class="form-control" name="entry_question_<?= $qid ?>" rows="3" maxlength="600"><?= old('entry_question_' . $qid) ?? ($answersMap[$qid] ?? '') ?></textarea>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold text-primary"><?= esc(lang('Entrant.contact_person')) ?></h6>
            </div>
            <div class="card-body">
                <div class="row g-3 mb-3">
                    <div class="col-md-3">
                        <label class="form-label" for="designer_first_name"><?= esc(lang('Entrant.first_name')) ?> <span class="text-danger">*</span></label>
                        <input id="designer_first_name" name="designer_first_name" type="text" class="form-control" maxlength="100" required value="<?= old('designer_first_name') ?? ($entry->designer_first_name ?? $user->first_name ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="designer_last_name"><?= esc(lang('Entrant.last_name')) ?> <span class="text-danger">*</span></label>
                        <input id="designer_last_name" name="designer_last_name" type="text" class="form-control" maxlength="100" required value="<?= old('designer_last_name') ?? ($entry->designer_last_name ?? $user->last_name ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="designer_email_address"><?= esc(lang('Entrant.email_address')) ?> <span class="text-danger">*</span></label>
                        <input id="designer_email_address" name="designer_email_address" type="email" class="form-control" maxlength="100" required value="<?= old('designer_email_address') ?? ($entry->designer_email_address ?? $user->email_address ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="designer_phone"><?= esc(lang('Entrant.telephone')) ?> <span class="text-danger">*</span></label>
                        <input id="designer_phone" name="designer_phone" type="text" class="form-control" maxlength="25" required value="<?= old('designer_phone') ?? ($entry->designer_phone ?? $user->phone ?? '') ?>">
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-md-12">
                        <label class="form-label" for="additional_team_members"><?= esc(lang('Entrant.designers')) ?></label>
                        <textarea id="additional_team_members" name="additional_team_members" class="form-control" rows="3" maxlength="500"><?= old('additional_team_members') ?? ($entry->additional_team_members ?? '') ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <?php if (! $isEdit): ?>
            <p class="fst-italic"><?= esc(lang('Entrant.terms_acceptance_prefix')) ?> <a href="https://www.sparkawards.com/terms-conditions/" target="_blank" rel="noopener"><?= esc(lang('Entrant.terms_and_conditions')) ?></a>.</p>
        <?php endif; ?>

        <div class="d-flex gap-2">
            <?php if ($isEdit): ?>
                <button type="submit" class="btn btn-primary mr-2"><?= esc(lang('Entrant.save_changes')) ?></button>
                <a href="<?= site_url('/submissions') ?>" class="btn btn-light"><?= esc(lang('Entrant.cancel')) ?></a>
            <?php else: ?>
                <button type="submit" name="saveDraft" value="1" class="btn btn-secondary mr-3"><?= esc(lang('Entrant.save_as_draft')) ?></button>
                <button type="submit" name="submitPayment" value="1" class="btn btn-primary mr-3"><?= esc(lang('Entrant.save_and_proceed_to_cart')) ?></button>
                <a href="<?= site_url('/submissions') ?>" class="btn btn-primary"><?= esc(lang('Entrant.cancel')) ?></a>
            <?php endif; ?>
        </div>
    </form>

    <?php if ($isEdit && $deletePhotoIds !== []): ?>
        <?php foreach (array_keys($deletePhotoIds) as $photoId): ?>
            <form
                id="delete-photo-<?= (int) $photoId ?>"
                action="<?= site_url('submissions/photo/delete/' . (int) $photoId) ?>"
                method="post"
                class="d-none"
            >
                <?= csrf_field() ?>
            </form>
        <?php endforeach; ?>
    <?php endif; ?>

    <div class="modal fade" id="photoPreviewModal" tabindex="-1" role="dialog" aria-labelledby="photoPreviewTitle" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h5 class="modal-title" id="photoPreviewTitle"><?= esc(lang('Entrant.photo')) ?></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="<?= esc(lang('Entrant.cancel')) ?>">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body text-center">
                    <img id="photoPreviewImage" src="" alt="" class="img-fluid">
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        'use strict';
        function upsertInvalidFeedback(field, message) {
            if (!field || !field.parentNode) {
                return;
            }

            var group = field.parentNode;
            var feedback = group.querySelector('.invalid-feedback');
            if (!feedback) {
                feedback = document.createElement('div');
                feedback.className = 'invalid-feedback';
                group.appendChild(feedback);
            }
            feedback.textContent = message || '';
        }

        function applyBootstrapErrors(form) {
            var controls = form.querySelectorAll('input, textarea, select');
            controls.forEach(function (field) {
                if (field.disabled || field.type === 'hidden' || field.type === 'submit' || field.type === 'button') {
                    return;
                }

                if (field.checkValidity()) {
                    field.classList.remove('is-invalid');
                    return;
                }

                field.classList.add('is-invalid');
                upsertInvalidFeedback(field, field.validationMessage);
            });
        }

        function wordCount(text) {
            var normalized = (text || '').trim();
            if (!normalized) {
                return 0;
            }
            return normalized.split(/\s+/).length;
        }

        function validYoutubeUrl(url) {
            var trimmed = (url || '').trim();
            if (!trimmed) {
                return true;
            }
            var patterns = [
                /^https?:\/\/youtu\.be\/[\w-]{6,25}(?:\?.*)?$/i,
                /^https?:\/\/(?:www\.)?youtube\.com\/watch\?v=[\w-]{6,25}(?:&.*)?$/i,
                /^https?:\/\/(?:www\.)?youtube\.com\/embed\/[\w-]{6,25}(?:\?.*)?$/i
            ];
            for (var i = 0; i < patterns.length; i++) {
                if (patterns[i].test(trimmed)) {
                    return true;
                }
            }
            return false;
        }

        function applyQuestionAnswerLimits(form) {
            var message = <?= json_encode(lang('Entrant.question_answer_word_limit_error')) ?>;
            var answerFields = form.querySelectorAll('textarea[name^="entry_question_"]');
            answerFields.forEach(function (field) {
                field.setCustomValidity(wordCount(field.value) > 50 ? message : '');
            });
        }

        function applyRequiredPhotoSlotLimits(form) {
            var requiredMessageTpl = <?= json_encode(lang('Entrant.photo_slot_required', ['{slot}'])) ?>;
            var photoInputs = form.querySelectorAll('input[type="file"][data-photo-slot]');
            photoInputs.forEach(function (input) {
                var isRequired = input.getAttribute('data-photo-required') === '1';
                if (!isRequired) {
                    input.setCustomValidity('');
                    return;
                }

                var slot = input.getAttribute('data-photo-slot') || '';
                var hasExisting = input.getAttribute('data-photo-has-existing') === '1';
                var hasNewUpload = input.files && input.files.length > 0;
                var isMissingRequired = !hasExisting && !hasNewUpload;

                if (isMissingRequired) {
                    input.setCustomValidity(requiredMessageTpl.replace('{slot}', slot));
                } else {
                    input.setCustomValidity('');
                }
            });
        }

        var forms = document.querySelectorAll('.needs-validation');
        Array.prototype.slice.call(forms).forEach(function (form) {
            form.addEventListener('submit', function (event) {
                var shortEl = document.getElementById('short_description');
                var fullEl = document.getElementById('full_description');
                var youtubeEl = document.getElementById('youtube_url');

                if (shortEl) {
                    var shortWords = wordCount(shortEl.value);
                    shortEl.setCustomValidity(shortWords > 50 ? <?= json_encode(lang('Entrant.short_description_word_limit_error')) ?> : '');
                }
                if (fullEl) {
                    var fullWords = wordCount(fullEl.value);
                    fullEl.setCustomValidity(fullWords > 200 ? <?= json_encode(lang('Entrant.full_description_word_limit_error')) ?> : '');
                }
                if (youtubeEl) {
                    youtubeEl.setCustomValidity(
                        validYoutubeUrl(youtubeEl.value)
                            ? ''
                            : <?= json_encode(lang('Entrant.youtube_share_url_invalid')) ?>
                    );
                }
                applyQuestionAnswerLimits(form);
                applyRequiredPhotoSlotLimits(form);

                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                applyBootstrapErrors(form);
                form.classList.add('was-validated');
            }, false);

            form.addEventListener('input', function (event) {
                var target = event.target;
                if (!(target instanceof HTMLElement)) {
                    return;
                }
                if (!target.matches('input, textarea, select')) {
                    return;
                }
                if (target.checkValidity()) {
                    target.classList.remove('is-invalid');
                }
            });
        });

        var shortDesc = document.getElementById('short_description');
        var fullDesc = document.getElementById('full_description');
        var youtube = document.getElementById('youtube_url');
        if (shortDesc) {
            shortDesc.addEventListener('input', function () {
                this.setCustomValidity(wordCount(this.value) > 50 ? <?= json_encode(lang('Entrant.short_description_word_limit_error')) ?> : '');
            });
        }
        if (fullDesc) {
            fullDesc.addEventListener('input', function () {
                this.setCustomValidity(wordCount(this.value) > 200 ? <?= json_encode(lang('Entrant.full_description_word_limit_error')) ?> : '');
            });
        }
        if (youtube) {
            youtube.addEventListener('input', function () {
                this.setCustomValidity(
                    validYoutubeUrl(this.value)
                        ? ''
                        : <?= json_encode(lang('Entrant.youtube_share_url_invalid')) ?>
                );
            });
        }

        var qaFields = document.querySelectorAll('textarea[name^="entry_question_"]');
        qaFields.forEach(function (field) {
            field.addEventListener('input', function () {
                this.setCustomValidity(
                    wordCount(this.value) > 50
                        ? <?= json_encode(lang('Entrant.question_answer_word_limit_error')) ?>
                        : ''
                );
            });
        });

        var photoPreviewModal = document.getElementById('photoPreviewModal');
        var photoPreviewImage = document.getElementById('photoPreviewImage');
        var photoPreviewTitle = document.getElementById('photoPreviewTitle');
        var photoPreviewButtons = document.querySelectorAll('.photo-preview-trigger');
        photoPreviewButtons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                var url = this.getAttribute('data-photo-url') || '';
                var title = this.getAttribute('data-photo-title') || '';
                if (!url || !photoPreviewModal || !photoPreviewImage) {
                    return;
                }

                photoPreviewImage.setAttribute('src', url);
                photoPreviewImage.setAttribute('alt', title);
                if (photoPreviewTitle) {
                    photoPreviewTitle.textContent = title || <?= json_encode(lang('Entrant.photo')) ?>;
                }

                if (window.bootstrap && window.bootstrap.Modal && typeof window.bootstrap.Modal.getOrCreateInstance === 'function') {
                    window.bootstrap.Modal.getOrCreateInstance(photoPreviewModal).show();
                    return;
                }
                if (typeof window.jQuery !== 'undefined' && window.jQuery.fn && typeof window.jQuery.fn.modal === 'function') {
                    window.jQuery(photoPreviewModal).modal('show');
                }
            });
        });

        // Client-side JPG and size checks (1 MB max each).
        var maxBytes = 1 * 1024 * 1024;
        for (var i = 1; i <= 10; i++) {
            var input = document.getElementById('low_photo_' + i);
            if (!input) {
                continue;
            }
            input.addEventListener('change', function () {
                var f = this.files && this.files[0] ? this.files[0] : null;
                if (!f) {
                    var parentForm = this.closest('form');
                    if (parentForm) {
                        applyRequiredPhotoSlotLimits(parentForm);
                    }
                    return;
                }
                var name = (f.name || '').toLowerCase();
                var isJpg = name.endsWith('.jpg') || name.endsWith('.jpeg');
                if (!isJpg) {
                    alert(<?= json_encode(lang('Entrant.only_jpg_allowed_alert')) ?>);
                    this.value = '';
                    return;
                }
                if (f.size > maxBytes) {
                    alert(<?= json_encode(lang('Entrant.image_max_size_alert')) ?>);
                    this.value = '';
                }
                var parentForm = this.closest('form');
                if (parentForm) {
                    applyRequiredPhotoSlotLimits(parentForm);
                }
            });
        }
    })();
</script>

<?= $this->endSection() ?>
