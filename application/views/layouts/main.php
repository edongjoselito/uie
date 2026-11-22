<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo e($page_title); ?></title>
<link rel="stylesheet" href="<?php echo base_url('assets/css/bootstrap.min.css'); ?>">
<link rel="stylesheet" href="<?php echo base_url('assets/css/bootstrap-icons.min.css'); ?>">
<link rel="stylesheet" href="<?php echo base_url('assets/css/app.css'); ?>">
</head>
<body>
<div class="uie-wrapper">
    <!-- ============ SIDEBAR ============ -->
    <aside class="uie-sidebar" id="uieSidebar">
        <div class="sidebar-brand">
            <div class="brand-seal"><img src="<?php echo base_url('assets/images/region11-seal.jpg'); ?>" alt="Region XI"></div>
            <div class="brand-text">
                <span class="brand-name">UIEIMS</span>
                <small><?php echo e(app_setting('region_office', 'DepEd Region XI')); ?></small>
            </div>
        </div>
        <nav class="sidebar-nav">
            <ul class="nav flex-column">
                <?php if ($user && $user['level'] === 'system'): ?>
                <li class="nav-section">System Administration</li>
                <li><a class="nav-link <?php echo nav_active('dashboard', 2) ?: (uri_string() === 'superadmin' ? 'active' : ''); ?>" href="<?php echo site_url('superadmin/dashboard'); ?>"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                <li><a class="nav-link <?php echo nav_active('regions', 2); ?>" href="<?php echo site_url('superadmin/regions'); ?>"><i class="bi bi-globe-asia-australia"></i> Regions</a></li>
                <li><a class="nav-link <?php echo nav_active('divisions', 2); ?>" href="<?php echo site_url('superadmin/divisions'); ?>"><i class="bi bi-diagram-3"></i> Divisions</a></li>
                <li><a class="nav-link <?php echo nav_active('schools', 2); ?>" href="<?php echo site_url('superadmin/schools'); ?>"><i class="bi bi-building"></i> Schools</a></li>
                <li><a class="nav-link <?php echo nav_active('users', 2); ?>" href="<?php echo site_url('superadmin/users'); ?>"><i class="bi bi-people"></i> Users</a></li>
                <li><a class="nav-link <?php echo nav_active('learners', 2); ?>" href="<?php echo site_url('superadmin/learners'); ?>"><i class="bi bi-person-vcard"></i> Learner Registry</a></li>
                <li><a class="nav-link <?php echo nav_active('school-years', 2); ?>" href="<?php echo site_url('superadmin/school-years'); ?>"><i class="bi bi-calendar3"></i> School Years</a></li>

                <li class="nav-section">Program Modules</li>
                <?php foreach (programs() as $p): ?>
                <li><a class="nav-link disabled text-white-50" href="#" title="<?php echo e($p['name']); ?> &middot; Coming soon"><i class="bi <?php echo $p['icon']; ?>" style="color:<?php echo $p['color']; ?>"></i> <?php echo e($p['short']); ?> <span class="badge text-bg-secondary ms-1">soon</span></a></li>
                <?php endforeach; ?>
                <?php else: ?>
                <li class="nav-section"><?php echo e(ucfirst($user['level'])); ?> Menu</li>
                <li><a class="nav-link <?php echo nav_active('dashboard'); ?>" href="<?php echo site_url('dashboard'); ?>"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                <li><a class="nav-link disabled text-white-50" href="#" title="Coming in Phase 3"><i class="bi bi-person-vcard"></i> Learners <span class="badge text-bg-secondary ms-1">soon</span></a></li>
                <li><a class="nav-link disabled text-white-50" href="#" title="Coming in Phase 7"><i class="bi bi-send"></i> Submissions <span class="badge text-bg-secondary ms-1">soon</span></a></li>
                <li><a class="nav-link disabled text-white-50" href="#" title="Coming in Phase 7"><i class="bi bi-file-earmark-bar-graph"></i> Reports <span class="badge text-bg-secondary ms-1">soon</span></a></li>
                <?php endif; ?>
            </ul>
        </nav>
        <div class="sidebar-footer">
            <div class="program-strip mb-2">
                <?php foreach (programs() as $p): ?>
                <span class="program-chip" style="--prog-color: <?php echo $p['color']; ?>" title="<?php echo e($p['name']); ?>">
                    <i class="bi <?php echo $p['icon']; ?>"></i> <?php echo e($p['short']); ?>
                </span>
                <?php endforeach; ?>
            </div>
            <small class="text-white-50">&ldquo;<?php echo e(app_setting('region_slogan', 'Leading the Way')); ?>&rdquo; &middot; UIEIMS v1.0</small>
        </div>
    </aside>

    <!-- ============ MAIN ============ -->
    <div class="uie-main">
        <header class="uie-topbar">
            <button class="btn btn-link sidebar-toggle d-lg-none" id="sidebarToggle" aria-label="Toggle navigation"><i class="bi bi-list fs-4"></i></button>
            <nav aria-label="breadcrumb" class="ms-1">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="<?php echo site_url(); ?>">UIEIMS</a></li>
                    <?php if ( ! empty($breadcrumbs)): foreach ($breadcrumbs as $label => $url): ?>
                        <?php if ($url): ?><li class="breadcrumb-item"><a href="<?php echo $url; ?>"><?php echo e($label); ?></a></li>
                        <?php else: ?><li class="breadcrumb-item active" aria-current="page"><?php echo e($label); ?></li><?php endif; ?>
                    <?php endforeach; endif; ?>
                </ol>
            </nav>
            <div class="ms-auto d-flex align-items-center gap-3">
                <?php if ($sy = active_sy()): ?>
                <span class="badge text-bg-light border d-none d-md-inline" title="Active School Year"><i class="bi bi-calendar-check me-1"></i>SY <?php echo e($sy->year_label); ?></span>
                <?php endif; ?>
                <div class="dropdown">
                    <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="avatar-circle"><?php echo e(strtoupper(substr($user['first_name'] ?? 'U', 0, 1))); ?></span>
                        <span class="d-none d-sm-block ms-2">
                            <span class="d-block fw-semibold small"><?php echo e($user['full_name'] ?? ''); ?></span>
                            <span class="d-block text-muted" style="font-size:.72rem"><?php echo e(implode(', ', $user['role_names'] ?? array())); ?></span>
                        </span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="<?php echo site_url('change-password'); ?>"><i class="bi bi-key me-2"></i>Change Password</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="<?php echo site_url('logout'); ?>"><i class="bi bi-box-arrow-right me-2"></i>Sign Out</a></li>
                    </ul>
                </div>
            </div>
        </header>

        <main class="uie-content">
            <?php echo flash_html(); ?>
            <?php echo $content; ?>
        </main>

        <footer class="uie-footer">
            <span>Unified Inclusive Education Information and Management System</span>
            <span class="text-muted">Learner Profiling, Records Management &amp; Program Monitoring</span>
        </footer>
    </div>
</div>

<script src="<?php echo base_url('assets/js/jquery-3.7.1.min.js'); ?>"></script>
<script src="<?php echo base_url('assets/js/bootstrap.bundle.min.js'); ?>"></script>
<script src="<?php echo base_url('assets/js/chart.umd.min.js'); ?>"></script>
<script src="<?php echo base_url('assets/js/app.js'); ?>"></script>
<script>
var UIEIMS = {
    baseUrl: '<?php echo base_url(); ?>',
    siteUrl: '<?php echo site_url(); ?>',
    csrf: <?php echo json_encode(csrf_json()); ?>
};
</script>
<?php if (isset($page_scripts)) echo $page_scripts; ?>
</body>
</html>
