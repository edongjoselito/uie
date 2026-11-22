-- SF 1 (School Register) alignment for the centralized learner registry.
-- Adds the SF1 identity columns not yet covered by uie_learners.
-- Run once: mysql -h127.0.0.1 -uroot -p uie_db < db/migrations/20261122_sf1_learner_fields.sql

ALTER TABLE uie_learners
    ADD COLUMN mother_tongue VARCHAR(120) NULL COMMENT 'SF1: Mother Tongue' AFTER place_of_birth,
    ADD COLUMN ip_ethnicity  VARCHAR(120) NULL COMMENT 'SF1: IP (Ethnic Group)' AFTER mother_tongue,
    ADD COLUMN religion      VARCHAR(120) NULL COMMENT 'SF1: Religion' AFTER ip_ethnicity;
