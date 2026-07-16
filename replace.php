<?php
$files = [
    'app/Http/Controllers/ActivityController.php',
    'app/Http/Controllers/AgreementSignatureController.php',
    'app/Http/Controllers/AiContentTransferController.php',
    'app/Http/Controllers/KanbanController.php',
    'app/Http/Controllers/TaskActionController.php',
    'app/Services/ActivityService.php',
    'app/Services/AgreementPdfService.php',
    'app/Models/Activities/AgreementActivity.php',
    'app/Models/Activity.php',
    'resources/views/expedientes/show.blade.php',
    'resources/views/components/ai-assistant.blade.php',
    'resources/views/metrics/personal/daily.blade.php',
    'resources/views/teams/activities/index.blade.php',
    'resources/views/teams/activities/edit.blade.php',
    'resources/views/teams/activities/create.blade.php',
    'resources/views/teams/activities/select_type.blade.php',
    'resources/views/teams/activities/types/link/show-content.blade.php',
    'resources/views/teams/activities/types/document/show-content.blade.php',
    'resources/views/teams/activities/types/task/show-content.blade.php',
    'resources/views/teams/activities/types/agreement/show-content.blade.php',
    'resources/views/teams/activities/types/agreement/form.blade.php',
    'config/activity_templates/agreement_activity.json'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $content = str_replace("'decision'", "'agreement'", $content);
        $content = str_replace('"decision"', '"agreement"', $content);
        $content = str_replace('DecisionActivity', 'AgreementActivity', $content);
        $content = str_replace('decision_activity', 'agreement_activity', $content);
        file_put_contents($file, $content);
    }
}
echo "Done";
