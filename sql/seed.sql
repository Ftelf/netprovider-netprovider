-- -----------------------------------------------------------------------------
-- netprovider — minimal bootstrap seed (NOT for integration tests)
--
-- The smallest set of rows that makes a freshly-created database loginable:
-- one super-administrator account joined to an admin group with its own
-- person account. Load this AFTER sql/schema.sql, and only for a real setup —
-- the integration tier seeds its own data and must NOT load this file.
--
-- Login uses legacy md5() hashing (see site/index.php): PE_password must equal
-- md5(<plaintext>). The default credentials below are:
--
--     username: admin
--     password: changeme            <-- CHANGE THIS IMMEDIATELY AFTER FIRST LOGIN
--
-- Password minimum length enforced by the login form is 6 characters.
-- -----------------------------------------------------------------------------

SET NAMES utf8mb4;

-- Person account backing the admin person (person.PE_personaccountid is NOT NULL).
INSERT INTO `personaccount` (`PA_personaccountid`, `PA_currency`, `PA_income`, `PA_outcome`)
VALUES (1, 'CZK', 0.00, 0.00);

-- Super-administrator group (Group::SUPER_ADMINISTRATOR = 9; GR_acl mirrors how
-- the app creates groups, i.e. 0 — super-admin power derives from GR_level).
INSERT INTO `group` (`GR_groupid`, `GR_name`, `GR_acl`, `GR_level`)
VALUES (1, 'Administrators', 0, 9);

-- The admin login. PE_status = Person::STATUS_ACTIVE (1); password = md5('changeme').
INSERT INTO `person`
    (`PE_personid`, `PE_groupid`, `PE_personaccountid`, `PE_firstname`, `PE_surname`,
     `PE_username`, `PE_password`, `PE_status`, `PE_registerdate`)
VALUES
    (1, 1, 1, 'System', 'Administrator',
     'admin', MD5('changeme'), 1, NOW());
