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

// Main RPC assignment
var assign;
var rpc_updateAssignDate = true;
// rpc_stepDnd: dojo.dnd.Source
var rpc_stepDnd;
var rpc_stepDndDragStartHandle;
var rpc_stepDndDropHanle;
// rpc_Dialog: dijit.Dialog dialog box called when needed.
var rpc_Dialog;
var rpc_aDates = [];
// Keep track of open WYSIWYG editors
var rpc_openEditors = [];
// Global settings for WYSIWYG editors
var rpc_editorSettings = {
	focusOnLoad: true,
	extraplugins: ["dijit._editor.plugins.AlwaysShowToolbar","dijit._editor.plugins.FontChoice"],
	plugins: ["undo","redo","cut","copy","paste","|","removeFormat","formatBlock","bold","italic","underline","strikethrough","|","subscript","superscript","|","insertOrderedList","insertUnorderedList","indent","outdent","|","createLink","unlink","insertImage"]
};
var rpc_DateTextBoxSettings = {
	datePattern: rpc_dateShortFmt,
	selector: 'date',
	hasDownArrow: false
};
// Global settings for dijit InlineEditBox
var rpc_TitleInlineEditorSettings = { autoSave: false };
var rpc_NumberSpinnerSettings = {
	smallDelta: 10,
	constraints: {
		min: 0,
		max: 100,
		places: 0
	}
};

// Do all requires after load completes.
// Note: dijit.Editor and associated components
// won't be loaded until needed when an editor is launched.
// FX not needed until step creation/deletion
var rpc_initRequires = function() {
	dojo.require('dojo.dnd.Source');
	dojo.require('dijit.InlineEditBox');
	dojo.require('dijit.Dialog');
	dojo.require('dijit.form.DateTextBox');
};
dojo.addOnLoad(rpc_initRequires);
var rpc_initAdditionalRequires = function() {
	// Regular assignments use DateTextBox, Templates use NumberSpinner
	if (rpc_assignParams.objectType == "template") {
		dojo.require('dijit.form.NumberSpinner');
	}
};
dojo.addOnLoad(rpc_initAdditionalRequires);

/**
 * Setup the step list Dnd system
 */
var rpc_initStepDnd = function() {
	// In basic edit mode, start the DnD source disabled
	var dndIsSource = rpc_assignParams.editMode == "BASIC" ? false : true;
	// .dojoDndItem was removed if rpc_destroyStepDnd() was called
	dojo.query(".step-container").addClass("dojoDndItem");
	dojo.query(".step-head-content-edit").addClass("dojoDndHandle");
	rpc_stepDnd = new dojo.dnd.Source(dojo.byId('stepListDnd'), {withHandles: true, singular: true, isSource: dndIsSource});
	rpc_stepDndDragStartHandle = dojo.connect(rpc_stepDnd, "onDndStart", null, rpc_stepStartDrag);
	rpc_stepDndDropHanle = dojo.connect(rpc_stepDnd, "onDndDrop", null, rpc_stepDrop);

	// Immediately turn it off in basic mode
	if (rpc_assignParams.editMode == "BASIC") {
		rpc_disableStepDnd();
	}
};
var rpc_enableStepDnd = function() {
	dojo.query(".step-container").addClass("dojoDndItem");
	dojo.query(".step-head-content-edit").addClass("dojoDndHandle");
	rpc_stepDnd.isSource = true;
	rpc_stepDnd.sync();
}
/**
 * Remove the DnD system
 */
var rpc_disableStepDnd = function() {
	// Failure to disconnect handles causes them to execute 2x
	// Necessary to take off .dojoDndItem so the hover elements are all removed
	rpc_stepDnd.selectNone();
	dojo.query(".step-container.dojoDndItem").removeClass("dojoDndItem");
	dojo.query(".step-head-content-edit.dojoDndHandle").removeClass("dojoDndHandle");
	rpc_stepDnd.isSource = false;
}
// Initialization of drag/drop list area and RPCAssignment object
var rpc_initAssignmentEditor = function() {
	// Build the assignment
	try {
		rpc_initStepDnd();
		assign = new RPCAssignment(rpc_assignParams);
	}
	catch(err) {
		var addErr = "";
		rpc_showDialog('An error occurred. The assignment editor cannot be loaded.' + addErr, 'Assignment error');
		return;
	}
	if (assign.id == undefined) {
		rpc_showDialog('An error occurred. The assignment editor cannot be loaded.', 'Assignment error');
		return;
	}
};
dojo.addOnLoad(rpc_initAssignmentEditor);
// Verify all WYSIWYGs are closed before navigating away
var rpc_onUnload = function() {
	if (rpc_openEditors.length > 0) {
		return "You have unsaved changes in step instructions or annotations!";
	}
};
dojo.addOnLoad(function() {
	window.onbeforeunload = rpc_onUnload;
});

/**
 * Callback for start drag operation.
 * Stores the current order of steps and due dates
 */
function rpc_stepStartDrag(source, nodes, copy) {
	var step = nodes[0].RPCStep;
	step.oldPosition = step.position;
	// Resync assign.steps
	assign.updateStepsList();
	// Resync rpc_aDates with up to date list
	step.beforeMove();
	return;
}
/**
 * Callback for drop action onDndDrop()
 */
function rpc_stepDrop(source, nodes, copy) {
	var step = nodes[0].RPCStep;
	var droppedId = step.stepId;
	step.updateListPosition();
	// Attempt to move it to the new position on the server
	step.moveTo(step.position);

	source.selectNone();
	assign.updateStepHeaders();
	// Animate the dropped item's background-color
	step.animateStepChange();
}

/**
 * Class RPCAssignment
 * Receives object params to set internal defaults
 * NOTE: Presently, object params is constructed by a PHP Smarty template
 * This seems like a stupid way to do it, and it's on the TODO list to fix
 * Meanwhile, the RPCStep objects are still screen-scraped by DOM id.  Also needs
 * fixing.
 * --MJB
 *
 * @param object params
 * @access public
 * @return RPCAssignment
 */
