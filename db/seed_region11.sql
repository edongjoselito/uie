-- ============================================================================
-- UIEIMS - DepEd Region XI (Davao Region) seed data
-- Real SDO structure + sample uie_schools and accounts for testing.
-- NOTE: school rows use plausible DepEd-style IDs as seed/test data -
-- verify against the official EBEIS masterlist in production.
-- ============================================================================
USE uie_db;

SET @r11 = (SELECT id FROM uie_regions WHERE code = 'R-XI');

-- ----------------------------------------------------------------------------
-- Schools Division Offices of DepEd Region XI (Davao Region)
-- ----------------------------------------------------------------------------
INSERT INTO uie_divisions (code, name, region_id, status) VALUES
('DVO-CITY',      'Schools Division Office - Davao City',              @r11, 'active'),
('DVO-DE-ORO',    'Schools Division Office - Davao de Oro',            @r11, 'active'),
('DVO-DEL-NORTE', 'Schools Division Office - Davao del Norte',         @r11, 'active'),
('DVO-DEL-SUR',   'Schools Division Office - Davao del Sur',           @r11, 'active'),
('DVO-OCC',       'Schools Division Office - Davao Occidental',        @r11, 'active'),
('DVO-OR',        'Schools Division Office - Davao Oriental',          @r11, 'active'),
('DIGOS',         'Schools Division Office - Digos City',              @r11, 'active'),
('IGACOS',        'Schools Division Office - Island Garden City of Samal', @r11, 'active'),
('MATI',          'Schools Division Office - Mati City',               @r11, 'active'),
('PANABO',        'Schools Division Office - Panabo City',             @r11, 'active'),
('TAGUM',         'Schools Division Office - Tagum City',              @r11, 'active')
ON DUPLICATE KEY UPDATE name = VALUES(name), region_id = VALUES(region_id);

-- ----------------------------------------------------------------------------
-- Sample uie_schools (Division of Davao City + a few other uie_divisions)
-- ----------------------------------------------------------------------------
SET @dvo = (SELECT id FROM uie_divisions WHERE code = 'DVO-CITY');

INSERT INTO uie_schools (school_id, name, region_id, division_id, municipality, barangay, classification, school_type, status) VALUES
('304301', 'Davao City National High School',          @r11, @dvo, 'Davao City', 'Catalunan Grande', 'Senior High School',           'Public', 'active'),
('129302', 'Catalunan Grande Elementary School',       @r11, @dvo, 'Davao City', 'Catalunan Grande', 'Elementary',                  'Public', 'active'),
('129510', 'Matina Elementary School',                 @r11, @dvo, 'Davao City', 'Matina Crossing',  'Elementary',                  'Public', 'active'),
('304305', 'Sta. Ana National High School',            @r11, @dvo, 'Davao City', 'Poblacion',        'Junior High School',           'Public', 'active'),
('129501', 'Bangkal Elementary School',                @r11, @dvo, 'Davao City', 'Bangkal',          'Elementary',                  'Public', 'active'),
('500301', 'IP Learning Center of Davao (Integrated)', @r11, @dvo, 'Davao City', 'Buhangin',         'Integrated School (K-12)',     'Public', 'active'),
('405901', 'Davao City Madrasah Central Elementary',   @r11, @dvo, 'Davao City', 'SIR Matina',       'Elementary',                  'Public', 'active')
ON DUPLICATE KEY UPDATE name = VALUES(name);

SET @digos = (SELECT id FROM uie_divisions WHERE code = 'DIGOS');
INSERT INTO uie_schools (school_id, name, region_id, division_id, municipality, barangay, classification, school_type, status) VALUES
('303901', 'Digos City National High School', @r11, @digos, 'Digos City', 'Zone II', 'Senior High School', 'Public', 'active')
ON DUPLICATE KEY UPDATE name = VALUES(name);

