-- -----------------------------------------------------------------------------
-- netprovider — minimal bootstrap seed (NOT for integration tests)
--
-- The smallest set of rows that makes a freshly-created database ready for an
-- administrator: one admin person joined to an admin group with its own person
-- account. Load this AFTER sql/schema.sql, and only for a real setup — the
-- integration tier seeds its own data and must NOT load this file.
--
-- No password ships here. The admin row is created with PE_password = NULL, and
-- login (site/index.php) compares md5(<input>) against PE_password, so a NULL
-- password can never match: the account is created but NOT loginable until a
-- password is set. Choose ONE of:
--
--   * `composer db:seed` (AdminSeeder) — generates a strong random password,
--     sets it, and prints it once. Preferred.
--   * Manual load (`mysql < sql/seed.sql`) — then set a password yourself:
--       UPDATE `person` SET `PE_password` = MD5('<your-password>')
--        WHERE `PE_username` = 'admin';
--     The login form enforces a 6-character minimum.
--
-- Idempotent: every INSERT is guarded, so re-running this file is a no-op and
-- never clobbers a password you have already set.
-- -----------------------------------------------------------------------------

SET NAMES utf8mb4;

-- Person account backing the admin person (person.PE_personaccountid is NOT NULL).
INSERT INTO `personaccount` (`PA_personaccountid`, `PA_currency`, `PA_income`, `PA_outcome`)
VALUES (1, 'CZK', 0.00, 0.00)
ON DUPLICATE KEY UPDATE `PA_personaccountid` = `PA_personaccountid`;

-- Super-administrator group (Group::SUPER_ADMINISTRATOR = 9; GR_acl mirrors how
-- the app creates groups, i.e. 0 — super-admin power derives from GR_level).
INSERT INTO `group` (`GR_groupid`, `GR_name`, `GR_acl`, `GR_level`)
VALUES (1, 'Administrators', 0, 9)
ON DUPLICATE KEY UPDATE `GR_groupid` = `GR_groupid`;

-- The admin login. PE_status = Person::STATUS_ACTIVE (1). PE_password is left
-- NULL on purpose (see header) — the account is not loginable until a password
-- is set. The no-op ON DUPLICATE guard makes a re-run leave any set password
-- untouched.
INSERT INTO `person`
    (`PE_personid`, `PE_groupid`, `PE_personaccountid`, `PE_firstname`, `PE_surname`,
     `PE_username`, `PE_password`, `PE_status`, `PE_registerdate`)
VALUES
    (1, 1, 1, 'System', 'Administrator',
     'admin', NULL, 1, NOW())
ON DUPLICATE KEY UPDATE `PE_personid` = `PE_personid`;
