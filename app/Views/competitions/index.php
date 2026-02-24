<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

    <!-- Begin Page Content -->
    <div class="container-fluid">

        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800"><?= esc(lang('Entrant.competitions_title')) ?></h1>
        </div>

        <p><?= esc(lang('Entrant.competitions_intro')) ?>
            <a href="https://www.sparkawards.com/about/process/" target="_blank" rel="noopener"><strong><?= esc(lang('Entrant.process_page')) ?></strong></a> <?= esc(lang('Entrant.process_page_suffix')) ?></p>

        <hr class="mb-4">

        <div class="row">

            <!-- Open Competitions -->
            <div class="col-lg-12 mb-12">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary"><?= esc(lang('Entrant.open_competitions')) ?></h6>
                    </div>
                    <div class="card-body">
                        <?php
                        if (!empty($open)): ?>
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover align-middle">
                                    <thead>
                                    <tr>
                                        <th width="55%"><?= esc(lang('Entrant.competition')) ?></th>
                                        <th width="15%"><?= esc(lang('Entrant.opens')) ?></th>
                                        <th width="15%"><?= esc(lang('Entrant.closes')) ?></th>
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
                                                    <?= esc(lang('Entrant.create_submission')) ?>
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
                            <p class="mb-0"><?= esc(lang('Entrant.no_open_competitions')) ?></p>
                        <?php
                        endif; ?>
                    </div>
                </div>
            </div>

            <!-- Upcoming Competitions -->
            <div class="col-lg-12 mb-12">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary"><?= esc(lang('Entrant.upcoming_competitions')) ?></h6>
                    </div>
                    <div class="card-body">
                        <?php
                        if (!empty($upcoming)): ?>
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover align-middle">
                                    <thead>
                                    <tr>
                                        <th width="55%"><?= esc(lang('Entrant.competition')) ?></th>
                                        <th width="15%"><?= esc(lang('Entrant.opens')) ?></th>
                                        <th width="15%"><?= esc(lang('Entrant.closes')) ?></th>
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
                            <p class="mb-0"><?= esc(lang('Entrant.no_upcoming_competitions')) ?></p>
                        <?php
                        endif; ?>
                    </div>
                </div>
            </div>

        </div>

    </div>

<?= $this->endSection() ?>
