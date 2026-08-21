<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Panel</title>

    <!-- Google Font -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="<?= base_url('assets/AdminLTE3/plugins/fontawesome-free/css/all.min.css'); ?>">

    <!-- Bootstrap -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <!-- AdminLTE -->
    <link rel="stylesheet" href="<?= base_url('assets/AdminLTE3/dist/css/adminlte.min.css'); ?>">

    <!-- OPTIONAL (kalau ada dipakai) -->
    <link rel="stylesheet" href="<?= base_url('assets/AdminLTE3/plugins/icheck-bootstrap/icheck-bootstrap.min.css'); ?>">

    <!-- jQuery -->
    <script src="<?= base_url('assets/AdminLTE3/plugins/jquery/jquery.min.js'); ?>"></script>

    <!-- Bootstrap JS -->
    <script src="<?= base_url('assets/AdminLTE3/plugins/bootstrap/js/bootstrap.bundle.min.js'); ?>"></script>

    <!-- AdminLTE JS -->
    <script src="<?= base_url('assets/AdminLTE3/dist/js/adminlte.min.js'); ?>"></script>

    <!-- Knockout -->
    <script src="<?= base_url('assets/knockout/knockout-3.1.0.js'); ?>"></script>
    <script src="<?= base_url('assets/knockout/knockout.mapping-latest.js'); ?>"></script>

    <!-- SweetAlert -->
    <link href="<?= base_url('assets/AdminLTE3/alert/sweetalert.css'); ?>" rel="stylesheet">
    <script src="<?= base_url('assets/AdminLTE3/alert/sweetalert.min.js'); ?>"></script>

    <script>
        var model = {
            Processing: ko.observable(true),
        }
    </script>

</head>

<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

    <div class="preloader flex-column justify-content-center align-items-center"
        data-bind='visible: model.Processing()==true'>
        <img class="animation__shake"
            src="<?= base_url('assets/AdminLTE3/dist/img/AdminLTELogo.png'); ?>"
            height="60" width="60">
    </div>