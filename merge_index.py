import re

with open('resources/views/tasks/index.blade.php', 'r') as f:
    tasks_content = f.read()

with open('resources/views/teams/activities/index.blade.php', 'r') as f:
    activities_content = f.read()

# We want the <x-app-layout> from tasks but we will keep the new filters.
# Let's extract the header from activities_content
header_match = re.search(r'(<x-slot name="header">.*?</x-slot>)', activities_content, re.DOTALL)
activities_header = header_match.group(1) if header_match else ""

# Extract the filters from activities_content
filters_match = re.search(r'(<form action="\{\{ route\(\'teams\.activities\.index\'.*?</form>)', activities_content, re.DOTALL)
activities_filters = filters_match.group(1) if filters_match else ""

# Extract the bulk action bar from tasks_content
bulk_action_match = re.search(r'(<div id="bulkActionBar".*?</div>\s*</div>)', tasks_content, re.DOTALL)
bulk_action = bulk_action_match.group(1) if bulk_action_match else ""

# Extract the table from tasks_content
table_match = re.search(r'(<table class="w-full text-left border-collapse min-w-\[700px\]">.*?</table>)', tasks_content, re.DOTALL)
table = table_match.group(1) if table_match else ""

# Extract the pagination from tasks_content
pagination_match = re.search(r'(@if \(\$tasks->hasPages\(\)\).*?@endif)', tasks_content, re.DOTALL)
pagination = pagination_match.group(1) if pagination_match else ""

# Extract the scripts and forms from the bottom of tasks_content
scripts_match = re.search(r'(@push\(\'scripts\'\).*?</x-app-layout>)', tasks_content, re.DOTALL)
scripts = scripts_match.group(1) if scripts_match else ""

# Replace variables in the tasks parts
bulk_action = bulk_action.replace("applyBulkUpdate('assigned_user_id'", "applyBulkUpdate('assigned_user_id'") # remains same
# in table, we need to replace $tasks with $activities
table = table.replace("$tasks as $task", "$activities as $activity")
table = table.replace("$task->", "$activity->")
table = table.replace("$task[", "$activity[")
table = table.replace("teams.tasks.", "teams.activities.")
table = table.replace("tasks.show", "activities.show")
table = table.replace("tasks.edit", "activities.edit")
table = table.replace("tasks.destroy", "activities.destroy")
table = table.replace("task-checkbox", "activity-checkbox") # Maybe change it? No, keep it task-checkbox or rename it. We will rename to task-checkbox for js to work or just leave it task-checkbox. Wait, js expects .task-checkbox
# let's change task-checkbox to activity-checkbox in JS too.
table = table.replace("task-checkbox", "task-checkbox") 

pagination = pagination.replace("$tasks->", "$activities->")

scripts = scripts.replace("$tasks as $t", "$activities as $t")
scripts = scripts.replace("$tasks as $sub", "$activities as $sub")
scripts = scripts.replace("teams.tasks.", "teams.activities.")
scripts = scripts.replace("tasks.bulk-delete", "activities.bulk-delete")
scripts = scripts.replace("tasks.bulk-update", "activities.bulk-update")
scripts = scripts.replace("tasks.purge-trash", "activities.purge-trash")
scripts = scripts.replace("tasks.bulk-merge", "activities.bulk-merge")
scripts = scripts.replace("tasks.toggle-hide-completed", "activities.toggle-hide-completed")
scripts = scripts.replace("'/teams/' . $team->id . '/tasks'", "'/teams/' . $team->id . '/activities'")
scripts = scripts.replace("activities.bulk-delete", "activities.bulk-delete")

# Build the new content
new_content = f"""<x-app-layout>
    @section('title', 'Actividades — ' . $team->name)

    {activities_header}

    <div class="space-y-6">
        <!-- Filtros y Búsqueda -->
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-4 shadow-sm transition-all">
            {activities_filters}
        </div>

        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-xl rounded-2xl overflow-hidden transition-all">
            {bulk_action}
            
            <div class="overflow-x-auto min-h-[200px] no-scrollbar">
                {table}
            </div>

            {pagination}
        </div>
    </div>
    {scripts}
"""

with open('resources/views/teams/activities/index.blade.php', 'w') as f:
    f.write(new_content)

print("Done generating new index.")