function RPCAssignment(params) {
	if (params.objectType !== "assignment" && params.objectType !== "template" && params.objectType !== "link") {
		console.error("Illegal object type: " + params.objectType);
		return;
	}

	this.transId = params.transId;
	this.objectType = params.objectType;
	this.userType = params.userType == "STUDENT" || params.userType == "TEACHER" ? params.userType : "STUDENT";
	this.editMode = params.editMode;
	this.id = params.id;
	this.node = dojo.byId("assignment");
	this.title = params.title;
	this.description = params.description;
	this.className = params.className;
	this.steps = dojo.query("li.step-container")

	// Common DOM components
	this.titleNode = dojo.byId('assign-title');
	this.descriptionNode = dojo.byId('assign-descrption');
	this.classNameNode = dojo.byId('assign-classname');

	switch (this.objectType) {
		case "assignment":
			this.startDate = rpc_YYYYMMDDToDate(params.startDate);
			this.dueDate = rpc_YYYYMMDDToDate(params.dueDate);
			this.daysLeft = params.daysLeft;
			this.sendReminders = params.sendReminders;
			this.shared = params.shared;
			// Assignment DOM components
			this.dateStartNode = dojo.byId('assign-start');
			this.dateDueNode = dojo.byId('assign-due');
			this.daysLeftNode = dojo.byId('assign-daysleft');
			break;
		case "template":
			this.authorNode = dojo.byId('template-author-name');
			this.lastEditName = dojo.byId('template-lastedit-name');
			this.published = params.published;
			break;
		case "link":
			this.sendReminders = params.sendReminders;
			break;
	}

	// XHR params
	this.xhrArgs = {};
	this.xhrArgs.form = dojo.byId('frm-assign-handler');
	this.xhrArgs.timeout = 5000;
	this.xhrArgs.handleAs = "text";
	this.xhrArgs.error = rpc_error;
	// Unlinke RPCStep.xhrArgs.load, all of them can basically share
	// for the RPCAssignment object
	var tmp_this = this;
	this.xhrArgs.load = function(response, ioArgs) {
		var aResp = response.split('|');
		switch (aResp[0]) {
			case "OK":
				tmp_this.transId = aResp[2];
				tmp_this.form.transid.value = tmp_this.transId;
				rpc_showDialog("Assignment was successfully updated.", "Assignment changed");
				break;
			case "FAIL":
				rpc_showDialog("Assignment could not be updated: " + aResp[1], "Change failed");
				break;
		}
	};

	// AJAX params
	this.form = {};
	this.form.transid = dojo.byId('assign-handler-transid');
	this.form.id = dojo.byId('assign-handler-id');
	this.form.action = dojo.byId('assign-handler-action');
	this.form.val = dojo.byId('assign-handler-val');
	this.form.type = dojo.byId('assign-handler-type');
	this.form.transid.value = this.transId;
	this.form.id.value = this.id;
	// Assignment or template gets passed back to PHP
	this.form.type.value = this.objectType;

	// Widgets
	// Title editor for assignments/templates, never linked assignments
	if (this.objectType !== "link") {
		this.titleWidget = new dijit.InlineEditBox(rpc_TitleInlineEditorSettings, this.titleNode);
		dojo.connect(this.titleWidget, "onChange", this, this.titleOnChange);
	}
	// Reminders for assignments or linked, never templates
	if (this.objectType == "assignment" || this.objectType == "link") {
		this.sendRemindersWidget = dojo.byId('assign-remind');
		dojo.connect(this.sendRemindersWidget, "onchange", this, this.sendRemindersOnChange);
		// IE needs to blur() onclick so the onchange fires
		dojo.connect(this.sendRemindersWidget, "onclick", this, function(){if (dojo.isIE) {this.sendRemindersWidget.blur();}});
	}

	switch (this.objectType) {
		// Date widgets, reminders, shared, edit mode only available for regular assignments
		case "assignment":
			rpc_DateTextBoxSettings.value = this.dueDate;

			this.dateDueWidget = new dijit.form.DateTextBox(rpc_DateTextBoxSettings, this.dateDueNode);
			// Will need to know if focused so the date onChange doesn't always fire.
			this.dateDueWidget.rpc_hasLastFocus = false;
			dojo.connect(this.dateDueWidget, "onChange", this, this.dateDueOnChange);
			dojo.connect(this.dateDueWidget, "onFocus", this, function(){this.rpc_hasLastFocus = true;});

			rpc_DateTextBoxSettings.value = this.startDate;
			this.dateStartWidget = new dijit.form.DateTextBox(rpc_DateTextBoxSettings, this.dateStartNode);
			this.dateStartWidget.rpc_hasLastFocus = false;
			dojo.connect(this.dateStartWidget, "onChange", this, this.dateStartOnChange);
			dojo.connect(this.dateStartWidget, "onFocus", this, function(){this.rpc_hasLastFocus = true;});

			// Set date constraints on widgets
			this.updateDateConstraints();

			// Shared assignment
			this.sharedWidget = dojo.byId('assign-public');
			dojo.connect(this.sharedWidget, "onchange", this, this.sharedOnChange);
			dojo.connect(this.sharedWidget, "onclick", this, function(){if (dojo.isIE) {this.sharedWidget.blur();}});

			// Edit mode checkbox
			this.editModeWidget = dojo.byId('assign-advanced');
			dojo.connect(this.editModeWidget, "onchange", this, this.editModeOnChange);
			dojo.connect(this.editModeWidget, "onclick", this, function(){if (dojo.isIE) {this.editModeWidget.blur();}});
			break;
		case "template":
			// Published widget only available to templates
			this.publishedWidget = dojo.byId('template-published');
			dojo.connect(this.publishedWidget, "onchange", this, this.publishedOnChange);
			dojo.connect(this.publishedWidget, "onclick", this, function(){if (dojo.isIE) {this.publishedWidget.blur();}});
			break;
	}
	// Attach a RPCStep to each DnD step node
	var parentassign = this;
	dojo.forEach(this.steps, function(step, i) {
		step.RPCStep = new RPCStep(step, parentassign);
		step.RPCStep.position = i;
	});

	// Finally, issue a warning if editing a template
	if (this.objectType == "template")
	{
		rpc_showDialog("Warning: You are editing an assignment template. <br />Any changes you make will be immediately visible in published templates!", "Warning");
	}
	return;
}
/**
 * Enable/disable advanced control widgets
 */
