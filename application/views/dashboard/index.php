<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-0"><?php echo e(ucfirst($user['level'])); ?> Dashboard</h4>
        <small class="text-muted">
            <?php if (isset($school_label)): ?><?php echo e($school_label); ?> &middot; <?php endif; ?>
            <?php if (isset($division_label)): ?><?php echo e($division_label); ?> &middot; <?php endif; ?>
            <?php if (isset($region_label)): ?><?php echo e($region_label); ?><?php endif; ?>
            <?php if ($sy): ?> &middot; SY <?php echo e($sy->year_label); ?><?php endif; ?>
        </small>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card"><div class="card-body d-flex align-items-center gap-3">
            <div class="stat-icon bg-grad-primary"><i class="bi bi-person-vcard"></i></div>
            <div><div class="stat-value"><?php echo number_format($total_learners); ?></div><div class="stat-label"><?php echo $user['level'] === 'school' ? 'Learners in Active SY' : 'Learners in Scope'; ?></div></div>
        </div></div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card"><div class="card-body d-flex align-items-center gap-3">
            <div class="stat-icon bg-grad-green"><i class="bi bi-tree"></i></div>
            <div><div class="stat-value"><?php echo number_format($program_counts['iped']); ?></div><div class="stat-label">IPEd Learners</div></div>
        </div></div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card"><div class="card-body d-flex align-items-center gap-3">
            <div class="stat-icon bg-grad-amber"><i class="bi bi-moon-stars"></i></div>
            <div><div class="stat-value"><?php echo number_format($program_counts['madrasah']); ?></div><div class="stat-label">Madrasah Learners</div></div>
        </div></div>
    </div>
    <?php foreach (array('sned' => 'SNED', 'spfl' => 'FL / SPFL') as $code => $label): ?>
    <div class="col-sm-6 col-xl-3"><div class="card stat-card"><div class="card-body d-flex align-items-center gap-3">
        <div class="stat-icon bg-grad-primary"><i class="bi <?php echo e(programs($code)['icon']); ?>"></i></div>
        <div><div class="stat-value"><?php echo number_format($program_counts[$code]); ?></div><div class="stat-label"><?php echo e($label); ?> Learners</div></div>
    </div></div></div>
    <?php endforeach; ?>
</div>
<p class="small text-muted">Program counts reflect the active school year. Learners with multiple tags appear in each applicable program.</p>

<div class="card mb-4">
    <div class="card-header bg-white fw-semibold"><i class="bi bi-bar-chart me-2"></i>Learners by Program <small class="text-muted">(active school year)</small></div>
    <div class="card-body">
        <div style="height: 280px">
            <canvas id="chartLearnersByProgram" role="img" aria-label="Active school year learner counts by education program"></canvas>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var labels = <?php echo json_encode(array_values(array_map(function ($program) { return $program['short']; }, programs())), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    var counts = <?php echo json_encode(array_values($program_counts)); ?>;
    var colors = <?php echo json_encode(array_values(array_map(function ($program) { return $program['color']; }, programs())), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;

    new Chart(document.getElementById('chartLearnersByProgram'), {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{ label: 'Learners', data: counts, backgroundColor: colors }]
        },
        options: {
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
        }
    });
});
</script>

<?php if ($user['level'] === 'school' && ! empty($user['school_id']) && $this->access_control->can('learners.view')): ?>
<div class="card"><div class="card-body p-4">
    <h5 class="fw-semibold">Learner encoding</h5>
    <p class="text-muted">Record learners by school year and grade level. Tag each enrollment with SNED, Madrasah, IPEd, FL / SPFL, or multiple programs as applicable.</p>
    <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-outline-primary" href="<?php echo site_url('learners'); ?>">View school learners</a>
        <?php if ($this->access_control->can('learners.create')): ?><a class="btn btn-primary" href="<?php echo site_url('learners/create'); ?>">Encode learner</a><?php endif; ?>
    </div>
</div></div>
<?php elseif ($user['level'] === 'division' && $this->access_control->can('learners.view')): ?>
<div class="card"><div class="card-body p-4">
    <h6 class="fw-semibold">Division learner directory</h6>
    <p class="text-muted">Browse current learner enrollments across schools in your division. This account has view-only access.</p>
    <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-outline-primary" href="<?php echo site_url('learners'); ?>"><i class="bi bi-eye me-1" aria-hidden="true"></i>View division learners</a>
        <?php if ($this->access_control->can('reports.view')): ?><a class="btn btn-primary" href="<?php echo site_url('reports'); ?>"><i class="bi bi-file-earmark-bar-graph me-1" aria-hidden="true"></i>View reports</a><?php endif; ?>
    </div>
</div></div>
<?php elseif ($user['level'] === 'region' && $this->access_control->can('reports.view')): ?>
<div class="card"><div class="card-body p-4">
    <h6 class="fw-semibold">Regional learner reports</h6>
    <p class="text-muted">Review learner program summaries across your region and filter results by district.</p>
    <a class="btn btn-primary" href="<?php echo site_url('reports'); ?>"><i class="bi bi-file-earmark-bar-graph me-1" aria-hidden="true"></i>View regional reports</a>
</div></div>
<?php else: ?>
<div class="card"><div class="card-body p-4"><h6 class="fw-semibold">Learner registry</h6><p class="text-muted mb-0">School accounts encode learners by school year, grade level, and education program.</p></div></div>
<?php endif; ?>
