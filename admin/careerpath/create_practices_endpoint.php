<?php
global $DB;
require_once(__DIR__ . '/../../../../config.php');
require_once(__DIR__ . '/../course/create_practice_action.php');
require_login();
require_sesskey();

$courseid    = optional_param('id', null, PARAM_INT);
$courses    = $_POST['courses'] ?? array();
$globalPractices = $_POST['practices'] ?? array();

foreach ($courses as $courseId => $course) {
    if (empty($course['practices'])) {
        debugging('The array practices was not found in the course: ' . $courseId);
        continue;
    }
    $allPractices = array_replace_recursive($globalPractices, $course['practices']);
    CreatePracticeAction($allPractices, $courseid+1);
}

redirect(new moodle_url('/theme/rainmake/admin/createcareerpath/prove.php', ['id' => $courseid]));
