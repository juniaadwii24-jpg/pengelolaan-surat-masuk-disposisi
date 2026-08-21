<script>
    model.masterModel = {
        id: "0",
        name: "",
        position: "",
        department: "",
        email: ""
    }

    var recipient = {
        title: "Master Penerima Disposisi",
        Recordrecipient: ko.mapping.fromJS(model.masterModel),
        Listrecipient: ko.observableArray([]),
        Mode: ko.observable(''),
        DataFilter: ko.observable('name'),
        FilterText: ko.observable(''),
        FilterValue: ko.observable('name'),

        SELECTFILTERVALUE: [
            { name: 'Nama', value: 'name' },
            { name: 'Jabatan', value: 'position' },
            { name: 'Departemen', value: 'department' },
            { name: 'Email', value: 'email' },
        ],
    }

    recipient.filterData = function() {
        if (recipient.grid) recipient.grid.ajax.reload();
    }

    recipient.resetFilter = function() {
        recipient.FilterText('');
        if (recipient.grid) recipient.grid.ajax.reload();
    }

    recipient.back = function(tab) {
        recipient.Mode('');
        if (recipient.grid) recipient.grid.ajax.reload();
        ko.mapping.fromJS(model.masterModel, recipient.Recordrecipient);
        if (tab) $('a[href="#tablist"]').tab('show');
    }

    recipient.selectdata = function(id) {
        $.ajax({
            url: "<?php echo base_url('pengelolaan/RecipientsController/getDataSelect') ?>",
            type: "POST",
            data: JSON.stringify({ id: id }),
            contentType: "application/json",
            dataType: "json",
            success: function(res) {
                if (res && res.id) {
                    ko.mapping.fromJS(res, recipient.Recordrecipient);
                    recipient.Mode("Update");
                    $('a[href="#tabform"]').tab('show');
                }
            }
        });
    }

    recipient.reset = function() {
        ko.mapping.fromJS(model.masterModel, recipient.Recordrecipient);
        recipient.Mode('');
    }

    recipient.save = function() {
        model.Processing(true);
        var val = recipient.Recordrecipient;
        swal({
            title: "Perhatian",
            text: "Anda akan simpan data ini?",
            type: "info",
            className: 'animate_animated animate_fadeInUp',
            showCancelButton: true,
            confirmButtonColor: "#DD6B55",
            confirmButtonText: "Yes!",
            cancelButtonText: "No!",
            closeOnConfirm: false,
            showLoaderOnConfirm: true,
        }, function(isConfirm) {
            if (isConfirm) {
                if (recipient.Recordrecipient.name() == "") {
                    setTimeout(function() {
                        swal("Peringatan!", "Data Harap diisi Dengan Benar!", "warning");
                    });
                } else {
                    var url = "<?php echo base_url('pengelolaan/RecipientsController/save') ?>";

                    if (recipient.Mode() === 'Update')
                        url = "<?php echo base_url('pengelolaan/RecipientsController/update') ?>";

                    ajaxPost(url, recipient.Recordrecipient,
                        function(res) {
                            console.log(res.result);
                            if (res.result == true || recipient.Mode() == "Update") {
                                if (res.result == true) {
                                    setTimeout(function() {
                                        swal({
                                            title: "Good job!",
                                            text: "Data Berhasil di input!",
                                            icon: "success",
                                        });
                                    }, 2000);
                                }
                                if (recipient.Mode() == "Update") {
                                    setTimeout(function() {
                                        swal({
                                            title: "Good job!",
                                            text: "Data Berhasil di ubah!",
                                            icon: "success",
                                        });
                                    }, 2000);
                                }
                                recipient.back(1);
                            } else {
                                setTimeout(function() {
                                    swal("Gagal!", res.message || "Data gagal disimpan.", "error");
                                });
                            }
                        });
                }
            }
            model.Processing(false);
        }); // END isconfirm swal
        model.Processing(false);
    }

    recipient.remove = function(id) {
        swal({
            title: "Yakin?",
            text: "Data akan dihapus permanen!",
            type: "warning",
            showCancelButton: true,
            confirmButtonText: "Ya, hapus!",
            cancelButtonText: "Batal"
        }, function(isConfirm) {
            if (isConfirm) {
                $.ajax({
                    url: "<?php echo base_url('pengelolaan/RecipientsController/delete') ?>",
                    type: "POST",
                    data: JSON.stringify({ id: id }),
                    contentType: "application/json",
                    dataType: "json",
                    success: function(res) {
                        if (res.result) {
                            if (recipient.grid) recipient.grid.ajax.reload();
                            swal("Terhapus!", res.message || "Data berhasil dihapus.", "success");
                        } else {
                            swal("Gagal!", res.message, "error");
                        }
                    }
                });
            }
        });
    }
