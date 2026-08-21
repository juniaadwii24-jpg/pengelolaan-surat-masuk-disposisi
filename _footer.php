<footer class="main-footer">
    <center>
        <strong>&copy; Copyright Kazuya Media Indonesia 2020 | All right reserved</strong>
    </center>
</footer>
<aside class="control-sidebar control-sidebar-dark">

</aside>

</div>

<!--
    [FIX - CONSOLE ERROR: tempusdominus butuh moment.js, $(...).sortable is not a function]
    Sebelumnya di sini ada Chart.min.js, sparkline.js, jquery.vmap*.js,
    jquery.knob.min.js, daterangepicker.js, tempusdominus-bootstrap-4.min.js,
    summernote-bs4.min.js, dan dashboard.js DI-LOAD GLOBAL DI SEMUA HALAMAN.

    Padahal semua itu cuma dipakai di halaman Dashboard (chart, date-range
    picker, sortable widget, dll) — bukan di halaman CRUD (Incoming Letters,
    Recipients, Dispositions). Akibatnya di SETIAP halaman selain dashboard,
    console selalu error karena:
      - tempusdominus butuh versi moment.js tertentu yang gagal/gak sesuai
      - dashboard.js manggil $(...).sortable() padahal plugin jQuery UI
        Sortable-nya gak pernah di-load

    Errors ini bikin console berisik & berpotensi ganggu urutan eksekusi
    script lain. SUDAH DIPINDAH keluar dari sini — load script-script itu
    HANYA di view Dashboard-nya sendiri (bukan di _footer.php global),
    misal lewat blok <script> tambahan di bagian bawah view dashboard.php.

    Yang tetap di-load di sini cuma yang memang dipakai lintas halaman:
    jQuery DataTables (untuk tabel) dan util umum (moment untuk formatting
    tanggal via ajaxPost, kalau memang dipakai di banyak halaman).
-->

<!-- This is data table -->
<script src="<?= base_url(); ?>assets/js/jquery.dataTables.min.js"></script>

<!-- start - This is for export functionality only -->
<script src="<?= base_url(); ?>assets/js/dataTables.buttons.min.js"></script>

<!-- moment - dipakai ajaxPost() di bawah, aman di-load global karena gak
     ada dependency version-check kayak tempusdominus -->
<script src="<?= base_url(); ?>assets/js/moment.min.js"></script>

<script>
    model.activetab = function(index) {
        $("#tabnavform li>.nav-link").removeClass("active");
        $("#tabnavform li>.nav-link").attr({
            "aria-expanded": false
        });
        $("#tabnavform li>.nav-link").eq(index).addClass("active");
        $("#tabnavform li>.nav-link").eq(index).attr({
            "aria-expanded": true
        });
        $("#tabnavform-content div.tab-pane").removeClass("active");
        $("#tabnavform-content div.tab-pane").attr({
            "aria-expanded": false
        });
        $("#tabnavform-content div.tab-pane").eq(index).addClass("active");
        $("#tabnavform-content div.tab-pane").eq(index).attr({
            "aria-expanded": true
        });
    }

    function ajaxPost(url, data, callbackSuccess, callbackError, otherConfig) {
        var startReq = moment();
        var callbackScheduler = function(callback) {
            callback();
        };
        if (typeof callbackSuccess == "object") {
            otherConfig = callbackSuccess;
            callbackSuccess = function() {};
            callbackError = function() {};
        }
        if (typeof callbackError == "object") {
            otherConfig = callbackError;
            callbackError = function() {};
        }
        var config = {
            url: url,
            type: 'post',
            dataType: 'json',
            contentType: 'application/json; charset=utf-8',
            data: ko.mapping.toJSON(data),
            success: function(a) {
                callbackScheduler(function() {
                    if (callbackSuccess !== undefined) {
                        callbackSuccess(a);
                    }
                });
            },
            error: function(a, b, c) {
                callbackScheduler(function() {
                    if (callbackError !== undefined) {
                        callbackError(a, b, c);
                    }
                });
            }
        };
        if (data instanceof FormData) {
            delete config.config;
            config.data = data;
            config.async = false;
            config.cache = false;
            config.contentType = false;
            config.processData = false;
        }
        if (otherConfig != undefined) {
            config = $.extend(true, config, otherConfig);
        }
        return $.ajax(config);
    };


    ko.applyBindings(model);
    $(document).ready(function() {
        model.Processing(false);
    });
</script>

</body>

</html>