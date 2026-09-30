import re

with open('resources/views/time-logs/index.blade.php', 'r', encoding='utf-8') as f:
    content = f.read()

# 1. Add the filteredSkills logic
php_block_pattern = re.compile(r'(\$levelThresholds = \[.*?\];.*?)(@endphp)', re.DOTALL)
match = php_block_pattern.search(content)

if match:
    new_php = match.group(1) + """
                            $filteredSkills = $allSkills->filter(function($skill) use ($team, $potentialXp, $completedTaskCount) {
                                if ($skill->team_id === $team->id) return true;
                                $planData = $potentialXp->get($skill->name);
                                $pendingCount = $planData ? $planData->count : 0;
                                $completedCount = $completedTaskCount->get($skill->name, 0);
                                return ($pendingCount + $completedCount) > 0;
                            });
                        """ + match.group(2)
    content = content.replace(match.group(0), new_php)

# 2. Add the @if @else for empty state
foreach_pattern = r'@foreach\(\$allSkills as \$skill\)'
new_foreach = """@if($filteredSkills->isEmpty())
                            <div class="w-full text-center py-6 flex flex-col items-center">
                                <div class="w-12 h-12 bg-gray-50 dark:bg-gray-800/50 rounded-full flex items-center justify-center mb-3 text-gray-400">
                                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                                    </svg>
                                </div>
                                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-widest">Este equipo aún no ha desarrollado habilidades</p>
                            </div>
                        @else
                            @foreach($filteredSkills as $skill)"""

content = content.replace(foreach_pattern, new_foreach)

# 3. Add the @endif after the foreach ends
# The foreach ends at `</div>` before `<div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mt-6">`
# Let's find the closing endforeach

endforeach_pattern = r'(@endforeach\s*)</div>\s*<div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mt-6">'
new_endforeach = r'\1@endif </div> <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mt-6">'

content = re.sub(endforeach_pattern, new_endforeach, content)

with open('resources/views/time-logs/index.blade.php', 'w', encoding='utf-8') as f:
    f.write(content)
print("Replaced!")
