</div>

<footer class="sticky-footer bg-white">
    <div class="container my-auto">
        <div class="copyright text-center my-auto">
            <p><span>Copyright <?= date('Y') ?> Spark Design Awards&trade; All rights reserved.</span></p>
            <a href="https://www.sparkawards.com/privacy-policy/" title="Privacy Policy" target="_blank" rel="noopener">Privacy Policy</a>
            &nbsp;|&nbsp;
            <a href="https://www.sparkawards.com/terms-conditions/" title="Terms & Conditions" target="_blank" rel="noopener">Terms &amp; Conditions</a>
            &nbsp;|&nbsp;
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
                <h5 class="modal-title" id="logoutModalLabel">Ready to leave?</h5>
                <button class="close" type="button" data-dismiss="modal" aria-label="Cancel">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">Select "Logout" below if you are ready to end your current session.</div>
            <div class="modal-footer">
                <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                <a class="btn btn-primary" href="<?= site_url('auth/logout') ?>">Logout</a>
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
<script src="/js/private-datatable-init.js"></script>
<script src="/js/utils/spark.js"></script>
<script src="/js/utils/needs-validation.js"></script>
<script src="/js/utils/input-filters.js"></script>

</body>
</html>
