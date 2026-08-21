<script>
    // ============================================================
    // @incoming_letterdetail BAGIAN 1: MODEL DASAR (default value) 
    // ============================================================
    // Bentuk kosong satu record disposisi BARU untuk surat ini.
    // letter_id sudah tetap (surat yang sedang dibuka), sisanya kosong.
    model.masterModelDisposisiBaru = {
        id: "0",
        letter_id: "<?= $surat['id'] ?>",
        recipient_id: "",
        instruction: "",
        disposition_date: "",
        status: "Pending",   // disposisi baru selalu mulai dari Pending
        notes: ""
    }

    // ============================================================
    // BAGIAN 2: VIEW MODEL UTAMA (Knockout ViewModel bernama "suratDetail")
    // ============================================================
    var suratDetail = {
        title: "Detail Surat Masuk",

        letterId: "<?= $surat['id'] ?>",
        Recordmaterial: ko.mapping.fromJS(model.masterModelDisposisiBaru),

        // Daftar penerima untuk dropdown, di-load lewat AJAX (konsisten dengan
        // pola disposisi.loadSelectPenerima() di Master Disposisi).
        SELECTPENERIMA: ko.observableArray([]),

        // Pilihan status disposisi (sama seperti Master Disposisi, sesuai
        // CHECK constraint chk_disposition_status pada tabel dispositions).
        SELECTSTATUS: [
            { name: 'Pending', value: 'Pending' },
            { name: 'In Progress', value: 'In Progress' },
            { name: 'Completed', value: 'Completed' },
        ],

        // Riwayat disposisi untuk surat ini, di-load lewat AJAX (observableArray,
        // bukan lagi hasil render PHP langsung), supaya bisa refresh tanpa reload halaman.
        ListDisposisi: ko.observableArray([])
    }

    // ============================================================
    // BAGIAN 3: FUNGSI-FUNGSI VIEW MODEL
    // ============================================================

    // Ambil daftar penerima untuk dropdown "Pilih Penerima".
    suratDetail.loadSelectPenerima = function () {
        $.ajax({
            url: "<?php echo site_url('pengelolaan/DispositionsController/getSelectPenerima') ?>",
            type: "GET",
            dataType: "json",
            success: function (res) {
                suratDetail.SELECTPENERIMA(res);
            },
            error: function (err) {
                console.log("Gagal load penerima", err);
            }
        });
    }

    // Ambil riwayat disposisi milik surat ini (endpoint baru, khusus per-surat,
    // padanan DispositionsController::getByLetterId yang di-JSON-kan).
    suratDetail.loadHistory = function () {
        $.ajax({
            url: "<?php echo site_url('pengelolaan/DispositionsController/getByLetterId') ?>",
            type: "POST",
            data: JSON.stringify({ letter_id: suratDetail.letterId }),
            contentType: "application/json",
            dataType: "json",
            success: function (res) {
                suratDetail.ListDisposisi(res || []);
            },
            error: function (err) {
                console.log("Gagal load riwayat disposisi", err);
            }
        });
    }

    // Kosongkan form ke nilai default (letter_id tetap dipertahankan).
    suratDetail.resetForm = function () {
        var fresh = JSON.parse(JSON.stringify(model.masterModelDisposisiBaru));
        ko.mapping.fromJS(fresh, suratDetail.Recordmaterial);
    }

    // Simpan disposisi baru untuk surat ini.
    suratDetail.save = function () {
        var val = suratDetail.Recordmaterial;

        // --- VALIDASI SISI CLIENT (diulang lagi di server oleh DispositionsController::_validate()) ---
        if (!val.recipient_id()) {
            swal("Peringatan!", "Penerima disposisi wajib dipilih!", "warning");
            return;
        }
        if (!val.disposition_date()) {
            swal("Peringatan!", "Tanggal disposisi wajib diisi!", "warning");
            return;
        }
        if (!val.instruction()) {
            swal("Peringatan!", "Instruksi disposisi wajib diisi!", "warning");
            return;
        }

        swal({
            title: "Perhatian",
            text: "Simpan disposisi baru untuk surat ini?",
            type: "info",
            className: 'animate_animated animate_fadeInUp',
            showCancelButton: true,
            confirmButtonColor: "#DD6B55",
            confirmButtonText: "Yes!",
            cancelButtonText: "No!",
            closeOnConfirm: false,
            showLoaderOnConfirm: true
        }, function (isConfirm) {
            if (isConfirm) {
                $.ajax({
                    url: "<?php echo base_url('pengelolaan/DispositionsController/save') ?>",
                    type: "POST",
                    data: JSON.stringify(ko.mapping.toJS(val)),
                    contentType: "application/json",
                    dataType: "json",
                    success: function (res) {
                        if (res.result) {
                            swal({
                                title: "Good job!",
                                text: "Disposisi berhasil disimpan!",
                                icon: "success"
                            });
                            suratDetail.resetForm();
                            suratDetail.loadHistory(); // refresh riwayat tanpa reload halaman
                        } else {
                            swal("Gagal!", res.message || "Terjadi kesalahan.", "error");
                        }
                    }
                });
            }
        });
    }

    // Ubah status disposisi langsung dari dropdown di riwayat, tanpa reload halaman.
    suratDetail.changeStatus = function (id, newStatus) {
        $.ajax({
            url: "<?php echo base_url('pengelolaan/DispositionsController/updateStatusOnly') ?>",
            type: "POST",
            data: JSON.stringify({ id: id, status: newStatus }),
            contentType: "application/json",
            dataType: "json",
            success: function (res) {
                if (res.result) {
                    swal("Berhasil!", "Status disposisi diperbarui.", "success");
                    suratDetail.loadHistory();
                } else {
                    swal("Gagal!", "Status tidak berhasil diperbarui.", "error");
                }
            }
        });
    }