RPCAssignment.prototype.editModeOnChange = function(value) {
	// Cannot change modes of template or link
	if (this.objectType !== "assignment") {
		return false;
	}
	var advEnabled = this.editModeWidget.checked;
	if (advEnabled) {
		rpc_showDialog("<strong>Attention:</strong><br />Enabling advanced controls will allow you to make changes to assignment steps, <br />possibly compromising the original intent of its authors.<br />Only make changes if you are certain you want to!", "Warning");
		dojo.query(".ctl-advanced", this.node).style("visibility", "visible");
		rpc_enableStepDnd();
		this.editMode = "ADVANCED"
	}
	else {
		dojo.query(".ctl-advanced", this.node).style("visibility", "hidden");
		rpc_disableStepDnd();
		this.editMode = "BASIC"
	}
	// Switch step titles on or off
	dojo.forEach(this.steps, function(step, i) {
		step.RPCStep.titleWidget.attr("disabled", !advEnabled);
	});
}
/**
 * onChange handler for assignment title
 */
RPCAssignment.prototype.titleOnChange = function(value) {
	this.form.transid.value = this.transId;
	// RPC_Assignment::ACTION_SET_TITLE == 3
	this.form.action.value = 3;
	this.form.val.value = value;
	var deferred = dojo.xhrPost(this.xhrArgs);
	deferred.addCallback(this, function() {
		this.title = value;
	});
	return;
};

RPCAssignment.prototype.dateStartOnChange = function(value) {
	this.form.transid.value = this.transId;
	// RPC_Assignment::ACTION_SET_STARTDATE == 11
	this.form.action.value = 11;
	dojo.byId("assign-start-calbutton").focus();
	this.dateOnChange(value);
	return;
};
RPCAssignment.prototype.dateDueOnChange = function(value) {
	this.form.transid.value = this.transId;
	// RPC_Assignment::ACTION_SET_DUEDATE == 12
	this.form.action.value = 12;
	dojo.byId("assign-due-calbutton").focus();
	this.dateOnChange(value);
	this.daysLeft = rpc_getDaysFromToday(this.dueDate);
	this.daysLeftNode.innerHTML = this.daysLeft >= 1 ? this.daysLeft : "None";
	return;
};
/**
 * onChange handler for assignment dates
 */
RPCAssignment.prototype.dateOnChange = function(value) {
	// If an active step is currently flagged, disable it to prevent
	// its onchange from firing.
	RPCStep.activeStep = null;

	this.form.val.value = rpc_DateToYYYYMMDD(value);
	var tmp_this = this;
	// Only this method uses a different xhr load handler so clone its xhrArgs param
	var datexhrArgs = {};
	datexhrArgs.form = this.xhrArgs.form;
	datexhrArgs.timeout = this.xhrArgs.timeout;
	datexhrArgs.handleAs = this.xhrArgs.handleAs;
	datexhrArgs.error = this.xhrArgs.error;

	datexhrArgs.load = function(response, ioArgs) {
		var aResp = response.split('|');
		switch (aResp[0]) {
			case "OK":
				tmp_this.transId = aResp[2];
				tmp_this.form.transid.value = tmp_this.transId;
				// Update step dueDates with return JSON
				tmp_this.datesFromJSON(aResp[3]);
				break;
			case "FAIL":
				rpc_showDialog("Assignment could not be updated: " + aResp[1], "Change failed");
				break;
		}
	};
	if (rpc_updateAssignDate) {
		var deferred = dojo.xhrPost(datexhrArgs);
		deferred.addCallback(this, this.dateOnChangeCallback);
		deferred.addCallback(this, function(){
			rpc_showDialog("Assignment was successfully updated.\n<br />All step due dates have been recalculated!", "Assignment changed");
		});
	}
	// If this wasn't an AJAX transaction, just call dateOnChangeCallback to update constraints etc.
	else {
		this.dateOnChangeCallback();
	}
	// Reset flag to allow next date update
	rpc_updateAssignDate = true;
	return deferred;
};
/**
 * Actions to take after the main dates change:
 * Update the dueDate and daysLeft as well as their nodes
 * Strip current date constraints so no steps report range errors after update,
 * then set new constraints on all date widgets.
 */
RPCAssignment.prototype.dateOnChangeCallback = function() {
	this.removeDateConstraints();
	this.startDate = this.dateStartWidget.attr('value');
	this.dueDate = this.dateDueWidget.attr('value');
	this.daysLeft = rpc_getDaysFromToday(this.dueDate);
	this.daysLeftNode.innerHTML = this.daysLeft >= 1 ? this.daysLeft : "None";
	this.updateDateConstraints();
	// Reset the days left of each step
	dojo.forEach(this.steps, function(step, i) {
		step.RPCStep.dateOnChangeCallback();
	});
};
/**
 * Return the RPCStep object of the assignments current first step
 */
RPCAssignment.prototype.getFirstStep = function() {
	return this.steps[0].RPCStep;
}
/**
 * Return the RPCStep object of the assignments current last step
 */
RPCAssignment.prototype.getLastStep = function() {
	return this.steps[this.steps.length - 1].RPCStep;
}
/**
 * Prevent start and due dates from overlapping each other
 * Set start date max constraint to due date,
 * Set due date min constraint to start date
 */
RPCAssignment.prototype.updateDateConstraints = function() {
	this.dateDueWidget.constraints.min = this.startDate;
	this.dateStartWidget.constraints.max = this.dueDate;
	// Step RPCStep objects aren't initialized the first time this gets called
	if (this.steps) {
		var tmp_this = this;
		dojo.forEach(this.steps, function(step, i) {
			if (step.RPCStep) {
				step.RPCStep.dateWidget.constraints.min = tmp_this.startDate;
				step.RPCStep.dateWidget.constraints.max = tmp_this.dueDate;
			}
		});
	}
};
/**
 * Remove date constraints from all assignment and child
 * step date widgets
 */
