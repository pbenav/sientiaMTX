with open('resources/views/teams/activities/index.blade.php', 'r') as f:
    content = f.read()

content = content.replace("[$team, $task]", "[$team, $activity]")
content = content.replace("['task' => $task,", "['task' => $activity,")
content = content.replace("@can('update', $task)", "@can('update', $activity)")
content = content.replace("[$team, $task]", "[$team, $activity]")
content = content.replace("$tasks->isEmpty()", "$activities->isEmpty()")

with open('resources/views/teams/activities/index.blade.php', 'w') as f:
    f.write(content)
