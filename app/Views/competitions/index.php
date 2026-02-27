<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

    <!-- Begin Page Content -->
    <div class="container-fluid">

        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">Competitions</h1>
        </div>

        <p>Entering your design is quick and easy. Please read the preceding information on the
            <a href="https://www.sparkawards.com/about/process/" target="_blank" rel="noopener"><strong>Process</strong></a> page.</p>

        <hr class="mb-4">

        <div class="row">

            <!-- Open Competitions -->
            <div class="col-lg-12 mb-12">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Open Competitions: Enter Now</h6>
                    </div>
                    <div class="card-body">
                        <?php
                        if (!empty($open)): ?>
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover align-middle">
                                    <thead>
                                    <tr>
                                        <th width="55%">Competition</th>
                                        <th width="15%">Opens</th>
                                        <th width="15%">Closes</th>
                                        <th width="15%"></th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php
                                    foreach ($open as $c): ?>
                                        <?php
                                        $compId = (int)($c->comp_id ?? 0);
                                        $type = (string)($c->comp_type_name ?? '');
                                        $year = (int)($c->comp_year ?? 0);
                                        $openAt = (string)($c->comp_phase_1_open ?? $c->comp_phase_2_open ?? '');
                                        $closeAt = (string)($c->comp_phase_1_close ?? $c->comp_phase_2_close ?? '');
                                        $name = trim($type . ' ' . ($year > 0 ? $year : ''));
                                        ?>
                                        <tr>
                                            <td>
                                                <a href="<?= site_url('competitions/create/' . $compId) ?>">
                                                    <?= esc($name) ?>
                                                </a>
                                            </td>
                                            <td><?= esc(format_datetime_ui($openAt)) ?></td>
                                            <td><?= esc(format_datetime_ui($closeAt)) ?></td>
                                            <td class="text-center">
                                                <a href="<?= site_url('competitions/create/' . $compId) ?>"
                                                   class="btn btn-sm btn-primary shadow-sm">
                                                    Create Submission
                                                </a>
                                            </td>
                                        </tr>
                                    <?php
                                    endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php
                        else: ?>
                            <p class="mb-0">There are currently no open competitions at this time.</p>
                        <?php
                        endif; ?>
                    </div>
                </div>
            </div>

            <!-- Upcoming Competitions -->
            <div class="col-lg-12 mb-12">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Upcoming Competitions</h6>
                    </div>
                    <div class="card-body">
                        <?php
                        if (!empty($upcoming)): ?>
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover align-middle">
                                    <thead>
                                    <tr>
                                        <th width="55%">Competition</th>
                                        <th width="15%">Opens</th>
                                        <th width="15%">Closes</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php
                                    foreach ($upcoming as $c): ?>
                                        <?php
                                        $type = (string)($c->comp_type_name ?? '');
                                        $year = (int)($c->comp_year ?? 0);
                                        $openAt = (string)($c->comp_phase_1_open ?? '');
                                        $closeAt = (string)($c->comp_phase_1_close ?? '');
                                        $name = trim($type . ' ' . ($year > 0 ? $year : ''));
                                        ?>
                                        <tr>
                                            <td><?= esc($name) ?></td>
                                            <td><?= esc(format_datetime_ui($openAt)) ?></td>
                                            <td><?= esc(format_datetime_ui($closeAt)) ?></td>
                                        </tr>
                                    <?php
                                    endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php
                        else: ?>
                            <p class="mb-0">There are currently no additional upcoming competitions at this time.</p>
                        <?php
                        endif; ?>
                    </div>
                </div>
            </div>

        </div>

    </div>

<?= $this->endSection() ?>
