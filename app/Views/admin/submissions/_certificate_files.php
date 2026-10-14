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
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-file-pdf" aria-hidden="true"></i></span>
                    </div>
                    <input type="file" name="certificate_pdf" class="form-control" accept=".pdf,application/pdf">
                </div>
                <input type="hidden" name="certificate_thumb_data" id="certificate_thumb_data" value="">
                <div id="certificate-thumb-status" class="small text-muted mt-1"></div>
                <button
                    type="submit"
                    class="btn btn-sm btn-outline-primary mt-2"
                    form="generate-certificate-form"
                    formnovalidate
                    onclick="return confirm('Generate and assign a certificate from the current saved submission data?');"
                >
                    <i class="fas fa-certificate" aria-hidden="true"></i>
                    Generate Certificate
                </button>
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
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-image" aria-hidden="true"></i></span>
                        </div>
                        <input type="file" name="winner_badge" class="form-control" accept="image/*,.jpg,.jpeg,.png,.tif,.tiff">
                    </div>
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
                            <button
                                type="submit"
                                class="btn btn-link text-danger p-0 ml-2"
                                form="delete-photo-<?= (int)$winnerBadge['entry_photo_id'] ?>"
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
