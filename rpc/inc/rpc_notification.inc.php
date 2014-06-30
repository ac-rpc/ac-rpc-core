<?php
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
 */

/**
 * Class RPC_Notification
 * Notifier class for assignment step reminders
 *
 * Uses html2text.php, originally by Jon Abernathy <jon@chuggnutt.com>
 * and modified for the RoundCube webmail project.  RoundCube repaired
 * code injection vulnerabilities.
 *
 * @package RPC
 */
class RPC_Notification
{
	const ERR_NO_RECIPIENT = 1;
	const ERR_CANNOT_SEND = 2;
	const ERR_NOT_PREPARED = 3;

	/**
	 * Message subject
	 *
	 * @var string
	 * @access public
	 */
	public $subject;
	/**
	 * HTML body before being stripped to text
	 *
	 * @var string
	 * @access public
	 */
	public $html_body;
	/**
	 * Plain text body
	 *
	 * @var string
	 * @access public
	 */
	public $text_body;
	/**
	 * Plain text email body after Smarty template
	 *
	 * @var string
	 * @access public
	 */
	public $templated_body_text;
	/**
	 * HTML email body after Smarty template
	 *
	 * @var string
	 * @access public
	 */
	public $templated_body_html;
	/**
	 * Headers block
	 *
	 * @var string
	 * @access public
	 */
	public $headers;
	/**
	 * Email address appearing in the From header
	 *
	 * @var mixed
	 * @access public
	 */
	public $from_address;
	/**
	 * Human-readable name part of RFC-2822 email address
	 *
	 * @var string
	 * @access public
	 */
	public $from_name;
	/**
	 * Email address of recipient
	 *
	 * @var string
	 * @access public
	 */
	public $recipient_address;
	/**
	 * Message ID
	 *
	 * @var string
	 * @access public
	 */
	public $mesage_id;
	/**
	 * MIME boundary
	 *
	 * @var string
	 * @access public
	 */
	public $mime_boundary;
	/**
	 * Has this notification been sent?
	 *
	 * @var boolean
	 * @access public
	 */
	public $sent = FALSE;

	/**
	 * RPC_Smarty templating object
	 *
	 * @var RPC_Smarty
	 * @access private
	 */
	private $_smarty;
	/**
	 * Have the subject and body been sanitized against email injection?
	 *
	 * @var boolean
	 * @access private
	 */
	private $_is_prepared = FALSE;
	/**
	 * Error status
	 *
	 * @var integer
	 * @access public
	 */
	public $error = NULL;
	/**
	 * RPC_Config global configuration singleton
	 *
	 * @var RPC_Config
	 * @access public
	 */
	public $config = NULL;

