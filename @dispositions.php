<script>
    // ============================================================
    // BAGIAN 1: MODEL DASAR (default value)
    // ============================================================
    // Objek ini adalah "bentuk kosong" dari satu record disposisi.
    // Dipakai sebagai nilai awal saat form dibuka (mode tambah data)
    // dan juga dipakai untuk me-reset form (lihat disposisi.reset / disposisi.back).
    model.masterModel = {
        id: "0",                 // id record, "0" berarti data baru (belum tersimpan di DB)
        letter_id: "",           // FK ke tabel incoming_letters (surat masuk yang didisposisikan)
        recipient_id: "",        // FK ke tabel recipients (penerima disposisi)
        instruction: "",         // isi instruksi disposisi
        disposition_date: "",    // tanggal disposisi dibuat
        status: "Pending",       // status default saat data baru dibuat
        notes: ""                // catatan tambahan (opsional)
    }

    // ============================================================
    // BAGIAN 2: VIEW MODEL UTAMA (Knockout ViewModel bernama "disposisi")
    // ============================================================
    var disposisi = {
        title: "Master Disposisi",

        // Recordmaterial = objek observable yang di-bind ke form (tab "Buat Disposisi").
        // ko.mapping.fromJS mengubah object JS biasa (model.masterModel) menjadi
        // observable Knockout, supaya perubahan di input form otomatis ter-track.
        Recordmaterial: ko.mapping.fromJS(model.masterModel),

        // Listmaterial: observableArray cadangan (saat ini list utama sebenarnya
        // di-render lewat DataTables server-side, bukan lewat array ini).
        Listmaterial: ko.observableArray([]),

        // Mode menandai state form: '' (tambah baru) atau 'Update' (edit data lama).
        // Dipakai untuk enable/disable field tertentu & menampilkan tombol "Kembali".
        Mode: ko.observable(''),

        // FilterText & FilterValue: dipakai oleh fitur pencarian/filter tabel.
        FilterText: ko.observable(''),
        FilterValue: ko.observable('letter_number'), // kolom default yang difilter

        // Data untuk mengisi pilihan dropdown, di-load lewat AJAX (lihat loadSelectSurat & loadSelectPenerima).
        SELECTSURAT: ko.observableArray([]),     // daftar surat masuk untuk dropdown "Surat Masuk"
        SELECTPENERIMA: ko.observableArray([]),  // daftar penerima untuk dropdown "Penerima Disposisi"

        // Daftar pilihan status yang tersedia (statis, tidak perlu AJAX)
        SELECTSTATUS: [
            { name: 'Pending', value: 'Pending' },
            { name: 'In Progress', value: 'In Progress' },
            { name: 'Completed', value: 'Completed' },
        ],

        // Daftar kolom yang bisa dipilih user sebagai target pencarian/filter
        SELECTFILTERVALUE: [
            { name: 'Nomor Surat', value: 'letter_number' },
            { name: 'Nama Penerima', value: 'recipient_name' },
            { name: 'Bagian/Departemen', value: 'department' },
            { name: 'Instruksi', value: 'instruction' },
            { name: 'Status', value: 'status' }
        ]
    }

    // ============================================================
    // BAGIAN 3: FUNGSI-FUNGSI VIEW MODEL (aksi-aksi yang bisa dipanggil dari UI)
    // ============================================================

    // Dipanggil saat tombol "Cari" (search) di tab List ditekan.
    // Cukup memanggil ulang ajax DataTables; parameter filter (FilterText/FilterValue)
    // otomatis ikut terkirim karena diambil di fungsi "data" pada konfigurasi ajax DataTables di bawah.
    disposisi.filterData = function () {
        if (disposisi.grid) disposisi.grid.ajax.reload();
    }

    // Dipanggil saat tombol reset (ikon refresh) ditekan: mengosongkan kotak pencarian
    // lalu me-reload tabel supaya kembali menampilkan semua data.
    disposisi.resetFilter = function () {
        disposisi.FilterText('');
        if (disposisi.grid) disposisi.grid.ajax.reload();
    }

    // Dipanggil saat user selesai/batal dari form (misal setelah simpan sukses,
    // atau menekan tombol "Kembali"). Fungsinya:
    // 1. Reset Mode ke '' (kembali ke mode tambah data)
    // 2. Reload tabel list supaya data terbaru muncul
    // 3. Kosongkan kembali isi form ke nilai default (model.masterModel)
    // 4. Jika parameter tab=true, otomatis pindah tampilan ke tab "Daftar Disposisi"
    disposisi.back = function (tab) {
        disposisi.Mode('');
        if (disposisi.grid) disposisi.grid.ajax.reload();
        ko.mapping.fromJS(model.masterModel, disposisi.Recordmaterial);
        if (tab) $('a[href="#tablist"]').tab('show');
    }

    // Dipanggil saat user klik tombol "Edit" pada baris tabel.
    // Alurnya:
    // 1. Kirim id record ke server (getDataSelect) untuk ambil detail lengkapnya
    // 2. Isi ulang Recordmaterial dengan data hasil response (res[0])
    // 3. Ubah Mode jadi 'Update' (supaya form tahu ini mode edit, bukan tambah baru)
    // 4. Pindahkan tampilan ke tab form supaya user langsung bisa mengedit
    disposisi.selectdata = function (id) {
        $.ajax({
            url: "<?php echo base_url('pengelolaan/DispositionsController/getDataSelect') ?>",
            type: "POST",
            data: JSON.stringify({ id: id }),
            contentType: "application/json",
            dataType: "json",
            success: function (res) {
                if (res && res[0]) {
                    ko.mapping.fromJS(res[0], disposisi.Recordmaterial);
                    disposisi.Mode("Update");
                    $('a[href="#tabform"]').tab('show');
                }
            }
        });
    }

    // Mengosongkan form kembali ke nilai default & keluar dari mode Update.
    // Berbeda dengan "back": fungsi ini tidak reload tabel dan tidak pindah tab.
    disposisi.reset = function () {
        ko.mapping.fromJS(model.masterModel, disposisi.Recordmaterial);
        disposisi.Mode('');
    }

    // Dipanggil saat tombol "Simpan" ditekan. Menangani DUA kasus sekaligus:
    // tambah data baru (Mode kosong) dan update data lama (Mode == 'Update').
    disposisi.save = function () {
        var val = disposisi.Recordmaterial;

        // --- VALIDASI SISI CLIENT ---
        // Validasi sisi client (business rule wajib) sebelum request dikirim,
        // supaya feedback ke user lebih cepat. Validasi tetap diulang di server.

        // Surat masuk wajib dipilih HANYA saat tambah data baru
        // (saat update, letter_id tidak diubah / field-nya disabled di form).
        if (!val.letter_id() && disposisi.Mode() !== 'Update') {
            swal("Peringatan!", "Surat masuk wajib dipilih!", "warning");
            return;
        }
        if (!val.recipient_id()) {
            swal("Peringatan!", "Penerima disposisi wajib dipilih!", "warning");
            return;
        }
        if (!val.instruction()) {
            swal("Peringatan!", "Instruksi disposisi wajib diisi!", "warning");
            return;
        }
        if (!val.disposition_date()) {
            swal("Peringatan!", "Tanggal disposisi wajib diisi!", "warning");
            return;
        }

        // --- KONFIRMASI SEBELUM SIMPAN ---
        // Menampilkan dialog konfirmasi (SweetAlert) sebelum benar-benar mengirim data ke server.
        swal({
            title: "Perhatian",
            text: "Anda akan simpan data disposisi ini?",
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
                // Tentukan endpoint tujuan: save (data baru) atau update (data lama)
                var url = "<?php echo base_url('pengelolaan/DispositionsController/save') ?>";
                if (disposisi.Mode() === 'Update') {
                    url = "<?php echo base_url('pengelolaan/DispositionsController/update') ?>";
                }

                // Kirim seluruh isi Recordmaterial (diubah dulu jadi plain JS object
                // lewat ko.mapping.toJS) sebagai JSON ke server.
                $.ajax({
                    url: url,
                    type: "POST",
                    data: JSON.stringify(ko.mapping.toJS(val)),
                    contentType: "application/json",
                    dataType: "json",
                    success: function (res) {
                        if (res.result) {
                            // Simpan/Update berhasil: tampilkan notifikasi sukses,
                            // lalu panggil back(1) untuk reset form + reload tabel + pindah ke tab list.
                            swal({
                                title: "Good job!",
                                text: disposisi.Mode() === 'Update' ? "Data berhasil diubah!" : "Data berhasil disimpan!",
                                icon: "success"
                            });
                            disposisi.back(1);
                        } else {
                            // Gagal (misal validasi server-side tidak lolos): tampilkan pesan error dari server.
                            swal("Gagal!", res.message || "Terjadi kesalahan.", "error");
                        }
                    }
                });
            }
        });
    }

    // Dipanggil saat tombol hapus (ikon tong sampah) pada baris tabel ditekan.
    // Menampilkan konfirmasi dulu sebelum benar-benar menghapus data ke server.
    disposisi.remove = function (id) {
        swal({
            title: "Yakin?",
            text: "Data disposisi akan dihapus permanen!",
            type: "warning",
            showCancelButton: true,
            confirmButtonText: "Ya, hapus!",
            cancelButtonText: "Batal"
        }, function (isConfirm) {
            if (isConfirm) {
                $.ajax({
                    url: "<?php echo base_url('pengelolaan/DispositionsController/delete') ?>",
                    type: "POST",
                    data: JSON.stringify({ id: id }),
                    contentType: "application/json",
                    dataType: "json",
                    success: function (res) {
                        if (res.result) {
                            // Hapus sukses: reload tabel supaya baris yang terhapus hilang dari tampilan.
                            if (disposisi.grid) disposisi.grid.ajax.reload();
                            swal("Terhapus!", "Data berhasil dihapus.", "success");
                        } else {
                            swal("Gagal!", res.message, "error");
                        }
                    }
                });
            }
        });
    }

    // Update status LANGSUNG dari dropdown di tabel list, tanpa reload halaman
    // (menerapkan requirement MVVM poin 9: "update status tanpa reload jika memungkinkan")
    // Dipanggil dari event listener dropdown status di dalam tabel (lihat bagian bawah file, event 'change' pada '.status-select').
    disposisi.changeStatus = function (id, newStatus) {
        $.ajax({
            url: "<?php echo base_url('pengelolaan/DispositionsController/updateStatusOnly') ?>",
            type: "POST",
            data: JSON.stringify({ id: id, status: newStatus }),
            contentType: "application/json",
            dataType: "json",
            success: function (res) {
                if (res.result) {
                    // reload tanpa reset halaman: parameter kedua "false" membuat DataTables
                    // tetap berada di halaman/pagination yang sama setelah reload data.
                    if (disposisi.grid) disposisi.grid.ajax.reload(null, false);
                    swal("Berhasil!", "Status disposisi diperbarui.", "success");
                } else {
                    swal("Gagal!", "Status tidak berhasil diperbarui.", "error");
                }
            }
        });
    }

    // Mengambil daftar surat masuk dari server untuk mengisi dropdown "Surat Masuk" di form.
    // Dipanggil sekali saat halaman siap (lihat $(document).ready di bagian bawah file).
    disposisi.loadSelectSurat = function () {
        $.ajax({
            url: "<?php echo site_url('pengelolaan/DispositionsController/getSelectSurat') ?>",
            type: "GET",
            dataType: "json",
            success: function (res) {
                disposisi.SELECTSURAT(res);
            },
            error: function (err) {
                console.log("Gagal load surat", err);
            }
        });
    }

    // Mengambil daftar penerima dari server untuk mengisi dropdown "Penerima Disposisi" di form.
    // Dipanggil sekali saat halaman siap (lihat $(document).ready di bagian bawah file).
    disposisi.loadSelectPenerima = function () {
        $.ajax({
            url: "<?php echo site_url('pengelolaan/DispositionsController/getSelectPenerima') ?>",
            type: "GET",
            dataType: "json",
            success: function (res) {
                disposisi.SELECTPENERIMA(res);
            },
            error: function (err) {
                console.log("Gagal load penerima", err);
            }
        });
    }