RPCAssignment.prototype.removeDateConstraints = function() {
	this.dateDueWidget.constraints.min = null;
	this.dateStartWidget.constraints.max = null;
	dojo.forEach(this.steps, function(step, i) {
		if (step.RPCStep) {
			step.RPCStep.dateWidget.constraints.min = null;
			step.RPCStep.dateWidget.constraints.max = null;
		}
	});
	return;
}
RPCAssignment.prototype.sendRemindersOnChange = function(value) {
	// RPC_Assignment::ACTION_SET_NOTIFY == 13
	this.form.action.value = 13;
	this.form.val.value = this.sendRemindersWidget.checked ? 1 : 0;
	var deferred = dojo.xhrPost(this.xhrArgs);
	if (dojo.isIE) {
		this.sendRemindersWidget.blur();
	}
	return;
};
RPCAssignment.prototype.sharedOnChange = function(value) {
	// RPC_Assignment::ACTION_SET_SHARED == 14
	this.form.action.value = 14;
	this.form.val.value = this.sharedWidget.checked ? 1 : 0;
	this.shared = this.sharedWidget.checked ? true : false;
	var deferred = dojo.xhrPost(this.xhrArgs);
	var linkNode = dojo.byId('assignment-sharelink');
	if (linkNode) {
		if (this.shared) {
			if (dojo.hasClass(linkNode, 'invisible')) {
				dojo.removeClass(linkNode, 'invisible');
			}
		}
		else {
			dojo.addClass(linkNode, 'invisible');
		}
	}
	if (dojo.isIE) {
		this.sharedWidget.blur();
	}
	return;
};
RPCAssignment.prototype.publishedOnChange = function(value) {
	// RPC_Template::ACTION_SET_PUBLISHED = 21
	this.form.action.value = 21;
	this.form.val.value = this.publishedWidget.checked ? 1 : 0;
	var deferred = dojo.xhrPost(this.xhrArgs);
	if (dojo.isIE) {
		this.publishedWidget.blur();
	}
};
/**
 * Return RPCStep for stepId from this.steps
 *
 * @param int stepId
 * @return RPCStep
 */
RPCAssignment.prototype.getStep = function(stepId) {
	var n = this.steps.length;
	for (var i=0; i<n; i++) {
		if (this.steps[i].RPCStep.stepId == stepId) {
			return this.steps[i].RPCStep;
		}
	}
	return false;
};
/**
 * Update step headings on screen to reflect changes to step position
 */
RPCAssignment.prototype.updateStepHeaders = function() {
	var positionNodes;
	if (this.objectType == "assignment") {
		var daysNodes;
		var today_t = new Date();
		var today = new Date(today_t.getFullYear(), today_t.getMonth(), today_t.getDate());
	}
	dojo.forEach(this.steps, function(step, i) {
		step.RPCStep.position = i;
		step.RPCStep.positionNode.innerHTML = i + 1; // Switch to 1-base
		// for assignments, set all the dates
		if (this.objectType == "assignment") {
			step.RPCStep.daysLeft = rpc_getDaysFromToday(step.RPCStep.dueDate);
			step.RPCStep.daysLeftNode.innerHTML = step.RPCStep.daysLeft >= 1 ? step.RPCStep.daysLeft : "None";
		}
	});
	return;
};
/**
 * Update the current positions of all steps
 */
RPCAssignment.prototype.updateStepsList = function() {
	// Load all step nodes
	this.steps = rpc_stepDnd.getAllNodes();
	// Iterate to set their current positions
	dojo.forEach(this.steps, function(step, i) {
		step.RPCStep.oldPosition = step.RPCStep.position;
		step.RPCStep.position = i;
	});
};
/**
 * Iterate over this.steps and return a JSON object of step due dates
 * in the format YYYYMMDD indexed by step id
 */
RPCAssignment.prototype.datesToJSON = function() {
	var json = {};
	json.dates = [];
	dojo.forEach(this.steps, function(step, i) {
		var d = rpc_DateToYYYYMMDD(step.RPCStep.dueDate);
		json.dates.push({
			"id": step.RPCStep.stepId,
			"dueDate": rpc_DateToYYYYMMDD(step.RPCStep.dueDate)
		});
	});
	return dojo.toJson(json);
}
/**
 * Receives a JSON object and updates step due dates
 * Array: 'dates' (object)
 * dates[].id (integer step id)
 * dates[].dueDate (due date YYYYMMDD)
 */
RPCAssignment.prototype.datesFromJSON = function(json) {
	var o = dojo.fromJson(json);
	if (!o.dates) {
		return false;
	}
	dojo.forEach(this.steps, function(step, i) {
		// Steps may be in the wrong order.
		// Find the step's new date then null it out in the array
		dojo.forEach(o.dates, function(d, i){
			if (d && (d.id == step.RPCStep.stepId)) {
				step.RPCStep.dueDate = rpc_YYYYMMDDToDate(d.dueDate);
				step.RPCStep.dateWidget.attr("value", step.RPCStep.dueDate);
				o.dates[i] = null;
			}
		});
	});
}
/**
 * Class RPCStep
 *
 * @constructor
 * @param DOMnode stepNode Node to attach
 * @param RPCAssignment parent assignment
 */
