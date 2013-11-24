<html>
<body style='font-family: arial,helvetica,sans-serif; font-size: 100%; color: black;'>
	<h2 style='font-size: 150%'>{$application.long_name|escape} assignment reminder</h2>

	<hr style='border: 1px solid black;' />
	<h3>Assignment: {$assignment.title|escape}</h3>

	<p>
		This is a reminder from the {$application.long_name} of an assignment milestone due soon.
	</p>
	<p>
		<strong>By {$step.due_date|date_format:$application.long_date}</strong>, you should complete the following:
	</p>
	<div id='#step-html' style='padding: 10px; margin: 5px; border: 1px solid black;'>
		<h4 style='border-bottom: 1px solid black;'>Step {$step.position}: {$step.title|escape}</h4>
		{$notification.step_html}
	</div>

	<p>
		Good luck on your assignment!
	</p>
	<a href='{$assignment.url}'>View your assignment</a>
</body>
</html>
