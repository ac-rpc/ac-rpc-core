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
 * Global rpcAdmin
 */
var rpcAdmin;

// FX required for wipe
dojo.require('dojo.fx');

function RPCAdmin() {
	this.divUserSearchResult = dojo.byId('admin-searchusers-result');
	this.divUserSearchResult.style.display = 'none';
	this.txtSearchField = dojo.byId('admin-searchusers-username');
	// Row ID suffix for added privileged user table rows
	this.addedRowId = 0;

	// Permission modification properties/form
	this.form = {};
	this.form.transid = dojo.byId('admin-handler-transid');
	this.form.id = dojo.byId('admin-handler-id');
	this.form.action = dojo.byId('admin-handler-action');
	this.form.perm = dojo.byId('admin-handler-perm');

	this.form.transid.value = dojo.byId('admin-handler-transid').value;
	this.form.id.value = null;
	this.form.action.value = null;
	this.form.perm.value = null;
	return;
}
RPCAdmin.prototype.clearSearch = function() {
	if (this.txtSearchField.value !== "" || this.divUserSearchResult.innerHTML !== "") {
		var wOut = dojo.fx.wipeOut({
			node: this.divUserSearchResult,
			duration: 300,
			// Add DOM nodes needed inside callback
			txtSearchField: this.txtSearchField,
			divUserSearchResult: this.divUserSearchResult,
			// Clear search box and div contents when anim finishes
			onEnd: function() {
				this.txtSearchField.value = "";
				this.divUserSearchResult.innerHTML = "";
			}
		});
		wOut.play();
	}
}
RPCAdmin.prototype.grantPublisher = function(userid) {
	this.form.action.value = 'g';
	this.form.perm.value = 'p';
	this.form.id.value = userid;
	this.doPermAction();
}
RPCAdmin.prototype.grantAdministrator = function(userid) {
	this.form.action.value = 'g';
	this.form.perm.value = 'a';
	this.form.id.value = userid;
	this.doPermAction();
}
RPCAdmin.prototype.revoke = function(userid) {
	this.form.action.value = 'r';
	this.form.perm.value = null;
	this.form.id.value = userid;
	this.doPermAction();
}
RPCAdmin.prototype.doPermAction = function() {
	dojo.xhrPost({
		url: '../handlers/admin_perms.php',
		handleAs: 'text',
		form: dojo.byId('frm-admin-handler'),
		load: function(response) {
			var arrResponse = response.split("|");
			var divPermsMessage = dojo.byId('admin-perms-message');
			// Flag to remove table row after revoking privs.
			var deleteRowAfter = false;
			// Notify progress...
			divPermsMessage.innerHTML = "Saving changes...";
			if (arrResponse[0] !== "OK") {
				deleteRowAfter = true;
				divPermsMessage.innerHTML = arrResponse[1];
			}
			else {
				// If this was a revoke, remove table row
				if (this.form.action.value === 'r') {
					deleteRowAfter = true;
				}
				divPermsMessage.innerHTML = "Changes saved.";
				this.form.transid.value = arrResponse[2];
			}
			var wipeAttrs = {node: 'admin-perms-message', duration: 300};
			divPermsMessage.style.display = "none";
			wIn = dojo.fx.wipeIn(wipeAttrs);
			wOut = dojo.fx.wipeOut(wipeAttrs);
			wOut.delay = 1000;
			wOut.trDeleteId = this.form.id.value;
			if (deleteRowAfter === true) {
				// After the animation is done, remove the table row for revoke action
				wOut.onEnd = function() {
					dojo.destroy(dojo.byId('tr-privileged-user-' + this.trDeleteId));
					dojo.style('admin-perms-message', 'display', 'none');
				}
			}
			dojo.fx.chain([wIn, wOut]).play();
		}
	});
}

