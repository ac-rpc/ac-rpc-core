ALTER TABLE users ADD passwordsalt VARCHAR(10) NOT NULL DEFAULT '' AFTER password;
