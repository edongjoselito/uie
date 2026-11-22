<div class="learner-workspace learner-directory directory-refined">
    <header class="learner-page-heading">
        <div>
            <p class="learner-eyebrow">DIVISION / LEARNERS</p>
            <h1>Learner Directory</h1>
            <p>Current learner enrollments across schools in <?php echo e($division->name); ?>.</p>
        </div>
        <span class="badge text-bg-light border"><i class="bi bi-eye me-1" aria-hidden="true"></i>Read only</span>
    </header>

    <section class="directory-filter-card" aria-labelledby="division-learner-filter-title">
        <div class="directory-filter-heading"><h2 id="division-learner-filter-title"><i class="bi bi-sliders2" aria-hidden="true"></i> Find Learners</h2></div>
        <form method="get" action="<?php echo site_url('learners'); ?>" class="directory-filters">
            <div class="directory-search">
                <label for="division-learner-search" class="form-label">Name, LRN, or school</label>
                <div class="directory-search-input"><i class="bi bi-search" aria-hidden="true"></i><input id="division-learner-search" name="q" value="<?php echo e($q); ?>" class="form-control" maxlength="120" type="search" autocomplete="off"></div>
            </div>
            <button class="btn directory-filter-submit" type="submit">Search</button>
            <?php if ($q !== ''): ?><a class="btn btn-outline-secondary" href="<?php echo site_url('learners'); ?>">Clear</a><?php endif; ?>
        </form>
    </section>

    <section class="directory-table-card mt-3" aria-labelledby="division-learner-list-title">
        <div class="directory-table-toolbar">
            <div><h2 id="division-learner-list-title">Division Learners <span><?php echo number_format($total); ?></span></h2><p><?php echo $q !== '' ? 'Matching current enrollments' : 'Current enrollments across the division'; ?></p></div>
        </div>
        <div class="table-responsive">
            <table class="table table-uie table-hover mb-0">
                <thead><tr><th>LRN</th><th>Learner Name</th><th>Sex</th><th>Grade</th><th>School</th><th>School Year</th></tr></thead>
                <tbody>
                <?php if ($rows): foreach ($rows as $learner): ?>
                    <tr>
                        <td><span class="badge text-bg-light border"><?php echo e($learner->lrn ?: '—'); ?></span></td>
                        <td class="fw-semibold"><?php echo e($learner->last_name.', '.$learner->first_name.($learner->middle_name ? ' '.substr($learner->middle_name, 0, 1).'.' : '').($learner->extension_name ? ' '.$learner->extension_name : '')); ?></td>
                        <td><?php echo e($learner->sex ?: '—'); ?></td>
                        <td><?php echo e($learner->grade_level ?: '—'); ?></td>
                        <td><?php echo e($learner->school_name ?: '—'); ?></td>
                        <td><?php echo e($learner->year_label ?: '—'); ?></td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="6"><div class="empty-state"><i class="bi bi-inbox"></i><?php echo $q !== '' ? 'No learners match your search.' : 'No current learner enrollments found for this division.'; ?></div></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white d-flex justify-content-between align-items-center">
            <small class="text-muted"><?php echo number_format($total); ?> learner(s)</small>
            <?php echo $pagination; ?>
        </div>
    </section>
</div>
