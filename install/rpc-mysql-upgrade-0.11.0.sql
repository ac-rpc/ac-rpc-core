/**
 * `users`.`username` and `users`.`email` changed from 
 * VARCHAR(320) to VARCHAR(255) since UTF8 default databases
 * cannot create an index on a field that long.
 *
 * 255 is a theoretical max for email anyway, but the length
 * is NOT being modified on existing databases
 */

-- `users`.`passwordsalt` extended to VARCHAR(255) for better hashing algorithms
ALTER TABLE `users` MODIFY `passwordsalt` VARCHAR(255) NOT NULL DEFAULT '';

-- Adds a hash type identifier and set all existing types to 'sha1'
ALTER TABLE `users` ADD `hashtype` VARCHAR(16) NOT NULL DEFAULT 'sha1' AFTER `passwordsalt`;

-- Adds a last-login timestamp
ALTER TABLE `users` ADD `last_login` DATETIME NULL;

-- Adds a password reset token to replace emailed passwords
ALTER TABLE `users` ADD `reset_token` VARCHAR(128) NULL;
ALTER TABLE `users` ADD `reset_token_expires` DATETIME NULL;
