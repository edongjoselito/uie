<?php
$value = function ($field, $default = '') use ($enrollment) {
    return e(set_value($field, $enrollment && isset($enrollment->$field) ? $enrollment->$field : $default, FALSE));
};
$profile_value = function ($program, $field, $default = '') use ($profile_data) {
    $value = isset($profile_data[$program][$field]) ? $profile_data[$program][$field] : $default;
    return e(is_scalar($value) ? $value : $default);
};
$profile_selected = function ($program, $field, $id) use ($profile_data) {
    return isset($profile_data[$program][$field]) && in_array((string) $id, array_map('strval', (array) $profile_data[$program][$field]), TRUE);
};
$selected_year_label = 'Not selected';
foreach ($years as $sy) if ((int) $sy->id === (int) $year_id) $selected_year_label = $sy->year_label;
?>
<div class="learner-workspace<?php echo $enrollment ? ' learner-workspace-fluid' : ''; ?>">
    <a class="learner-back" href="<?php echo site_url('learners?school_year_id='.(int) $year_id); ?>"><i class="bi bi-arrow-left" aria-hidden="true"></i> Back to learner profiles</a>
    <header class="learner-page-heading">
        <div><p class="learner-eyebrow">LEARNERS / PROFILE</p><h1><?php echo $enrollment ? 'Edit Enrollment' : 'Add a Learner'; ?></h1><p>Every learner, a place to belong. Record their details and programs for the school year.</p></div>
        <span class="learner-school-pill"><i class="bi bi-building" aria-hidden="true"></i><span><?php echo e($school->name); ?></span></span>
    </header>
    <?php echo validation_errors('<div class="alert alert-danger" role="alert">', '</div>'); ?>
    <?php if ($error): ?><div class="alert alert-danger" role="alert"><?php echo e($error); ?></div><?php endif; ?>
    <?php echo form_open($enrollment ? 'learners/edit/'.(int) $enrollment->id : 'learners/create', array('class' => 'learner-entry-form')); ?>
    <div class="learner-entry-layout">
        <div class="learner-entry-sections">
            <section class="learner-entry-card" aria-labelledby="identity-title">
                <div class="learner-section-heading"><span class="learner-step">01</span><div><h2 id="identity-title">Learner Information</h2><p><?php echo $enrollment ? 'The learner linked to this enrollment.' : 'Start with a new learner or select an existing school record.'; ?></p></div><i class="bi bi-person-vcard learner-section-icon" aria-hidden="true"></i></div>
                <div class="learner-section-body">
                <?php if ($enrollment): ?>
                    <div class="learner-identity-preview"><span class="learner-avatar"><i class="bi bi-person" aria-hidden="true"></i></span><div><strong><?php echo e($enrollment->last_name.', '.$enrollment->first_name); ?></strong><p>LRN: <?php echo e($enrollment->lrn ?: 'Not assigned'); ?> · <?php echo e($enrollment->sex); ?> · <?php echo e($enrollment->date_of_birth); ?></p></div></div>
                <?php else: ?>
                    <div class="learner-record-picker"><label class="form-label" for="learner-id">Learner Record</label><select class="form-select" id="learner-id" name="learner_id"><option value="0">Register a new learner</option>
                        <?php foreach ($known as $person): ?><option value="<?php echo (int) $person->id; ?>" <?php echo (int) $this->input->post('learner_id') === (int) $person->id ? 'selected' : ''; ?>><?php echo e($person->last_name.', '.$person->first_name.' · '.($person->lrn ?: 'No LRN')); ?></option><?php endforeach; ?>
                    </select><div class="form-text">Returning learner? Select their existing record to add a new school year.</div></div>
                    <div id="new-learner-fields" class="row g-4">
                        <div class="col-12"><p class="learner-subsection-label">SF 1 · Learner Name</p></div>
                        <?php foreach (array('last_name' => 'Last Name', 'first_name' => 'First Name', 'middle_name' => 'Middle Name') as $field => $label): ?><div class="col-md-3"><label for="<?php echo e($field); ?>" class="form-label"><?php echo e($label); ?> <?php if ($field === 'middle_name'): ?><span class="learner-optional">Optional</span><?php else: ?><span class="learner-required" aria-hidden="true">*</span><?php endif; ?></label><input id="<?php echo e($field); ?>" name="<?php echo e($field); ?>" data-uppercase-name autocapitalize="characters" class="form-control" value="<?php echo $value($field); ?>" maxlength="80" <?php echo $field !== 'middle_name' ? 'data-new-required required' : ''; ?>></div><?php endforeach; ?>
                        <div class="col-md-3"><label for="extension-name" class="form-label">Extension Name <span class="learner-optional">Optional</span></label><input id="extension-name" name="extension_name" data-uppercase-name autocapitalize="characters" class="form-control" value="<?php echo $value('extension_name'); ?>" maxlength="20" placeholder="Jr., Sr., III"></div>
                        <div class="col-12"><p class="learner-subsection-label">SF 1 · Birth &amp; Demographics</p></div>
                        <div class="col-md-3"><label for="date-of-birth" class="form-label">Date of Birth <span class="learner-required" aria-hidden="true">*</span></label><input id="date-of-birth" name="date_of_birth" type="date" class="form-control" value="<?php echo $value('date_of_birth'); ?>" max="<?php echo date('Y-m-d'); ?>" data-new-required required></div>
                        <div class="col-md-3"><label for="sex" class="form-label">Sex <span class="learner-required" aria-hidden="true">*</span></label><select id="sex" name="sex" class="form-select" data-new-required required><option value="">Select sex</option><?php foreach (array('Male', 'Female') as $sex): ?><option <?php echo set_select('sex', $sex); ?>><?php echo e($sex); ?></option><?php endforeach; ?></select></div>
                        <div class="col-md-3"><label for="place-of-birth" class="form-label">Place of Birth <span class="learner-optional">Optional</span></label><input id="place-of-birth" name="place_of_birth" class="form-control" value="<?php echo $value('place_of_birth'); ?>" maxlength="200"></div>
                        <div class="col-md-3"><label for="lrn" class="form-label">LRN <span class="learner-optional">Optional</span></label><input id="lrn" name="lrn" class="form-control learner-lrn" value="<?php echo $value('lrn'); ?>" inputmode="numeric" pattern="[0-9]{12}" maxlength="12" aria-describedby="lrn-hint"><div class="form-text" id="lrn-hint">12-digit Learner Reference Number.</div></div>
                        <div class="col-md-4"><label for="mother-tongue" class="form-label">Mother Tongue <span class="learner-optional">Optional</span></label><input id="mother-tongue" name="mother_tongue" class="form-control" value="<?php echo $value('mother_tongue'); ?>" maxlength="120"></div>
                        <div class="col-md-4"><label for="ip-ethnicity" class="form-label">IP (Ethnic Group) <span class="learner-optional">Optional</span></label><input id="ip-ethnicity" name="ip_ethnicity" class="form-control" value="<?php echo $value('ip_ethnicity'); ?>" maxlength="120"></div>
                        <div class="col-md-4"><label for="religion" class="form-label">Religion <span class="learner-optional">Optional</span></label><input id="religion" name="religion" class="form-control" value="<?php echo $value('religion'); ?>" maxlength="120"></div>
                        <div class="col-12"><p class="learner-subsection-label">SF 1 · Address</p></div>
                        <div class="col-md-6"><label for="address" class="form-label">House No. / Street / Sitio <span class="learner-optional">Optional</span></label><input id="address" name="address" class="form-control" value="<?php echo $value('address'); ?>" maxlength="255"></div>
                        <div class="col-md-6"><label for="barangay" class="form-label">Barangay <span class="learner-optional">Optional</span></label><input id="barangay" name="barangay" class="form-control" value="<?php echo $value('barangay'); ?>" maxlength="120"></div>
                        <div class="col-md-6"><label for="municipality" class="form-label">Municipality / City <span class="learner-optional">Optional</span></label><input id="municipality" name="municipality" class="form-control" value="<?php echo $value('municipality'); ?>" maxlength="120"></div>
                        <div class="col-md-6"><label for="province" class="form-label">Province <span class="learner-optional">Optional</span></label><input id="province" name="province" class="form-control" value="<?php echo $value('province'); ?>" maxlength="120"></div>
                        <div class="col-12"><p class="learner-subsection-label">SF 1 · Parents / Guardian</p></div>
                        <div class="col-md-6"><label for="father-name" class="form-label">Father's Name <span class="learner-optional">Optional</span></label><input id="father-name" name="father_name" data-uppercase-name autocapitalize="characters" class="form-control" value="<?php echo $value('father_name'); ?>" maxlength="160" placeholder="Last name, First name, Middle name"></div>
                        <div class="col-md-6"><label for="mother-name" class="form-label">Mother's Maiden Name <span class="learner-optional">Optional</span></label><input id="mother-name" name="mother_name" data-uppercase-name autocapitalize="characters" class="form-control" value="<?php echo $value('mother_name'); ?>" maxlength="160" placeholder="Last name, First name, Middle name"></div>
                        <div class="col-md-5"><label for="guardian-name" class="form-label">Guardian's Name <span class="learner-optional">Optional</span></label><input id="guardian-name" name="guardian_name" data-uppercase-name autocapitalize="characters" class="form-control" value="<?php echo $value('guardian_name'); ?>" maxlength="160"></div>
                        <div class="col-md-3"><label for="guardian-relationship" class="form-label">Relationship <span class="learner-optional">Optional</span></label><input id="guardian-relationship" name="guardian_relationship" class="form-control" value="<?php echo $value('guardian_relationship'); ?>" maxlength="60" list="guardian-relationships"><datalist id="guardian-relationships"><option value="Father"></option><option value="Mother"></option><option value="Guardian"></option><option value="Grandparent"></option><option value="Sibling"></option><option value="Relative"></option></datalist></div>
                        <div class="col-md-4"><label for="contact-number" class="form-label">Contact Number <span class="learner-optional">Optional</span></label><input id="contact-number" name="contact_number" class="form-control" value="<?php echo $value('contact_number'); ?>" maxlength="30" inputmode="tel" placeholder="Parent or guardian"></div>
                    </div>
                    <p class="learner-field-note"><i class="bi bi-info-circle" aria-hidden="true"></i> Fields follow the SF 1 (School Register) layout and can also be filled by uploading the register on the <a href="<?php echo site_url('learners/import'); ?>">SF 1 import page</a>. For learners transferring from another school, contact your administrator.</p>
                <?php endif; ?>
                </div>
            </section>
            <section class="learner-entry-card" aria-labelledby="enrollment-title">
                <div class="learner-section-heading"><span class="learner-step">02</span><div><h2 id="enrollment-title">School-Year Enrollment</h2><p>Set the learner’s placement for this school year.</p></div><i class="bi bi-calendar3 learner-section-icon" aria-hidden="true"></i></div>
                <div class="learner-section-body"><div class="row g-4">
                    <div class="col-md-4"><label for="school-year" class="form-label">School Year <span class="learner-required" aria-hidden="true">*</span></label><select id="school-year" name="school_year_id" class="form-select" required><option value="">Select school year</option>
                    <?php foreach ($years as $sy): if ($sy->status !== 'open' || ($enrollment && (int) $sy->id !== (int) $enrollment->school_year_id)) continue; ?><option value="<?php echo (int) $sy->id; ?>" <?php echo (int) $year_id === (int) $sy->id ? 'selected' : ''; ?>><?php echo e($sy->year_label); ?></option><?php endforeach; ?>
                    </select><div class="form-text">Open years only.</div></div>
                    <div class="col-md-4"><label for="grade-level" class="form-label">Grade Level <span class="learner-required" aria-hidden="true">*</span></label><select id="grade-level" name="grade_level" class="form-select" required><option value="">Select grade level</option><?php foreach ($grades as $grade): ?><option <?php echo set_select('grade_level', $grade, $enrollment && $enrollment->grade_level === $grade); ?>><?php echo e($grade); ?></option><?php endforeach; ?></select></div>
                    <div class="col-md-4"><label for="section" class="form-label">Section <span class="learner-optional">Optional</span></label><input id="section" name="section" class="form-control" maxlength="60" value="<?php echo $value('section'); ?>"></div>
                </div></div>
            </section>
            <section class="learner-entry-card" aria-labelledby="program-title">
                <div class="learner-section-heading"><span class="learner-step">03</span><div><h2 id="program-title">Education Programs</h2><p>Select all that apply. A learner may belong to more than one program.</p></div><span class="learner-multiple-badge">Multiple selection</span></div>
                <fieldset class="learner-section-body"><legend class="visually-hidden">Program Tags for This School Year</legend>
                    <div class="learner-program-grid"><?php foreach (programs() as $p): ?>
                        <div class="learner-program-option" style="--tag-color: <?php echo e($p['color']); ?>">
                            <input type="checkbox" name="programs[]" id="program-<?php echo e($p['code']); ?>" value="<?php echo e($p['code']); ?>" <?php echo in_array($p['code'], $selected_tags, TRUE) ? 'checked' : ''; ?>>
                            <label for="program-<?php echo e($p['code']); ?>"><span class="learner-program-symbol"><i class="bi <?php echo e($p['icon']); ?>" aria-hidden="true"></i></span><span class="learner-program-copy"><strong><?php echo e($p['code'] === 'spfl' ? 'FL / SPFL' : $p['short']); ?></strong><span><?php echo e($p['name']); ?></span></span><span class="learner-program-check"><i class="bi bi-check" aria-hidden="true"></i></span></label>
                        </div>
                    <?php endforeach; ?></div>
                    <p class="learner-field-note mb-0"><i class="bi bi-check2-circle" aria-hidden="true"></i> No program applies? You can leave all options unchecked.</p>
                </fieldset>
            </section>
            <section class="learner-entry-card d-none" data-program-fields="iped" aria-labelledby="iped-fields-title">
                <div class="learner-section-heading"><span class="learner-step"><i class="bi bi-tree" aria-hidden="true"></i></span><div><h2 id="iped-fields-title">IPEd Information</h2><p>Community, language, and program participation details.</p></div></div>
                <div class="learner-section-body row g-4">
                    <div class="col-md-6"><label class="form-label" for="iped-icc">Indigenous Cultural Community</label><select class="form-select" id="iped-icc" name="profiles[iped][icc_id]"><option value="">Select community</option><?php foreach ($profile_options['cultural_communities'] as $option): ?><option value="<?php echo (int) $option->id; ?>" <?php echo (string) $option->id === $profile_value('iped', 'icc_id') ? 'selected' : ''; ?>><?php echo e($option->name); ?></option><?php endforeach; ?></select></div>
                    <div class="col-md-6"><label class="form-label" for="iped-ethnic-group">Ethnic Group</label><select class="form-select" id="iped-ethnic-group" name="profiles[iped][ethnic_group_id]"><option value="">Select ethnic group</option><?php foreach ($profile_options['ethnic_groups'] as $option): ?><option value="<?php echo (int) $option->id; ?>" <?php echo (string) $option->id === $profile_value('iped', 'ethnic_group_id') ? 'selected' : ''; ?>><?php echo e($option->name); ?></option><?php endforeach; ?></select></div>
                    <?php foreach (array('tribe' => array('Tribe', 120), 'indigenous_language' => array('Indigenous Language', 120), 'mother_tongue' => array('Mother Tongue', 120), 'ancestral_area' => array('Ancestral Area', 200), 'program_participation' => array('Program Participation', 150), 'educational_assistance' => array('Educational Assistance', 255), 'program_support' => array('Program Support', 255), 'community_participation' => array('Community Participation', 255)) as $field => $info): ?><div class="col-md-6"><label class="form-label" for="iped-<?php echo e($field); ?>"><?php echo e($info[0]); ?></label><input class="form-control" id="iped-<?php echo e($field); ?>" name="profiles[iped][<?php echo e($field); ?>]" maxlength="<?php echo (int) $info[1]; ?>" value="<?php echo $profile_value('iped', $field); ?>"></div><?php endforeach; ?>
                    <?php foreach (array('other_cultural_info' => 'Other Cultural Information', 'remarks' => 'Remarks') as $field => $label): ?><div class="col-12"><label class="form-label" for="iped-<?php echo e($field); ?>"><?php echo e($label); ?></label><textarea class="form-control" id="iped-<?php echo e($field); ?>" name="profiles[iped][<?php echo e($field); ?>]" rows="2" maxlength="4000"><?php echo $profile_value('iped', $field); ?></textarea></div><?php endforeach; ?>
                </div>
            </section>
            <section class="learner-entry-card d-none" data-program-fields="madrasah" aria-labelledby="madrasah-fields-title">
                <div class="learner-section-heading"><span class="learner-step"><i class="bi bi-moon-stars" aria-hidden="true"></i></span><div><h2 id="madrasah-fields-title">Madrasah Information</h2><p>Participation, proficiency, and Madrasah programs.</p></div></div>
                <div class="learner-section-body row g-4">
                    <div class="col-md-6"><label class="form-label" for="madrasah-alive">ALIVE Participation</label><select class="form-select" id="madrasah-alive" name="profiles[madrasah][alive_participation]"><option value="">Select</option><?php foreach (array('Yes', 'No') as $choice): ?><option value="<?php echo e($choice); ?>" <?php echo $profile_value('madrasah', 'alive_participation') === $choice ? 'selected' : ''; ?>><?php echo e($choice); ?></option><?php endforeach; ?></select></div>
                    <div class="col-md-6"><label class="form-label" for="madrasah-islamic">Islamic Values Education Participation</label><select class="form-select" id="madrasah-islamic" name="profiles[madrasah][islamic_values_participation]"><option value="">Select</option><?php foreach (array('Yes', 'No') as $choice): ?><option value="<?php echo e($choice); ?>" <?php echo $profile_value('madrasah', 'islamic_values_participation') === $choice ? 'selected' : ''; ?>><?php echo e($choice); ?></option><?php endforeach; ?></select></div>
                    <div class="col-md-6"><label class="form-label" for="madrasah-arabic">Arabic Proficiency</label><input class="form-control" id="madrasah-arabic" name="profiles[madrasah][arabic_proficiency]" maxlength="60" value="<?php echo $profile_value('madrasah', 'arabic_proficiency'); ?>"></div>
                    <div class="col-md-6"><label class="form-label" for="madrasah-years">Years in Program</label><input class="form-control" id="madrasah-years" name="profiles[madrasah][years_in_program]" type="number" min="0" max="255" value="<?php echo $profile_value('madrasah', 'years_in_program'); ?>"></div>
                    <div class="col-md-6"><label class="form-label" for="madrasah-status">Participation Status</label><input class="form-control" id="madrasah-status" name="profiles[madrasah][participation_status]" maxlength="60" value="<?php echo $profile_value('madrasah', 'participation_status'); ?>"></div>
                    <div class="col-12"><fieldset><legend class="form-label">Madrasah Programs</legend><div class="row g-2"><?php foreach ($profile_options['madrasah_programs'] as $option): ?><div class="col-md-6"><label class="form-check"><input class="form-check-input" type="checkbox" name="profiles[madrasah][program_ids][]" value="<?php echo (int) $option->id; ?>" <?php echo $profile_selected('madrasah', 'program_ids', $option->id) ? 'checked' : ''; ?>><span class="form-check-label"><?php echo e($option->name); ?></span></label></div><?php endforeach; ?></div></fieldset></div>
                    <?php foreach (array('educational_assistance' => 'Educational Assistance', 'program_support' => 'Program Support', 'remarks' => 'Remarks') as $field => $label): ?><div class="col-md-6"><label class="form-label" for="madrasah-<?php echo e($field); ?>"><?php echo e($label); ?></label><textarea class="form-control" id="madrasah-<?php echo e($field); ?>" name="profiles[madrasah][<?php echo e($field); ?>]" rows="2" maxlength="<?php echo $field === 'remarks' ? 4000 : 255; ?>"><?php echo $profile_value('madrasah', $field); ?></textarea></div><?php endforeach; ?>
                </div>
            </section>
            <section class="learner-entry-card d-none" data-program-fields="sned" aria-labelledby="sned-fields-title">
                <div class="learner-section-heading"><span class="learner-step"><i class="bi bi-universal-access" aria-hidden="true"></i></span><div><h2 id="sned-fields-title">SNED Information</h2><p>Support needs, accommodations, and services. Complete only information needed for learner support.</p></div></div>
                <div class="learner-section-body row g-4">
                    <div class="col-12"><fieldset><legend class="form-label">Disability / Support Categories</legend><div class="row g-2"><?php foreach ($profile_options['disability_categories'] as $option): ?><div class="col-md-6"><label class="form-check"><input class="form-check-input" type="checkbox" name="profiles[sned][disability_category_ids][]" value="<?php echo (int) $option->id; ?>" <?php echo $profile_selected('sned', 'disability_category_ids', $option->id) ? 'checked' : ''; ?>><span class="form-check-label"><?php echo e($option->name); ?></span></label></div><?php endforeach; ?></div></fieldset></div>
                    <?php foreach (array('functional_needs' => 'Functional Needs', 'accommodations' => 'Accommodations', 'interventions' => 'Interventions', 'progress_monitoring' => 'Progress Monitoring') as $field => $label): ?><div class="col-12"><label class="form-label" for="sned-<?php echo e($field); ?>"><?php echo e($label); ?></label><textarea class="form-control" id="sned-<?php echo e($field); ?>" name="profiles[sned][<?php echo e($field); ?>]" rows="2" maxlength="4000"><?php echo $profile_value('sned', $field); ?></textarea></div><?php endforeach; ?>
                    <?php foreach (array('assistive_devices' => array('Assistive Devices', 255), 'accessibility_requirements' => array('Accessibility Requirements', 255), 'assistive_technology' => array('Assistive Technology', 255), 'therapy_support' => array('Therapy Support', 255), 'support_services' => array('Support Services', 255), 'referral_info' => array('Referral Information', 255), 'doc_reference' => array('Assessment Document Reference', 200)) as $field => $info): ?><div class="col-md-6"><label class="form-label" for="sned-<?php echo e($field); ?>"><?php echo e($info[0]); ?></label><input class="form-control" id="sned-<?php echo e($field); ?>" name="profiles[sned][<?php echo e($field); ?>]" maxlength="<?php echo (int) $info[1]; ?>" value="<?php echo $profile_value('sned', $field); ?>"></div><?php endforeach; ?>
                    <div class="col-md-6"><label class="form-label" for="sned-iep-status">IEP Status</label><select class="form-select" id="sned-iep-status" name="profiles[sned][iep_status]"><?php foreach (array('None', 'In Development', 'Implemented', 'Under Review') as $choice): ?><option value="<?php echo e($choice); ?>" <?php echo $profile_value('sned', 'iep_status', 'None') === $choice ? 'selected' : ''; ?>><?php echo e($choice); ?></option><?php endforeach; ?></select></div>
                    <div class="col-md-6"><label class="form-label" for="sned-updated">Last Profile Update</label><input class="form-control" id="sned-updated" type="date" name="profiles[sned][last_profile_update]" max="<?php echo date('Y-m-d'); ?>" value="<?php echo $profile_value('sned', 'last_profile_update') ? e(substr($profile_value('sned', 'last_profile_update'), 0, 10)) : ''; ?>"></div>
                    <div class="col-12"><label class="form-label" for="sned-remarks">Remarks</label><textarea class="form-control" id="sned-remarks" name="profiles[sned][remarks]" rows="2" maxlength="4000"><?php echo $profile_value('sned', 'remarks'); ?></textarea></div>
                </div>
            </section>
            <section class="learner-entry-card d-none" data-program-fields="spfl" aria-labelledby="spfl-fields-title">
                <div class="learner-section-heading"><span class="learner-step"><i class="bi bi-translate" aria-hidden="true"></i></span><div><h2 id="spfl-fields-title">FL / SPFL Information</h2><p>Foreign language learning and program support.</p></div></div>
                <div class="learner-section-body row g-4">
                    <div class="col-md-6"><label class="form-label" for="spfl-language">Foreign Language</label><input class="form-control" id="spfl-language" name="profiles[spfl][foreign_language]" maxlength="120" value="<?php echo $profile_value('spfl', 'foreign_language'); ?>"></div>
                    <div class="col-md-6"><label class="form-label" for="spfl-proficiency">Proficiency Level</label><input class="form-control" id="spfl-proficiency" name="profiles[spfl][proficiency_level]" maxlength="60" value="<?php echo $profile_value('spfl', 'proficiency_level'); ?>"></div>
                    <?php foreach (array('educational_assistance' => 'Educational Assistance', 'program_support' => 'Program Support', 'remarks' => 'Remarks') as $field => $label): ?><div class="col-md-6"><label class="form-label" for="spfl-<?php echo e($field); ?>"><?php echo e($label); ?></label><textarea class="form-control" id="spfl-<?php echo e($field); ?>" name="profiles[spfl][<?php echo e($field); ?>]" rows="2" maxlength="<?php echo $field === 'remarks' ? 4000 : 255; ?>"><?php echo $profile_value('spfl', $field); ?></textarea></div><?php endforeach; ?>
                </div>
            </section>
            <div class="learner-form-actions"><span><i class="bi bi-asterisk" aria-hidden="true"></i> Required fields</span><div><a class="btn learner-cancel" href="<?php echo site_url('learners?school_year_id='.(int) $year_id); ?>">Cancel</a><button class="btn btn-primary learner-save" type="submit"><i class="bi bi-check2" aria-hidden="true"></i> Save enrollment</button></div></div>
        </div>
        <aside class="learner-summary" aria-labelledby="summary-title">
            <div class="learner-summary-heading"><span class="learner-summary-icon"><i class="bi bi-clipboard-check" aria-hidden="true"></i></span><h2 id="summary-title">Enrollment Overview</h2><p>A quick look before you save.</p></div>
            <dl><dt>School</dt><dd><?php echo e($school->name); ?></dd><dt>School year</dt><dd id="summary-year"><?php echo e($selected_year_label); ?></dd><dt>Grade level</dt><dd id="summary-grade"><?php echo e($enrollment ? $enrollment->grade_level : 'Not selected'); ?></dd><dt>Section</dt><dd id="summary-section"><?php echo e($enrollment && $enrollment->section ? $enrollment->section : 'Not specified'); ?></dd></dl>
            <div class="learner-summary-programs"><p>Program tags <span id="summary-tag-count"><?php echo count($selected_tags); ?></span></p><div id="summary-tags"><?php if ( ! $selected_tags): ?><span class="learner-summary-empty">No programs selected</span><?php else: foreach ($selected_tags as $code): if ( ! is_string($code) || ! programs($code)) continue; ?><span class="learner-summary-tag"><?php echo e($code === 'spfl' ? 'FL / SPFL' : programs($code)['short']); ?></span><?php endforeach; endif; ?></div></div>
            <div class="learner-summary-note"><i class="bi bi-shield-check" aria-hidden="true"></i><p>This enrollment is linked to your school. Records from previous years are preserved.</p></div>
        </aside>
    </div>
    <?php echo form_close(); ?>