</script>
<!-- ============================================================
     BAGIAN 4: HTML / TAMPILAN HALAMAN (Disesuaikan tanpa content-wrapper)
     ============================================================ -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-12">
                <h1><?= isset($title) ? $title : 'Master Disposisi' ?></h1>
            </div>
        </div>
    </div>
</section>

<section class="content" data-bind="with: disposisi">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card card-light">
                    <div class="card-header">
                        <ul class="nav nav-tabs card-header-tabs">
                            <li class="nav-item"><a class="nav-link active" href="#tabform" data-toggle="tab">Buat Disposisi</a></li>
                            <li class="nav-item"><a class="nav-link" href="#tablist" data-toggle="tab">Daftar Disposisi</a></li>
                        </ul>
                    </div>
                    <div class="card-body">
                        <div class="tab-content">

                            <!-- ==================== TAB FORM ==================== -->
                            <div class="tab-pane active" id="tabform">
                                <div class="row mb-3">
                                    <div class="col-12">
                                        <button class="btn btn-sm btn-warning mr-1" data-bind="click: function() { back(1); }, visible: Mode() == 'Update'">
                                            <i class="fa fa-arrow-left"></i> Kembali
                                        </button>
                                        <button class="btn btn-sm btn-info" data-bind="click: save">
                                            <i class="fa fa-save"></i> Simpan
                                        </button>
                                    </div>
                                </div>

                                <div class="card card-olive">
                                    <div class="card-header">
                                        <h3 class="card-title">Detail Disposisi</h3>
                                    </div>
                                    <div class="card-body" data-bind="with: Recordmaterial">
                                        <div class="row">
                                            <div class="col-12 col-md-6">
                                                <div class="form-group">
                                                    <label>Surat Masuk</label>
                                                    <select class="form-control" data-bind="
                                                        options: disposisi.SELECTSURAT,
                                                        optionsText: 'name',
                                                        optionsValue: 'value',
                                                        optionsCaption: '-- Pilih Surat --',
                                                        value: letter_id,
                                                        enable: disposisi.Mode() != 'Update'">
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="col-12 col-md-6">
                                                <div class="form-group">
                                                    <label>Penerima Disposisi</label>
                                                    <select class="form-control" data-bind="
                                                        options: disposisi.SELECTPENERIMA,
                                                        optionsText: 'name',
                                                        optionsValue: 'value',
                                                        optionsCaption: '-- Pilih Penerima --',
                                                        value: recipient_id">
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="col-12 col-md-6">
                                                <div class="form-group">
                                                    <label>Tanggal Disposisi</label>
                                                    <input type="date" class="form-control" data-bind="value: disposition_date">
                                                </div>
                                            </div>

                                            <div class="col-12 col-md-6">
                                                <div class="form-group">
                                                    <label>Status</label>
                                                    <select class="form-control" data-bind="
                                                        options: disposisi.SELECTSTATUS,
                                                        optionsText: 'name',
                                                        optionsValue: 'value',
                                                        value: status">
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="col-12">
                                                <div class="form-group">
                                                    <label>Instruksi Disposisi</label>
                                                    <textarea class="form-control" rows="3" data-bind="value: instruction" placeholder="Masukkan instruksi disposisi"></textarea>
                                                </div>
                                            </div>

                                            <div class="col-12">
                                                <div class="form-group mb-0">
                                                    <label>Catatan</label>
                                                    <textarea class="form-control" rows="2" data-bind="value: notes" placeholder="Catatan tambahan (opsional)"></textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- ==================== TAB LIST ==================== -->
                            <div class="tab-pane" id="tablist">
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
                                            <table id="tableDisposisi" width="100%" class="table table-bordered table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>No. Surat</th>
                                                        <th>Penerima</th>
                                                        <th>Bagian</th>
                                                        <th>Instruksi</th>
                                                        <th>Tgl Disposisi</th>
                                                        <th>Status</th>
                                                        <th>Catatan</th>
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
</div>

