CREATE TABLE IF NOT EXISTS uie_learner_program_tags (
    learner_school_record_id INT UNSIGNED NOT NULL,
    program ENUM('sned','madrasah','iped','spfl') NOT NULL,
    PRIMARY KEY (learner_school_record_id, program),
    CONSTRAINT fk_program_tag_enrollment FOREIGN KEY (learner_school_record_id)
        REFERENCES uie_learner_school_records(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Preserve existing role grants while exposing the permission names used by controllers.
INSERT IGNORE INTO uie_permissions (module, name, slug, description)
SELECT 'learners', name, CONCAT('learners.', SUBSTRING_INDEX(slug, '.', -1)), description
FROM uie_permissions
WHERE SUBSTRING_INDEX(slug, '.', 1) IN ('ipdms_learners', 'uie_learners');

INSERT IGNORE INTO uie_role_permissions (role_id, permission_id)
SELECT rp.role_id, canonical.id
FROM uie_role_permissions rp
JOIN uie_permissions legacy ON legacy.id = rp.permission_id
JOIN uie_permissions canonical ON canonical.slug = CONCAT('learners.', SUBSTRING_INDEX(legacy.slug, '.', -1))
WHERE SUBSTRING_INDEX(legacy.slug, '.', 1) IN ('ipdms_learners', 'uie_learners');
