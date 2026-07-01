<?php

$dir = new RecursiveDirectoryIterator('resources/views/teams/activities');
$ite = new RecursiveIteratorIterator($dir);
$files = new RegexIterator($ite, '/\.blade\.php$/', RegexIterator::GET_MATCH);
$rawStrings = [];

foreach ($files as $file) {
    $path = $file[0];
    $content = file_get_contents($path);
    // Find text nodes between HTML tags that contain letters and are not inside {{ }} or {!! !!} or @ 
    // This is hard to do perfectly with regex, but we can look for common Spanish words or capital letters not in tags.
    // Let's just find things like <button>Texto</button>
    preg_match_all('/>([^<\{\}@]+)</', $content, $matches);
    foreach ($matches[1] as $match) {
        $trimmed = trim($match);
        if (strlen($trimmed) > 3 && preg_match('/[a-zA-Z]/', $trimmed) && !preg_match('/^&[a-z]+;$/', $trimmed)) {
            $rawStrings[$path][] = $trimmed;
        }
    }
}

foreach ($rawStrings as $file => $strings) {
    echo "--- $file ---\n";
    foreach (array_unique($strings) as $str) {
        echo "  - $str\n";
    }
}
