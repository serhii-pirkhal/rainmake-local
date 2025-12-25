<?php
global $DB;
require_once(__DIR__ . '/../../../../config.php');
require_once(__DIR__ . '/create_practice_action.php');

$save_only = optional_param('save_only', 0, PARAM_INT);
if ($save_only) {
    ob_start();
}

require_login();
require_sesskey();

$practices    = $_POST['practices'] ?? array();
$courseid    = optional_param('id', null, PARAM_INT);

CreatePracticeAction($practices, $courseid);

if ($save_only) {
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'courseid' => $courseid,
        'message' => 'Practice saved successfully'
    ]);
    exit;
}

redirect(new moodle_url('/theme/rainmake/admin/createcourse/publish.php', ['id' => $courseid]));