<!-- ============================================================
     BAGIAN 5: INISIALISASI DATATABLES (server-side) + EVENT HANDLER
     ============================================================ -->
<script>
$(document).ready(function () {
    // Saat halaman siap, langsung ambil data untuk isi kedua dropdown di form
    disposisi.loadSelectSurat();
    disposisi.loadSelectPenerima();
        // Tambahkan listener Bootstrap tab "shown"
    $('a[href="#tablist"]').on('shown.bs.tab', function () {
        if (disposisi.grid) {
            disposisi.grid.columns.adjust().draw();
        }
    });

    // Inisialisasi DataTables dengan mode server-side processing.
    // Artinya: pencarian, sorting, dan pagination diproses di server (PHP),
    // bukan di browser. Cocok untuk data yang jumlahnya besar.
    disposisi.grid = $("#tableDisposisi").DataTable({
        processing: true,   // tampilkan indikator loading saat ambil data
        serverSide: true,   // aktifkan mode server-side
        searching: false,   // matikan kotak pencarian bawaan DataTables (pakai filter custom sendiri)
        lengthChange: false,// sembunyikan dropdown "tampilkan X data per halaman"
        info: false,        // sembunyikan info "menampilkan X dari Y data"
        autoWidth: false,   // 👈 tambahkan ini untuk merapikan spasi di list agar DataTables gak coba nebak-nebak lebar kolom
        ajax: {
            url: "<?php echo base_url('pengelolaan/DispositionsController/getData') ?>",
            type: "POST",
            // Menambahkan parameter filter custom (filtervalue & filtertext) ke
            // request standar DataTables (start, length, dll) sebelum dikirim ke server.
            data: function (d) {
                d.filtervalue = disposisi.FilterValue();
                d.filtertext = disposisi.FilterText();
                return d;
            },
            // Menyesuaikan format response server (RecordsTotal/RecordsFiltered/Data)
            // menjadi format yang dikenali DataTables (recordsTotal/recordsFiltered, dan array data).
            dataSrc: function (json) {
                json.recordsTotal = json.RecordsTotal;
                json.recordsFiltered = json.RecordsFiltered;
                return json.Data ? json.Data : [];
            }
        },
        // Definisi kolom tabel & cara menampilkan tiap kolom
        columns: [
            { data: "letter_number" },   // No. Surat
            { data: "recipient_name" },  // Nama penerima
            { data: "department" },      // Bagian/departemen penerima
            { data: "instruction" },     // Instruksi disposisi
            { data: "disposition_date" },// Tanggal disposisi
            {
                data: "status",
                // Kolom status dirender sebagai dropdown <select>, bukan teks biasa,
                // supaya status bisa langsung diganti dari tabel (lihat event listener di bawah).
                render: function (data, type, full) {
                    // Dropdown status langsung di tabel: ganti status tanpa reload halaman
                    var options = ['Pending', 'In Progress', 'Completed'];
                    var select = '<select class="form-control form-control-sm status-select" data-id="' + full.id + '">';
                    options.forEach(function (opt) {
                        select += '<option value="' + opt + '"' + (opt === data ? ' selected' : '') + '>' + opt + '</option>';
                    });
                    select += '</select>';
                    return select;
                }
            },
            { data: "notes", defaultContent: '-' }, // Catatan; jika kosong tampilkan '-'
            {
                // Kolom aksi (tombol edit & hapus), memanggil fungsi global
                // disposisi.selectdata / disposisi.remove lewat atribut onclick inline.
                data: "id",
                render: function (data) {
                    return '<button class="btn btn-sm btn-info" onclick="disposisi.selectdata(\'' + data + '\')"><i class="fa fa-edit"></i></button> ' +
                           '<button class="btn btn-sm btn-danger" onclick="disposisi.remove(\'' + data + '\')"><i class="fa fa-trash"></i></button>';
                }
            }
        ]
    });

    // Event listener untuk dropdown status di dalam tabel (AJAX tanpa reload)
    // Menggunakan event delegation ($('#tableDisposisi').on(...)) karena baris tabel
    // (termasuk dropdown status) dibuat secara dinamis oleh DataTables setelah AJAX selesai,
    // jadi tidak bisa langsung di-bind saat dokumen pertama kali dimuat.
    $('#tableDisposisi').on('change', '.status-select', function () {
        var id = $(this).data('id');       // ambil id disposisi dari atribut data-id
        var newStatus = $(this).val();     // ambil status baru yang dipilih user
        disposisi.changeStatus(id, newStatus); // kirim perubahan ke server via AJAX
    });
});
</script>