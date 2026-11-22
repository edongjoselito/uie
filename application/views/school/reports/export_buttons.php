<?php if ($this->access_control->can('reports.export')): ?>
<div class="report-section-exports report-screen-only" role="group" aria-label="<?php echo e('Download '.$export_label); ?>">
    <span>Export</span>
    <?php foreach (array('xlsx' => array('Excel', 'bi-file-earmark-excel'), 'docx' => array('Word', 'bi-file-earmark-word'), 'pdf' => array('PDF', 'bi-file-earmark-pdf')) as $format => $option): ?>
    <a class="report-export-link report-export-<?php echo e($format); ?>" href="<?php echo e(site_url('reports/export/'.$export_section.'/'.$format).'?'.http_build_query(array('school_year_id' => (int) $year_id, 'grade_level' => $grade, 'district' => isset($district) ? $district : ''))); ?>" aria-label="<?php echo e('Download '.$export_label.' as '.$option[0]); ?>" title="Download all matching records as <?php echo e($option[0]); ?>"><i class="bi <?php echo e($option[1]); ?>" aria-hidden="true"></i><?php echo e($option[0]); ?></a>
    <?php endforeach; ?>
</div>
<?php endif; ?>
