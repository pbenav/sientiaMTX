<?php
$q = 1;
$priority = $q == 1 || $q == 2 ? ['high', 'critical'] : ['low', 'medium'];
$urgency = $q == 1 || $q == 3 ? ['high', 'critical'] : ['low', 'medium'];
echo json_encode(['priority' => $priority, 'urgency' => $urgency]) . "\n";
