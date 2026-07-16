<?php
$files = [
    'resources/views/teams/activities/types/agreement/show-content.blade.php',
    'resources/views/teams/activities/types/agreement/edit-fields.blade.php',
    'resources/views/teams/activities/edit.blade.php',
    'resources/views/teams/activities/select_type.blade.php'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $content = str_replace('decision_type', 'agreement_type', $content);
        $content = str_replace('DecisionType', 'AgreementType', $content);
        $content = str_replace('decisionType', 'agreementType', $content);
        $content = str_replace('decisionMeta', 'agreementMeta', $content);
        file_put_contents($file, $content);
    }
}
echo "Done";
