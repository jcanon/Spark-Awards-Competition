(function () {
    var spark = window.Spark || {};
    if (!spark.photoPreviewModal || typeof spark.photoPreviewModal.initGalleryModal !== 'function') {
        return;
    }

    spark.photoPreviewModal.initGalleryModal({
        modalId: 'judgingPhotoModal',
        imageId: 'judgingPhotoModalImage',
        titleId: 'judgingPhotoModalLabel',
        counterId: 'judgingPhotoCounter',
        prevBtnId: 'judgingPhotoPrevBtn',
        nextBtnId: 'judgingPhotoNextBtn',
        linkSelector: '.js-judging-photo-link',
        defaultTitle: 'Entry Photo'
    });
})();
