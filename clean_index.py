import re

with open('resources/views/time-logs/index.blade.php', 'r', encoding='utf-8') as f:
    content = f.read()

# Remove the $filteredSkills definition
old_php = """                            $filteredSkills = $allSkills->filter(function($skill) use ($team, $potentialXp, $completedTaskCount) {
                                if ($skill->team_id === $team->id) return true;
                                $planData = $potentialXp->get($skill->name);
                                $pendingCount = $planData ? $planData->count : 0;
                                $completedCount = $completedTaskCount->get($skill->name, 0);
                                return ($pendingCount + $completedCount) > 0;
                            });"""
content = content.replace(old_php, "")

# Replace @if($filteredSkills->isEmpty()) with @if($allSkills->isEmpty())
content = content.replace('@if($filteredSkills->isEmpty())', '@if($allSkills->isEmpty())')

# Replace @foreach($filteredSkills as $skill) with @foreach($allSkills as $skill)
content = content.replace('@foreach($filteredSkills as $skill)', '@foreach($allSkills as $skill)')

with open('resources/views/time-logs/index.blade.php', 'w', encoding='utf-8') as f:
    f.write(content)
print("Cleaned!")
