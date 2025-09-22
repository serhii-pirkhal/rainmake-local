<?php
global $DB;
require_once(__DIR__ . '/../../../../config.php');
require_once(__DIR__ . '/create_practice_action.php');
require_login();
require_sesskey();

$practices    = $_POST['practices'] ?? array();
$courseid    = optional_param('id', null, PARAM_INT);

CreatePracticeAction($practices, $courseid);

redirect(new moodle_url('/theme/rainmake/admin/createcourse/publish.php', ['id' => $courseid]));