</div>
<script>
(function () {
    var select = document.getElementById('learner-id');
    var fields = document.getElementById('new-learner-fields');
    if (select && fields) {
        function updateIdentity() {
            var existing = select.value !== '0';
            fields.classList.toggle('d-none', existing);
            fields.querySelectorAll('input, select').forEach(function (input) {
                input.disabled = existing;
                if (input.hasAttribute('data-new-required')) input.required = !existing;
            });
        }
        select.addEventListener('change', updateIdentity);
        updateIdentity();
    }
    var year = document.getElementById('school-year');
    var grade = document.getElementById('grade-level');
    var section = document.getElementById('section');
    var tags = document.querySelectorAll('.learner-program-option input');
    function updateSummary() {
        document.getElementById('summary-year').textContent = year.value ? year.options[year.selectedIndex].text : 'Not selected';
        document.getElementById('summary-grade').textContent = grade.value || 'Not selected';
        document.getElementById('summary-section').textContent = section.value.trim() || 'Not specified';
        var selected = Array.from(tags).filter(function (input) { return input.checked; });
        document.getElementById('summary-tag-count').textContent = selected.length;
        var container = document.getElementById('summary-tags');
        container.textContent = '';
        selected.forEach(function (input) {
            var tag = document.createElement('span');
            tag.className = 'learner-summary-tag';
            tag.textContent = input.nextElementSibling.querySelector('strong').textContent;
            container.appendChild(tag);
        });
        if (!selected.length) {
            var empty = document.createElement('span');
            empty.className = 'learner-summary-empty';
            empty.textContent = 'No programs selected';
            container.appendChild(empty);
        }
    }
    year.addEventListener('change', updateSummary);
    grade.addEventListener('change', updateSummary);
    section.addEventListener('input', updateSummary);
    tags.forEach(function (input) { input.addEventListener('change', updateSummary); });
    updateSummary();
    var programOptions = document.querySelectorAll('.learner-program-option input');
    function updateProgramFields() {
        programOptions.forEach(function (input) {
            var panel = document.querySelector('[data-program-fields="' + input.value + '"]');
            if (!panel) return;
            panel.classList.toggle('d-none', !input.checked);
            panel.querySelectorAll('input, select, textarea').forEach(function (field) {
                field.disabled = !input.checked;
            });
        });
    }
    programOptions.forEach(function (input) { input.addEventListener('change', updateProgramFields); });
    updateProgramFields();
}());
</script>
