<section class="content">
    <div class="container-fluid">

        <!-- ==================== Kartu Ringkasan ==================== -->
        <div class="row">
            <div class="col-lg-4 col-md-6 mb-3">
                <div class="card text-white bg-primary shadow-sm h-100">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <div class="h2 mb-0"><?= (int) $totalSurat ?></div>
                            <div>Total Surat Masuk</div>
                        </div>
                        <i class="fas fa-envelope fa-2x opacity-75"></i>
                    </div>
                    <a href="<?= base_url('pengelolaan/Incoming_lettersController') ?>" class="card-footer text-white d-flex justify-content-between align-items-center" style="background:rgba(0,0,0,.15);">
                        Lihat Daftar Surat <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>

            <div class="col-lg-4 col-md-6 mb-3">
                <div class="card text-white bg-warning shadow-sm h-100">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <div class="h2 mb-0"><?= (int) $totalDisposisi ?></div>
                            <div>Total Disposisi</div>
                        </div>
                        <i class="fas fa-share fa-2x opacity-75"></i>
                    </div>
                    <a href="<?= base_url('pengelolaan/DispositionsController') ?>" class="card-footer text-white d-flex justify-content-between align-items-center" style="background:rgba(0,0,0,.15);">
                        Lihat Daftar Disposisi <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>

            <div class="col-lg-4 col-md-6 mb-3">
                <div class="card text-white bg-success shadow-sm h-100">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <div class="h2 mb-0"><?= (int) $totalPenerima ?></div>
                            <div>Total Penerima Disposisi</div>
                        </div>
                        <i class="fas fa-users fa-2x opacity-75"></i>
                    </div>
                    <a href="<?= base_url('pengelolaan/RecipientsController') ?>" class="card-footer text-white d-flex justify-content-between align-items-center" style="background:rgba(0,0,0,.15);">
                        Lihat Daftar Penerima <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- ==================== Breakdown Status Surat ==================== -->
            <div class="col-md-6 mb-3">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-chart-bar mr-1"></i> Status Surat Masuk</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        // Warna badge disamakan dengan yang sudah dipakai di
                        // @incoming_letters.php / @incoming_lettersdetail.php,
                        // supaya konsisten di seluruh aplikasi.
                        $suratColors = [
                            'Received'   => 'info',
                            'Processing' => 'warning',
                            'Completed'  => 'success',
                            'Archived'   => 'secondary',
                        ];
                        $suratTotal = max(1, array_sum($statusSurat)); // hindari div by zero
                        foreach ($statusSurat as $status => $jumlah):
                            $percent = round($jumlah / $suratTotal * 100);
                        ?>
                        <div class="mb-2">
                                <div class="d-flex justify-content-between">
                                    <span class="badge badge-<?= (isset($suratColors[$status]) ? $suratColors[$status] : 'light') ?>"><?= $status ?></span>                                <span><?= (int) $jumlah ?> surat</span>
                                </div>
                                <div class="progress" style="height:8px;">
                                <div class="progress-bar bg-<?= (isset($suratColors[$status]) ? $suratColors[$status] : 'secondary') ?>" style="width: <?= $percent ?>%;"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- ==================== Breakdown Status Disposisi ==================== -->
            <div class="col-md-6 mb-3">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-chart-pie mr-1"></i> Status Disposisi</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $dispoColors = [
                            'Pending'     => 'secondary',
                            'In Progress' => 'warning',
                            'Completed'   => 'success',
                        ];
                        $dispoTotal = max(1, array_sum($statusDisposisi));
                        foreach ($statusDisposisi as $status => $jumlah):
                            $percent = round($jumlah / $dispoTotal * 100);
                        ?>
                        <div class="mb-2">
                            <div class="d-flex justify-content-between">
                                <span class="badge badge-<?= (isset($dispoColors[$status]) ? $dispoColors[$status] : 'light') ?>"><?= $status ?></span>
                                <span><?= (int) $jumlah ?> disposisi</span>
                            </div>
                            <div class="progress" style="height:8px;">
                                <div class="progress-bar bg-<?= (isset($dispoColors[$status]) ? $dispoColors[$status] : 'secondary') ?>" style="width: <?= $percent ?>%;"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- ==================== Surat Terbaru ==================== -->
            <div class="col-md-6 mb-3">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-clock mr-1"></i> Surat Masuk Terbaru</h5>
                        <a href="<?= base_url('pengelolaan/Incoming_lettersController') ?>" class="small">Lihat semua</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>No. Surat</th>
                                    <th>Perihal</th>
                                    <th>Tgl Diterima</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($recentLetters)): foreach ($recentLetters as $l): ?>
                                <tr>
                                    <td>
                                        <a href="<?= base_url('pengelolaan/Incoming_lettersController/detail/' . $l->id) ?>">
                                            <?= html_escape($l->letter_number) ?>
                                        </a>
                                    </td>
                                    <td><?= html_escape($l->subject) ?></td>
                                    <td><?= $l->received_date ?></td>
                                    <td>
                                        <span class="badge badge-<?= isset($suratColors[$l->status]) ? $suratColors[$l->status] : 'light' ?>"><?= $l->status ?></span>
                                    </td>
                                </tr>
                                <?php endforeach; else: ?>
                                <tr><td colspan="4" class="text-center text-muted py-3">Belum ada surat masuk.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ==================== Disposisi Perlu Ditindaklanjuti ==================== -->
            <div class="col-md-6 mb-3">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-tasks mr-1"></i> Disposisi Perlu Ditindaklanjuti</h5>
                        <a href="<?= base_url('pengelolaan/DispositionsController') ?>" class="small">Lihat semua</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>No. Surat</th>
                                    <th>Penerima</th>
                                    <th>Instruksi</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($activeDispositions)): foreach ($activeDispositions as $d): ?>
                                <tr>
                                    <td><?= html_escape($d->letter_number) ?></td>
                                    <td><?= html_escape($d->recipient_name) ?></td>
                                    <td><?= html_escape(mb_strimwidth($d->instruction, 0, 40, '...')) ?></td>
                                    <td>
                                        <span class="badge badge-<?= isset($dispoColors[$d->status]) ? $dispoColors[$d->status] : 'light' ?>"><?= $d->status ?></span>
                                    </td>
                                </tr>
                                <?php endforeach; else: ?>
                                <tr><td colspan="4" class="text-center text-muted py-3">Tidak ada disposisi aktif saat ini. 🎉</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>
</section>