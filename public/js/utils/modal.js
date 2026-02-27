(function (window) {
    if (!window) {
        return;
    }

    var spark = window.Spark || (window.Spark = {});

    var show = function (element) {
        if (!element) {
            return;
        }

        if (window.bootstrap && window.bootstrap.Modal) {
            if (typeof window.bootstrap.Modal.getOrCreateInstance === 'function') {
                window.bootstrap.Modal.getOrCreateInstance(element).show();
                return;
            }
            try {
                (new window.bootstrap.Modal(element)).show();
                return;
            } catch (error) {
                // Fall through to jQuery modal fallback.
            }
        }

        if (window.jQuery && window.jQuery.fn && window.jQuery.fn.modal) {
            window.jQuery(element).modal('show');
        }
    };

    spark.modal = {
        show: show
    };
})(window);
