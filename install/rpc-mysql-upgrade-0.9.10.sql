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
