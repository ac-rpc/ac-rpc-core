/**
 * Copyright 2010 by the Regents of the University of Minnesota,
 * University Libraries - Minitex
 *
 * This file is part of The Research Project Calculator (RPC).
 *
 * RPC is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * RPC is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with The RPC.  If not, see <http://www.gnu.org/licenses/>.
 *
 *
 * MySQL table schema
 *
 * Author: Michael Berkowski <mjb@umn.edu>
 */

DROP TABLE IF EXISTS template_usage;
DROP TABLE IF EXISTS linked_steps;
DROP TABLE IF EXISTS linked_assignments;
DROP TABLE IF EXISTS steps;
DROP TABLE IF EXISTS assignments;
DROP TABLE IF EXISTS native_sessions;
DROP TABLE IF EXISTS users;
CREATE TABLE users
(
	userid INTEGER UNSIGNED PRIMARY KEY NOT NULL AUTO_INCREMENT,
	/* Usernames must be stored locally even when using auth API
	 */
	username VARCHAR(255) NOT NULL,
	/* NULL password is weird, but allows for auth API to store
	 * local admin users w/o passwords since they're authenticated
	 * by a remote system or plugin.
	 */
	password VARCHAR(256) NULL,
	passwordsalt VARCHAR(10) NOT NULL DEFAULT '',
	hashtype VARCHAR(16) NOT NULL DEFAULT '',
	email VARCHAR(255) NOT NULL UNIQUE,
	name VARCHAR(256) NULL,
	usertype VARCHAR(10) NOT NULL DEFAULT 'STUDENT',
	/* Default permissions is 'user' 001 */
	perms INTEGER NOT NULL DEFAULT 1,
	token VARCHAR(64) NULL,
	last_login DATETIME NULL,
	reset_token VARCHAR(128) NULL,
	reset_token_expires DATETIME NULL
) ENGINE=InnoDB;

/**
 * Sessions for native auth plugin users
 */
