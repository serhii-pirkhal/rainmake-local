<?php
global $DB;
require_once(__DIR__ . '/../../../../config.php');
require_once(__DIR__ . '/../course/create_practice_action.php');

$save_only = optional_param('save_only', 0, PARAM_INT);
if ($save_only) {
    ob_start();
}

require_login();
require_sesskey();

$courseid    = optional_param('id', null, PARAM_INT);
$proves    = $_POST['prove'] ?? array();
$globalPractices = $_POST['practices'] ?? array();

if (!$save_only) {
    debugging(json_encode($proves, JSON_PRETTY_PRINT));
}

$allPractices = array_replace_recursive($globalPractices, $proves);

CreatePracticeAction($allPractices, $courseid);

if ($save_only) {
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'courseid' => $courseid,
        'message' => 'Prove saved successfully'
    ]);
    exit;
}

redirect(new moodle_url('/theme/rainmake/admin/createcareerpath/publish.php', ['id' => $courseid]));
