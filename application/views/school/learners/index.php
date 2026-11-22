<?php
$can_create = $year && $year->status === 'open' && $this->access_control->can('learners.create');
$can_edit = $year && $year->status === 'open' && $this->access_control->can('learners.edit');
$has_filters = $q !== '' || $grade !== '' || $program !== '';
$list_query = array('school_year_id' => (int) $year_id, 'q' => $q, 'grade_level' => $grade, 'program' => $program, 'per_page' => $per_page, 'sort' => $sort, 'direction' => $direction);
$sort_link = function ($key) use ($list_query, $sort, $direction) {
    $params = $list_query;
    $params['sort'] = $key;
    $params['direction'] = $sort === $key && $direction === 'asc' ? 'desc' : 'asc';
    return site_url('learners').'?'.http_build_query($params);
};
$sort_icon = function ($key) use ($sort, $direction) { return $sort === $key ? ($direction === 'asc' ? 'bi-arrow-up' : 'bi-arrow-down') : 'bi-arrow-down-up'; };
?>
<div class="learner-workspace learner-workspace-fluid learner-directory directory-refined">
    <header class="learner-page-heading">
        <div><p class="learner-eyebrow">LEARNERS / PROFILE</p><h1>Learner Profiles</h1><p>Manage your school’s learners, enrollments, and education programs in one place.</p></div>
        <?php if ($can_create || ($year && $year->status === 'open' && $this->access_control->can('learners.import'))): ?><div class="directory-add-group"><?php if ($this->access_control->can('learners.import')): ?><a class="btn btn-outline-primary directory-add" href="<?php echo site_url('learners/import?school_year_id='.(int) $year_id); ?>"><i class="bi bi-file-earmark-spreadsheet" aria-hidden="true"></i> Import SF 1</a><?php endif; ?><?php if ($can_create): ?><a class="btn btn-primary directory-add" href="<?php echo site_url('learners/create?school_year_id='.(int) $year_id); ?>"><i class="bi bi-plus-lg" aria-hidden="true"></i> Encode Learner</a><?php endif; ?></div><?php endif; ?>
    </header>
    <div class="directory-context"><span><i class="bi bi-building" aria-hidden="true"></i> <?php echo e($school->name); ?></span><span><i class="bi bi-calendar3" aria-hidden="true"></i> <?php echo $year ? 'SY '.e($year->year_label) : 'No School Year'; ?></span><?php if ($year): ?><span class="directory-year-status <?php echo $year->status === 'open' ? 'is-open' : ''; ?>"><span aria-hidden="true"></span><?php echo $year->status === 'open' ? 'Open for Encoding' : 'Closed · View Only'; ?></span><?php endif; ?></div>
    <section class="directory-overview" aria-label="Selected School-Year Overview">
        <div class="directory-overview-card"><span class="directory-overview-icon"><i class="bi bi-people" aria-hidden="true"></i></span><div><p>Total Learners</p><strong><?php echo number_format($overview['total']); ?></strong><span>In the selected school year</span></div></div>
        <div class="directory-overview-card"><span class="directory-overview-icon is-purple"><i class="bi bi-intersect" aria-hidden="true"></i></span><div><p>Multiple Programs</p><strong><?php echo number_format($overview['multiple_programs']); ?></strong><span>Learners with two or more tags</span></div></div>
        <div class="directory-overview-card"><span class="directory-overview-icon is-amber"><i class="bi bi-person-vcard" aria-hidden="true"></i></span><div><p>Without an LRN</p><strong><?php echo number_format($overview['missing_lrn']); ?></strong><span>Reference number not yet recorded</span></div></div>
    </section>
    <section class="directory-filter-card" aria-labelledby="filters-title">
        <div class="directory-filter-heading"><h2 id="filters-title"><i class="bi bi-sliders2" aria-hidden="true"></i> Filter Learners</h2><?php if ($has_filters): ?><a href="<?php echo site_url('learners?school_year_id='.(int) $year_id); ?>">Clear Filters <i class="bi bi-x-lg" aria-hidden="true"></i></a><?php endif; ?></div>
        <form method="get" action="<?php echo site_url('learners'); ?>" class="directory-filters">
            <input type="hidden" name="sort" value="<?php echo e($sort); ?>"><input type="hidden" name="direction" value="<?php echo e($direction); ?>"><input type="hidden" name="per_page" value="<?php echo (int) $per_page; ?>">
            <div class="directory-search"><label for="filter-search" class="form-label">Name or LRN</label><div class="directory-search-input"><i class="bi bi-search" aria-hidden="true"></i><input id="filter-search" name="q" value="<?php echo e($q); ?>" class="form-control" maxlength="120" type="search" autocomplete="off"></div></div>
            <div><label for="filter-year" class="form-label">School Year</label><select id="filter-year" name="school_year_id" class="form-select"><?php foreach ($years as $sy): ?><option value="<?php echo (int) $sy->id; ?>" <?php echo (int) $year_id === (int) $sy->id ? 'selected' : ''; ?>><?php echo e($sy->year_label); ?><?php echo $sy->status === 'closed' ? ' (Closed)' : ''; ?></option><?php endforeach; ?></select></div>
            <div><label for="filter-grade" class="form-label">Grade Level</label><select id="filter-grade" name="grade_level" class="form-select"><option value="">All Grades</option><?php foreach ($grades as $g): ?><option <?php echo $grade === $g ? 'selected' : ''; ?>><?php echo e($g); ?></option><?php endforeach; ?></select></div>
            <div><label for="filter-program" class="form-label">Program</label><select id="filter-program" name="program" class="form-select"><option value="">All Programs</option><?php foreach (programs() as $p): ?><option value="<?php echo e($p['code']); ?>" <?php echo $program === $p['code'] ? 'selected' : ''; ?>><?php echo e($p['code'] === 'spfl' ? 'FL / SPFL' : $p['short']); ?></option><?php endforeach; ?></select></div>
            <button class="btn directory-filter-submit" type="submit">Apply Filters</button>
        </form>
    </section>
    <?php if ($has_filters): ?><div class="directory-active-filters" aria-label="Active Filters"><span>Filtered By</span><?php foreach (array('q' => array('Search', $q), 'grade_level' => array('Grade', $grade), 'program' => array('Program', $program === '' ? '' : ($program === 'spfl' ? 'FL / SPFL' : programs($program)['short']))) as $key => $filter): if ($filter[1] === '') continue; $remove_query = $list_query; unset($remove_query[$key]); ?><a href="<?php echo e(site_url('learners').'?'.http_build_query($remove_query)); ?>" aria-label="<?php echo e('Remove '.$filter[0].' Filter'); ?>"><?php echo e($filter[0].': '.$filter[1]); ?><i class="bi bi-x" aria-hidden="true"></i></a><?php endforeach; ?></div><?php endif; ?>
    <?php if ($year && $year->status === 'closed'): ?><div class="directory-notice"><i class="bi bi-lock" aria-hidden="true"></i> This school year is closed. Learner enrollments are available for viewing.</div><?php endif; ?>
    <section class="directory-table-card" aria-labelledby="directory-table-title">
        <div class="directory-table-toolbar"><div><h2 id="directory-table-title">School Learners <span><?php echo number_format($total); ?></span></h2><p><?php echo $has_filters ? 'Results matching your filters' : 'Learner enrollments for the selected school year'; ?></p></div>
            <form method="get" action="<?php echo site_url('learners'); ?>" class="directory-page-size"><?php foreach ($list_query as $key => $val): if ($key === 'per_page') continue; ?><input type="hidden" name="<?php echo e($key); ?>" value="<?php echo e($val); ?>"><?php endforeach; ?><label for="page-size">Rows</label><select name="per_page" id="page-size" class="form-select"><?php foreach (array(10,25,50,100) as $size): ?><option value="<?php echo $size; ?>" <?php echo $size === $per_page ? 'selected' : ''; ?>><?php echo $size; ?></option><?php endforeach; ?></select><button type="submit" class="directory-size-submit">Apply</button></form>
        </div>
        <div class="directory-program-bar">
            <nav class="directory-program-tabs" aria-label="Filter by Education Program">
                <?php foreach (array('' => array('short' => 'All Learners', 'icon' => 'bi-people', 'color' => '#0d4f8b')) + programs() as $code => $p): $tab_query = $list_query; $tab_query['program'] = $code; ?>
                <a href="<?php echo e(site_url('learners').'?'.http_build_query($tab_query)); ?>" class="directory-program-tab <?php echo $program === $code ? 'is-active' : ''; ?>" <?php echo $program === $code ? 'aria-current="true"' : ''; ?> style="--tag-color: <?php echo e($p['color']); ?>"><i class="bi <?php echo e($p['icon']); ?>" aria-hidden="true"></i><?php echo e($code === 'spfl' ? 'FL / SPFL' : $p['short']); ?><span><?php echo number_format($code === '' ? $overview['total'] : $overview['programs'][$code]); ?></span></a>
                <?php endforeach; ?>
            </nav>
            <button type="button" class="directory-density" aria-pressed="false" hidden><i class="bi bi-distribute-vertical" aria-hidden="true"></i> Compact Rows</button>
        </div>
        <p class="directory-program-count-note">Program totals cover the selected school year, before name and grade filters.</p>
        <div class="directory-table-scroll" role="region" aria-label="Learner Profiles Table" tabindex="0">
            <table class="table directory-table"><caption class="visually-hidden">Learner enrollments at <?php echo e($school->name); ?><?php echo $year ? ' for '.e($year->year_label) : ''; ?></caption><thead><tr>
                <th scope="col" class="directory-row-number">#</th>
                <th scope="col" <?php echo $sort === 'name' ? 'aria-sort="'.($direction === 'asc' ? 'ascending' : 'descending').'"' : ''; ?>><a href="<?php echo e($sort_link('name')); ?>">Learner Name <i class="bi <?php echo $sort_icon('name'); ?>" aria-hidden="true"></i></a></th>
                <th scope="col" <?php echo $sort === 'lrn' ? 'aria-sort="'.($direction === 'asc' ? 'ascending' : 'descending').'"' : ''; ?>><a href="<?php echo e($sort_link('lrn')); ?>">LRN <i class="bi <?php echo $sort_icon('lrn'); ?>" aria-hidden="true"></i></a></th>
                <th scope="col">Sex</th>
                <th scope="col" <?php echo $sort === 'grade' ? 'aria-sort="'.($direction === 'asc' ? 'ascending' : 'descending').'"' : ''; ?>><a href="<?php echo e($sort_link('grade')); ?>">Grade / Section <i class="bi <?php echo $sort_icon('grade'); ?>" aria-hidden="true"></i></a></th>
                <th scope="col">Program Tags</th><th scope="col" class="text-end">Actions</th>
            </tr></thead><tbody>
            <?php foreach ($rows as $index => $row):
                $row_tags = $row->program_codes ? explode(',', $row->program_codes) : array();
                $initials = function_exists('mb_substr') ? mb_substr($row->first_name, 0, 1, 'UTF-8').mb_substr($row->last_name, 0, 1, 'UTF-8') : substr($row->first_name, 0, 1).substr($row->last_name, 0, 1);
            ?><tr>
                <td class="directory-row-number"><?php echo $offset + $index + 1; ?></td>
                <td><div class="directory-person"><span class="directory-person-avatar" aria-hidden="true"><?php echo e($initials); ?></span><div><strong><?php echo e($row->last_name.', '.$row->first_name); ?></strong><?php if ($row->middle_name): ?><span><?php echo e($row->middle_name); ?></span><?php endif; ?></div></div></td>
                <td><span class="directory-lrn <?php echo $row->lrn ? '' : 'is-missing'; ?>"><?php echo e($row->lrn ?: 'Not Assigned'); ?></span></td>
                <td><span class="directory-sex"><?php echo e($row->sex); ?></span></td>
                <td><strong class="directory-grade"><?php echo e($row->grade_level ?: 'Not Set'); ?></strong><span class="directory-section"><?php echo e($row->section ?: 'No Section'); ?></span></td>
                <td>
                    <div class="directory-program-tags">
                        <?php if ( ! $row_tags): ?><span class="directory-no-tags">No Program Tags</span><?php endif; ?>
                        <?php foreach ($row_tags as $code): $tag = programs($code); if ( ! $tag) continue; ?>
                            <span class="directory-program-tag" style="--tag-color: <?php echo e($tag['color']); ?>" title="<?php echo e($tag['name']); ?>"><i class="bi <?php echo e($tag['icon']); ?>" aria-hidden="true"></i><?php echo e($code === 'spfl' ? 'FL / SPFL' : $tag['short']); ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php if ($can_edit): ?>
                    <details class="directory-tag-editor">
                        <summary aria-label="<?php echo e('Edit program tags for '.$row->first_name.' '.$row->last_name); ?>">Edit Tags</summary>
                        <?php echo form_open(e(site_url('learners/program_tags/'.(int) $row->enrollment_id).'?'.http_build_query($list_query + array('page' => $offset)))); ?>
                            <fieldset>
                                <legend class="visually-hidden">Program tags</legend>
                                <?php foreach (programs() as $code => $tag): ?>
                                    <label><input type="checkbox" class="form-check-input" name="programs[]" value="<?php echo e($code); ?>" <?php echo in_array($code, $row_tags, TRUE) ? 'checked' : ''; ?>> <?php echo e($code === 'spfl' ? 'FL / SPFL' : $tag['short']); ?></label>
                                <?php endforeach; ?>
                            </fieldset>
                            <button class="btn btn-primary btn-sm" type="submit">Save Tags</button>
                        <?php echo form_close(); ?>
                    </details>
                    <?php endif; ?>
                </td>
                <td class="text-end">
                    <?php if ($can_edit): ?>
                    <div class="d-inline-flex flex-wrap justify-content-end gap-2">
                        <a class="directory-edit" href="<?php echo site_url('learners/edit/'.(int) $row->enrollment_id); ?>" aria-label="<?php echo e('Edit Enrollment for '.$row->first_name.' '.$row->last_name); ?>"><i class="bi bi-pencil-square" aria-hidden="true"></i> Edit</a>
                        <?php echo form_open(e(site_url('learners/delete/'.(int) $row->enrollment_id).'?'.http_build_query($list_query + array('page' => $offset))),
                            array('data-confirm' => 'Remove this enrollment from the selected school year? The learner profile and enrollment history will be kept.')); ?>
                            <button class="directory-edit directory-delete" type="submit" aria-label="<?php echo e('Delete Enrollment for '.$row->first_name.' '.$row->last_name); ?>"><i class="bi bi-trash3" aria-hidden="true"></i> Delete</button>
                        <?php echo form_close(); ?>
                    </div>
                    <?php else: ?>
                        <span class="directory-readonly"><i class="bi bi-lock" aria-hidden="true"></i> View Only</span>
                    <?php endif; ?>
                </td>
            </tr><?php endforeach; ?>
            <?php if ( ! $rows): ?><tr><td colspan="7"><div class="directory-empty"><span class="directory-empty-icon"><i class="bi <?php echo $has_filters ? 'bi-search' : 'bi-person-vcard'; ?>" aria-hidden="true"></i></span><h3><?php echo $has_filters ? 'No Matching Learners' : 'Your Learner List Starts Here'; ?></h3><p>No learners match these filters. <?php echo $has_filters ? 'Try a different name, grade, or program to find the record you need.' : 'Choose a school year or encode a learner to get started.'; ?></p><?php if ($has_filters): ?><a class="btn btn-outline-primary btn-sm" href="<?php echo site_url('learners?school_year_id='.(int) $year_id); ?>">Clear Filters</a><?php elseif ($can_create): ?><a class="btn btn-primary btn-sm" href="<?php echo site_url('learners/create?school_year_id='.(int) $year_id); ?>">Encode Learner</a><?php endif; ?></div></td></tr><?php endif; ?>
            </tbody></table>
        </div>
        <footer class="directory-table-footer"><p><?php if ($total): ?>Showing <strong><?php echo number_format($offset + 1); ?>–<?php echo number_format($offset + count($rows)); ?></strong> of <strong><?php echo number_format($total); ?></strong> learners<?php else: ?>0 learners<?php endif; ?></p><?php echo $pagination; ?></footer>
    </section>
    <p class="directory-footnote"><i class="bi bi-info-circle" aria-hidden="true"></i> Program tags apply to the selected school year. A learner can belong to multiple programs.</p>
</div>
<script>
document.getElementById('page-size').addEventListener('change', function () { this.form.requestSubmit(); });
document.querySelector('.directory-size-submit').hidden = true;
(function () {
    var directory = document.querySelector('.directory-refined');
    var button = directory.querySelector('.directory-density');
    button.hidden = false;
    function setCompact(compact) {
        directory.classList.toggle('is-compact', compact);
        button.setAttribute('aria-pressed', String(compact));
    }
    try { setCompact(localStorage.getItem('uie-learner-compact') === 'true'); } catch (e) {}
    button.addEventListener('click', function () {
        var compact = button.getAttribute('aria-pressed') !== 'true';
        setCompact(compact);
        try { localStorage.setItem('uie-learner-compact', String(compact)); } catch (e) {}
    });
}());
</script>
