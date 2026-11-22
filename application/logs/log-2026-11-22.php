<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

ERROR - 2026-11-22 04:25:25 --> Query error: Table 'uie_db.uie_spfl_learner_profiles' doesn't exist - Invalid query: SELECT `id`
FROM `uie_spfl_learner_profiles`
WHERE `learner_school_record_id` = 64
ERROR - 2026-11-22 04:25:25 --> Query error: Unknown column 'learner_school_record_id' in 'where clause' - Invalid query: DELETE FROM `uie_school_years`
WHERE `learner_school_record_id` = 64
AND `id` IN(42, 43, 44)
