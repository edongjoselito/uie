-- ============================================================================
-- UIEIMS - Unified Inclusive Education Information and Management System
-- Database Schema (Phase 1: Foundation + full common/program table structure)
-- Engine: InnoDB | Charset: utf8mb4
-- ============================================================================

CREATE DATABASE IF NOT EXISTS uie_db
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE uie_db;

SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================================
-- 1. ORGANIZATIONAL HIERARCHY
-- ============================================================================

DROP TABLE IF EXISTS uie_regions;
CREATE TABLE uie_regions (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    code            VARCHAR(20)  NOT NULL,
    name            VARCHAR(150) NOT NULL,
    description     TEXT         NULL,
    status          ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_by      INT UNSIGNED NULL,
    updated_by      INT UNSIGNED NULL,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_regions_code (code)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS uie_divisions;
CREATE TABLE uie_divisions (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    code            VARCHAR(20)  NOT NULL,
    name            VARCHAR(150) NOT NULL,
    region_id       INT UNSIGNED NOT NULL,
    status          ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_by      INT UNSIGNED NULL,
    updated_by      INT UNSIGNED NULL,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_divisions_code (code),
    KEY idx_divisions_region (region_id),
    CONSTRAINT fk_divisions_region FOREIGN KEY (region_id) REFERENCES uie_regions(id)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS uie_schools;
CREATE TABLE uie_schools (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    school_id       VARCHAR(20)  NOT NULL COMMENT 'Official DepEd School ID',
    name            VARCHAR(200) NOT NULL,
    region_id       INT UNSIGNED NOT NULL,
    division_id     INT UNSIGNED NOT NULL,
    municipality    VARCHAR(120) NULL,
    barangay        VARCHAR(120) NULL,
    classification  VARCHAR(100) NULL COMMENT 'e.g. Elementary, Secondary, Integrated',
    school_type     VARCHAR(50)  NULL COMMENT 'e.g. Public, Private, SUC/LUC',
    status          ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_by      INT UNSIGNED NULL,
    updated_by      INT UNSIGNED NULL,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_schools_deped_id (school_id),
    KEY idx_schools_region (region_id),
    KEY idx_schools_division (division_id),
    CONSTRAINT fk_schools_region   FOREIGN KEY (region_id)   REFERENCES uie_regions(id),
    CONSTRAINT fk_schools_division FOREIGN KEY (division_id) REFERENCES uie_divisions(id)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS uie_school_years;
CREATE TABLE uie_school_years (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    year_label      VARCHAR(9)   NOT NULL COMMENT 'Format: YYYY-YYYY',
    start_year      SMALLINT UNSIGNED NOT NULL,
    end_year        SMALLINT UNSIGNED NOT NULL,
    is_active       TINYINT(1)   NOT NULL DEFAULT 0,
    status          ENUM('open','closed') NOT NULL DEFAULT 'open',
    created_by      INT UNSIGNED NULL,
    updated_by      INT UNSIGNED NULL,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_sy_label (year_label)
) ENGINE=InnoDB;

-- ============================================================================
-- 2. USERS, ROLES, PERMISSIONS
-- ============================================================================

DROP TABLE IF EXISTS uie_roles;
CREATE TABLE uie_roles (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name            VARCHAR(60)  NOT NULL,
    slug            VARCHAR(60)  NOT NULL,
    description     VARCHAR(255) NULL,
    level           ENUM('system','region','division','school') NOT NULL DEFAULT 'school',
    is_system       TINYINT(1)   NOT NULL DEFAULT 0 COMMENT 'System uie_roles cannot be deleted',
    status          ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_roles_slug (slug)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS uie_permissions;
CREATE TABLE uie_permissions (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    module          VARCHAR(50)  NOT NULL,
    name            VARCHAR(80)  NOT NULL,
    slug            VARCHAR(80)  NOT NULL,
    description     VARCHAR(255) NULL,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_permissions_slug (slug)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS uie_role_permissions;
CREATE TABLE uie_role_permissions (
    role_id         INT UNSIGNED NOT NULL,
    permission_id   INT UNSIGNED NOT NULL,
    PRIMARY KEY (role_id, permission_id),
    CONSTRAINT fk_rp_role FOREIGN KEY (role_id)       REFERENCES uie_roles(id)       ON DELETE CASCADE,
    CONSTRAINT fk_rp_perm FOREIGN KEY (permission_id) REFERENCES uie_permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB;

DROP TABLE IF EXISTS uie_users;
CREATE TABLE uie_users (
    id                   INT UNSIGNED NOT NULL AUTO_INCREMENT,
    username             VARCHAR(60)  NOT NULL,
    email                VARCHAR(120) NOT NULL,
    password_hash        VARCHAR(255) NOT NULL,
    first_name           VARCHAR(80)  NOT NULL,
    middle_name          VARCHAR(80)  NULL,
    last_name            VARCHAR(80)  NOT NULL,
    suffix               VARCHAR(20)  NULL,
    position             VARCHAR(100) NULL,
    contact_number       VARCHAR(30)  NULL,
    status               ENUM('active','inactive','locked') NOT NULL DEFAULT 'active',
    failed_attempts      TINYINT UNSIGNED NOT NULL DEFAULT 0,
    locked_until         DATETIME     NULL,
    last_login_at        DATETIME     NULL,
    last_login_ip        VARCHAR(45)  NULL,
    password_changed_at  DATETIME     NULL,
    must_change_password TINYINT(1)   NOT NULL DEFAULT 0,
    created_by           INT UNSIGNED NULL,
    updated_by           INT UNSIGNED NULL,
    created_at           DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at           DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_username (username),
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS uie_user_roles;
CREATE TABLE uie_user_roles (
    user_id         INT UNSIGNED NOT NULL,
    role_id         INT UNSIGNED NOT NULL,
    PRIMARY KEY (user_id, role_id),
    CONSTRAINT fk_ur_user FOREIGN KEY (user_id) REFERENCES uie_users(id) ON DELETE CASCADE,
    CONSTRAINT fk_ur_role FOREIGN KEY (role_id) REFERENCES uie_roles(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Organizational assignment of a user (region / division / school scope)
DROP TABLE IF EXISTS uie_user_assignments;
CREATE TABLE uie_user_assignments (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id         INT UNSIGNED NOT NULL,
    region_id       INT UNSIGNED NULL,
    division_id     INT UNSIGNED NULL,
    school_id       INT UNSIGNED NULL,
    is_primary      TINYINT(1)   NOT NULL DEFAULT 1,
    status          ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_by      INT UNSIGNED NULL,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_ua_user    (user_id),
    KEY idx_ua_region  (region_id),
    KEY idx_ua_div     (division_id),
    KEY idx_ua_school  (school_id),
    CONSTRAINT fk_ua_user   FOREIGN KEY (user_id)     REFERENCES uie_users(id)     ON DELETE CASCADE,
    CONSTRAINT fk_ua_region FOREIGN KEY (region_id)   REFERENCES uie_regions(id),
    CONSTRAINT fk_ua_div    FOREIGN KEY (division_id) REFERENCES uie_divisions(id),
    CONSTRAINT fk_ua_school FOREIGN KEY (school_id)   REFERENCES uie_schools(id)
) ENGINE=InnoDB;

-- School staff mapping (School Administrator / Encoder rosters per school)
DROP TABLE IF EXISTS uie_school_users;
CREATE TABLE uie_school_users (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    school_id       INT UNSIGNED NOT NULL,
    user_id         INT UNSIGNED NOT NULL,
    school_role     ENUM('administrator','encoder','viewer') NOT NULL DEFAULT 'encoder',
    is_primary      TINYINT(1)   NOT NULL DEFAULT 1,
    status          ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_by      INT UNSIGNED NULL,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_school_user (school_id, user_id, school_role),
    CONSTRAINT fk_su_school FOREIGN KEY (school_id) REFERENCES uie_schools(id),
    CONSTRAINT fk_su_user   FOREIGN KEY (user_id)   REFERENCES uie_users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================================
-- 3. CENTRALIZED LEARNER REGISTRY
-- ============================================================================

DROP TABLE IF EXISTS uie_learners;
CREATE TABLE uie_learners (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    lrn             VARCHAR(12)  NULL COMMENT 'Learner Reference Number (unique when present)',
    last_name       VARCHAR(80)  NOT NULL,
    first_name      VARCHAR(80)  NOT NULL,
    middle_name     VARCHAR(80)  NULL,
    extension_name  VARCHAR(20)  NULL COMMENT 'Jr., Sr., III, etc.',
    sex             ENUM('Male','Female') NOT NULL,
    date_of_birth   DATE         NULL,
    place_of_birth  VARCHAR(200) NULL,
    address         VARCHAR(255) NULL,
    barangay        VARCHAR(120) NULL,
    municipality    VARCHAR(120) NULL,
    province        VARCHAR(120) NULL,
    father_name     VARCHAR(160) NULL,
    mother_name     VARCHAR(160) NULL,
    guardian_name   VARCHAR(160) NULL,
    guardian_relationship VARCHAR(60) NULL,
    contact_number  VARCHAR(30)  NULL,
    status          ENUM('active','archived') NOT NULL DEFAULT 'active',
    remarks         TEXT         NULL,
    created_by      INT UNSIGNED NULL,
    updated_by      INT UNSIGNED NULL,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_learners_lrn (lrn),
    KEY idx_learners_name (last_name, first_name),
    KEY idx_learners_muni (municipality)
) ENGINE=InnoDB;

-- Enrollment / school placement history (one row per school per school year)
DROP TABLE IF EXISTS uie_learner_school_records;
CREATE TABLE uie_learner_school_records (
    id                INT UNSIGNED NOT NULL AUTO_INCREMENT,
    learner_id        INT UNSIGNED NOT NULL,
    school_id         INT UNSIGNED NOT NULL,
    division_id       INT UNSIGNED NOT NULL,
    region_id         INT UNSIGNED NOT NULL,
    school_year_id    INT UNSIGNED NOT NULL,
    grade_level       VARCHAR(25)  NULL,
    section           VARCHAR(60)  NULL,
    curriculum        VARCHAR(60)  NULL,
    enrollment_status ENUM('Enrolled','Transferred In','Transferred Out','Dropped Out','Completed','No Longer Enrolled') NOT NULL DEFAULT 'Enrolled',
    previous_school_id INT UNSIGNED NULL,
    is_current        TINYINT(1)   NOT NULL DEFAULT 1,
    status            ENUM('active','archived') NOT NULL DEFAULT 'active',
    created_by        INT UNSIGNED NULL,
    updated_by        INT UNSIGNED NULL,
    created_at        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_lsr (learner_id, school_id, school_year_id),
    KEY idx_lsr_school   (school_id, school_year_id),
    KEY idx_lsr_division (division_id, school_year_id),
    KEY idx_lsr_region   (region_id, school_year_id),
    CONSTRAINT fk_lsr_learner FOREIGN KEY (learner_id)     REFERENCES uie_learners(id),
    CONSTRAINT fk_lsr_school  FOREIGN KEY (school_id)      REFERENCES uie_schools(id),
    CONSTRAINT fk_lsr_div     FOREIGN KEY (division_id)    REFERENCES uie_divisions(id),
    CONSTRAINT fk_lsr_region  FOREIGN KEY (region_id)      REFERENCES uie_regions(id),
    CONSTRAINT fk_lsr_sy      FOREIGN KEY (school_year_id) REFERENCES uie_school_years(id)
) ENGINE=InnoDB;

-- ============================================================================
-- 4. PROGRAM TABLES
-- ============================================================================

-- ---------- IPEd ----------
DROP TABLE IF EXISTS uie_iped_ethnic_groups;
CREATE TABLE uie_iped_ethnic_groups (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name        VARCHAR(120) NOT NULL,
    description VARCHAR(255) NULL,
    status      ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_iped_ethnic (name)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS uie_iped_cultural_communities;
CREATE TABLE uie_iped_cultural_communities (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name        VARCHAR(120) NOT NULL,
    description VARCHAR(255) NULL,
    status      ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_iped_icc (name)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS uie_iped_program_references;
CREATE TABLE uie_iped_program_references (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    ref_type    VARCHAR(60)  NOT NULL COMMENT 'e.g. program_participation, assistance, language',
    code        VARCHAR(60)  NULL,
    name        VARCHAR(150) NOT NULL,
    description VARCHAR(255) NULL,
    status      ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_iped_ref_type (ref_type)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS uie_iped_learner_profiles;
CREATE TABLE uie_iped_learner_profiles (
    id                    INT UNSIGNED NOT NULL AUTO_INCREMENT,
    learner_id            INT UNSIGNED NOT NULL,
    learner_school_record_id INT UNSIGNED NOT NULL COMMENT 'Enrollment row this profile applies to',
    icc_id                INT UNSIGNED NULL COMMENT 'Indigenous Cultural Community',
    ethnic_group_id       INT UNSIGNED NULL,
    tribe                 VARCHAR(120) NULL,
    indigenous_language   VARCHAR(120) NULL,
    mother_tongue         VARCHAR(120) NULL,
    ancestral_area        VARCHAR(200) NULL,
    other_cultural_info   TEXT         NULL,
    program_participation VARCHAR(150) NULL,
    educational_assistance VARCHAR(255) NULL,
    program_support       VARCHAR(255) NULL,
    community_participation VARCHAR(255) NULL,
    remarks               TEXT         NULL,
    status                ENUM('active','archived') NOT NULL DEFAULT 'active',
    created_by            INT UNSIGNED NULL,
    updated_by            INT UNSIGNED NULL,
    created_at            DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at            DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_iped_profile (learner_school_record_id),
    KEY idx_iped_learner (learner_id),
    CONSTRAINT fk_iped_lsr FOREIGN KEY (learner_school_record_id) REFERENCES uie_learner_school_records(id),
    CONSTRAINT fk_iped_lrn FOREIGN KEY (learner_id) REFERENCES uie_learners(id),
    CONSTRAINT fk_iped_icc FOREIGN KEY (icc_id) REFERENCES uie_iped_cultural_communities(id),
    CONSTRAINT fk_iped_eth FOREIGN KEY (ethnic_group_id) REFERENCES uie_iped_ethnic_groups(id)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS uie_iped_learner_history;
CREATE TABLE uie_iped_learner_history (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    profile_id  INT UNSIGNED NOT NULL,
    change_type VARCHAR(30)  NOT NULL,
    old_values  JSON         NULL,
    new_values  JSON         NULL,
    changed_by  INT UNSIGNED NULL,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_iped_hist (profile_id)
) ENGINE=InnoDB;

-- ---------- Madrasah ----------
DROP TABLE IF EXISTS uie_madrasah_programs;
CREATE TABLE uie_madrasah_programs (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    code        VARCHAR(60)  NULL,
    name        VARCHAR(150) NOT NULL,
    description VARCHAR(255) NULL,
    status      ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_madrasah_program (name)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS uie_madrasah_program_references;
CREATE TABLE uie_madrasah_program_references (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    ref_type    VARCHAR(60)  NOT NULL COMMENT 'e.g. participation_status, proficiency, assistance',
    code        VARCHAR(60)  NULL,
    name        VARCHAR(150) NOT NULL,
    description VARCHAR(255) NULL,
    status      ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_mad_ref_type (ref_type)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS uie_madrasah_learner_profiles;
CREATE TABLE uie_madrasah_learner_profiles (
    id                    INT UNSIGNED NOT NULL AUTO_INCREMENT,
    learner_id            INT UNSIGNED NOT NULL,
    learner_school_record_id INT UNSIGNED NOT NULL,
    alive_participation   ENUM('Yes','No') NULL,
    arabic_proficiency    VARCHAR(60)  NULL,
    islamic_values_participation ENUM('Yes','No') NULL,
    years_in_program      TINYINT UNSIGNED NULL,
    participation_status  VARCHAR(60)  NULL,
    educational_assistance VARCHAR(255) NULL,
    program_support       VARCHAR(255) NULL,
    remarks               TEXT         NULL,
    status                ENUM('active','archived') NOT NULL DEFAULT 'active',
    created_by            INT UNSIGNED NULL,
    updated_by            INT UNSIGNED NULL,
    created_at            DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at            DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_mad_profile (learner_school_record_id),
    KEY idx_mad_learner (learner_id),
    CONSTRAINT fk_mad_lsr FOREIGN KEY (learner_school_record_id) REFERENCES uie_learner_school_records(id),
    CONSTRAINT fk_mad_lrn FOREIGN KEY (learner_id) REFERENCES uie_learners(id)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS uie_madrasah_learner_programs;
CREATE TABLE uie_madrasah_learner_programs (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    profile_id  INT UNSIGNED NOT NULL,
    program_id  INT UNSIGNED NOT NULL,
    school_year_id INT UNSIGNED NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_mad_lp (profile_id, program_id, school_year_id),
    CONSTRAINT fk_mlp_profile FOREIGN KEY (profile_id) REFERENCES uie_madrasah_learner_profiles(id) ON DELETE CASCADE,
    CONSTRAINT fk_mlp_program FOREIGN KEY (program_id) REFERENCES uie_madrasah_programs(id)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS uie_madrasah_learner_history;
CREATE TABLE uie_madrasah_learner_history (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    profile_id  INT UNSIGNED NOT NULL,
    change_type VARCHAR(30)  NOT NULL,
    old_values  JSON         NULL,
    new_values  JSON         NULL,
    changed_by  INT UNSIGNED NULL,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_mad_hist (profile_id)
) ENGINE=InnoDB;

-- ---------- SNED ----------
DROP TABLE IF EXISTS uie_sned_disability_categories;
CREATE TABLE uie_sned_disability_categories (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    code        VARCHAR(40)  NULL,
    name        VARCHAR(150) NOT NULL,
    description VARCHAR(255) NULL,
    status      ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_sned_disability (name)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS uie_sned_learner_profiles;
CREATE TABLE uie_sned_learner_profiles (
    id                    INT UNSIGNED NOT NULL AUTO_INCREMENT,
    learner_id            INT UNSIGNED NOT NULL,
    learner_school_record_id INT UNSIGNED NOT NULL,
    functional_needs      TEXT         NULL,
    assistive_devices     VARCHAR(255) NULL,
    accessibility_requirements VARCHAR(255) NULL,
    iep_status            ENUM('None','In Development','Implemented','Under Review') NOT NULL DEFAULT 'None',
    accommodations        TEXT         NULL,
    assistive_technology  VARCHAR(255) NULL,
    interventions         TEXT         NULL,
    therapy_support       VARCHAR(255) NULL,
    support_services      VARCHAR(255) NULL,
    progress_monitoring   TEXT         NULL,
    referral_info         VARCHAR(255) NULL,
    doc_reference         VARCHAR(200) NULL COMMENT 'Assessment documentation reference',
    last_profile_update   DATETIME     NULL,
    remarks               TEXT         NULL,
    status                ENUM('active','archived') NOT NULL DEFAULT 'active',
    created_by            INT UNSIGNED NULL,
    updated_by            INT UNSIGNED NULL,
    created_at            DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at            DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_sned_profile (learner_school_record_id),
    KEY idx_sned_learner (learner_id),
    CONSTRAINT fk_sned_lsr FOREIGN KEY (learner_school_record_id) REFERENCES uie_learner_school_records(id),
    CONSTRAINT fk_sned_lrn FOREIGN KEY (learner_id) REFERENCES uie_learners(id)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS uie_sned_learner_disabilities;
CREATE TABLE uie_sned_learner_disabilities (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    profile_id    INT UNSIGNED NOT NULL,
    category_id   INT UNSIGNED NOT NULL,
    is_primary    TINYINT(1)   NOT NULL DEFAULT 0,
    remarks       VARCHAR(255) NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_sned_ld (profile_id, category_id, is_primary),
    CONSTRAINT fk_sld_profile  FOREIGN KEY (profile_id)  REFERENCES uie_sned_learner_profiles(id) ON DELETE CASCADE,
    CONSTRAINT fk_sld_category FOREIGN KEY (category_id) REFERENCES uie_sned_disability_categories(id)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS uie_sned_assessments;
CREATE TABLE uie_sned_assessments (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    profile_id    INT UNSIGNED NOT NULL,
    assessment_date DATE       NULL,
    assessor      VARCHAR(150) NULL,
    assessment_status ENUM('Pending','Scheduled','Completed','For Re-assessment') NOT NULL DEFAULT 'Pending',
    doc_reference VARCHAR(200) NULL,
    findings      TEXT         NULL,
    created_by    INT UNSIGNED NULL,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_sned_assess (profile_id),
    CONSTRAINT fk_sa_profile FOREIGN KEY (profile_id) REFERENCES uie_sned_learner_profiles(id) ON DELETE CASCADE
) ENGINE=InnoDB;

DROP TABLE IF EXISTS uie_sned_interventions;
CREATE TABLE uie_sned_interventions (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    profile_id    INT UNSIGNED NOT NULL,
    intervention_type VARCHAR(120) NULL,
    description   TEXT         NULL,
    provider      VARCHAR(150) NULL,
    start_date    DATE         NULL,
    end_date      DATE         NULL,
    status        ENUM('Planned','Ongoing','Completed','Discontinued') NOT NULL DEFAULT 'Planned',
    created_by    INT UNSIGNED NULL,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_sned_interv (profile_id),
    CONSTRAINT fk_si_profile FOREIGN KEY (profile_id) REFERENCES uie_sned_learner_profiles(id) ON DELETE CASCADE
) ENGINE=InnoDB;

DROP TABLE IF EXISTS uie_sned_iep_records;
CREATE TABLE uie_sned_iep_records (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    profile_id    INT UNSIGNED NOT NULL,
    school_year_id INT UNSIGNED NULL,
    iep_status    ENUM('In Development','Implemented','Under Review','Completed') NOT NULL DEFAULT 'In Development',
    goals         TEXT         NULL,
    developed_date DATE        NULL,
    review_date   DATE         NULL,
    created_by    INT UNSIGNED NULL,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_sned_iep (profile_id),
    CONSTRAINT fk_iep_profile FOREIGN KEY (profile_id) REFERENCES uie_sned_learner_profiles(id) ON DELETE CASCADE
) ENGINE=InnoDB;

DROP TABLE IF EXISTS uie_sned_support_services;
CREATE TABLE uie_sned_support_services (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    profile_id    INT UNSIGNED NOT NULL,
    service_type  VARCHAR(120) NULL,
    description   VARCHAR(255) NULL,
    provider      VARCHAR(150) NULL,
    frequency     VARCHAR(60)  NULL,
    status        ENUM('Active','Completed','Discontinued') NOT NULL DEFAULT 'Active',
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_sned_svc (profile_id),
    CONSTRAINT fk_ss_profile FOREIGN KEY (profile_id) REFERENCES uie_sned_learner_profiles(id) ON DELETE CASCADE
) ENGINE=InnoDB;

DROP TABLE IF EXISTS uie_sned_learner_history;
CREATE TABLE uie_sned_learner_history (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    profile_id  INT UNSIGNED NOT NULL,
    change_type VARCHAR(30)  NOT NULL,
    old_values  JSON         NULL,
    new_values  JSON         NULL,
    changed_by  INT UNSIGNED NULL,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_sned_hist (profile_id)
) ENGINE=InnoDB;

-- ============================================================================
-- 5. SUBMISSION / VALIDATION WORKFLOW
-- ============================================================================

DROP TABLE IF EXISTS uie_data_submissions;
CREATE TABLE uie_data_submissions (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    school_id       INT UNSIGNED NOT NULL,
    division_id     INT UNSIGNED NOT NULL,
    region_id       INT UNSIGNED NOT NULL,
    school_year_id  INT UNSIGNED NOT NULL,
    program         ENUM('iped','madrasah','sned','consolidated') NOT NULL DEFAULT 'consolidated',
    status          ENUM('draft','for_review','submitted','under_validation','returned','resubmitted','validated','archived') NOT NULL DEFAULT 'draft',
    submitted_by    INT UNSIGNED NULL,
    submitted_at    DATETIME     NULL,
    validated_by    INT UNSIGNED NULL,
    validated_at    DATETIME     NULL,
    remarks         TEXT         NULL,
    created_by      INT UNSIGNED NULL,
    updated_by      INT UNSIGNED NULL,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_ds_school   (school_id, school_year_id),
    KEY idx_ds_division (division_id, school_year_id),
    KEY idx_ds_status   (status),
    CONSTRAINT fk_ds_school FOREIGN KEY (school_id)   REFERENCES uie_schools(id),
    CONSTRAINT fk_ds_div    FOREIGN KEY (division_id) REFERENCES uie_divisions(id),
    CONSTRAINT fk_ds_region FOREIGN KEY (region_id)   REFERENCES uie_regions(id),
    CONSTRAINT fk_ds_sy     FOREIGN KEY (school_year_id) REFERENCES uie_school_years(id)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS uie_data_submission_details;
CREATE TABLE uie_data_submission_details (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    submission_id   INT UNSIGNED NOT NULL,
    learner_id      INT UNSIGNED NOT NULL,
    program         ENUM('iped','madrasah','sned') NOT NULL,
    record_status   ENUM('pending','validated','returned') NOT NULL DEFAULT 'pending',
    remarks         VARCHAR(255) NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_dsd (submission_id, learner_id, program),
    CONSTRAINT fk_dsd_sub FOREIGN KEY (submission_id) REFERENCES uie_data_submissions(id) ON DELETE CASCADE,
    CONSTRAINT fk_dsd_lrn FOREIGN KEY (learner_id)    REFERENCES uie_learners(id)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS uie_data_validation_logs;
CREATE TABLE uie_data_validation_logs (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    submission_id   INT UNSIGNED NOT NULL,
    detail_id       INT UNSIGNED NULL,
    action          ENUM('submit','review','approve','return','resubmit','archive','note') NOT NULL,
    remarks         TEXT         NULL,
    acted_by        INT UNSIGNED NULL,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_dvl_sub (submission_id),
    CONSTRAINT fk_dvl_sub FOREIGN KEY (submission_id) REFERENCES uie_data_submissions(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Per-record lock/status for learner records flowing through the workflow
DROP TABLE IF EXISTS uie_learner_record_history;
CREATE TABLE uie_learner_record_history (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    learner_id      INT UNSIGNED NOT NULL,
    table_name      VARCHAR(64)  NOT NULL,
    record_id       INT UNSIGNED NOT NULL,
    change_type     VARCHAR(30)  NOT NULL COMMENT 'create, update, submit, validate, return, archive, restore',
    old_values      JSON         NULL,
    new_values      JSON         NULL,
    remarks         VARCHAR(255) NULL,
    changed_by      INT UNSIGNED NULL,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_lrh_learner (learner_id),
    KEY idx_lrh_record  (table_name, record_id)
) ENGINE=InnoDB;

-- School-level submission tracking (Phase 3+; header/history pair)
DROP TABLE IF EXISTS uie_school_submissions;
CREATE TABLE uie_school_submissions (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    school_id       INT UNSIGNED NOT NULL,
    school_year_id  INT UNSIGNED NOT NULL,
    program         ENUM('iped','madrasah','sned','consolidated') NOT NULL DEFAULT 'consolidated',
    status          ENUM('draft','for_review','submitted','under_validation','returned','resubmitted','validated') NOT NULL DEFAULT 'draft',
    record_count    INT UNSIGNED NOT NULL DEFAULT 0,
    submitted_by    INT UNSIGNED NULL,
    submitted_at    DATETIME     NULL,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_ss_school (school_id, school_year_id)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS uie_school_submission_history;
CREATE TABLE uie_school_submission_history (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    submission_id   INT UNSIGNED NOT NULL,
    action          VARCHAR(40)  NOT NULL,
    remarks         TEXT         NULL,
    acted_by        INT UNSIGNED NULL,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_ssh_sub (submission_id),
    CONSTRAINT fk_ssh_sub FOREIGN KEY (submission_id) REFERENCES uie_school_submissions(id) ON DELETE CASCADE
) ENGINE=InnoDB;

DROP TABLE IF EXISTS uie_school_data_completeness;
CREATE TABLE uie_school_data_completeness (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    school_id       INT UNSIGNED NOT NULL,
    school_year_id  INT UNSIGNED NOT NULL,
    program         ENUM('iped','madrasah','sned','overall') NOT NULL DEFAULT 'overall',
    total_records   INT UNSIGNED NOT NULL DEFAULT 0,
    complete_records INT UNSIGNED NOT NULL DEFAULT 0,
    incomplete_records INT UNSIGNED NOT NULL DEFAULT 0,
    percentage      DECIMAL(5,2) NOT NULL DEFAULT 0,
    computed_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_sdc (school_id, school_year_id, program)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS uie_school_import_logs;
CREATE TABLE uie_school_import_logs (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    school_id       INT UNSIGNED NULL,
    user_id         INT UNSIGNED NULL,
    program         VARCHAR(30)  NULL,
    filename        VARCHAR(200) NULL,
    total_rows      INT UNSIGNED NOT NULL DEFAULT 0,
    imported        INT UNSIGNED NOT NULL DEFAULT 0,
    failed          INT UNSIGNED NOT NULL DEFAULT 0,
    error_log       TEXT         NULL,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_sil_school (school_id)
) ENGINE=InnoDB;

-- ============================================================================
-- 6. SYSTEM TABLES
-- ============================================================================

DROP TABLE IF EXISTS uie_system_modules;
CREATE TABLE uie_system_modules (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    code        VARCHAR(40)  NOT NULL,
    name        VARCHAR(120) NOT NULL,
    description VARCHAR(255) NULL,
    status      ENUM('enabled','disabled') NOT NULL DEFAULT 'enabled',
    sort_order  TINYINT UNSIGNED NOT NULL DEFAULT 0,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_modules_code (code)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS uie_audit_logs;
CREATE TABLE uie_audit_logs (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id     INT UNSIGNED NULL,
    username    VARCHAR(60)  NULL,
    action      VARCHAR(80)  NOT NULL,
    module      VARCHAR(50)  NULL,
    table_name  VARCHAR(64)  NULL,
    record_id   VARCHAR(40)  NULL,
    description VARCHAR(500) NULL,
    old_values  JSON         NULL,
    new_values  JSON         NULL,
    ip_address  VARCHAR(45)  NULL,
    user_agent  VARCHAR(255) NULL,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_audit_user   (user_id),
    KEY idx_audit_table  (table_name, record_id),
    KEY idx_audit_action (action),
    KEY idx_audit_date   (created_at)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS uie_notifications;
CREATE TABLE uie_notifications (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id     INT UNSIGNED NOT NULL,
    title       VARCHAR(150) NOT NULL,
    message     VARCHAR(500) NULL,
    link        VARCHAR(255) NULL,
    is_read     TINYINT(1)   NOT NULL DEFAULT 0,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_notif_user (user_id, is_read),
    CONSTRAINT fk_notif_user FOREIGN KEY (user_id) REFERENCES uie_users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

DROP TABLE IF EXISTS uie_system_settings;
CREATE TABLE uie_system_settings (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    setting_key VARCHAR(80)  NOT NULL,
    setting_value TEXT       NULL,
    description VARCHAR(255) NULL,
    updated_by  INT UNSIGNED NULL,
    updated_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_settings_key (setting_key)
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- SEED DATA
-- ============================================================================

-- Regions (Philippines)
INSERT INTO uie_regions (code, name, description) VALUES
('NCR',    'National Capital Region', 'Metro Manila'),
('CAR',    'Cordillera Administrative Region', NULL),
('R-I',    'Region I - Ilocos Region', NULL),
('R-II',   'Region II - Cagayan Valley', NULL),
('R-III',  'Region III - Central Luzon', NULL),
('R-IVA',  'Region IV-A - CALABARZON', NULL),
('MIMAROPA','MIMAROPA Region', NULL),
('R-V',    'Region V - Bicol Region', NULL),
('NIR',    'Negros Island Region', NULL),
('R-VI',   'Region VI - Western Visayas', NULL),
('R-VII',  'Region VII - Central Visayas', NULL),
('R-VIII', 'Region VIII - Eastern Visayas', NULL),
('R-IX',   'Region IX - Zamboanga Peninsula', NULL),
('R-X',    'Region X - Northern Mindanao', NULL),
('R-XI',   'Region XI - Davao Region', NULL),
('R-XII',  'Region XII - SOCCSKSARGEN', NULL),
('R-XIII', 'Region XIII - Caraga', NULL),
('BARMM',  'Bangsamoro Autonomous Region in Muslim Mindanao', NULL);

-- School years
INSERT INTO uie_school_years (year_label, start_year, end_year, is_active) VALUES
('2020-2021', 2020, 2021, 0),
('2021-2022', 2021, 2022, 0),
('2022-2023', 2022, 2023, 0),
('2023-2024', 2023, 2024, 0),
('2024-2025', 2024, 2025, 0),
('2025-2026', 2025, 2026, 1),
('2026-2027', 2026, 2027, 0);

-- Roles
INSERT INTO uie_roles (name, slug, description, level, is_system) VALUES
('Super Administrator',    'superadmin',      'Full system administration',            'system',   1),
('Regional Administrator', 'regional_admin',  'Regional office data administrator',    'region',   1),
('Division Administrator', 'division_admin',  'Schools Division Office administrator', 'division', 1),
('School Administrator',   'school_admin',    'School-level data administrator',       'school',   1),
('School Encoder',         'school_encoder',  'School-level data encoder',             'school',   1),
('Regional Viewer',        'regional_viewer', 'Read-only regional access',             'region',   0),
('Division Viewer',        'division_viewer', 'Read-only division access',             'division', 0),
('School Viewer',          'school_viewer',   'Read-only school access',               'school',   0);

-- Permissions
INSERT INTO uie_permissions (module, name, slug) VALUES
('dashboard',   'View Dashboard',            'dashboard.view'),
('uie_regions',     'View Regions',              'uie_regions.view'),
('uie_regions',     'Create Regions',            'uie_regions.create'),
('uie_regions',     'Edit Regions',              'uie_regions.edit'),
('uie_regions',     'Deactivate Regions',        'uie_regions.deactivate'),
('uie_divisions',   'View Divisions',            'uie_divisions.view'),
('uie_divisions',   'Create Divisions',          'uie_divisions.create'),
('uie_divisions',   'Edit Divisions',            'uie_divisions.edit'),
('uie_divisions',   'Deactivate Divisions',      'uie_divisions.deactivate'),
('uie_schools',     'View Schools',              'uie_schools.view'),
('uie_schools',     'Create Schools',            'uie_schools.create'),
('uie_schools',     'Edit Schools',              'uie_schools.edit'),
('uie_schools',     'Deactivate Schools',        'uie_schools.deactivate'),
('uie_schools',     'Import School Masterlist',  'uie_schools.import'),
('uie_users',       'View Users',                'uie_users.view'),
('uie_users',       'Create Users',              'uie_users.create'),
('uie_users',       'Edit Users',                'uie_users.edit'),
('uie_users',       'Deactivate Users',          'uie_users.deactivate'),
('uie_users',       'Reset User Passwords',      'uie_users.reset_password'),
('uie_users',       'Assign Roles',              'uie_users.assign_roles'),
('uie_roles',       'Manage Roles',              'uie_roles.manage'),
('modules',     'Manage Modules',            'modules.manage'),
('settings',    'Manage Settings',           'settings.manage'),
('audit',       'View Audit Trail',          'audit.view'),
('uie_learners',    'View Learners',             'uie_learners.view'),
('uie_learners',    'Create Learners',           'uie_learners.create'),
('uie_learners',    'Edit Learners',             'uie_learners.edit'),
('uie_learners',    'Archive Learners',          'uie_learners.archive'),
('uie_learners',    'Restore Learners',          'uie_learners.restore'),
('uie_learners',    'Import Learners',           'uie_learners.import'),
('uie_learners',    'Export Learners',           'uie_learners.export'),
('uie_learners',    'Print Profiles',            'uie_learners.print'),
('uie_school_years','View School Years',         'uie_school_years.view'),
('uie_school_years','Manage School Years',       'uie_school_years.manage'),
('submissions', 'View Submissions',          'submissions.view'),
('submissions', 'Submit Records',            'submissions.submit'),
('submissions', 'Review Submissions',        'submissions.review'),
('submissions', 'Validate Submissions',      'submissions.validate'),
('submissions', 'Return for Correction',     'submissions.return'),
('reports',     'View Reports',              'reports.view'),
('reports',     'Export Reports',            'reports.export'),
('sned_sensitive','View Sensitive SNED Data','sned.sensitive'),
('profile',     'Edit Own Profile',          'profile.edit');

-- Role-permission mapping
INSERT INTO uie_role_permissions (role_id, permission_id)
SELECT 1, id FROM uie_permissions;                                                    -- superadmin: all

INSERT INTO uie_role_permissions (role_id, permission_id)
SELECT 2, id FROM uie_permissions WHERE slug IN (
  'dashboard.view','uie_learners.view','uie_learners.export','uie_learners.print',
  'uie_regions.view','uie_divisions.view','uie_schools.view','uie_school_years.view',
  'submissions.view','submissions.review','submissions.return',
  'reports.view','reports.export','sned.sensitive','profile.edit');                -- regional_admin

INSERT INTO uie_role_permissions (role_id, permission_id)
SELECT 3, id FROM uie_permissions WHERE slug IN (
  'dashboard.view','uie_learners.view','uie_learners.create','uie_learners.edit',
  'uie_learners.export','uie_learners.print','uie_learners.archive',
  'uie_schools.view','uie_school_years.view',
  'submissions.view','submissions.review','submissions.validate','submissions.return',
  'reports.view','reports.export','sned.sensitive','profile.edit');                -- division_admin

INSERT INTO uie_role_permissions (role_id, permission_id)
SELECT 4, id FROM uie_permissions WHERE slug IN (
  'dashboard.view','uie_learners.view','uie_learners.create','uie_learners.edit',
  'uie_learners.import','uie_learners.export','uie_learners.print',
  'submissions.view','submissions.submit','reports.view','reports.export',
  'uie_school_years.view','profile.edit');                                             -- school_admin

INSERT INTO uie_role_permissions (role_id, permission_id)
SELECT 5, id FROM uie_permissions WHERE slug IN (
  'dashboard.view','uie_learners.view','uie_learners.create','uie_learners.edit',
  'submissions.view','uie_school_years.view','profile.edit');                          -- school_encoder

INSERT INTO uie_role_permissions (role_id, permission_id)
SELECT 6, id FROM uie_permissions WHERE slug IN (
  'dashboard.view','uie_learners.view','uie_regions.view','uie_divisions.view','uie_schools.view',
  'submissions.view','reports.view','uie_school_years.view','profile.edit');           -- regional_viewer

INSERT INTO uie_role_permissions (role_id, permission_id)
SELECT 7, id FROM uie_permissions WHERE slug IN (
  'dashboard.view','uie_learners.view','uie_schools.view',
  'submissions.view','reports.view','uie_school_years.view','profile.edit');           -- division_viewer

INSERT INTO uie_role_permissions (role_id, permission_id)
SELECT 8, id FROM uie_permissions WHERE slug IN (
  'dashboard.view','uie_learners.view','submissions.view',
  'reports.view','uie_school_years.view','profile.edit');                              -- school_viewer

-- System modules
INSERT INTO uie_system_modules (code, name, description, sort_order) VALUES
('iped',     'Indigenous Peoples Education', 'IPEd learner profiling and monitoring', 1),
('madrasah', 'Madrasah Education',           'Madrasah learner profiling and monitoring', 2),
('sned',     'Special Needs Education',      'SNED learner profiling and monitoring', 3);

-- Super admin account (username: admin, password: Admin@123)
INSERT INTO uie_users (username, email, password_hash, first_name, last_name, position, status)
VALUES ('admin', 'admin@uie.local',
        '$2y$10$IzouP.HfvnrKhSUryueGWuYQUrdt6mrfxzrtoPfaY0W0.bJqtbrM6',
        'System', 'Administrator', 'Super Administrator', 'active');
INSERT INTO uie_user_roles (user_id, role_id) VALUES (1, 1);

-- System settings
INSERT INTO uie_system_settings (setting_key, setting_value, description) VALUES
('system_name',    'UIEIMS', 'System short name'),
('system_title',   'Unified Inclusive Education Information and Management System', 'Full system name'),
('system_subtitle','Learner Profiling, Records Management, and Program Monitoring System', 'Subtitle'),
('login_max_attempts', '5',  'Failed logins before lockout'),
('login_lockout_minutes', '15', 'Lockout duration in minutes'),
('records_per_page', '15', 'Default pagination size');

-- SNED disability categories
INSERT INTO uie_sned_disability_categories (code, name) VALUES
('VI',  'Visual Impairment'),
('HI',  'Hearing Impairment'),
('ID',  'Intellectual Disability'),
('LD',  'Learning Disability'),
('ASD', 'Autism Spectrum Disorder'),
('PD',  'Physical Disability'),
('SLD', 'Speech and Language Disorder'),
('MD',  'Multiple Disabilities'),
('EBD', 'Emotional and Behavioral Disorders'),
('OSEN','Other Special Educational Needs');

-- IPEd reference seeds (configurable)
INSERT INTO uie_iped_ethnic_groups (name) VALUES
('Aeta/Agta'),('Ati'),('Badjao/Sama Dilaut'),('B''laan'),('Bagobo'),('Batak'),
('Bontoc'),('Buhid'),('Higaonon'),('Ibaloi'),('Ifugao'),('Ilongot'),('Isneg'),
('Ivatan'),('Kalagan'),('Kalinga'),('Kankanaey'),('Mangyan'),('Manobo'),
('Mansaka'),('Palawano'),('Sama'),('Subanen'),('T''boli'),('Tagbanua'),
('Tausug'),('Teduray'),('Tumandok'),('Yakan'),('Other');

INSERT INTO uie_iped_cultural_communities (name) VALUES
('Aeta'),('Ati'),('Badjao'),('B''laan'),('Bagobo'),('Batak'),('Bontoc'),
('Higaonon'),('Ibaloi'),('Ifugao'),('Igorot'),('Isneg'),('Kalinga'),
('Kankanaey'),('Lumad'),('Mangyan'),('Manobo'),('Palawano'),('Sama'),
('Subanen'),('T''boli'),('Tagbanua'),('Tausug'),('Teduray'),('Yakan'),('Other');

-- Madrasah program seeds
INSERT INTO uie_madrasah_programs (code, name) VALUES
('ALIVE', 'Arabic Language and Islamic Values Education (ALIVE)'),
('WKND',  'Weekend Madrasah Program'),
('TRAD',  'Traditional Madrasah'),
('PRIV',  'Private Madrasah'),
('TAD',   'Tahderiyyah / Asidig Program');
