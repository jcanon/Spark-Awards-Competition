</div>

<footer class="sticky-footer bg-white">
    <div class="container my-auto">
        <div class="copyright text-center my-auto">
            <p><span>Copyright <?= date('Y') ?> Spark Design Awards&trade; All rights reserved.</span></p>
            <a href="https://www.sparkawards.com/privacy-policy/" title="Privacy Policy" target="_blank" rel="noopener">Privacy Policy</a>
            |
            <a href="https://www.sparkawards.com/terms-conditions/" title="Terms & Conditions" target="_blank" rel="noopener">Terms &amp; Conditions</a>
            |
            <a href="https://www.jcanon.org/" title="Jessie Canon | JCanon.org" target="_blank" rel="noopener">Web Development</a>
        </div>
    </div>
</footer>

</div>
</div>

<a class="scroll-to-top rounded" href="#page-top">
    <i class="fas fa-angle-up"></i>
</a>

<div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="logoutModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="logoutModalLabel"><?= esc(lang('Entrant.logout_modal_title')) ?></h5>
                <button class="close" type="button" data-dismiss="modal" aria-label="<?= esc(lang('Entrant.logout_modal_cancel')) ?>">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body"><?= esc(lang('Entrant.logout_modal_body')) ?></div>
            <div class="modal-footer">
                <button class="btn btn-secondary" type="button" data-dismiss="modal"><?= esc(lang('Entrant.logout_modal_cancel')) ?></button>
                <a class="btn btn-primary" href="<?= site_url('auth/logout') ?>"><?= esc(lang('Entrant.nav_logout')) ?></a>
            </div>
        </div>
    </div>
</div>

<script src="/vendor/jquery/jquery.min.js"></script>
<script src="/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="/vendor/jquery-easing/jquery.easing.min.js"></script>
<script src="/js/sb-admin-2.min.js"></script>
<script src="/vendor/chart.js/Chart.min.js"></script>
<script src="/vendor/datatables/jquery.dataTables.min.js"></script>
<script src="/vendor/datatables/dataTables.bootstrap4.min.js"></script>

<script>
    (function () {
        $('.datatable').each(function () {
            var $table = $(this);
            if ($.fn.DataTable.isDataTable(this)) {
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
</script>

</body>
</html>
