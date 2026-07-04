import re
with open('resources/views/teams/activities/partials/activity-timer-button.blade.php', 'r') as f:
    c = f.read()
c = c.replace('$task', '$activity')
c = c.replace('tasks.time', 'activities.time')
with open('resources/views/teams/activities/partials/activity-timer-button.blade.php', 'w') as f:
    f.write(c)

with open('resources/views/teams/activities/show.blade.php', 'r') as f:
    c = f.read()
c = c.replace("include('activities.partials.activity-timer-button'", "include('teams.activities.partials.activity-timer-button'")
with open('resources/views/teams/activities/show.blade.php', 'w') as f:
    f.write(c)
