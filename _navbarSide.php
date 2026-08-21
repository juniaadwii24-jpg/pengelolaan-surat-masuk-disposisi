<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <a href="index3.html" class="brand-link">
        <img src="<?= base_url(); ?>assets\img\faces\admin.png" alt="AdminLTE Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
        <span class="brand-text font-weight-light">Daftar Surat</span>
    </a>

    <div class="sidebar">
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">

                <!-- SKILLS -->
                <li class="nav-item has-treeview <?= ($this->uri->segment(2) == 'Incoming_LettersController' || $this->uri->segment(2) == 'RecipientsController' || $this->uri->segment(2) == 'DispositionsController') ? 'menu-open' : ''; ?>">
                    <a href="#" class="nav-link <?= ($this->uri->segment(2) == 'Incoming_LettersController' || $this->uri->segment(2) == 'RecipientsController' || $this->uri->segment(2) == 'DispositionsController') ? 'active' : ''; ?>">
                        <i class="nav-icon far fa-circle"></i>
                        <p>
                            Master Surat
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>

                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="<?= base_url('pengelolaan/DashboardController'); ?>" class="nav-link <?= ($this->uri->segment(2) == 'DashboardController') ? 'active' : ''; ?>">
                                <i class="nav-icon far fa-circle"></i>
                                <p>Dashboard</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= base_url('pengelolaan/Incoming_LettersController'); ?>" class="nav-link <?= ($this->uri->segment(2) == 'Incoming_LettersController') ? 'active' : ''; ?>">
                                <i class="nav-icon far fa-circle"></i>
                                <p>Incoming Letters</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= base_url('pengelolaan/RecipientsController'); ?>" class="nav-link <?= ($this->uri->segment(2) == 'RecipientsController') ? 'active' : ''; ?>">
                                <i class="nav-icon far fa-circle"></i>
                                <p>Recipients</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= base_url('pengelolaan/DispositionsController'); ?>" class="nav-link <?= ($this->uri->segment(2) == 'DispositionsController') ? 'active' : ''; ?>">
                                <i class="nav-icon far fa-circle"></i>
                                <p>Dispositions</p>
                            </a>
                        </li>
                    </ul>
                </li>

            </ul>
        </nav>
    </div>
    <!-- /.sidebar -->
</aside>