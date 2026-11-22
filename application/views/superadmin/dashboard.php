<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-0">System Overview</h4>
        <small class="text-muted">Consolidated statistics<?php if ($sy): ?> &middot; Active SY <?php echo e($sy->year_label); ?><?php endif; ?></small>
    </div>
</div>

<!-- Summary cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-4 col-xl-3"><div class="card stat-card"><div class="card-body d-flex align-items-center gap-3">
        <div class="stat-icon bg-grad-navy"><i class="bi bi-globe-asia-australia"></i></div>
        <div><div class="stat-value"><?php echo number_format($counts['regions']); ?></div><div class="stat-label">Regions</div></div>
    </div></div></div>
    <div class="col-6 col-md-4 col-xl-3"><div class="card stat-card"><div class="card-body d-flex align-items-center gap-3">
        <div class="stat-icon bg-grad-teal"><i class="bi bi-diagram-3"></i></div>
        <div><div class="stat-value"><?php echo number_format($counts['divisions']); ?></div><div class="stat-label">Divisions</div></div>
    </div></div></div>
    <div class="col-6 col-md-4 col-xl-3"><div class="card stat-card"><div class="card-body d-flex align-items-center gap-3">
        <div class="stat-icon bg-grad-slate"><i class="bi bi-building"></i></div>
        <div><div class="stat-value"><?php echo number_format($counts['schools']); ?></div><div class="stat-label">Schools</div></div>
    </div></div></div>
    <div class="col-6 col-md-4 col-xl-3"><div class="card stat-card"><div class="card-body d-flex align-items-center gap-3">
        <div class="stat-icon bg-grad-purple"><i class="bi bi-people"></i></div>
        <div><div class="stat-value"><?php echo number_format($counts['users']); ?></div><div class="stat-label">Users</div></div>
    </div></div></div>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-4 col-xl-3"><div class="card stat-card"><div class="card-body d-flex align-items-center gap-3">
        <div class="stat-icon bg-grad-green"><i class="bi bi-tree"></i></div>
        <div><div class="stat-value"><?php echo number_format($counts['iped']); ?></div><div class="stat-label">IPEd Learners</div></div>
    </div></div></div>
    <div class="col-6 col-md-4 col-xl-3"><div class="card stat-card"><div class="card-body d-flex align-items-center gap-3">
        <div class="stat-icon bg-grad-amber"><i class="bi bi-moon-stars"></i></div>
        <div><div class="stat-value"><?php echo number_format($counts['madrasah']); ?></div><div class="stat-label">Madrasah Learners</div></div>
    </div></div></div>
    <div class="col-6 col-md-4 col-xl-3"><div class="card stat-card"><div class="card-body d-flex align-items-center gap-3">
        <div class="stat-icon bg-grad-purple"><i class="bi bi-universal-access"></i></div>
        <div><div class="stat-value"><?php echo number_format($counts['sned']); ?></div><div class="stat-label">SNED Learners</div></div>
    </div></div></div>
    <div class="col-6 col-md-4 col-xl-3"><div class="card stat-card"><div class="card-body d-flex align-items-center gap-3">
        <div class="stat-icon bg-grad-primary"><i class="bi bi-person-vcard"></i></div>
        <div><div class="stat-value"><?php echo number_format($counts['unique_learners']); ?></div><div class="stat-label">Unique Learners (SY)</div></div>
    </div></div></div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4"><div class="card stat-card"><div class="card-body d-flex align-items-center gap-3">
        <div class="stat-icon bg-grad-slate"><i class="bi bi-send"></i></div>
        <div><div class="stat-value"><?php echo number_format($counts['submitted']); ?></div><div class="stat-label">Pending Submissions</div></div>
    </div></div></div>
    <div class="col-md-4"><div class="card stat-card"><div class="card-body d-flex align-items-center gap-3">
        <div class="stat-icon bg-grad-green"><i class="bi bi-check2-circle"></i></div>
        <div><div class="stat-value"><?php echo number_format($counts['validated']); ?></div><div class="stat-label">Validated Submissions</div></div>
    </div></div></div>
    <div class="col-md-4"><div class="card stat-card"><div class="card-body d-flex align-items-center gap-3">
        <div class="stat-icon bg-grad-navy"><i class="bi bi-database"></i></div>
        <div><div class="stat-value"><?php echo number_format($counts['total_learners']); ?></div><div class="stat-label">Registry Total (All Years)</div></div>
    </div></div></div>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header bg-white fw-semibold"><i class="bi bi-bar-chart me-2"></i>Enrolled Learners by Region <small class="text-muted">(active SY)</small></div>
            <div class="card-body"><canvas id="chartRegions" height="120"></canvas></div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header bg-white fw-semibold"><i class="bi bi-graph-up me-2"></i>Enrollment Trend by School Year</div>
            <div class="card-body"><canvas id="chartTrend" height="140"></canvas></div>
        </div>
    </div>
</div>

<div class="row g-3 mt-1">
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header bg-white fw-semibold"><i class="bi bi-puzzle me-2"></i>Program Modules</div>
            <ul class="list-group list-group-flush">
                <?php foreach ($modules as $m): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <div><b><?php echo e($m->name); ?></b><br><small class="text-muted"><?php echo e($m->description); ?></small></div>
                    <?php echo status_badge($m->status); ?>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header bg-white fw-semibold"><i class="bi bi-clock-history me-2"></i>Recent Activity</div>
            <div class="table-responsive">
                <table class="table table-uie table-hover mb-0">
                    <thead><tr><th>When</th><th>User</th><th>Action</th><th>Details</th></tr></thead>
                    <tbody>
                    <?php if ($recent_audit): foreach ($recent_audit as $a): ?>
                        <tr>
                            <td class="text-nowrap text-muted"><?php echo e(date('M d, H:i', strtotime($a->created_at))); ?></td>
                            <td><?php echo e($a->username ?: 'system'); ?></td>
                            <td><span class="badge text-bg-light border"><?php echo e($a->action); ?></span></td>
                            <td class="text-muted small"><?php echo e($a->description); ?></td>
                        </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="4" class="text-center text-muted py-4">No activity recorded yet.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var regionData = <?php echo json_encode(array_map(function ($r) { return array('name' => $r->name, 'total' => (int) $r->total); }, $by_region)); ?>;
    var trendData  = <?php echo json_encode(array_map(function ($r) { return array('label' => $r->year_label, 'total' => (int) $r->total); }, $trend)); ?>;

    if (regionData.length) {
        new Chart(document.getElementById('chartRegions'), {
            type: 'bar',
            data: {
                labels: regionData.map(function (r) { return r.name; }),
                datasets: [{ label: 'Learners', data: regionData.map(function (r) { return r.total; }), backgroundColor: '#2e7fc4' }]
            },
            options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
        });
    }
    new Chart(document.getElementById('chartTrend'), {
        type: 'line',
        data: {
            labels: trendData.map(function (r) { return r.label; }),
            datasets: [{ label: 'Learners', data: trendData.map(function (r) { return r.total; }), borderColor: '#0d4f8b', backgroundColor: 'rgba(13,79,139,.12)', fill: true, tension: .3 }]
        },
        options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
    });
});
</script>