RPCAdmin.prototype.searchUser = function() {
	// Increment row ID before xhrGet
	this.addedRowId++;
	dojo.xhrGet({
		url: '../handlers/admin_searchuser.php',
		handleAs: 'text',
		content: {username: this.txtSearchField.value},
		// DOM nodes needed inside callback
		divUserSearchResult: this.divUserSearchResult,
		addedRowId: this.addedRowId,
		load: function(response) {
			var arrResponse = response.split("|");
			// Error status
			if (arrResponse[6] !== "") {
				this.divUserSearchResult.innerHTML = arrResponse[6];
			}
			else {
				this.divUserSearchResult.style.display = "none";
				// If the user is already privileged, she should be listed in the table to begin with
				// Or if the row ID exists already
				if (dojo.query("#tr-privileged-user-" + arrResponse[0]).length > 0) {
					this.divUserSearchResult.innerHTML = "User " + arrResponse[1] + " is already in the Current Privileged Users table.";
				}
				else {
					var tblPrivilegedUsers = dojo.byId('table-privileged-users-body');
					// Add a new row to the existing privileged users table
					var addedRow = dojo.create("tr", {
						"class": "tr-new",
						"id": "tr-privileged-user-" + arrResponse[0]
					}, tblPrivilegedUsers);
					dojo.create("td", {innerHTML: arrResponse[1]}, addedRow);
					dojo.create("td", {innerHTML: arrResponse[3]}, addedRow);
					dojo.create("td", {"class": "radio-col", innerHTML: " -- "}, addedRow);
					var addedRowRadioOpts = {
						"type": "radio",
						"name": arrResponse[0] + "-privs",
						"value": arrResponse[0],
						"onclick": function() {this.blur();}
					};
					// IE6 doesn't like dynamic radio buttons with onclick attributes, so they have to be created
					// with document.createElement.
					var radioSkelStart =  "<input type='radio' name='" + arrResponse[0] + "-privs' value='" + arrResponse[0] + "' onclick='this.blur();' onchange='rpcAdmin.";
					var radioSkelEnd = "(this.value);' />";
					var radioOnchange = "";
					// Admin radio button
					var addedRowAdmin = dojo.create("td", {"class": "radio-col"}, addedRow);
					var addedRowAdminButton;
					// Publisher radio button
					var addedRowPublisher = dojo.create("td", {"class": "radio-col"}, addedRow);
					var addedRowPublisherButton;
					// Revoke radio button
					var addedRowRevoke = dojo.create("td", {"class": "radio-col"}, addedRow);
					var addedRowRevokeButton;
					// Special handling for IE6...
					if (dojo.isIE <= 6) {
						radioOnchange = "grantAdministrator";
						addedRowAdminButton = document.createElement(radioSkelStart + radioOnchange + radioSkelEnd);
						addedRowAdmin.appendChild(addedRowAdminButton);

						radioOnchange = "grantPublisher";
						addedRowPublisherButton = document.createElement(radioSkelStart + radioOnchange + radioSkelEnd);
						addedRowPublisher.appendChild(addedRowPublisherButton);

						radioOnchange = "revoke";
						addedRowRevokeButton = document.createElement(radioSkelStart + radioOnchange + radioSkelEnd);
						addedRowRevoke.appendChild(addedRowRevokeButton);
					}
					// Non-IE6 object creation with dojo
					else {
						addedRowAdminButton = dojo.create("input", addedRowRadioOpts, addedRowAdmin);
						addedRowAdminButton.onchange = function() {rpcAdmin.grantAdministrator(this.value);};
						addedRowPublisherButton = dojo.create("input", addedRowRadioOpts, addedRowPublisher);
						addedRowPublisherButton.onchange = function() {rpcAdmin.grantPublisher(this.value);};
						addedRowRevokeButton = dojo.create("input", addedRowRadioOpts, addedRowRevoke);
						addedRowRevokeButton.onchange = function() {rpcAdmin.revoke(this.value);};
					}

					// Fade in the new row's color
					dojo.animateProperty({
						node: addedRow.id,
						duration: 1500,
						properties: {
							backgroundColor: {
								start: "white",
								end: "#AA8888"
							}
						}
					}).play();

					// Show feedback string
					this.divUserSearchResult.innerHTML = "User " + arrResponse[1] + " was added to the Current Privileged Users table. You may set the user's privileges now.";
				}
			}
			dojo.fx.wipeIn({node: this.divUserSearchResult, duration: 300}).play();
			return;
		},
		error: function() {
			console.log("ERROR");
		}
	});
	return;
}
// Setup a copy of the object...
dojo.addOnLoad(
	function() {
		rpcAdmin = new RPCAdmin();
	}
);
