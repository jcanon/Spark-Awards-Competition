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

        $table.DataTable({
            pageLength: 100,
            lengthMenu: [[25, 50, 100, 500, 1000], [25, 50, 100, 500, 1000]],
            order: order,
            responsive: false
        });
    });
})();
