<?php
function parse_rows($file) {
    $content = file_get_contents($file);
    preg_match('/INSERT INTO `tasks` VALUES\n(.*?);/s', $content, $matches);
    if (!$matches) return [];
    $rows_str = $matches[1];
    // This is a very rough parser, but might work for simple values
    $rows = explode("),\n(", trim($rows_str, "()\n"));
    $data = [];
    foreach ($rows as $row) {
        $parts = str_getcsv($row, ",", "'");
        if (isset($parts[0])) {
            $data[$parts[0]] = $parts;
        }
    }
    return $data;
}

$tasks23 = parse_rows('dump_mtx_230426.sql');
$tasks24 = parse_rows('dump_mtx_240426.sql');

echo "Tasks in 23: " . count($tasks23) . "\n";
echo "Tasks in 24: " . count($tasks24) . "\n";

$commonIds = array_intersect(array_keys($tasks23), array_keys($tasks24));
echo "Common Tasks: " . count($commonIds) . "\n";

foreach ($commonIds as $id) {
    $t23 = $tasks23[$id];
    $t24 = $tasks24[$id];
    
    $diff = [];
    // We know column names from previous view_file
    $cols = [
        0 => 'id', 1 => 'uuid', 2 => 'team_id', 3 => 'is_template', 4 => 'is_autoprogrammable',
        6 => 'parent_id', 7 => 'assigned_user_id', 8 => 'title', 19 => 'status', 22 => 'progress'
    ];
    
    foreach ($cols as $idx => $name) {
        if (($t23[$idx] ?? null) !== ($t24[$idx] ?? null)) {
            $diff[$name] = ['old' => $t23[$idx], 'new' => $t24[$idx]];
        }
    }
    
    if (!empty($diff)) {
        echo "Task $id ({$t23[8]}):\n";
        print_r($diff);
    }
}
