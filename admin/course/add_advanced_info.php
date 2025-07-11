<?php
require_once(__DIR__ . '/../../../../config.php');
require_login();
require_sesskey();

global $DB;

$courseid = required_param('id', PARAM_INT);
$audience = optional_param_array('audience', [], PARAM_TEXT);
$requirements = optional_param_array('requirements', [], PARAM_TEXT);
$will_teach = optional_param_array('will_teach', [], PARAM_TEXT);
$description = optional_param('description', '', PARAM_TEXT);

$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);

$meta = $DB->get_record('local_rainmake_backend_coursemeta', ['courseid' => $courseid]);

$will_teach_json = json_encode(array_values(array_filter($will_teach)));
$audience_json = json_encode(array_values(array_filter($audience)));
$requirements_json = json_encode(array_values(array_filter($requirements)));

if ($meta) {
    $meta->target = $audience_json;
    $meta->will_teach = $will_teach_json;
    $meta->requirements = $requirements_json;
    $meta->description = $description;
    $DB->update_record('local_rainmake_backend_coursemeta', $meta);
} else {
    $DB->insert_record('local_rainmake_backend_coursemeta', [
        'courseid' => $courseid,
        'target' => $audience_json,
        'will_teach' => $will_teach_json,
        'requirements' => $requirements_json,
        'description' => $description,
        'timecreated' => time(),
    ]);
}

redirect(new moodle_url('/theme/rainmake/admin/createcourse/curriculum.php', ['id' => $courseid]), 'Audience saved', 2);