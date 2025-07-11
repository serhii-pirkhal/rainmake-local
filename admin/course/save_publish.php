<?php
require_once(__DIR__ . '/../../../../config.php');
require_login();
require_sesskey();

global $DB;

$courseid = required_param('id', PARAM_INT);
$welcome = optional_param('welcomemessage', '', PARAM_TEXT);
$congrats = optional_param('congratulationmessage', '', PARAM_TEXT);

$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$meta = $DB->get_record('local_rainmake_backend_coursemeta', ['courseid' => $courseid]);

if ($meta) {
    $meta->welcome = $welcome;
    $meta->congrats = $congrats;
    $DB->update_record('local_rainmake_backend_coursemeta', $meta);
} else {
    $DB->insert_record('local_rainmake_backend_coursemeta', [
        'courseid' => $courseid,
        'welcome' => $welcome,
        'congrats' => $congrats,
        'timecreated' => time(),
    ]);
}

redirect(new moodle_url('/theme/rainmake/course.php', ['id' => $courseid]), 'Published', 2);