function RPCStep(stepNode, parentAssignment) {
	this.parentAssignment = parentAssignment;
	this.node = stepNode;
	this.stepId = null;
	this.position = null;
	this.positionNode = null;
	this.oldPosition = null;
	this.titleNode = null;
	this.titleWidget = null;
	// Editor widget only gets loaded when called
	this.editorWidget = [];
	this.editorOldValue = [];

	// Assignment steps only
	if (this.parentAssignment.objectType == "assignment") {
		this.dueDate = null;
		this.dateWidget = null;
		this.daysLeft = null;
		this.daysLeftNode = null;
	}
	// Template steps only
	else {
		this.percent = null;
		this.percentNode = null;
		this.percentWidget = null;
	}

	// AJAX form submission nodes
	this.form = {};
	this.form.transid = dojo.byId('step-handler-transid');
	this.form.id = dojo.byId('step-handler-id');
	this.form.action = dojo.byId('step-handler-action');
	this.form.val_1 = dojo.byId('step-handler-val_1');
	this.form.val_2 = dojo.byId('step-handler-val_2');

	// AJAX xhr defaults
	this.xhrArgs = {};
	this.xhrArgs.form = dojo.byId('frm-step-handler');
	this.xhrArgs.timeout = 5000;
	this.xhrArgs.handleAs = "text";
	this.xhrArgs.error = rpc_error;
	this.xhrArgs.load = null;

	var idx = this.node.id.indexOf("_");
	if (idx) {
		this.stepId = parseInt(this.node.id.substring(idx + 1), 10);
		if (!this.stepId) {
			console.error("Invalid RPCStep at node " + this.node.id);
			return;
		}
	}
	else {
		console.error("Invalid RPCStep at node " + this.node.id);
		return;
	}

	// Populate DOM node properties by scraping
	this.positionNode = dojo.query(".step-num", this.node).pop();

	if (this.parentAssignment.objectType !== "link") {
		// Attach title widget (Dijit.InlineEditBox)
		this.titleNode = dojo.query(".step-title", this.node).pop();
		this.titleWidget = new dijit.InlineEditBox(rpc_TitleInlineEditorSettings, this.titleNode);
		dojo.connect(this.titleWidget, "onChange", this, this.titleOnChange);
		// Disabled title in BASIC edit mode
		if (this.parentAssignment.editMode == "BASIC") {
			this.titleWidget.attr("disabled", true);
		}
	}

	switch (this.parentAssignment.objectType) {
		case "assignment":
			// HTML nodes for days left and step number are first .days-left, .step-num
			// class for this.node
			this.daysLeftNode = dojo.query(".days-left", this.node).pop();
			this.daysLeft = this.daysLeftNode.innerHTML;

			// Attach date widget (Dijit.DateTextBox)
			var dateNode = dojo.query(".rpc-DateTextBox", this.node).pop();
			this.dueDate = rpc_YYYYMMDDToDate(dateNode.value);
			rpc_DateTextBoxSettings.value = this.dueDate;
			this.dateWidget = new dijit.form.DateTextBox(rpc_DateTextBoxSettings, dateNode);
			dojo.connect(this.dateWidget, "onChange", this, this.dateOnChange);
			// The most recently activated date widget is stored
			dojo.connect(this.dateWidget, "onFocus", this, function() {RPCStep.activeStep = this;});
			dojo.connect(this.dateWidget, "onBlur", this, function() {RPCStep.activeStep = null;});

			// By default, all date widgets are bound by start/due dates
			this.dateWidget.constraints.min = parentAssignment.startDate;
			this.dateWidget.constraints.max = parentAssignment.dueDate;
			break;
		// Percent node and widget for template types
		case "template":
			this.percentNode = dojo.query(".step-percent", this.node).pop();
			this.percent = parseInt(this.percentNode.value, 10) > 0 ? parseInt(this.percentNode.value, 10) : 0;
			// Dijit NumberSpinner attaches to percentNode
			this.percentWidget = new dijit.form.NumberSpinner(rpc_NumberSpinnerSettings, this.percentNode);
			this.percentWidget.attr("intermediateChanges", true);
			this.percentWidget.attr("value", this.percent);
			dojo.connect(this.percentWidget, "onChange", this, this.percentOnChange);
			dojo.connect(this.percentWidget, "onFocus", this, function() {RPCStep.activeStep = this;});
			dojo.connect(this.percentWidget, "onBlur", this, function() {RPCStep.activeStep = null;});
			break;
	}
	return;
}
/**
 * Static members
 */
RPCStep.activeStep = null;

RPCStep.prototype.dateOnChange = function(value) {
	/* We don't want this event to fire if the widget value is changed
	 * as a result of another widget updating (such as reordering steps),
	 * as that would result in all steps constantly updating duedates.
	 *
	 * Instead check that this was widget was activated by onClick.
	 */
	if (this === RPCStep.activeStep) {

		this.form.transid.value = this.parentAssignment.transId;
		this.form.id.value = this.stepId;
		// Action RPC_Step::ACTION_SET_DUEDATE = 8
		this.form.action.value = 8;
		this.form.val_1.value = rpc_DateToYYYYMMDD(value);
		this.form.val_2.value = "EXTEND";

		// Post to AJAX
		var tmp_this = this;
		this.xhrArgs.load = function(response, ioArgs) {
			var aResp = response.split('|');
			switch (aResp[0]) {
				case "OK":
					tmp_this.parentAssignment.transId = aResp[2];
					tmp_this.parentAssignment.form.transid.value = aResp[2];
					tmp_this.form.transid.value = aResp[2];
					// Focus the days left node, to prevent the calendar from popping back up
					// when the dialog box is dismissed since the dialog's refocus is set for accessibility
					dojo.query(".cal-button", tmp_this.node).pop().focus();
					rpc_showDialog("Step due date was successfully updated.", "Assignment changed");
					RPCStep.activeStep = null;
					break;
				case "FAIL":
					rpc_showDialog("Step due date could not be updated: " + aResp[1], "Change failed");
					break;
			}
		};
		var deferred = dojo.xhrPost(this.xhrArgs);
		deferred.addCallback(this, function(){this.dueDate = value;});
		deferred.addCallback(this, this.dateOnChangeCallback);
		// If this was the last step, modify the assignment duedate info too.
		deferred.addCallback(this, function(){
			if (this === this.parentAssignment.getLastStep()) {
				// Make sure the AJAX transaction doesn't fire on the assignment
				rpc_updateAssignDate = false;
				this.parentAssignment.dateDueWidget.attr("value", this.dueDate);
			}
		});
	}
	return;
};
/**
 * Actions to take after changing date:
 * Update days left node
 */
RPCStep.prototype.dateOnChangeCallback = function() {
	//this.dueDate = value;
	this.daysLeft = rpc_getDaysFromToday(this.dueDate);
	this.daysLeftNode.innerHTML = this.daysLeft >= 1 ? this.daysLeft : "None";
};

/**
 * onChange callback for step title InlineEditBox widgets
 */
RPCStep.prototype.titleOnChange = function(value) {
	this.form.transid.value = this.parentAssignment.transId;
	this.form.id.value = this.stepId;
	// Action RPC_Step::ACTION_SET_TITLE = 5
	this.form.action.value = 5;
	this.form.val_1.value = value;
	this.form.val_2.value = "";

	var tmp_this = this;
	this.xhrArgs.load = function(response, ioArgs) {
		var aResp = response.split('|');
		switch (aResp[0]) {
			case "OK":
				tmp_this.parentAssignment.transId = aResp[2];
				tmp_this.parentAssignment.form.transid.value = aResp[2];
				tmp_this.form.transid.value = aResp[2];
				// On success, show dialog
				rpc_showDialog("Step title successfully updated", "Assignment changed");
				break;
			case "FAIL":
				// On failure, restore value
				// TODO: Restore the value!
				rpc_showDialog("An error occurred. Step title could not be updated: " + aResp[1], "Change failed");
				break;
		}
	};
	// Post to AJAX
	var deferred = dojo.xhrPost(this.xhrArgs);
	return;
};

