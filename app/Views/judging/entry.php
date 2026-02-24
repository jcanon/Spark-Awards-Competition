<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0 text-gray-800"><?= esc((string)$entry['design_name']) ?></h1>
        <a class="btn btn-sm btn-secondary" href="<?= site_url('judging/entries/competition/' . (int)$compId . '/status/' . rawurlencode($status) . '?phase=' . rawurlencode((string)$phase) . ($fullList ? '&all=1' : '') . '&view=' . rawurlencode((string)($viewMode ?? 'applications'))) ?>">Back to Entry List</a>
    </div>
    <?php if (!empty($judgingContextLabel ?? null)): ?>
        <p class="mb-1 text-muted"><?= esc((string)$judgingContextLabel) ?></p>
    <?php endif; ?>
    <?php if (!empty($competitionLabel ?? null)): ?>
        <p class="mb-3 font-weight-bold text-primary"><?= esc((string)$competitionLabel) ?></p>
    <?php endif; ?>

    <?php if ($msg = session('success')): ?>
        <div class="alert alert-success"><?= esc($msg) ?></div>
    <?php endif; ?>
    <?php if ($msg = session('error')): ?>
        <div class="alert alert-danger"><?= esc($msg) ?></div>
    <?php endif; ?>

    <div class="row">
        <div class="col-12 col-lg-8">
            <div class="card mb-4">
                <div class="card-body">
                    <p><strong>Designer:</strong> <?= esc(trim((string)$entry['designer_first_name'] . ' ' . (string)$entry['designer_last_name'])) ?></p>
                    <p><strong>Design Type:</strong> <?= esc((string)($entry['design_type'] ?? '')) ?></p>
                    <p><strong>Launch Year:</strong> <?= esc((string)($entry['launch_year'] ?? '')) ?></p>
                    <p><strong>Series:</strong> <?= esc((string)($entry['series'] ?? 'No')) ?></p>
                    <?php if (!empty($entry['short_description'])): ?><p><strong>Short Description:</strong><br><?= esc((string)$entry['short_description']) ?></p><?php endif; ?>
                    <?php if (!empty($entry['full_description'])): ?><p><strong>Full Description:</strong><br><?= esc((string)$entry['full_description']) ?></p><?php endif; ?>
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
                                        data-photo-title="<?= esc((string)$entry['design_name']) ?> - Photo <?= (int)($photo['entry_photo_order'] ?? 0) ?>"
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
        </div>

        <div class="col-12 col-lg-4">
            <div class="card mb-4 border-left-warning">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-warning">
                        Vote Now
                        <span class="text-muted font-weight-normal">
                            (<?= (int)($scoredCount ?? 0) ?> out of <?= (int)($entryCount ?? 0) ?> entries scored)
                        </span>
                    </h6>
                </div>
                <div class="card-body">
                    <?php if (!empty($votingOpen)): ?>
                        <form action="<?= site_url('judging/score') ?>" method="post">
                            <?= csrf_field() ?>
                            <input type="hidden" name="entry_id" value="<?= esc((string)$entry['entry_id']) ?>">
                            <input type="hidden" name="entry_phase" value="<?= esc((string)$phase) ?>">
                            <input type="hidden" name="comp_id" value="<?= (int)$compId ?>">
                            <input type="hidden" name="status" value="<?= esc((string)$status) ?>">
                            <input type="hidden" name="full_list" value="<?= !empty($fullList) ? '1' : '0' ?>">
                            <input type="hidden" name="view_mode" value="<?= esc((string)($viewMode ?? 'applications')) ?>">

                            <div class="form-group">
                                <label for="entry_score">Score</label>
                                <select class="form-control" id="entry_score" name="entry_score" required>
                                    <option value="0" <?= ((string)($myScore['entry_score'] ?? '') === '0') ? 'selected' : '' ?>>0 - Poor</option>
                                    <option value="1" <?= ((string)($myScore['entry_score'] ?? '') === '1') ? 'selected' : '' ?>>1 - Good</option>
                                    <option value="2" <?= ((string)($myScore['entry_score'] ?? '') === '2') ? 'selected' : '' ?>>2 - Great</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="entry_comments">Comments</label>
                                <textarea id="entry_comments" name="entry_comments" rows="8" class="form-control"><?= esc((string)($myScore['entry_comments'] ?? '')) ?></textarea>
                            </div>

                            <div class="d-flex">
                                <button class="btn btn-warning flex-fill mr-2" type="submit">Save Vote</button>
                                <a class="btn btn-outline-secondary flex-fill" href="<?= esc((string)($skipTargetUrl ?? current_url())) ?>">Skip</a>
                            </div>
                            <p class="small text-muted mt-2 mb-0">
                                Save Vote and Skip both move to the next unscored entry. After all entries are scored, you will be sent to Score Review.
                            </p>
                        </form>
                    <?php else: ?>
                        <p class="mb-0 text-muted">Voting is not currently open for this entry.</p>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($entryPosition !== null): ?>
                <div class="text-center small text-muted mb-2">Viewing entry <?= (int)$entryPosition ?> of <?= (int)$entryCount ?></div>
            <?php endif; ?>
            <div class="d-flex justify-content-between">
                <?php if (!empty($prevEntry)): ?>
                    <a class="btn btn-outline-primary btn-sm" href="<?= site_url('judging/entry/' . rawurlencode((string)$prevEntry) . '/status/' . rawurlencode($status) . '?phase=' . rawurlencode((string)$phase) . '&comp=' . (int)$compId . ($fullList ? '&all=1' : '') . '&view=' . rawurlencode((string)($viewMode ?? 'applications'))) ?>">&laquo; Previous</a>
                <?php else: ?><span></span><?php endif; ?>
                <?php if (!empty($nextEntry)): ?>
                    <a class="btn btn-outline-primary btn-sm" href="<?= site_url('judging/entry/' . rawurlencode((string)$nextEntry) . '/status/' . rawurlencode($status) . '?phase=' . rawurlencode((string)$phase) . '&comp=' . (int)$compId . ($fullList ? '&all=1' : '') . '&view=' . rawurlencode((string)($viewMode ?? 'applications'))) ?>">Next &raquo;</a>
                <?php endif; ?>
            </div>
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
                <img id="judgingPhotoModalImage" src="" alt="" class="img-fluid rounded border" style="max-height:75vh;">
            </div>
            <div class="modal-footer d-flex justify-content-between">
                <button type="button" class="btn btn-outline-secondary btn-sm" id="judgingPhotoPrevBtn">Previous</button>
                <div class="small text-muted" id="judgingPhotoCounter"></div>
                <button type="button" class="btn btn-outline-secondary btn-sm" id="judgingPhotoNextBtn">Next</button>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        var modalEl = document.getElementById('judgingPhotoModal');
        var modalImage = document.getElementById('judgingPhotoModalImage');
        var modalTitle = document.getElementById('judgingPhotoModalLabel');
        var modalCounter = document.getElementById('judgingPhotoCounter');
        var prevBtn = document.getElementById('judgingPhotoPrevBtn');
        var nextBtn = document.getElementById('judgingPhotoNextBtn');
        var photoLinks = document.querySelectorAll('.js-judging-photo-link');
        var activeIndex = -1;

        if (!modalEl || !modalImage || !prevBtn || !nextBtn || photoLinks.length === 0) {
            return;
        }

        var showModal = function (element) {
            if (window.bootstrap && window.bootstrap.Modal) {
                if (typeof window.bootstrap.Modal.getOrCreateInstance === 'function') {
                    window.bootstrap.Modal.getOrCreateInstance(element).show();
                    return;
                }
                try {
                    (new window.bootstrap.Modal(element)).show();
                    return;
                } catch (e) {
                    // fall through to jQuery modal fallback
                }
            }
            if (window.jQuery && window.jQuery.fn && window.jQuery.fn.modal) {
                window.jQuery(element).modal('show');
            }
        };

        var setPhotoByIndex = function (index) {
            if (index < 0 || index >= photoLinks.length) {
                return;
            }
            activeIndex = index;
            var link = photoLinks[index];
            var photoUrl = link.getAttribute('data-photo-url') || '';
            var photoTitle = link.getAttribute('data-photo-title') || 'Entry Photo';
            if (!photoUrl) {
                return;
            }

            modalImage.src = photoUrl;
            modalImage.alt = photoTitle;
            if (modalTitle) {
                modalTitle.textContent = photoTitle;
            }
            if (modalCounter) {
                modalCounter.textContent = 'Photo ' + (activeIndex + 1) + ' of ' + photoLinks.length;
            }
            prevBtn.disabled = activeIndex <= 0;
            nextBtn.disabled = activeIndex >= (photoLinks.length - 1);
        };

        photoLinks.forEach(function (link, idx) {
            link.addEventListener('click', function (event) {
                event.preventDefault();
                setPhotoByIndex(idx);
                showModal(modalEl);
            });
        });

        prevBtn.addEventListener('click', function () {
            setPhotoByIndex(activeIndex - 1);
        });
        nextBtn.addEventListener('click', function () {
            setPhotoByIndex(activeIndex + 1);
        });

        document.addEventListener('keydown', function (event) {
            if (!modalEl.classList.contains('show')) {
                return;
            }
            if (event.key === 'ArrowLeft') {
                event.preventDefault();
                setPhotoByIndex(activeIndex - 1);
            } else if (event.key === 'ArrowRight') {
                event.preventDefault();
                setPhotoByIndex(activeIndex + 1);
            }
        });
    })();
</script>

<?= $this->endSection() ?>
