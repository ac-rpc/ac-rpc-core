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

// Toggle delete button on/off based on inverse of checkbox state
function toggleAcctConfirmDelete(state) {
	dojo.byId('deleteacct-submit').disabled = !state;
	return;
}

/**
 * Functions for date manipulations
 */

/**
 * Convert a date supplied by DateTextBox from mm/dd/yyyy to yyyymmdd
 * with appropriate zero-padding
 * or return it if already in the format yyyymmdd
 */
function rpc_DateToYYYYMMDD(dateVal) {
	// Date as mm/dd/yyyy
	if (typeof(dateVal) == "string") {
		if (dateVal.match(/^\d{1,2}\/\d{1,2}\/\d{4}$/)) {
			var arrDates = dateVal.split('/');
			if (arrDates.length == 3) {
				return arrDates[2] + (arrDates[0].length == 1 ? "0" : "") + arrDates[0] + (arrDates[1].length == 1 ? "0" : "") + arrDates[1];
			}
			else {
				return false;
			}
		}
		// Date as yyyymmdd
		else if (dateVal.match(/^\d{8}$/)) {
			return dateVal;
		}
		else return false;
	}
	// Is JS Date object
	else if (typeof(dateVal) == "object") {
		if (dateVal.getFullYear && dateVal.getMonth) {
			var y = dateVal.getFullYear();
			var m = dateVal.getMonth() + 1;
			var d = dateVal.getDate();
			return y.toString() + (m < 10 ? "0" : "") + m.toString() + (d < 10 ? "0" : "") + d.toString();
		}
		else return false;
	}
	else return false;
}
/**
 * Convert a string in the format YYYYMMDD back into a Date object
 */
function rpc_YYYYMMDDToDate(dateVal) {
	if (dateVal.match(/^\d{8}$/)) {
		var y = parseInt(dateVal.substr(0, 4));
		var m = dateVal.substr(4, 2);
		// Leading zeros have to go.
		m = m.indexOf('0') === 0 ? parseInt(m.substr(1,1)) : parseInt(m);
		var d = dateVal.substr(6, 2);
		d = d.indexOf('0') === 0 ? parseInt(d.substr(1,1)) : parseInt(d);
		return new Date(y, m-1, d);
	}
	else return false;
}

/**
 * Calculate the number of days from today in date
 *
 * @param Date date
 */
function rpc_getDaysFromToday(date) {
	var now = new Date();
	var today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
	return dojo.date.difference(today, date, "day");
}
