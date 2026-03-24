<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0 text-gray-800"><?= esc((string)($entry->design_name ?? 'Submission Preview')) ?></h1>
        <a class="btn btn-sm btn-secondary" href="<?= site_url('submissions/update/' . rawurlencode((string)($entry->entry_id ?? ''))) ?>">Back to Entry Form</a>
    </div>
    <div class="row">
        <div class="col-12 col-lg-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">Entry Details</h6>
                </div>
                <div class="card-body">
                    <p><strong>Designer:</strong> <?= esc(trim((string)($entry->designer_first_name ?? '') . ' ' . (string)($entry->designer_last_name ?? ''))) ?></p>
                    <?php if (!empty($competition)): ?>
                        <p><strong>Competition:</strong> <?= esc((string)($competition->comp_type_name ?? '') . ' ' . (string)($competition->comp_year ?? '')) ?></p>
                    <?php endif; ?>
                    <p><strong>Design Type:</strong> <?= esc((string)($entry->design_type ?? '')) ?></p>
                    <p><strong>Series:</strong> <?= esc((string)($entry->series ?? 'No')) ?></p>
                    <?php if (!empty($entry->short_description)): ?><p><strong>Short Description:</strong><br><?= esc((string)$entry->short_description) ?></p><?php endif; ?>
                    <?php if (!empty($entry->full_description)): ?><p><strong>Full Description:</strong><br><?= esc((string)$entry->full_description) ?></p><?php endif; ?>
                </div>
            </div>

            <?php if (!empty($photos)): ?>
                <div class="card mb-4">
                    <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">Entry Photos</h6></div>
                    <div class="card-body">
                        <div class="row">
                            <?php foreach ($photos as $photo): ?>
                                <div class="col-6 col-md-4 mb-3">
                                    <button
                                        type="button"
                                        class="btn p-0 border-0 bg-transparent js-judging-photo-link"
                                        data-photo-url="<?= esc(site_url('media/photo/' . (int)$photo['entry_photo_id'])) ?>"
                                        data-photo-title="<?= esc((string)($entry->design_name ?? 'Entry')) ?> - Photo <?= (int)($photo['entry_photo_order'] ?? 0) ?>"
                                        aria-label="Open entry photo"
                                    >
                                        <img src="<?= site_url('media/photo/' . (int)$photo['entry_photo_id']) ?>" class="img-fluid img-thumbnail" alt="Entry photo">
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($videoEmbed['embed_url'])): ?>
                <div class="card mb-4">
                    <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">Entry Video</h6></div>
                    <div class="card-body">
                        <div class="embed-responsive embed-responsive-16by9">
                            <iframe
                                class="embed-responsive-item"
                                src="<?= esc((string)$videoEmbed['embed_url']) ?>"
                                title="<?= esc((string)($entry->design_name ?? 'Entry')) ?> video"
                                allow="autoplay; fullscreen; picture-in-picture; encrypted-media"
                                allowfullscreen
                                loading="lazy"
                                referrerpolicy="strict-origin-when-cross-origin"
                            ></iframe>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="modal fade" id="judgingPhotoModal" tabindex="-1" role="dialog" aria-labelledby="judgingPhotoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="judgingPhotoModalLabel">Entry Photo</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body text-center">
                <img id="judgingPhotoModalImage" src="" alt="" class="img-fluid rounded border spark-max-h-75vh">
            </div>
            <div class="modal-footer d-flex justify-content-between">
                <button type="button" class="btn btn-outline-secondary btn-sm" id="judgingPhotoPrevBtn">Previous</button>
                <div class="small text-muted" id="judgingPhotoCounter"></div>
                <button type="button" class="btn btn-outline-secondary btn-sm" id="judgingPhotoNextBtn">Next</button>
            </div>
        </div>
    </div>
</div>

<script src="/js/utils/modal.js"></script>
<script src="/js/utils/photo-preview-modal.js"></script>
<script src="/js/pages/judging-entry.js"></script>

<?= $this->endSection() ?>
