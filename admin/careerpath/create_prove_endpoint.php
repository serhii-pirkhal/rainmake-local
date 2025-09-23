<?php
global $DB;
require_once(__DIR__ . '/../../../../config.php');
require_once(__DIR__ . '/../course/create_practice_action.php');
require_login();
require_sesskey();

$courseid    = optional_param('id', null, PARAM_INT);
$prove    = $_POST['prove'] ?? array();

debugging(json_encode($prove, JSON_PRETTY_PRINT));

CreatePracticeAction($prove, $courseid);

redirect(new moodle_url('/theme/rainmake/admin/createcareerpath/publish.php', ['id' => $courseid]));