</script>

<!-- ============================================================
     BAGIAN 4: HTML / TAMPILAN HALAMAN
     ============================================================ -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-12">
                <a href="<?= base_url('pengelolaan/Incoming_lettersController') ?>" class="btn btn-secondary btn-sm mb-2">
                    <i class="fas fa-arrow-left"></i> Kembali ke Daftar Surat
                </a>
                <h1><?= isset($title) ? $title : 'Detail Surat Masuk' ?></h1>
            </div>
        </div>
    </div>
</section>

<section class="content" data-bind="with: suratDetail">
    <div class="container-fluid">

        <!-- ==================== Detail Surat (read-only, dari PHP) ==================== -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Surat: <?= html_escape($surat['letter_number']) ?></h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Pengirim:</strong> <?= html_escape($surat['sender']) ?></p>
                        <p><strong>Perihal:</strong> <?= html_escape($surat['subject']) ?></p>
                        <p><strong>Keterangan:</strong> <?= html_escape($surat['description'] ?: '-') ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Tanggal Surat:</strong> <?= $surat['letter_date'] ?></p>
                        <p><strong>Tanggal Diterima:</strong> <?= $surat['received_date'] ?></p>
                        <p><strong>Status Surat:</strong>
                            <span class="badge badge-<?= $surat['status'] == 'Completed' ? 'success' : ($surat['status'] == 'Processing' ? 'warning' : ($surat['status'] == 'Archived' ? 'secondary' : 'info')) ?>">
                                <?= $surat['status'] ?>
                            </span>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- ==================== Form Disposisi Baru (Knockout) ==================== -->
            <div class="col-md-5">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0">Buat Disposisi Baru</h5>
                    </div>
                    <div class="card-body" data-bind="with: Recordmaterial">
                        <div class="form-group">
                            <label>Pilih Penerima</label>
                            <select class="form-control" data-bind="
                                options: suratDetail.SELECTPENERIMA,
                                optionsText: 'name',
                                optionsValue: 'value',
                                optionsCaption: '-- Pilih Penerima --',
                                value: recipient_id">
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Tanggal Disposisi</label>
                            <input type="date" class="form-control" data-bind="value: disposition_date">
                        </div>

                        <div class="form-group">
                            <label>Instruksi</label>
                            <textarea class="form-control" rows="3" placeholder="Instruksi disposisi..." data-bind="value: instruction"></textarea>
                        </div>

                        <div class="form-group">
                            <label>Catatan Opsional</label>
                            <textarea class="form-control" rows="2" placeholder="Catatan tambahan..." data-bind="value: notes"></textarea>
                        </div>

                        <button type="button" class="btn btn-success btn-block" data-bind="click: suratDetail.save">
                            <i class="fas fa-paper-plane"></i> Simpan Disposisi
                        </button>
                    </div>
                </div>
            </div>

            <!-- ==================== Riwayat Disposisi (Knockout foreach) ==================== -->
            <div class="col-md-7">
                <div class="card shadow-sm">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">Riwayat Disposisi Surat</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Penerima</th>
                                        <th>Instruksi</th>
                                        <th>Tgl</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody data-bind="foreach: ListDisposisi">
                                    <tr>
                                        <td>
                                            <strong data-bind="text: recipient_name"></strong><br>
                                            <small class="text-muted" data-bind="text: department"></small>
                                        </td>
                                        <td>
                                            <span data-bind="text: instruction"></span>
                                            <!-- ko if: notes -->
                                            <br><small class="text-info">Note: <span data-bind="text: notes"></span></small>
                                            <!-- /ko -->
                                        </td>
                                        <td data-bind="text: disposition_date"></td>
                                        <td>
                                            <select class="form-control form-control-sm history-status-select"
                                                    data-bind="value: status, attr: { 'data-id': id }">
                                                <option value="Pending">Pending</option>
                                                <option value="In Progress">In Progress</option>
                                                <option value="Completed">Completed</option>
                                            </select>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            <!-- ko if: ListDisposisi().length === 0 -->
                            <p class="text-center text-muted mb-0">Belum ada riwayat disposisi untuk surat ini.</p>
                            <!-- /ko -->
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</section>

<!-- ============================================================
     BAGIAN 5: INISIALISASI HALAMAN + EVENT HANDLER
     ============================================================ -->
<script>
$(document).ready(function () {
    // Saat halaman siap: isi dropdown penerima & load riwayat disposisi surat ini.
    suratDetail.loadSelectPenerima();
    suratDetail.loadHistory();

    // Event delegation untuk dropdown status di riwayat (baris dibuat dinamis
    // oleh Knockout foreach, jadi bind lewat elemen induk yang statis).
    $(document).on('change', '.history-status-select', function () {
        var id = $(this).data('id');
        var newStatus = $(this).val();
        suratDetail.changeStatus(id, newStatus);
    });
});
</script>