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

// Needs Smarty base class in include path
require_once('Smarty/libs/Smarty.class.php');
require_once('rpc.inc.php');
require_once('rpc_assignment_base.inc.php');
require_once('rpc_assignment.inc.php');
require_once('rpc_template.inc.php');
require_once('rpc_linked_assignment.inc.php');
require_once('rpc_step.inc.php');
require_once('rpc_user.inc.php');
/**
 * Wrapper for Smarty templating
 * Sets up basic common configurations and template variables
 *
 * @package RPC
 */
class RPC_Smarty extends Smarty
{
	const OBJECT_TYPE_ASSIGNMENT = 1;
	const OBJECT_TYPE_TEMPLATE = 2;
	const OBJECT_TYPE_LINKED_ASSIGNMENT = 3;

	/**
	 * Absolute path to system templates directory
	 * @access private
	 */
	private $_global_template_dir;
	/**
	 * Absolute path to active skin templates directory
	 * @access private
	 */
	private $_skin_template_dir;
	/**
	 * Global RPC_Config configuration singleton
	 *
	 * @var object RPC_Config configuration singleton
	 * @access public
	 */
	public $config;
	/**
	 * Initialize a Smarty object, setup its configuration,
	 * assign application global variables to 'application',
	 * and return the Smarty object.
	 *
	 * @param object $config RPC_Config global configuration singleton
	 * @access public
	 * @return object Smarty
	 */
	public function __construct($config)
	{
		parent::__construct();

		// Suppress notices for undefined variables
		$this->error_reporting = E_ALL & ~E_NOTICE;
		$this->config = $config;
		// Setup smarty directories
		$smarty_path = dirname(__FILE__) . "/../tmpl/";

		//$this->setConfigDir($smarty_path . "config");
		$this->setCompileDir($smarty_path . "compile");
		$this->setCacheDir($smarty_path . "cache");

		// Templates reside with the current skin or system templates dirs
		$this->_skin_template_dir = $config->app_file_path . "/skins/" . $config->skin . "/tmpl";
		$this->_global_template_dir = $config->app_file_path . "/tmpl/system_templates";

		// Initially, the skin is used as template_dir, but this is changed on each subsequent call
		// of global_fetch() or global_display()
		$this->template_dir = $this->_skin_template_dir;

		// Load basic variables from global config
		$arr_app_vars = array(
			'long_name' => $config->app_long_name,
			'short_name' => $config->app_short_name,
			'email_from_address' => $config->app_email_from_address,
			'email_from_sender_name' => $config->app_email_from_sender_name,
			'rfc_email_address' => $config->app_rfc_email_address,
			'fixed_web_path' => $config->app_fixed_web_path,
			'relative_web_path' => $config->app_relative_web_path,
			'dojo_path' => $config->app_dojo_path,
			'short_date' => $config->short_date_format,
			'long_date' => $config->long_date_format,
			'skin' => $config->skin,
			'skin_path' => $config->app_skin_path,
			'version' => RPC_VERSION,
			'builddate' => RPC_BUILDDATE
		);
		$this->assign('application', $arr_app_vars);

		// Auth plugin stored -- native authentication gets some extra
		// display options in some templates
		$this->assign('auth_plugin', $config->auth_plugin);

		return;
	}
	/**
	 * Display a template from the shared system templates directory
	 *
	 * @param string $template
	 * @access public
	 * @return void
	 */
	public function global_display($template)
	{
		$this->setTemplateDir($this->_global_template_dir);
		$this->display($template);
		return;
	}
	/**
	 * Fetch a template from the shared system templates directory
	 * (Retrieve a string but do not emit it).
	 *
	 * @param string $template
	 * @access public
	 * @return string
	 */
	public function global_fetch($template)
	{
		$this->setTemplateDir($this->_global_template_dir);
		return $this->fetch($template);
	}
	/**
	 * Display a template from the active skin templates directory
	 *
	 * @param string $template
	 * @access public
	 * @return void
	 */
	public function skin_display($template)
	{
		$this->setTemplateDir($this->_skin_template_dir);
		$this->display($template);
		return;
	}
	/**
	 * Fetch a template from the active skin templates directory
	 * (Retrieve a string but do not emit it).
	 *
	 * @param string $template
	 * @access public
	 * @return string
	 */
	public function skin_fetch($template)
	{
		$this->setTemplateDir($this->_skin_template_dir);
		return $this->fetch($template);
	}