	/**
	 * Public constructor
	 *
	 * @param RPC_Config $config
	 * @access public
	 * @return RPC_Notification
	 */
	public function __construct($config)
	{
		$this->config = $config;

		$this->_smarty = new RPC_Smarty($this->config);
		$this->from_address = $this->config->app_rfc_email_address;
		$this->from_name = $this->config->app_email_from_sender_name;

		$this->message_id = '<' . md5(rand() . time()) . '@' . php_uname('n') . '>';
		$this->mime_boundary = "MIME_BOUNDARY." . md5(rand() . time());

		$this->headers = "From: {$this->from_address}" . PHP_EOL;
		$this->headers .= "Sender: {$this->from_address}" . PHP_EOL;
		$this->headers .= "Return-path: {$this->from_address}" . PHP_EOL;
		$this->headers .= "Reply-to: {$this->from_address}" . PHP_EOL;
		$this->headers .= "Date: " . date('r') . PHP_EOL;
		$this->headers .= "X-Mailer: PHP/" . phpversion() . PHP_EOL;
		$this->headers .= "X-Calc: {$this->config->app_long_name}" . PHP_EOL;
		$this->headers .= "X-Calc-URL: {$this->config->app_fixed_web_path}" . PHP_EOL;
		$this->headers .= "Message-Id: {$this->message_id}" . PHP_EOL;
		$this->headers .= "MIME-Version: 1.0" . PHP_EOL;
		$this->headers .= "Content-type: multipart/alternative;" . PHP_EOL . "    boundary=\"{$this->mime_boundary}\"";

		$this->subject = "{$this->config->app_long_name} reminder";
		return;
	}
	/**
	 * Prepare template and recipient
	 *
	 * @param RPC_User $user
	 * @param RPC_Assignment $assignment
	 * @param RPC_Step $step
	 * @access public
	 * @return boolean
	 */
	public function prepare($user, $assignment, $step)
	{
		if (empty($user->email))
		{
			$this->error = self::ERR_NO_RECIPIENT;
			$this->_is_prepared = FALSE;
			return FALSE;
		}
		$this->recipient_address = $user->email;
		// In case a line break made its way into the application name...
		$this->subject = preg_replace("/\r?\n/", " ", $this->subject);

		$this->html_body = $step->description;
		$h2t = new html2text($this->html_body, FALSE, TRUE, 72);
		$this->text_body = $h2t->get_text();

		$this->_smarty->set_user($user);
		if (get_class($assignment) === "RPC_Assignment") $object_type = RPC_Smarty::OBJECT_TYPE_ASSIGNMENT;
		if (get_class($assignment) === "RPC_Linked_Assignment") $object_type = RPC_Smarty::OBJECT_TYPE_LINKED_ASSIGNMENT;
		$this->_smarty->set_assignment($assignment, $object_type);
		$this->_smarty->set_step($step);

		$this->_smarty->assign('notification', array('step_text' => $this->text_body, 'step_html' => $this->html_body));

		$this->_build_multipart();

		$this->_is_prepared = TRUE;
		return $this->_is_prepared;
	}
	/**
	 * Build the MIME multipart/alternative message body
	 * consisting of a text/plain component and text/html component
	 *
	 * @access private
	 * @return void
	 */
	private function _build_multipart()
	{
		$this->templated_body_text = $this->_smarty->global_fetch('notifications/step_reminder_text.tpl');
		$this->templated_body_html = $this->_smarty->global_fetch('notifications/step_reminder_html.tpl');
		$crlf = "\r\n";

		$this->multipart_message = "This is a multipart message in MIME format." . $crlf . $crlf;
		$this->multipart_message .= "--{$this->mime_boundary}{$crlf}";
		$this->multipart_message .= "Content-type: text/plain; charset=\"us-ascii\"{$crlf}";
		$this->multipart_message .= "Content-transfer-encoding: 7bit{$crlf}{$crlf}";
		$this->multipart_message .= $this->templated_body_text . $crlf . $crlf;
		$this->multipart_message .= "--{$this->mime_boundary}{$crlf}";
		$this->multipart_message .= "Content-type: text/html; charset=\"iso-8859-1\"{$crlf}";
		$this->multipart_message .= "Content-transfer-encoding: 7bit{$crlf}{$crlf}";
		$this->multipart_message .= $this->templated_body_html . $crlf . $crlf;
		$this->multipart_message .= "--{$this->mime_boundary}--";
		return;
	}
	/**
	 * Send the notification
	 *
	 * @access public
	 * @return boolean
	 */
	public function send()
	{
		if (!$this->_is_prepared)
		{
			$this->error = self::ERR_NOT_PREPARED;
			return;
		}

		if (mail($this->recipient_address, $this->subject, $this->multipart_message, $this->headers, "-f{$this->config->app_email_from_address}"))
		{
			$this->sent = TRUE;
		}
		else
		{
			$this->sent = FALSE;
			$this->error = self::ERR_CANNOT_SEND;
		}
		return $this->sent;
	}
	/**
	 * Return an error string
	 *
	 * @access public
	 * @return string
	 */
	public function get_error()
	{
		if (!empty($this->error))
		{
			switch ($this->error)
			{
				case self::ERR_NO_RECIPIENT: return "No email address was given.";
				case self::ERR_CANNOT_SEND: return "Email sending failed.";
				case self::ERR_DATA_UNSANITIZED: return "Message has not been prepared.";
			}
		}
		else return "";
	}

	/**
	 * Retrieve an array of user id, assignment id, step id
	 * pending notification for both regular and linked assignments
	 *
	 * @param RPC_Config $config Global configuration singleton
	 * @param \PDO $db PDO database connection singleton
	 * @static
	 * @access public
	 * @return array Associative array userid,assignid,stepid
	 */
	public static function get_pending_notifications($config, $db)
	{
		// Using $config->app_notification_advance_days as cutoff for notifications
		// Any that have not been sent yet will be selected in this batch (reminder_sent_date IS NULL)
		$qry = <<<QRY
			SELECT NULL as linkid, steps_vw.userid, steps_vw.assignid, steps_vw.id AS stepid
			FROM steps_vw JOIN assignments ON steps_vw.assignid = assignments.assignid
			WHERE remind = 1
				AND steps_vw.due_date <= (UNIX_TIMESTAMP(CURDATE()) + (86400 * :days))
				AND (reminder_sent_date IS NULL OR reminder_sent_date = 0)
				AND assignments.template = 0
			UNION
			SELECT linked_assignments.linkid, linked_assignments.userid, linked_assignments.assignid, linked_steps.stepid as stepid
			FROM
				linked_steps JOIN linked_assignments ON linked_steps.linkid = linked_assignments.linkid
				JOIN steps_vw ON linked_steps.stepid = steps_vw.id
			WHERE linked_assignments.remind = 1
				AND steps_vw.due_date <= UNIX_TIMESTAMP(CURDATE()) + (86400 * :days_union)
				AND (linked_steps.remindersentdate IS NULL OR linked_steps.remindersentdate = 0);
QRY;
		$stmt = $db->prepare($qry);
		$stmt->bindParam(':days', $config->app_notification_advance_days, \PDO::PARAM_INT);
		$stmt->bindParam(':days_union', $config->app_notification_advance_days, \PDO::PARAM_INT);

		if ($stmt->execute())
		{
			$arr_notifications = array();
			$rows = $stmt->fetchAll();
			foreach ($rows as $row)
			{
				$arr_notifications[] = $row;
			}
			$stmt->closeCursor();
			return $arr_notifications;
		}
		else
		{
	 		return FALSE;
		}
	}
}
?>
