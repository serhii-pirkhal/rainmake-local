<?php
require_once(__DIR__ . '/../../../../config.php');
require_login();
require_sesskey();

$fullname    = required_param('title', PARAM_TEXT);
$description = optional_param('description', null, PARAM_TEXT);
$courseid    = optional_param('id', null, PARAM_INT);
$audience = optional_param_array('audience', [], PARAM_TEXT);
$requirements = optional_param_array('requirements', [], PARAM_TEXT);
$will_teach = optional_param_array('will_teach', [], PARAM_TEXT);

require_once($CFG->dirroot . '/course/lib.php');

$course = (object)[
    'fullname' => $fullname,
    'summary' => $description,
    'category' => 1,
];

if ($courseid) {
    $existing = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
    $course->id = $courseid;
    $course->format = $existing->format ?? 'topics';
    update_course($course);

    $courseid = $course->id;
} else {
    $newcourse = create_course($course);
    $DB->insert_record('local_rainmake_backend_course_types', [
        'course_id' => $newcourse->id,
        'type' => "careerpath",
        'timecreated' => time()
    ]);
    $courseid = $newcourse->id;
}

$record = (object)[
    'courseid'         => $courseid,
    'topic'            => optional_param('coursetopic', '', PARAM_TEXT),
    'language'         => optional_param('courselanguage', '', PARAM_TEXT),
    'level'            => optional_param('courselevel', '', PARAM_TEXT),
    'timecreated'      => time(),
];

$will_teach_json = json_encode(array_values(array_filter($will_teach)));
$audience_json = json_encode(array_values(array_filter($audience)));
$requirements_json = json_encode(array_values(array_filter($requirements)));


$meta = $DB->get_record('local_rainmake_backend_coursemeta', ['courseid' => $courseid]);
if ($meta) {
    $record->id = $meta->id;
    $record->target = $audience_json;
    $record->will_teach = $will_teach_json;
    $record->requirements = $requirements_json;
    $DB->update_record('local_rainmake_backend_coursemeta', $record);
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

$SESSION->new_course_id = $courseid;

redirect(new moodle_url('/theme/rainmake/admin/createcareerpath/publish.php', ['id' => $courseid]));
