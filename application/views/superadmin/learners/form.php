<?php echo form_open($learner ? 'superadmin/learners/edit/'.$learner->id : 'superadmin/learners/create'); ?>
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h4 class="fw-bold mb-0"><i class="bi bi-person-vcard me-2"></i><?php echo $learner ? 'Edit' : 'Register'; ?> Learner</h4>
    <div class="d-flex gap-2">
        <a href="<?php echo site_url('superadmin/learners'); ?>" class="btn btn-outline-secondary btn-sm">Cancel</a>
        <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-check-lg me-1"></i><?php echo $learner ? 'Save Changes' : 'Register Learner'; ?></button>
    </div>
</div>

<?php echo validation_errors('<div class="alert alert-danger py-2 small">', '</div>'); ?>

<div class="row g-3">
    <div class="col-lg-8">
        <!-- Personal Information -->
        <div class="card mb-3">
            <div class="card-body">
                <div class="section-title">Personal Information</div>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">LRN <small class="text-muted">(12 digits)</small></label>
                        <input type="text" name="lrn" class="form-control" maxlength="12" value="<?php echo e(set_value('lrn', $learner->lrn ?? '')); ?>" placeholder="Optional but recommended">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label required-label">Sex</label>
                        <select name="sex" class="form-select" required>
                            <option value="">-- Select --</option>
                            <option value="Male"   <?php echo set_value('sex', $learner->sex ?? '') === 'Male'   ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?php echo set_value('sex', $learner->sex ?? '') === 'Female' ? 'selected' : ''; ?>>Female</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Date of Birth</label>
                        <input type="date" name="date_of_birth" class="form-control" value="<?php echo e(set_value('date_of_birth', $learner->date_of_birth ?? '')); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label required-label">Last Name</label>
                        <input type="text" name="last_name" data-uppercase-name autocapitalize="characters" class="form-control" maxlength="80" value="<?php echo e(set_value('last_name', $learner->last_name ?? '')); ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label required-label">First Name</label>
                        <input type="text" name="first_name" data-uppercase-name autocapitalize="characters" class="form-control" maxlength="80" value="<?php echo e(set_value('first_name', $learner->first_name ?? '')); ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Middle Name</label>
                        <input type="text" name="middle_name" data-uppercase-name autocapitalize="characters" class="form-control" maxlength="80" value="<?php echo e(set_value('middle_name', $learner->middle_name ?? '')); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Extension</label>
                        <input type="text" name="extension_name" data-uppercase-name autocapitalize="characters" class="form-control" maxlength="20" value="<?php echo e(set_value('extension_name', $learner->extension_name ?? '')); ?>" placeholder="Jr., III">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Place of Birth</label>
                        <input type="text" name="place_of_birth" class="form-control" maxlength="200" value="<?php echo e(set_value('place_of_birth', $learner->place_of_birth ?? '')); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Contact Number</label>
                        <input type="text" name="contact_number" class="form-control" maxlength="30" value="<?php echo e(set_value('contact_number', $learner->contact_number ?? '')); ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Complete Address</label>
                        <input type="text" name="address" class="form-control" maxlength="255" value="<?php echo e(set_value('address', $learner->address ?? '')); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Barangay</label>
                        <input type="text" name="barangay" class="form-control" maxlength="120" value="<?php echo e(set_value('barangay', $learner->barangay ?? '')); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Municipality / City</label>
                        <input type="text" name="municipality" class="form-control" maxlength="120" value="<?php echo e(set_value('municipality', $learner->municipality ?? '')); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Province</label>
                        <input type="text" name="province" class="form-control" maxlength="120" value="<?php echo e(set_value('province', $learner->province ?? '')); ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- Parent / Guardian -->
        <div class="card mb-3">
            <div class="card-body">
                <div class="section-title">Parent / Guardian Information</div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Father's Name</label>
                        <input type="text" name="father_name" data-uppercase-name autocapitalize="characters" class="form-control" maxlength="160" value="<?php echo e(set_value('father_name', $learner->father_name ?? '')); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Mother's Name</label>
                        <input type="text" name="mother_name" data-uppercase-name autocapitalize="characters" class="form-control" maxlength="160" value="<?php echo e(set_value('mother_name', $learner->mother_name ?? '')); ?>">
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Guardian's Name</label>
                        <input type="text" name="guardian_name" data-uppercase-name autocapitalize="characters" class="form-control" maxlength="160" value="<?php echo e(set_value('guardian_name', $learner->guardian_name ?? '')); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Relationship to Learner</label>
                        <input type="text" name="guardian_relationship" class="form-control" maxlength="60" value="<?php echo e(set_value('guardian_relationship', $learner->guardian_relationship ?? '')); ?>">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <!-- Enrollment -->
        <div class="card mb-3">
            <div class="card-body">
                <div class="section-title">Enrollment Information</div>
                <div class="mb-3">
                    <label class="form-label">Region</label>
                    <select id="regionSelect" class="form-select form-select-sm"
                            data-loads="#divisionSelect"
                            data-endpoint="<?php echo site_url('ajax/divisions-by-region/'); ?>">
                        <option value="">-- Select Region --</option>
                        <?php foreach ($regions as $r): ?>
                        <option value="<?php echo $r->id; ?>" <?php echo (int) $sel_region === (int) $r->id ? 'selected' : ''; ?>><?php echo e($r->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Division</label>
                    <select id="divisionSelect" class="form-select form-select-sm"
                            data-loads="#schoolSelect"
                            data-endpoint="<?php echo site_url('ajax/schools-by-division/'); ?>">
                        <option value="">-- Select Division --</option>
                        <?php foreach ($divisions as $d): ?>
                        <option value="<?php echo $d->id; ?>" <?php echo (int) $sel_division === (int) $d->id ? 'selected' : ''; ?>><?php echo e($d->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label required-label">School</label>
                    <select name="school_id" id="schoolSelect" class="form-select form-select-sm" required>
                        <option value="">-- Select School --</option>
                        <?php foreach ($schools as $s): ?>
                        <option value="<?php echo $s->id; ?>" <?php echo (int) $sel_school === (int) $s->id ? 'selected' : ''; ?>><?php echo e($s->school_id.' - '.$s->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="row g-2">
                    <div class="col-6">
                        <label class="form-label required-label">School Year</label>
                        <select name="school_year_id" class="form-select form-select-sm" required>
                            <?php foreach ($school_years as $sy): ?>
                            <option value="<?php echo $sy->id; ?>" <?php echo (int) set_value('school_year_id', $enrollment->school_year_id ?? ($sy->is_active ? $sy->id : 0)) === (int) $sy->id ? 'selected' : ''; ?>>SY <?php echo e($sy->year_label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Grade Level</label>
                        <select name="grade_level" class="form-select form-select-sm">
                            <option value="">--</option>
                            <?php foreach ($grade_levels as $g): ?>
                            <option value="<?php echo $g; ?>" <?php echo set_value('grade_level', $enrollment->grade_level ?? '') === $g ? 'selected' : ''; ?>><?php echo $g; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Section</label>
                        <input type="text" name="section" class="form-control form-control-sm" maxlength="60" value="<?php echo e(set_value('section', $enrollment->section ?? '')); ?>">
                    </div>
                    <div class="col-6">
                        <label class="form-label">Curriculum</label>
                        <input type="text" name="curriculum" class="form-control form-control-sm" maxlength="60" value="<?php echo e(set_value('curriculum', $enrollment->curriculum ?? '')); ?>" placeholder="e.g. K-12, BEC">
                    </div>
                    <div class="col-12">
                        <label class="form-label required-label">Enrollment Status</label>
                        <select name="enrollment_status" class="form-select form-select-sm" required>
                            <?php foreach (array('Enrolled','Transferred In','Transferred Out','Dropped Out','Completed','No Longer Enrolled') as $es): ?>
                            <option value="<?php echo $es; ?>" <?php echo set_value('enrollment_status', $enrollment->enrollment_status ?? 'Enrolled') === $es ? 'selected' : ''; ?>><?php echo $es; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Remarks / duplicate confirm -->
        <div class="card">
            <div class="card-body">
                <div class="section-title">Additional Information</div>
                <label class="form-label">Remarks</label>
                <textarea name="remarks" class="form-control" rows="3"><?php echo e(set_value('remarks', $learner->remarks ?? '')); ?></textarea>
                <?php if (isset($_GET['dupe']) || $this->session->userdata('dupes')): $this->session->unset_userdata('dupes'); ?>
                <div class="form-check mt-3">
                    <input class="form-check-input" type="checkbox" name="confirm_duplicate" value="1" id="confirmDupe">
                    <label class="form-check-label small" for="confirmDupe">Confirm Add Anyway (Possible Duplicate Detected)</label>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php echo form_close(); ?>