SET @tagum = (SELECT id FROM uie_divisions WHERE code = 'TAGUM');
INSERT INTO uie_schools (school_id, name, region_id, division_id, municipality, barangay, classification, school_type, status) VALUES
('304401', 'Tagum City National High School', @r11, @tagum, 'Tagum City', 'Mankilam', 'Senior High School', 'Public', 'active')
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- ----------------------------------------------------------------------------
-- Region XI system identity
-- ----------------------------------------------------------------------------
INSERT INTO uie_system_settings (setting_key, setting_value, description) VALUES
('home_region_code', 'R-XI', 'Operating region code of this UIEIMS installation'),
('region_office',    'DepEd Region XI - Davao Region', 'Identity shown in branding'),
('region_slogan',    'Leading the Way', 'DepEd Region XI official slogan'),
('deped_name',       'Department of Education', 'Department name for branding'),
('region_address',   'Davao City, Philippines', 'Regional office address')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);

-- ----------------------------------------------------------------------------
-- Test accounts for each level (password: Admin@123, change on first use)
-- ----------------------------------------------------------------------------
SET @hash = '$2y$10$IzouP.HfvnrKhSUryueGWuYQUrdt6mrfxzrtoPfaY0W0.bJqtbrM6';

-- Regional Administrator (Region XI)
INSERT INTO uie_users (username, email, password_hash, first_name, last_name, position, status)
VALUES ('region11.admin', 'region11.admin@uie.local', @hash, 'Regional', 'Administrator', 'Regional IPEd/Madrasah/SNED Focal', 'active');
SET @uid = LAST_INSERT_ID();
INSERT INTO uie_user_roles (user_id, role_id) VALUES (@uid, (SELECT id FROM uie_roles WHERE slug = 'regional_admin'));
INSERT INTO uie_user_assignments (user_id, region_id, is_primary, status) VALUES (@uid, @r11, 1, 'active');

-- Division Administrator (Davao City)
INSERT INTO uie_users (username, email, password_hash, first_name, last_name, position, status)
VALUES ('davaocity.admin', 'davaocity.admin@uie.local', @hash, 'Division', 'Administrator', 'SGOD Chief / Division Focal', 'active');
SET @uid = LAST_INSERT_ID();
INSERT INTO uie_user_roles (user_id, role_id) VALUES (@uid, (SELECT id FROM uie_roles WHERE slug = 'division_admin'));
INSERT INTO uie_user_assignments (user_id, region_id, division_id, is_primary, status) VALUES (@uid, @r11, @dvo, 1, 'active');

-- School Administrator (Davao City NHS)
INSERT INTO uie_users (username, email, password_hash, first_name, last_name, position, status)
VALUES ('dcnhs.admin', 'dcnhs.admin@uie.local', @hash, 'School', 'Administrator', 'School Principal', 'active');
SET @uid = LAST_INSERT_ID();
SET @sch = (SELECT id FROM uie_schools WHERE school_id = '304301');
INSERT INTO uie_user_roles (user_id, role_id) VALUES (@uid, (SELECT id FROM uie_roles WHERE slug = 'school_admin'));
INSERT INTO uie_user_assignments (user_id, region_id, division_id, school_id, is_primary, status) VALUES (@uid, @r11, @dvo, @sch, 1, 'active');
INSERT INTO uie_school_users (school_id, user_id, school_role, is_primary, status) VALUES (@sch, @uid, 'administrator', 1, 'active');

-- School Encoder (Davao City NHS)
INSERT INTO uie_users (username, email, password_hash, first_name, last_name, position, status)
VALUES ('dcnhs.encoder', 'dcnhs.encoder@uie.local', @hash, 'School', 'Encoder', 'Designated Data Encoder', 'active');
SET @uid = LAST_INSERT_ID();
INSERT INTO uie_user_roles (user_id, role_id) VALUES (@uid, (SELECT id FROM uie_roles WHERE slug = 'school_encoder'));
INSERT INTO uie_user_assignments (user_id, region_id, division_id, school_id, is_primary, status) VALUES (@uid, @r11, @dvo, @sch, 1, 'active');
INSERT INTO uie_school_users (school_id, user_id, school_role, is_primary, status) VALUES (@sch, @uid, 'encoder', 1, 'active');
