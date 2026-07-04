import re
import sys
import os

def convert_view(src_path, dest_path):
    if not os.path.exists(src_path):
        print(f"Source not found: {src_path}")
        return

    with open(src_path, 'r', encoding='utf-8') as f:
        content = f.read()

    # Replacements
    content = content.replace('$task->', '$activity->')
    content = content.replace('[$team, $task]', '[$team, $activity]')
    content = content.replace('compact(\'task\')', 'compact(\'activity\')')
    content = content.replace('\'task\' => $task', '\'activity\' => $activity')
    
    # Routes
    content = content.replace('teams.tasks.', 'teams.activities.')
    content = content.replace('google.sync_task', 'google.sync_activity')
    content = content.replace('google.disconnect_task', 'google.disconnect_activity')
    content = content.replace('google.export_calendar', 'google.export_activity_calendar')
    
    # Language
    content = content.replace('__(\'tasks.', '__(\'activities.')
    
    # Variables
    content = re.sub(r'\btask\b', 'activity', content)
    content = re.sub(r'\bTask\b', 'Activity', content)
    content = re.sub(r'\bTasks\b', 'Activities', content)
    content = re.sub(r'\btasks\b', 'activities', content)
    content = re.sub(r'\bTASK\b', 'ACTIVITY', content)
    
    # But restore some specific classes or Alpine variables if they broke, but "activity" is fine.
    
    with open(dest_path, 'w', encoding='utf-8') as f:
        f.write(content)
    
    print(f"Converted {src_path} -> {dest_path}")

convert_view('resources/views/tasks/show.blade.php', 'resources/views/teams/activities/show.blade.php')
convert_view('resources/views/tasks/edit.blade.php', 'resources/views/teams/activities/edit.blade.php')

