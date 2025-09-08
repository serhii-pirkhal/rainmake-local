<?php
require_once(__DIR__ . '/../../../../config.php');
require_login();
require_sesskey();

$fullname    = required_param('title', PARAM_TEXT);
$categoryid  = required_param('coursecategory', PARAM_INT);
$courseid    = optional_param('id', null, PARAM_INT);

require_once($CFG->dirroot . '/course/lib.php');

$course = (object)[
    'fullname' => $fullname,
    'category' => $categoryid,
];

if ($courseid) {
    $existing = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
    $course->id = $courseid;
    $course->format = $existing->format ?? 'topics';
    update_course($course);

    $courseid = $course->id;
} else {
    $newcourse = create_course($course);
    $courseid = $newcourse->id;
}

$record = (object)[
    'courseid'         => $courseid,
    'topic'            => optional_param('coursetopic', '', PARAM_TEXT),
    'language'         => optional_param('courselanguage', '', PARAM_TEXT),
    'level'            => optional_param('courselevel', '', PARAM_TEXT),
    'timecreated'      => time(),
];

$existingmeta = $DB->get_record('local_rainmake_backend_coursemeta', ['courseid' => $courseid]);
if ($existingmeta) {
    $record->id = $existingmeta->id;
    $DB->update_record('local_rainmake_backend_coursemeta', $record);
} else {
    $DB->insert_record('local_rainmake_backend_coursemeta', $record);
}

$SESSION->new_course_id = $courseid;

redirect(new moodle_url('/theme/rainmake/admin/createcourse/practice.php', ['id' => $courseid]));
