BEGIN;
SET search_path TO pomm_guard,public;
CREATE OR REPLACE FUNCTION pomm_user_encrypt_password()
RETURNS TRIGGER LANGUAGE plpgsql AS $_$
    BEGIN
        IF TG_OP = 'UPDATE' THEN
            IF NEW.password = OLD.password THEN
                RETURN NEW;
            END IF;
        END IF;

        NEW.password = crypt(NEW.password, gen_salt('md5'));

        RETURN NEW;
    END;
$_$;
COMMIT;
