<nav class="main-header navbar navbar-expand navbar-white navbar-light">

    <ul class="navbar-nav">
    <li class="nav-item">
            <a class="nav-link" data-widget="pushmenu" data-controlsidebar-slide="true" href="#" role="button"><i class="fas fa-bars"></i></a>
        </li>
        <li class="nav-item d-none d-sm-inline-block">
            <a href="<?= base_url('home'); ?>" class="nav-link">Home</a>
        </li>
        <!-- <li class="nav-item d-none d-sm-inline-block">
            <a href="#" class="nav-link">Contact</a>
        </li> -->
    </ul>
    <!-- navbar main -->
         <!-- Right navbar links -->
    <ul class="navbar-nav ml-auto">
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="http://example.com" id="navbarDropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <i class="fas fa-power-off"></i>
            </a>
            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdownMenuLink">
                <div class="divider"></div>
                <a href="<?= base_url(); ?>LoginTemplate1/logout" class="dropdown-item text-danger">
                    <i class="fas fa-power-off"></i> Log out
                </a>
            </div>
            <!-- +menu baru+ -->
        </li>
    </ul>
</nav>