/**
 * onChange callback for step title NumberSpinner percent widgets
 * Shows/hides a button used to actually send the new percent via AJAX
 */
RPCStep.prototype.percentOnChange = function(value) {
	if (RPCStep.activeStep == this) {
		var saveButton = dojo.query(".step-savepercent", this.node).pop();
		if (this.percent != value) {
			dojo.style(saveButton, "display", "inline");
		}
		else {
			dojo.style(saveButton, "display", "none");
		}
	}
};
/**
 * Onclick event for step-savepercent buttons
 * Checks that total percentage of all steps is <= 100 and saves changes
 */
RPCStep.prototype.updatePercent = function() {
	// Add up all step percents with this widget value and make sure they're <= 100
	var sum = parseInt(this.percentWidget.value, 10);
	var thisId = this.stepId;
	dojo.forEach(this.parentAssignment.steps, function(step, i){
		if (step.RPCStep.stepId != thisId) {
			sum += step.RPCStep.percent;
		}
	});
	if (sum > 100) {
		rpc_showDialog("Attention: Step percentages add up to more than 100%.<br/>Adjust some and try again.", "Assignment error");
		this.percentWidget.attr("value", this.percent);
		return;
	}
	else {
		// Update via AJAX
		this.percent = parseInt(this.percentWidget.value, 10);
		dojo.style(dojo.query(".step-savepercent", this.node).pop(), "display", "none");

		this.form.transid.value = this.parentAssignment.transId;
		this.form.id.value = this.stepId;
		this.form.action.value = 11; // RPC_Step::ACTION_SET_PERCENT == 11
		this.form.val_1.value = this.percent;
		this.form.val_2.value = "";
		var tmp_this = this;
		this.xhrArgs.load = function(response, ioArgs) {
			var aResp = response.split('|');
			switch (aResp[0]) {
				case "OK":
					tmp_this.parentAssignment.transId = aResp[2];
					tmp_this.parentAssignment.form.transid.value = aResp[2];
					tmp_this.form.transid.value = aResp[2];
					rpc_showDialog("Step percent was successfully updated.", "Assignment changed");
					RPCStep.activeStep = null;
					break;
				case "FAIL":
					rpc_showDialog("Step percent could not be updated: " + aResp[1], "Change failed");
					break;
			}
		};
		var deferred = dojo.xhrPost(this.xhrArgs);
	}
};
/**
 * Find this RPCStep.position within containing <ol> or <ul> node
 *
 * @return int position or -1 if not found
 */
RPCStep.prototype.updateListPosition = function() {
	var liNodesList = rpc_stepDnd.getAllNodes();
	for (var i=0; i<liNodesList.length; i++) {
		if (liNodesList[i].id == this.node.id) {
			this.oldPosition = this.position;
			this.position = i;
			return i;
		}
	}
	// Not found - return -1
	return -1;
};

/**
 * Permanently remove step
 */
RPCStep.prototype.remove = function() {
	// dojo.fx is needed for wipeOut
	dojo.require('dojo.fx');

	this.form.transid.value = this.parentAssignment.transId;
	this.form.id.value = this.stepId;
	this.form.action.value = 4;
	this.form.val_1.value = "";
	this.form.val_2.value = "";
	var tmp_this = this;
	this.xhrArgs.load = function(response, ioArgs) {
		var aResp = response.split('|');
		switch (aResp[0]) {
			case "OK":
				tmp_this.parentAssignment.transId = aResp[2];
				tmp_this.parentAssignment.form.transid.value = aResp[2];
				tmp_this.form.transid.value = aResp[2];
				dojo.fx.wipeOut({
					node: tmp_this.node,
					duration: 500,
					onEnd: function() {
						// Destroy the DOMnode and Reload the step array
						dojo.destroy(tmp_this.node);
						tmp_this.parentAssignment.updateStepsList();
						tmp_this.parentAssignment.updateStepHeaders();
					}
				}).play();
				rpc_showDialog("Step was successfully removed.", "Assignment changed");
				break;
			case "FAIL":
				rpc_showDialog("An error occurred. Step could not be removed: " + aResp[1], "Step deletion failed");
				break;
		}
	};
	var deferred = dojo.xhrPost(this.xhrArgs);
	return;
};
/**
 * Move step one position toward 0 within container ol/ul node
 *
 * @return integer new position
 */
RPCStep.prototype.moveUp = function() {
	if (this.position > 0) {
		this.beforeMove();
		// Move ahead of previous sibling
		dojo.place(this.node, this.parentAssignment.steps[this.position - 1], "before");
		if (this.moveTo(this.position - 1)) {
			this.afterMove();
			this.animateStepChange();
		}
	}
	this.updateListPosition();
	return this.position;
};

/**
 * Move step one position toward last within container ol/ul node
 *
 * @return integer new position
 */
RPCStep.prototype.moveDown = function() {
	// If more than 1 from the end, place after next sibling
	if (this.position <= this.parentAssignment.steps.length - 2) {
		this.beforeMove();
		// Move ahead of the next sibling
		dojo.place(this.node, this.parentAssignment.steps[this.position + 1], "after");
		if (this.moveTo(this.position + 1)) {
			this.afterMove();
			this.animateStepChange();
		}
	}
	this.updateListPosition();
	return this.position;
};

/**
 * Place step at position in the DnD and perform AJAX calls
 *
 * @param int position
 */
