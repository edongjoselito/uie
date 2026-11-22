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
            <div><div class="stat-value"><?php echo number_format($total_learners); ?></div><div class="stat-label">Learners in Scope</div></div>
        </div></div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card"><div class="card-body d-flex align-items-center gap-3">
            <div class="stat-icon bg-grad-teal"><i class="bi bi-building"></i></div>
            <div><div class="stat-value"><?php echo number_format($total_schools); ?></div><div class="stat-label">Schools</div></div>
        </div></div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card"><div class="card-body d-flex align-items-center gap-3">
            <div class="stat-icon bg-grad-green"><i class="bi bi-tree"></i></div>
            <div><div class="stat-value">0</div><div class="stat-label">IPEd Learners</div></div>
        </div></div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card"><div class="card-body d-flex align-items-center gap-3">
            <div class="stat-icon bg-grad-amber"><i class="bi bi-moon-stars"></i></div>
            <div><div class="stat-value">0</div><div class="stat-label">Madrasah Learners</div></div>
        </div></div>
    </div>
</div>

<div class="card">
    <div class="card-body text-center py-5">
        <i class="bi bi-diagram-3 fs-1 text-muted d-block mb-2"></i>
        <h6 class="fw-semibold">Foundation phase complete</h6>
        <p class="text-muted small mb-0" style="max-width:520px;margin:0 auto">
            Your <?php echo e($user['level']); ?>-level account is active and scoped correctly.
            Program modules (IPEd, Madrasah, SNED), learner profiling, submissions and
            reporting roll out in the next development phases.
        </p>
    </div>
</div>
