{$application.long_name} assignment reminder

----------------------------------
Assignment: {$assignment.title}
Step {$step.position}: {$step.title}
----------------------------------

This is a reminder from the {$application.long_name} of an assignment milestone due soon.

By {$step.due_date|date_format:$application.long_date}, you should complete the following.
Good luck on your assignment!

{$notification.step_text}

Follow this link to view your assignment:
{$assignment.url}

