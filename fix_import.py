import re
with open('resources/views/teams/activities/partials/import-modal-script.blade.php', 'r') as f:
    c = f.read()
c = c.replace('$task', '$activity')
c = c.replace('route(\'teams.tasks', 'route(\'teams.activities')
c = c.replace('route("teams.tasks', 'route("teams.activities')
c = c.replace('.tasks.', '.activities.')
with open('resources/views/teams/activities/partials/import-modal-script.blade.php', 'w') as f:
    f.write(c)
