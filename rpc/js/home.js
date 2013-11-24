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
 * Scripts for RPC home page, including assignment creation form
 */
var RPCAssignForm;
var RPCAssignFormDialog;
var d;
/**
 * Bind DateTextBox widgets on load
 */
var rpc_initRequires = function() {
	dojo.require("dijit.form.DateTextBox");
};
dojo.addOnLoad(rpc_initRequires);
var rpc_newAssignFormInit = function() {
	try {
		RPCAssignForm = new RPCNewAssignmentForm();
	}
	catch(err) {
		dojo.place("<div class='errormsg'>An error occurred while loading the form. If you are using Google Chrome, try reloading the page until this message disappears. We apologize for the inconvenience while this incompatibility is worked out.</div>", "frm-new-assign", "before");
	}
};
dojo.addOnLoad(rpc_newAssignFormInit);
// Focus the assignment title field
dojo.addOnLoad(function() {
	RPCAssignForm.titleWidget.focus();
});

/**
 * Class RPCNewAssignmentForm
 */
function RPCNewAssignmentForm() {
	this.startDate = new Date();
	this.startWidget = null;
	// Default end date +2mo
	this.dueDate = dojo.date.add(this.startDate, "month", 2);
	this.dueWidget =  null;

	this.dateWidgetSettings = {
		datePattern: rpc_dateShortFmt,
		selector: 'date',
		hasDownArrow: false
	};

	// Don't forget Dijit widgets need an explicit name for server-side submission!
	// We're setting these to the current name attributes of the attached textboxes
	// since the attached textboxes will be disabled (and not submitted) by dojo
	// Those attributes get dropped from the attached textbox when the widget is created
	// so we have to store them first.
	var startWidgetFormTitle = dojo.byId('new-assign-startdate').title;
	var dueWidgetFormTitle = dojo.byId('new-assign-duedate').title;

	this.dateWidgetSettings.name = "start";
	this.startWidget = new dijit.form.DateTextBox(this.dateWidgetSettings, dojo.byId('new-assign-startdate'));
	this.startWidget.attr("value", this.startDate);
	this.dateWidgetSettings.name = "due";
	this.dueWidget = new dijit.form.DateTextBox(this.dateWidgetSettings, dojo.byId('new-assign-duedate'));
	this.dueWidget.attr("value", this.dueDate);
	dojo.connect(this.startWidget, "onChange", this, this.dateOnChange);
	dojo.connect(this.dueWidget, "onChange", this, this.dateOnChange);

	// Title/Class simple form inputs
	this.titleWidget = dojo.byId('new-assign-title');
	this.title = this.titleWidget.value;
	dojo.connect(this.titleWidget, "onchange", this, function() {this.title = this.titleWidget.value;});

	this.classNameWidget = dojo.byId('new-assign-class');
	this.className = this.classNameWidget.value;
	dojo.connect(this.classNameWidget, "onchange", this, function() {this.className = this.classNameWidget.value;});

	this.templateWidget = dojo.query(".template-choice-widget", dojo.byId("template-list"));
	this.getTemplate();
	var tmp_this = this;
	// Onchange event for template radio buttons
	dojo.forEach(this.templateWidget, function(widget, i) {
		widget.onchange = function() {tmp_this.getTemplate();};
		widget.onclick = function() {if (dojo.isIE){widget.blur();}};
	});

	this.asTemplate = false;
	this.asTemplateWidget = dojo.byId('new-assign-astemplate');
	if (this.asTemplateWidget) {
		this.asTemplate = this.asTemplateWidget.checked;
	}
	else {
		this.asTemplateWidget = null;
	}
	return;
}
RPCNewAssignmentForm.prototype.dateOnChange = function() {
	this.startDate = this.startWidget.value;
	this.dueDate = this.dueWidget.value;
	this.updateDateConstraints();
	return;
}
/**
 * Set template-sel class on the newly selected item
 * Return the value of the template radio buttons and set this.template
 */
RPCNewAssignmentForm.prototype.getTemplate = function() {
	dojo.query(".template-choice").removeClass("template-sel");
	var tmp_this = this;
	dojo.forEach(this.templateWidget, function(widget, i) {
		if (widget.checked) {
			var widgetContainerLi = dojo.byId("template-choice-container_" + widget.value);
			dojo.addClass(widgetContainerLi, "template-sel");
			tmp_this.template = widget.value;
			return tmp_this.template;
		}
	});
}
RPCNewAssignmentForm.prototype.updateDateConstraints = function() {
	this.startWidget.constraints.max = this.dueDate;
	this.dueWidget.constraints.min = this.startDate;
	return;
}
/**
 * Start, Due Dates must be nonempty, and Dijit already verified the format
 * Title is non-empty
 * Class not required
 * A template or blank must be selected
 */
RPCNewAssignmentForm.prototype.validate = function() {
	var invalidMsg = "";
	if (!dojo.date.compare(this.startDate, this.dueDate, "date")) {
		invalidMsg += "Due date cannot be earlier than start date.";
	}
	if (this.template == "" || this.template == undefined) {
		if (invalidMsg != "") {
			invalidMsg += "<br />";
		}
		invalidMsg += "Please choose an assignment template.";
		var focusNode = this.templateWidget[0];
	}
	if (this.title == "") {
		var focusNode = this.titleWidget;
		if (invalidMsg != "") {
			invalidMsg += "<br />";
		}
		invalidMsg += "Please enter a title for your assignment.";
	}
	if (invalidMsg != "") {
		dojo.require("dijit.Dialog");
		dojo.byId("dialog-inner").innerHTML = invalidMsg;
		// Dialog gets created if it doesn't already exist
		if (!(d = dijit.byId("new-assignment-dialog"))) {
			d = new dijit.Dialog({
				title: "Form errors",
				id: "new-assignment-dialog",
				refocus: "true"
			}, dojo.byId("dialog-conent"));
		}
		d.attr("content", dojo.byId("dialog-content").innerHTML);
		// Set a widget to focus if it will need to be refocused
		if (focusNode) {
			focusNode.focus();
		}
		d.show();
		return false;
	}
	return true;
}
RPCNewAssignmentForm.prototype.toggleTemplateFields = function() {
	if (this.asTemplateWidget) {
		// If template is switched on, disable date widgets
		if (this.asTemplateWidget.checked == true) {
			this.startWidget.attr('disabled', true);
			this.dueWidget.attr('disabled', true);
		}
		else {
			this.startWidget.attr('disabled', false);
			this.dueWidget.attr('disabled', false);
		}
	}
	return;
}