CREATE TABLE native_sessions
(
	sessionid INTEGER UNSIGNED PRIMARY KEY NOT NULL AUTO_INCREMENT,
	userid INTEGER UNSIGNED NOT NULL,
	token VARCHAR(64) NOT NULL,
	session VARCHAR(64) NOT NULL,
	FOREIGN KEY (userid) REFERENCES users (userid) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;


/**
 * Assignments
 * userid may be NULL if a template owner is deleted *
 */
CREATE TABLE assignments
(
	assignid INTEGER UNSIGNED PRIMARY KEY NOT NULL AUTO_INCREMENT,
	userid INTEGER UNSIGNED NULL,
	lastedit_userid INTEGER UNSIGNED NULL,
	title VARCHAR(512) NOT NULL,
	description TEXT NULL,
	class VARCHAR(128) NULL,
	createdate TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	startdate DATETIME NULL DEFAULT 0,
	duedate DATETIME NULL DEFAULT 0,
	remind TINYINT(1) NULL DEFAULT 0,
	shared TINYINT(1) NULL DEFAULT 0,
	template TINYINT(1) NOT NULL DEFAULT 0,
	published TINYINT(1) NOT NULL DEFAULT 0,
	parent INTEGER UNSIGNED NULL,
	ancestraltemplate INTEGER UNSIGNED NULL,
	FOREIGN KEY (userid) REFERENCES users (userid) ON DELETE SET NULL ON UPDATE CASCADE,
	FOREIGN KEY (lastedit_userid) REFERENCES users (userid) ON DELETE SET NULL ON UPDATE CASCADE,
	FOREIGN KEY (parent) REFERENCES assignments (assignid) ON DELETE SET NULL ON UPDATE CASCADE,
	FOREIGN KEY (ancestraltemplate) REFERENCES assignments (assignid) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

/**
 * Assignment steps
 */
CREATE TABLE steps
(
	stepid INTEGER UNSIGNED PRIMARY KEY NOT NULL AUTO_INCREMENT,
	assignid INTEGER UNSIGNED NOT NULL,
	position smallint NOT NULL,
	title VARCHAR(512) NOT NULL,
	description TEXT NULL,
	teacher_description TEXT NULL,
	annotation TEXT NULL,
	reminderdate DATETIME NULL,
	remindersentdate DATETIME NULL,
	percent SMALLINT NULL,
	FOREIGN KEY (assignid) REFERENCES assignments (assignid) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

/**
 * Assignment links
 */
CREATE TABLE linked_assignments
(
	linkid INTEGER UNSIGNED PRIMARY KEY NOT NULL AUTO_INCREMENT,
	userid INTEGER UNSIGNED NOT NULL,
	assignid INTEGER UNSIGNED NOT NULL,
	remind TINYINT(1) NULL DEFAULT 0,
	FOREIGN KEY (userid) REFERENCES users (userid) ON DELETE CASCADE ON UPDATE CASCADE,
	FOREIGN KEY (assignid) REFERENCES assignments (assignid) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;
/**
 * Step annotations
 */
CREATE TABLE linked_steps
(
	annoid INTEGER UNSIGNED PRIMARY KEY NOT NULL AUTO_INCREMENT,
	linkid INTEGER UNSIGNED NOT NULL,
	stepid INTEGER UNSIGNED NOT NULL,
	annotation TEXT NULL,
	remindersentdate DATETIME NULL,
	FOREIGN KEY (linkid) REFERENCES linked_assignments (linkid) ON DELETE CASCADE ON UPDATE CASCADE,
	FOREIGN KEY (stepid) REFERENCES steps (stepid) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

/**
 * Usage report for templates
 */
CREATE TABLE template_usage
(
	assignid INTEGER UNSIGNED NOT NULL,
	usertype VARCHAR(10) NOT NULL DEFAULT 'STUDENT',
	saved TINYINT(1) NOT NULL DEFAULT 0,
	createdate TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	FOREIGN KEY (assignid) REFERENCES assignments (assignid) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE OR REPLACE VIEW assignments_brief_vw AS
(
SELECT
	assignment.assignid AS id,
	assignment.userid,
	assignment.title,
	assignment.description,
	assignment.class,
	UNIX_TIMESTAMP(assignment.createdate) as create_date,
	UNIX_TIMESTAMP(assignment.startdate) as start_date,
	UNIX_TIMESTAMP(assignment.duedate) as due_date,
	DATEDIFF(assignment.duedate, assignment.startdate) as days,
	DATEDIFF(assignment.duedate, NOW()) AS days_left,
	CASE
		WHEN assignment.duedate IS NULL OR assignment.duedate = 0 THEN 'INACTIVE'
		WHEN DATEDIFF(assignment.duedate, NOW()) >= 0 AND assignment.duedate > 0 THEN 'ACTIVE'
		ELSE 'EXPIRED'
	END AS status,
	assignment.shared AS is_shared,
	assignment.remind AS send_reminders,
	assignment.parent,
	CASE
		WHEN parent_object.template = 1 THEN 'template'
		WHEN parent_object.template = 0 THEN 'assignment'
		ELSE NULL
	END AS parent_type,
	assignment.ancestraltemplate AS ancestral_template
FROM assignments assignment LEFT OUTER JOIN assignments parent_object ON assignment.parent = parent_object.assignid
WHERE assignment.template = 0
);

CREATE OR REPLACE VIEW templates_vw AS
(
SELECT
	assignment.assignid AS id,
	assignment.userid,
	users_a.name AS author,
	assignment.lastedit_userid,
	users_l.name as lastedit_name,
	assignment.title,
	assignment.description,
	assignment.class,
	UNIX_TIMESTAMP(assignment.createdate) as create_date,
	assignment.published AS is_published,
	assignment.parent,
	CASE
		WHEN parent_object.template = 1 THEN 'template'
		WHEN parent_object.template = 0 THEN 'assignment'
		ELSE NULL
	END AS parent_type,
	assignment.ancestraltemplate AS ancestral_template
FROM assignments assignment
	LEFT OUTER JOIN users users_a ON assignment.userid = users_a.userid
	LEFT OUTER JOIN users users_l ON assignment.lastedit_userid = users_l.userid
	LEFT OUTER JOIN assignments parent_object ON assignment.parent = parent_object.assignid
WHERE assignment.template = 1
);

CREATE OR REPLACE VIEW steps_vw AS
(
SELECT
	steps.stepid AS id,
	assignments.userid,
	steps.assignid,
	CASE WHEN assignments.template = 1 THEN 1 ELSE 0 END AS template_step,
	assignments.title as assignment,
	steps.title as step,
	steps.description,
	steps.teacher_description,
	steps.annotation,
	steps.position,
	steps.percent,
	UNIX_TIMESTAMP(steps.reminderdate) AS due_date,
	DATEDIFF(steps.reminderdate, NOW()) AS days_left,
	UNIX_TIMESTAMP(steps.remindersentdate) AS reminder_sent_date,
	assignments.shared AS is_shared,
	CASE
		WHEN steps.reminderdate IS NULL OR steps.reminderdate = 0 THEN 'INACTIVE'
		WHEN DATEDIFF(steps.reminderdate, NOW()) >= 0 AND steps.reminderdate > 0 THEN 'ACTIVE'
	ELSE 'EXPIRED'
	END AS status
FROM steps JOIN assignments ON steps.assignid = assignments.assignid
);

CREATE OR REPLACE VIEW linked_steps_vw AS
(
SELECT
	linked_steps.linkid,
	linked_steps.annoid,
	linked_steps.stepid AS id,
	linked_steps.annotation,
	UNIX_TIMESTAMP(linked_steps.remindersentdate) AS reminder_sent_date
FROM linked_steps
);

CREATE OR REPLACE VIEW template_usage_vw AS
(
SELECT
	template_usage.assignid AS assignid,
	assignments.title AS title,
	assignments.description AS description,
	assignments.published AS is_published,
	template_usage.saved AS is_saved,
	UNIX_TIMESTAMP(template_usage.createdate) AS create_date
FROM template_usage JOIN assignments ON template_usage.assignid = assignments.assignid
);