RPCStep.prototype.moveTo = function(position) {
	// Reload the step order
	this.parentAssignment.updateStepsList();
	// Need to update all the due dates, shifting them up or down.
	// if this is an assignment, not a tempalte
	if (this.parentAssignment.objectType == "assignment") {
		dojo.forEach(this.parentAssignment.steps, function(step, i) {
			// If the move causes a date change, update it.
			if (step.RPCStep.dueDate != rpc_aDates[i]) {
				step.RPCStep.dateWidget.attr('value', rpc_aDates[i]);
				step.RPCStep.dueDate = rpc_aDates[i];
				step.RPCStep.daysLeft = rpc_getDaysFromToday(step.RPCStep.dueDate);
				step.RPCStep.daysLeftNode.innerHTML = step.RPCStep.daysLeft >= 1 ? step.RPCStep.daysLeft : "None";
			}
		});
	}
	this.form.transid.value = this.parentAssignment.transId;
	this.form.id.value = this.stepId;
	// RPC_Step::ACTION_MOVE_TO_POS == 3
	this.form.action.value = 3;
	// PHP receives these as true step positions, 1-based rather than 0
	this.form.val_1.value = position + 1;
	this.form.val_2.value = this.parentAssignment.objectType == "assignment" ? this.parentAssignment.datesToJSON() : "";
	var tmp_this = this;
	this.xhrArgs.load = function(response, ioArgs) {
		var aResp = response.split('|');
		switch (aResp[0]) {
			case "OK":
				tmp_this.parentAssignment.transId = aResp[2];
				tmp_this.parentAssignment.form.transid.value = aResp[2];
				tmp_this.form.transid.value = aResp[2];
				rpc_showDialog("Assignment step order was successfully updated.", "Assignment changed");
				break;
			case "FAIL":
				rpc_showDialog("An error occurred. Step could not be moved: " + aResp[1], "Step move failed");
				break;
		}
		return response;
	};
	var deferred = dojo.xhrPost(this.xhrArgs);
	return true;
};
/**
 * Functions to execute before attempting to move a step
 */
RPCStep.prototype.beforeMove = function() {
	this.oldPosition = this.position;
	this.parentAssignment.updateStepsList();
	var tmp_assign = this.parentAssignment;
	rpc_aDates = [];
	dojo.forEach(tmp_assign.steps, function(step, i) {
		rpc_aDates.push(tmp_assign.steps[i].RPCStep.dueDate);
	});
};
/**
 * Functions to execute after moving a step
 */
RPCStep.prototype.afterMove = function() {
	this.oldPosition = this.position;
	this.parentAssignment.updateStepsList();
	this.parentAssignment.updateStepHeaders();
	var tmp_assign = this.parentAssignment;
	rpc_aDates = [];
	dojo.forEach(tmp_assign.steps, function(step, i) {
		rpc_aDates.push(tmp_assign.steps[i].RPCStep.dueDate);
	});
};

/**
 * Add a new step below DOMnode stepNode
 * where stepNode is a <li> step list node
 */
RPCStep.prototype.addStepBelow = function() {
	// dojo.fx is needed for wipeOut
	dojo.require('dojo.fx');

	this.form.transid.value = this.parentAssignment.transId;
	this.form.id.value = this.stepId;
	// RPC_Step::ACTION_CREATE_STEP_AFTER == 9
	this.form.action.value = 9;
	this.form.val_1.value = "";
	this.form.val_2.value = "";
	var tmp_this = this;
	this.xhrArgs.load = function(response, ioArgs) {
		var aResp = response.split('|');
		// If the step HTML template had any pipe characters, we must
		// now combine them into one string since they were split into
		// array elements.
		// TODO: Fix this problem. It's a hack.
		var newStepHtml = "";
		if (aResp.length > 4) {
			for (var i=3; i<aResp.length; i++) {
				newStepHtml += aResp[i];
			}
		}
		// If aResp only has 4 elements we only need the full 4th.
		else {
			newStepHtml = aResp[3];
		}
		switch (aResp[0]) {
			case "OK":
				tmp_this.parentAssignment.transId = aResp[2];
				tmp_this.parentAssignment.form.transid.value = aResp[2];
				tmp_this.form.transid.value = aResp[2];
				// Stick the big html chunk onto the step list
				var newStepNode = dojo.place(newStepHtml, tmp_this.node, "after");
				// Height starts at 0 to wipe in.
				dojo.style(newStepNode, "height", "0px");
				// Attach the RPCStep
				try {
					newStepNode.RPCStep = new RPCStep(newStepNode, assign);
				}
				catch(err) {
					rpc_showDialog('An error occurred. The new step cannot be loaded.', 'Assignment error');
					return;
				}

				// Add the new step to the DnD and update the steps list
				// Requires re-enabling the DnD for a moment if it is disabled to be able to
				// resync steps correctly.
				// TODO: Fix this
				if (tmp_this.parentAssignment.editMode == "ADVANCED") {
					dojo.addClass(newStepNode, 'dojoDndItem');
					rpc_stepDnd.sync();
					tmp_this.parentAssignment.updateStepsList();
					tmp_this.parentAssignment.updateStepHeaders();
				}
				else {
					rpc_enableStepDnd();
					rpc_stepDnd.sync();
					tmp_this.parentAssignment.updateStepsList();
					tmp_this.parentAssignment.updateStepHeaders();
					rpc_disableStepDnd();
					// Reapply disabling controls for BASIC mode
					// If not done, they don't apply to the new step
					tmp_this.parentAssignment.editModeOnChange(false);
					// But enable the student content editor and title, since they're empty otherwise!
					dojo.query(".editor-controls.ctl-advanced", newStepNode).style("visibility", "visible");
					newStepNode.RPCStep.titleWidget.attr("disabled", false);
				}
				dojo.fx.wipeIn({
					node: newStepNode,
					duration: 500,
					onEnd: function() {
						// Chrome 5.0375 is doing weird things here, where edit nodes disappear
						// TODO: fix this ridiculous hack!
						// MJB, THIS MEANS YOU!!!
						if (dojo.isChrome || dojo.isSafari) {
							dojo.style(newStepNode, "display", "none");
							dojo.style(newStepNode, "display", "block");
						}
					}
				}).play();
				break;
			case "FAIL":
				rpc_showDialog("An error occurred. Step could not be added: " + aResp[1], "Change failed");
				break;
		}
	};
	var deferred = dojo.xhrPost(this.xhrArgs);
};
/**
 * Show animated visual feedback on a step node
 */
RPCStep.prototype.animateStepChange = function() {
	var old = dojo.style(this.node, "backgroundColor");
	dojo.animateProperty({
		node: this.node,
		duration: 1000,
		properties: {
			backgroundColor: {
				start: "#000",
				end: old
			}
		}
	}).play();
};

