(function () {
    if (!window.jQuery || !window.jQuery.fn || !window.jQuery.fn.DataTable) {
        return;
    }

    window.jQuery('.datatable').each(function () {
        var $table = window.jQuery(this);
        if (window.jQuery.fn.DataTable.isDataTable(this)) {
            return;
        }

        var order = [];
        var orderAttr = $table.attr('data-order');
        if (orderAttr) {
            try {
                order = JSON.parse(orderAttr);
            } catch (e) {
                order = [];
            }
        }

        var pageLength = parseInt($table.attr('data-page-length') || '100', 10);
        if (!pageLength || pageLength < 1) {
            pageLength = 100;
        }

        $table.DataTable({
            pageLength: pageLength,
            lengthMenu: [[25, 50, 100, 500, 1000], [25, 50, 100, 500, 1000]],
            order: order,
            responsive: false
        });
    });
})();
