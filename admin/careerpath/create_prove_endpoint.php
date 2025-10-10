<?php
global $DB;
require_once(__DIR__ . '/../../../../config.php');
require_once(__DIR__ . '/../course/create_practice_action.php');
require_login();
require_sesskey();

$courseid    = optional_param('id', null, PARAM_INT);
$proves    = $_POST['prove'] ?? array();
$globalPractices = $_POST['practices'] ?? array();
debugging(json_encode($proves, JSON_PRETTY_PRINT));

$allPractices = array_replace_recursive($globalPractices, $proves);

CreatePracticeAction($allPractices, $courseid);

redirect(new moodle_url('/theme/rainmake/admin/createcareerpath/publish.php', ['id' => $courseid]));