	/**
	 * Set template variables related to RPC_Assignment $assignment
	 *
	 * @param object $object RPC_Assignment
	 * @access public
	 * @return void
	 */
	public function set_assignment($object, $object_type=RPC_Smarty::OBJECT_TYPE_ASSIGNMENT)
	{
		// For linked assignments, use the $assignment property for most vars.
		// Assignments and templates just go in as is.
		// Mainly this is needed to sort out URLs and IDs
		$assignment = $object_type == RPC_Smarty::OBJECT_TYPE_LINKED_ASSIGNMENT ? $object->assignment : $object;
		$arr_assign_base_vars = array(
			'author' => $assignment->author,
			'title' => $assignment->title,
			'description' => $assignment->description,
			'class' => $assignment->class,
			'creation_date' => $assignment->creation_date,
			'is_editable' => $assignment->is_editable ? 1 : 0,
			'parent' => $assignment->parent,
			'parent_type' => $assignment->parent_type,
			'ancestral_template' => $assignment->ancestral_template,
			'steps' => array()
		);

		switch ($object_type)
		{
			case RPC_Smarty::OBJECT_TYPE_ASSIGNMENT:
				$arr_assign_additional_vars = array(
					'id' => $assignment->id,
					'type' => "assignment",
					'start_date' => $assignment->start_date,
					'due_date' => $assignment->due_date,
					'days_left' => $assignment->days_left,
					'is_shared' => $assignment->is_shared ? 1 : 0,
					'is_temporary' => $assignment->is_temporary ? 1 : 0,
					'send_reminders' => $assignment->send_reminders ? 1 : 0,
					'parent_url' => !empty($assignment->parent) && $assignment->parent_type == "assignment" ? RPC_Assignment::get_url($assignment->parent, "", $this->config) : RPC_Template::get_url($assignment->parent, "", $this->config),
					'url' => $assignment->url,
					'url_edit' => $assignment->url_edit,
					'url_delete' => $assignment->url_delete,
					'url_copy' => $assignment->url_copy,
					'url_link' => $assignment->url_link,
					'default_edit_mode' => $assignment->default_edit_mode,
					'valid_actions' => $assignment->valid_actions
				);
				break;
			case RPC_Smarty::OBJECT_TYPE_TEMPLATE:
				$arr_assign_additional_vars = array(
					'id' => $assignment->id,
					'type' => "template",
					'author_name' => $assignment->author_name,
					'lastedit_name' => $assignment->lastedit_name,
					'is_published' => $assignment->is_published ? 1 : 0,
					'parent_url' => !empty($assignment->parent) && $assignment->parent_type == "assignment" ? RPC_Assignment::get_url($assignment->parent, "", $this->config) : RPC_Template::get_url($assignment->parent, "", $this->config),
					'url' => $assignment->url,
					'url_edit' => $assignment->url_edit,
					'url_delete' => $assignment->url_delete,
					'default_edit_mode' => $assignment->default_edit_mode,
					'valid_actions' => $assignment->valid_actions
				);
				break;
			case RPC_Smarty::OBJECT_TYPE_LINKED_ASSIGNMENT:
				$arr_assign_additional_vars = array(
					'id' => $object->id,
					'type' => "link",
					'start_date' => $assignment->start_date,
					'due_date' => $assignment->due_date,
					'days_left' => $assignment->days_left,
					'send_reminders' => $object->send_reminders,
					'parent_url' => RPC_Assignment::get_url($assignment->parent, "", $this->config),
					'url' => $object->url,
					'url_edit' => $object->url_edit,
					'url_delete' => $object->url_delete,
					'default_edit_mode' => "BASIC", // Advanced mode not relevant for linked assignments
					'valid_actions' => $object->valid_actions
				);
				break;
			default: return FALSE;
		}

		$arr_assign_vars = array_merge($arr_assign_base_vars, $arr_assign_additional_vars);
		// Both assignments and templates get the same array vars for steps
		foreach ($assignment->steps as $step)
		{
			$arr_assign_vars['steps'][] = array(
				'id' => $step->id,
				'title' => $step->title,
				'description' => $step->description,
				'teacher_description' => $step->teacher_description,
				'annotation' => $step->annotation,
				'position' => $step->position,
				'due_date' => $step->due_date,
				'days_left' => $step->days_left,
				'percent' => !empty($step->percent) ? $step->percent : 0
			);
		}
		$this->assign('assignment', $arr_assign_vars);
		return;
	}

	/**
	 * Set template variables related to RPC_User $user or NULL
	 *
	 * @param object $user RPC_User
	 * @access public
	 * @return void
	 */
	public function set_user($user)
	{
		if ($user === NULL)
		{
			$this->assign('user', NULL);
			return;
		}
		$arr_user_vars = array(
			'id' => $user->id,
			'username' => $user->username,
			// Default empty name to username
			'name' => !empty($user->name) ? $user->name : $user->username,
			'email' => $user->email,
			'type' => $user->type,
			'is_publisher' => $user->is_publisher ? 1 : 0,
			'is_administrator' => $user->is_administrator ? 1 : 0,
			'is_superuser' => $user->is_superuser ? 1 : 0,
		);

		$this->assign('user', $arr_user_vars);
		return;
	}

	public function set_step($step)
	{
		$arr_step_vars = array(
			'id' => $step->id,
			'title' => $step->title,
			'description' => $step->description,
			'teacher_description' => $step->teacher_description,
			'annotation' => $step->annotation,
			'position' => $step->position,
			'due_date' => $step->due_date,
			'days_left' => $step->days_left
		);
		$this->assign('step', $arr_step_vars);
	}
}
?>
