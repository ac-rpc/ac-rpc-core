/**
 * Custom Dojo Toolkit build profile for Research Project Calculator
 */
dependencies = {
	layers: [
		{
			name: "rpc-assignment.js",
			dependencies: [
				"dojo.dnd.Source",
				"dojo.fx",
				"dijit.InlineEditBox",
				"dijit.Dialog",
				"dijit.form.NumberSpinner",
				"dijit.form.DateTextBox",
				"dijit.Editor",
				"dijit._editor.plugins.AlwaysShowToolbar",
				"dijit._editor.plugins.LinkDialog",
				"dijit._editor.plugins.FontChoice"
				
			]
		},
		{
			name: "rpc-home.js",
			dependencies: [
				"dijit.Dialog",
				"dijit.form.DateTextBox"
			]
		},
		{
			name: "rpc-admin.js",
			dependencies: [
				"dojo.fx"
			]
		}
	],
	prefixes: [
		["dijit", "../dijit"],
	]
};
