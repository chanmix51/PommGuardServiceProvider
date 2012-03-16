BEGIN;
CREATE SCHEMA pomm_guard;
SET search_path TO pomm_guard,public;
CREATE TABLE pomm_group (name varchar PRIMARY KEY, credentials varchar[]);
CREATE TABLE pomm_user (login VARCHAR PRIMARY KEY, password varchar NOT NULL, groups varchar[]);
CREATE OR REPLACE FUNCTION groups_exist(varchar[]) RETURNS boolean LANGUAGE sql AS $_$
WITH
user_groups (name) AS (SELECT unnest($1))
SELECT every(EXISTS (SELECT pg.name FROM pomm_guard.pomm_group pg WHERE pg.name = ug.name)) FROM user_groups ug
$_$
;
ALTER TABLE pomm_user ADD CONSTRAINT check_groups CHECK(groups_exist(groups));
COMMIT;
