<?php
global $DB;
require_once(__DIR__ . '/../../../../config.php');
require_once(__DIR__ . '/../course/create_practice_action.php');
require_login();
require_sesskey();

$courseid    = optional_param('id', null, PARAM_INT);
$courses    = $_POST['courses'] ?? array();

foreach ($courses as $course) {
    CreatePracticeAction($courses['practices'], $course['id']);
}

redirect(new moodle_url('/theme/rainmake/admin/createcareerpath/prove.php', ['id' => $courseid]));
