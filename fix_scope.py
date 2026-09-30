import re

with open('app/Models/Skill.php', 'r', encoding='utf-8') as f:
    content = f.read()

old_scope = """    public function scopeForTeamOrGlobal($query, $teamId)
    {
        return $query->where(function($q) use ($teamId) {
            $q->where('team_id', $teamId)
              ->orWhere(function($subQ) use ($teamId) {
                  $subQ->whereNull('team_id')
                       ->whereNotIn('name', function($nameQuery) use ($teamId) {
                           $nameQuery->select('name')
                                     ->from('skills')
                                     ->where('team_id', $teamId);
                       });
              });
        });
    }"""

new_scope = """    public function scopeForTeam($query, $teamId)
    {
        return $query->where('team_id', $teamId);
    }"""

content = content.replace(old_scope, new_scope)

with open('app/Models/Skill.php', 'w', encoding='utf-8') as f:
    f.write(content)
