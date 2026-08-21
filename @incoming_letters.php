<script>
    // ============================================================
    // BAGIAN 1: MODEL DASAR (default value)
    // ============================================================
    // Bentuk kosong satu record surat masuk, sesuai kolom tabel
    // incoming_letters pada pengelolaan.sql. Dipakai sebagai nilai awal
    // form (mode tambah) dan untuk reset form (surat.reset / surat.back).
    model.masterModelSurat = {
        id: "0",                // id record, "0" = data baru
        letter_number: "",      // No. Surat (UNIQUE di DB)
        letter_date: "",        // Tanggal surat dibuat/dikirim
        received_date: "",      // Tanggal surat diterima (>= letter_date, dicek CHECK constraint)
        sender: "",             // Pengirim
        subject: "",            // Perihal
        description: "",        // Keterangan/deskripsi (opsional)
        status: "Received"      // Status default saat surat baru masuk
    }

    // ============================================================
    // BAGIAN 2: VIEW MODEL UTAMA (Knockout ViewModel bernama "surat")
    // ============================================================
    var surat = {
        title: "Master Surat Masuk",

        // Recordmaterial = observable object yang di-bind ke form (tab "Tambah/Edit Surat").
        Recordmaterial: ko.mapping.fromJS(model.masterModelSurat),

        // Mode: '' (tambah baru) atau 'Update' (edit data lama).
        Mode: ko.observable(''),
        IsReadOnly: ko.observable(false),


        // Filter & pencarian tabel (kolom target bisa dipilih, sama seperti pola Disposisi).
        FilterText: ko.observable(''),
        FilterValue: ko.observable('letter_number'),

        // Pilihan status sesuai CHECK constraint chk_letter_status pada incoming_letters.
        SELECTSTATUS: [
            { name: 'Received',   value: 'Received' },
            { name: 'Processing', value: 'Processing' },
            { name: 'Completed',  value: 'Completed' },
            { name: 'Archived',   value: 'Archived' },
        ],

        // Kolom yang bisa dipilih user sebagai target pencarian/filter.
        SELECTFILTERVALUE: [
            { name: 'Nomor Surat', value: 'letter_number' },
            { name: 'Pengirim',    value: 'sender' },
            { name: 'Perihal',     value: 'subject' },
            { name: 'Status',      value: 'status' }
        ]
    }

    // ============================================================
    // BAGIAN 3: FUNGSI-FUNGSI VIEW MODEL
    // ============================================================

    // Dipanggil saat tombol "Cari" di tab List ditekan.
    surat.filterData = function () {
        if (surat.grid) surat.grid.ajax.reload();
    }

    // Reset kotak pencarian & reload tabel.
    surat.resetFilter = function () {
        surat.FilterText('');
        if (surat.grid) surat.grid.ajax.reload();
    }

    // Dipanggil setelah simpan sukses / batal edit:
    // 1. Mode kembali ke '' (mode tambah)
    // 2. Reload tabel
    // 3. Kosongkan form ke nilai default
    // 4. Jika tab=true, pindah tampilan ke tab "Daftar Surat"
    surat.back = function (tab) {
        surat.Mode('');
        surat.IsReadOnly(false); // tambahan
        if (surat.grid) surat.grid.ajax.reload();
        ko.mapping.fromJS(model.masterModelSurat, surat.Recordmaterial);
        if (tab) $('a[href="#tablistSurat"]').tab('show');
    }

    // Klik tombol "Edit" pada baris tabel:
    // ambil detail record dari server, isi form, set Mode('Update'), pindah ke tab form.
    surat.selectdata = function (id) {
        $.ajax({
            url: "<?php echo base_url('pengelolaan/Incoming_lettersController/getDataSelect') ?>",
            type: "POST",
            data: JSON.stringify({ id: id }),
            contentType: "application/json",
            dataType: "json",
            success: function (res) {
                if (res && res[0]) {
                    ko.mapping.fromJS(res[0], surat.Recordmaterial);
                    surat.Mode("Update");
                    surat.IsReadOnly(res[0].status === 'Archived'); // <-- tambahan
                    $('a[href="#tabformSurat"]').tab('show');
                }
            }
        });
    }

    // Kosongkan form & keluar dari mode Update tanpa reload tabel / pindah tab.
    surat.reset = function () {
        ko.mapping.fromJS(model.masterModelSurat, surat.Recordmaterial);
        surat.Mode('');
        surat.IsReadOnly(false); // tambahan
    }

    // Simpan (tambah baru / update), menangani dua kasus sekaligus lewat Mode.
    surat.save = function () {
        var val = surat.Recordmaterial;

        // --- VALIDASI SISI CLIENT (diulang lagi di server) ---
        if (!val.letter_number()) {
            swal("Peringatan!", "Nomor surat wajib diisi!", "warning");
            return;
        }
        if (!val.sender()) {
            swal("Peringatan!", "Pengirim wajib diisi!", "warning");
            return;
        }
        if (!val.subject()) {
            swal("Peringatan!", "Perihal wajib diisi!", "warning");
            return;
        }
        if (!val.letter_date()) {
            swal("Peringatan!", "Tanggal surat wajib diisi!", "warning");
            return;
        }
        if (!val.received_date()) {
            swal("Peringatan!", "Tanggal diterima wajib diisi!", "warning");
            return;
        }
        // Samakan dengan CHECK constraint chk_received_after_letter_date di DB:
        // received_date harus >= letter_date.
        if (val.received_date() < val.letter_date()) {
            swal("Peringatan!", "Tanggal diterima tidak boleh sebelum tanggal surat!", "warning");
            return;
        }

        swal({
            title: "Perhatian",
            text: "Anda akan simpan data surat masuk ini?",
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
                var url = "<?php echo base_url('pengelolaan/Incoming_lettersController/save') ?>";
                if (surat.Mode() === 'Update') {
                    url = "<?php echo base_url('pengelolaan/Incoming_lettersController/update') ?>";
                }

                $.ajax({
                    url: url,
                    type: "POST",
                    data: JSON.stringify(ko.mapping.toJS(val)),
                    contentType: "application/json",
                    dataType: "json",
                    success: function (res) {
                        if (res.result) {
                            swal({
                                title: "Good job!",
                                text: surat.Mode() === 'Update' ? "Data berhasil diubah!" : "Data berhasil disimpan!",
                                icon: "success"
                            });
                            surat.back(1);
                        } else {
                            swal("Gagal!", res.message || "Terjadi kesalahan.", "error");
                        }
                    }
                });
            }
        });
    }

    // Hapus surat (CASCADE ke dispositions sesuai fk_disposition_letter ON DELETE CASCADE).
    surat.remove = function (id) {
        swal({
            title: "Yakin?",
            text: "Surat beserta seluruh riwayat disposisinya akan dihapus permanen!",
            type: "warning",
            showCancelButton: true,
            confirmButtonText: "Ya, hapus!",
            cancelButtonText: "Batal"
        }, function (isConfirm) {
            if (isConfirm) {
                $.ajax({
                    url: "<?php echo base_url('pengelolaan/Incoming_lettersController/delete') ?>",
                    type: "POST",
                    data: JSON.stringify({ id: id }),
                    contentType: "application/json",
                    dataType: "json",
                    success: function (res) {
                        if (res.result) {
                            if (surat.grid) surat.grid.ajax.reload();
                            swal("Terhapus!", "Data berhasil dihapus.", "success");
                        } else {
                            swal("Gagal!", res.message, "error");
                        }
                    }
                });
            }
        });
    }

    // Update status langsung dari dropdown di tabel list, tanpa reload halaman.
    surat.changeStatus = function (id, newStatus) {
        $.ajax({
            url: "<?php echo base_url('pengelolaan/Incoming_lettersController/updateStatusOnly') ?>",
            type: "POST",
            data: JSON.stringify({ id: id, status: newStatus }),
            contentType: "application/json",
            dataType: "json",
            success: function (res) {
                if (res.result) {
                    if (surat.grid) surat.grid.ajax.reload(null, false);
                    swal("Berhasil!", "Status surat diperbarui.", "success");
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
                <h1><?= isset($title) ? $title : 'Master Surat Masuk' ?></h1>
            </div>
        </div>
    </div>
</section>

<section class="content" data-bind="with: surat">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card card-light">
                    <div class="card-header">
                        <ul class="nav nav-tabs card-header-tabs">
                            <li class="nav-item"><a class="nav-link active" href="#tabformSurat" data-toggle="tab">Tambah Surat</a></li>
                            <li class="nav-item"><a class="nav-link" href="#tablistSurat" data-toggle="tab">Daftar Surat</a></li>
                        </ul>
                    </div>
                    <div class="card-body">
                        <div class="tab-content">

                            <!-- ==================== TAB FORM ==================== -->
                            <div class="tab-pane active" id="tabformSurat">
                                <div class="row mb-3">
                                    <div class="col-12">
                                        <button class="btn btn-sm btn-warning mr-1" data-bind="click: function() { back(1); }, visible: Mode() == 'Update'">
                                            <i class="fa fa-arrow-left"></i> Kembali
                                        </button>
                                        <button class="btn btn-sm btn-info" data-bind="click: save,visible: !IsReadOnly()">
                                            <i class="fa fa-save"></i> Simpan
                                        </button>
                                        <span class="text-muted" data-bind="visible: IsReadOnly()">
                                            <i class="fa fa-lock"></i> Surat ini sudah diarsipkan dan bersifat read-only.
                                        </span>
                                    </div>
                                </div>

                                <div class="card card-olive">
                                    <div class="card-header">
                                        <h3 class="card-title">Detail Surat Masuk</h3>
                                    </div>
                                    <div class="card-body" data-bind="with: Recordmaterial">
                                        <div class="row">
                                            <div class="col-12 col-md-6">
                                                <div class="form-group">
                                                    <label>Nomor Surat</label>
                                                    <input type="text" class="form-control" data-bind="value: letter_number,disable: $parent.IsReadOnly"placeholder="Nomor surat (harus unik)">
                                                </div>
                                            </div>

                                            <div class="col-12 col-md-6">
                                                <div class="form-group">
                                                    <label>Pengirim</label>
                                                    <input type="text" class="form-control" data-bind="value: sender" placeholder="Pengirim surat">
                                                </div>
                                            </div>

                                            <div class="col-12 col-md-6">
                                                <div class="form-group">
                                                    <label>Tanggal Surat</label>
                                                    <input type="date" class="form-control" data-bind="value: letter_date">
                                                </div>
                                            </div>

                                            <div class="col-12 col-md-6">
                                                <div class="form-group">
                                                    <label>Tanggal Diterima</label>
                                                    <input type="date" class="form-control" data-bind="value: received_date">
                                                    <small class="text-muted">Harus sama atau setelah tanggal surat.</small>
                                                </div>
                                            </div>

                                            <div class="col-12 col-md-6">
                                                <div class="form-group">
                                                    <label>Status</label>
                                                    <select class="form-control" data-bind="
                                                        options: surat.SELECTSTATUS,
                                                        optionsText: 'name',
                                                        optionsValue: 'value',
                                                        value: status">
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="col-12">
                                                <div class="form-group">
                                                    <label>Perihal</label>
                                                    <input type="text" class="form-control" data-bind="value: subject" placeholder="Perihal surat">
                                                </div>
                                            </div>

                                            <div class="col-12">
                                                <div class="form-group mb-0">
                                                    <label>Keterangan / Deskripsi</label>
                                                    <textarea class="form-control" rows="3" data-bind="value: description" placeholder="Keterangan tambahan (opsional)"></textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- ==================== TAB LIST ==================== -->
                            <div class="tab-pane" id="tablistSurat">
                                <div class="row align-items-center">
                                    <div class="col-12 col-md-4 mb-2 mb-md-0">
                                        <select class="form-control" data-bind="value: FilterValue, options: SELECTFILTERVALUE, optionsText: 'name', optionsValue: 'value'"></select>
                                    </div>
                                    <div class="col-12 col-md-6 mb-2 mb-md-0">
                                        <input class="form-control" data-bind="value: FilterText, event: { keyup: function(data, event) { if (event.key === 'Enter') $data.filterData(); } }" placeholder="Cari data...">
                                    </div>
                                    <div class="col-12 col-md-2">
                                        <div class="btn-group w-100" role="group">
                                            <button class="btn btn-danger" data-bind="click: resetFilter" title="Reset"><span class="fa fa-retweet"></span></button>
                                            <button class="btn btn-primary" data-bind="click: filterData" title="Cari"><span class="fa fa-search"></span></button>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mt-3">
                                    <div class="col-12">
                                        <div class="table-responsive">
                                            <table id="tableSuratMasuk" width="100%" class="table table-bordered table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>No. Surat</th>
                                                        <th>Tgl Surat</th>
                                                        <th>Tgl Diterima</th>
                                                        <th>Pengirim</th>
                                                        <th>Perihal</th>
                                                        <th>Status</th>
                                                        <th>Aksi</th>
                                                    </tr>
                                                </thead>
                                                <tbody></tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ============================================================
     BAGIAN 5: INISIALISASI DATATABLES (server-side) + EVENT HANDLER
     ============================================================ -->
<script>
$(document).ready(function () {
    surat.grid = $("#tableSuratMasuk").DataTable({
        processing: true,
        serverSide: true,
        searching: false,
        lengthChange: false,
        info: false,
        ajax: {
            url: "<?php echo base_url('pengelolaan/Incoming_lettersController/getData') ?>",
            type: "POST",
            data: function (d) {
                d.filtervalue = surat.FilterValue();
                d.filtertext = surat.FilterText();
                return d;
            },
            dataSrc: function (json) {
                json.recordsTotal = json.RecordsTotal;
                json.recordsFiltered = json.RecordsFiltered;
                return json.Data ? json.Data : [];
            }
        },
        columns: [
            { data: "letter_number" },
            { data: "letter_date" },
            { data: "received_date" },
            { data: "sender" },
            { data: "subject" },
           {
    data: "status",
    render: function (data, type, full) {
        var options = ['Received', 'Processing', 'Completed', 'Archived'];
        var isArchived = (data === 'Archived');

        var select = '<select class="form-control form-control-sm letter-status-select" data-id="' + full.id + '"' 
                    + (isArchived ? ' disabled' : '') + '>';
        options.forEach(function (opt) {
            select += '<option value="' + opt + '"' + (opt === data ? ' selected' : '') + '>' + opt + '</option>';
        });
        select += '</select>';
        return select;
    }
},
{
    data: "id",
    render: function (data, type, full) {
        var isArchived = (full.status === 'Archived');
        var btns = '<a class="btn btn-sm btn-secondary" href="<?php echo base_url('pengelolaan/Incoming_lettersController/detail/') ?>' + data + '" title="Detail & Disposisi"><i class="fa fa-eye"></i></a> ';

        // Edit & Hapus cuma muncul kalau BUKAN Archived
        if (!isArchived) {
            btns += '<button class="btn btn-sm btn-info" onclick="surat.selectdata(\'' + data + '\')" title="Edit"><i class="fa fa-edit"></i></button> ' +
                    '<button class="btn btn-sm btn-danger" onclick="surat.remove(\'' + data + '\')" title="Hapus"><i class="fa fa-trash"></i></button>';
        }
        return btns;
    }
}
  ] // <-- tutup array columns
    }); // <-- tutup DataTable(...)
    // Event delegation: dropdown status dibuat dinamis oleh DataTables,
    // jadi bind-nya lewat $('#tableSuratMasuk').on(...), bukan langsung ke elemen.
    $('#tableSuratMasuk').on('change', '.letter-status-select', function () {
        var id = $(this).data('id');
        var newStatus = $(this).val();
        surat.changeStatus(id, newStatus);
    });
});
</script>