/**
 * Create a Dijit Rich Text Editor on the DOM element editNode,
 * and change the display button from edit mode to save mode
 *
 * @param string mode 't' = TEACHER, 's' = STUDENT, 'a' = ANNOTATION
 * @return void
 */
RPCStep.prototype.launchEditor = function(mode) {
	// Editor components get loaded on first edit
	dojo.require('dijit.Editor');
	dojo.require('dijit._editor.plugins.AlwaysShowToolbar');
	dojo.require('dijit._editor.plugins.LinkDialog');
	dojo.require('dijit._editor.plugins.FontChoice');

	// Store original value if edit is to be canceled...
	var modeType;
	switch (mode) {
		case 't': modeType = "teacher"; break;
		case 's': modeType = "student"; break;
		case 'a': modeType = "annotation"; break;
		default: return false;
	}
	var editNode = dojo.byId("stepdesc-" + modeType + "_" + this.stepId);
	this.editorOldValue[editNode.id] = editNode.innerHTML;

	dojo.byId(mode + '-close_' + this.stepId).style.display = 'inline';
	dojo.byId(mode + '-cancel_' + this.stepId).style.display = 'inline';
	dojo.byId(mode + '-open_' + this.stepId).style.display = 'none';
	// Editor as property of editnodes
	this.editorWidget[editNode.id] = new dijit.Editor(rpc_editorSettings, editNode);
	editNode.scrollIntoView(true);
	// Add to the list of open editors
	rpc_openEditors.push(editNode.id);
	return;
};
/**
 * Close Dijit Rich Text Editor on the DOM element editNode,
 * and change the display button from save mode to edit mode.
 * Save changes if saveChanges is true.
 *
 * @param string mode 't' = TEACHER, 's' = STUDENT
 * @param boolean saveChanges
 * @return void
 */
RPCStep.prototype.closeEditor = function(mode, saveChanges) {
	switch (mode) {
		case 't': modeType = "teacher"; break;
		case 's': modeType = "student"; break;
		case 'a': modeType = "annotation"; break;
		default: return false;
	}
	var editNode = dojo.byId("stepdesc-" + modeType + "_" + this.stepId);

	dojo.byId(mode + '-open_' + this.stepId).style.display = 'inline';
	dojo.byId(mode + '-close_' + this.stepId).style.display = 'none';
	dojo.byId(mode + '-cancel_' + this.stepId).style.display = 'none';
	this.editorWidget[editNode.id].destroyRecursive(true);
	if (!saveChanges) {
		editNode.innerHTML = this.editorOldValue[editNode.id];
	}
	// Call method to write changes via AJAX if the value has changed
	else {
		if (editNode.innerHTML != this.editorOldValue[editNode.id]) {
			this.updateDescription(editNode.innerHTML, mode);
		}
	}
	editNode.scrollIntoView(true);
	// Remove from the list of open editors
	for (var i=0; i<rpc_openEditors.length; i++) {
		if (rpc_openEditors[i] == editNode.id) {
			rpc_openEditors.splice(i, 1);
			break;
		}
	}
	return;
};
/**
 * Save changes via AJAX for step description or teacher description
 *
 * @param string newValue New HTML for description
 * @param string mode 't'|'s' Teacher or Student
 * @return void
 */
RPCStep.prototype.updateDescription = function(newValue, mode) {
	this.form.transid.value = this.parentAssignment.transId;
	this.form.id.value = this.stepId;
	// Action RPC_Step::ACTION_SET_DESC == 6
	// Action RPC_Step::ACTION_SET_TEACHER_DESC == 7
	// Action RPC_Step::ACTION_SET_ANNOTATION == 12
	// Action RPC_Linked_Assignment::ACTION_SET_LINKED_STEP_ANNOTATION == 31
	var actionDesc;
	switch (mode) {
		case 't':
			this.form.action.value = 7;
			actionDesc = "teacher description";
			break;
		case 's':
			this.form.action.value = 6;
			actionDesc = "description";
			break;
		case 'a':
			actionDesc = "annotation";
			// If the parent is a linked assignment,
			// use RPC_Linked_Assignment::ACTION_SET_LINKED_STEP_ANNOTATION (31)
			if (this.parentAssignment.type == "link") {
				this.form.action.value = 31;
			}
			else {
				this.form.action.value = 12;
			}
			break;
	}
	this.form.val_1.value = newValue;
	// Pass the stepId when handling a linked step annotation
	this.form.val_2.value = this.form.action.value == 31 ? this.stepId : "";
	var tmp_this = this;
	this.xhrArgs.load = function(response, ioArgs) {
		var aResp = response.split('|');
		d = dijit.byId('stepChangeDialog');
		switch (aResp[0]) {
			case 'OK':
				tmp_this.parentAssignment.transId = aResp[2];
				tmp_this.parentAssignment.form.transid.value = aResp[2];
				tmp_this.form.transid.value = aResp[2];
				rpc_showDialog("Step " + actionDesc + " successfully updated.", "Assignment changed");
				break;
			case 'FAIL':
				// TODO: Set the original value back
				rpc_showDialog("Step could not be updated: " + aResp[1], "Change failed");
				break;
		}
		return response;
	};
	// Linked step annotations (action 31) are processed by handlers/assignment.php
	// while anything else is in step.php, so in that case we need to switch
	// over and then back after the call
	if (this.form.action.value == 31) {
		this.xhrArgs.form = dojo.byId('frm-assign-handler');
	}
	var deferred = dojo.xhrPost(this.xhrArgs);

	// Reset the form target to the default for steps
	this.xhrArgs.form = dojo.byId('frm-step-handler');
};


/**
 * Display modal dialog with title 'title' and message 'message'
 */
function rpc_showDialog(message, title) {
	dojo.require('dijit.Dialog');
	if (rpc_Dialog == undefined) {
		rpc_Dialog = new dijit.Dialog({"id": "stepChangeDialog", "refocus": true});
	}
	if (title !== null && title.length > 0) {
		rpc_Dialog.attr("title", title);
	}
	dojo.byId("dialog-inner").innerHTML = message;
	rpc_Dialog.attr("content", dojo.byId("dialog-content").innerHTML);
	rpc_Dialog.show();
	return;
}
var rpc_error = function(error, ioArgs) {
	rpc_showDialog("An application error occurred. Your request could not be completed. (" + error.status + ")", "Error");
	console.error(error.message);
	return error;
};