</script>


<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-12">
                <h1><?= isset($title) ? $title : 'Master Penerima Disposisi' ?></h1>
            </div>
        </div>
    </div>
</section>
<section class="content" data-bind="with: recipient">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card card-light">
                    <div class="card-header">
                        <ul class="nav nav-tabs card-header-tabs">
                            <li class="nav-item"><a class="nav-link active" href="#tabform" data-toggle="tab">Tambah Penerima</a></li>
                            <li class="nav-item"><a class="nav-link" href="#tablist" data-toggle="tab">Daftar Penerima</a></li>
                        </ul>
                    </div>
                    <div class="card-body">
                        <div class="tab-content">

                            <!-- TAB FORM -->
                            <div class="tab-pane active" id="tabform">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <button class="btn btn-sm btn-info" data-bind="click:save" data-toggle="tooltip" data-placement="top" data-original-title="simpan">
                                                <span data-bind="dataoriginal-title:Mode"></span><i class="fa fa-save">Simpan</i>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="card card-olive">
                                            <div class="card card-header">
                                                <h3 class="card-title">Detail Penerima</h3>
                                            </div>
                                            <div class="card-body" data-bind="with: Recordrecipient">
                                                <div class="row">
                                                    <div class="col-md-12">
                                                        <div class="form-group">
                                                            <label for="idinstansi">Nama Lengkap</label>
                                                            <input type="text" name="name" class="form-control" data-bind="value: name" id="idinstansi" placeholder="Masukkan nama lengkap">
                                                        </div>
                                                        <div class="form-group">
                                                            <label for="position">Jabatan</label>
                                                            <textarea id="position" name="position" class="form-control" data-bind="value: position" placeholder="Masukkan jabatan"></textarea>
                                                        </div>
                                                        <div class="form-group">
                                                            <label for="department">Departemen</label>
                                                            <input type="text" name="department" id="department" class="form-control" data-bind="value: department" placeholder="Masukkan departemen">
                                                        </div>
                                                        <div class="form-group">
                                                            <label for="email">Email</label>
                                                            <input type="text" name="email" id="email" class="form-control" data-bind="value: email" placeholder="Masukkan email">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- END TAB FORM -->

                            <!-- TAB LIST -->
                            <div class="tab-pane" id="tablist">
                                <div class="row">
                                    <div class="col-md-3">
                                        <select class="form-control" data-bind="value: FilterValue, options: SELECTFILTERVALUE, optionsText: 'name', optionsValue: 'value'"></select>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <input data-bind="value: FilterText, event: { keyup: function(data, event) { if (event.key === 'Enter') $data.filterData(); } }" placeholder="Filter by data" class="form-control">
                                            <p><small class="text-muted">Contoh: ketik <i>name</i> lalu <b>Enter</b></small></p>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <button class="btn btn-md btn-danger" data-bind="click: resetFilter"><span class="fa fa-retweet"></span></button>
                                            <button class="btn btn-md btn-primary" data-bind="click: filterData"><span class="fa fa-search"></span></button>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="table-responsive m-t-40 animated fadeIn">
                                            <table id="myTable" width="100%" class="table table-bordered table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>id</th>
                                                        <th>name</th>
                                                        <th>position</th>
                                                        <th>department</th>
                                                        <th>email</th>
                                                        <th>Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody></tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- END TAB LIST -->

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
$(document).ready(function() {
    model.Processing(true);

    recipient.grid = $("#myTable").DataTable({
        processing: true,
        serverSide: true,
        searching: false,
        lengthChange: false,
        info: false,
        ajax: {
            url: "<?php echo base_url('pengelolaan/RecipientsController/getData') ?>",
            type: "POST",
            data: function(d) {
                d.filtervalue = recipient.FilterValue();
                d.filtertext = recipient.FilterText();
                return d;
            },
            dataSrc: function(json) {
                json.recordsTotal = json.RecordsTotal;
                json.recordsFiltered = json.RecordsFiltered;
                return json.Data ? json.Data : [];
            }
        },
        "searching": false,
        "columns": [
            { "data": "id" },
            { "data": "name" },
            { "data": "position" },
            { "data": "department" },
            { "data": "email" },
            {
                "data": "id",
                "render": function(data, type, full, meta) {
                    return '<button class="btn btn-sm btn-info" onclick="recipient.selectdata(\'' + data + '\')"><i class="fa fa-edit"></i></button> ' +
                           '<button class="btn btn-sm btn-danger" onclick="recipient.remove(\'' + data + '\')"><i class="fa fa-trash"></i></button>';
                }
            }
        ]
    });

    model.Processing(false);
});
</script>