<?php
require_once(__DIR__ . '/../../../config.php');

require_login();

$lectureid = required_param('lectureid', PARAM_INT);
$userid = $USER->id;

require_sesskey();

global $DB;

$lecture = $DB->get_record('local_rainmake_backend_lectures', ['id' => $lectureid]);
$session = $DB->get_record('local_rainmake_backend_sessions', ['id' => $lecture->sessionid]);
$course = $DB->get_record('course', ['id' => $session->courseid]);

$DB->get_record('local_rainmake_backend_lecture_views', [
    'userid' => $userid,
    'lectureid' => $lectureid,
    'courseid' => $course->id,
    'sessionid' => $session->id,
]);

$existing = $DB->get_record('local_rainmake_backend_lecture_views', [
    'userid' => $userid,
    'lectureid' => $lectureid
]);

if (!$existing) {
    $DB->insert_record('local_rainmake_backend_lecture_views', [
        'userid' => $userid,
        'lectureid' => $lectureid,
        'courseid' => $course->id,
        'sessionid' => $session->id,
        'viewed_at' => time()
    ]);
}

echo json_encode(['success' => true, 'data' => [
    'lectureid' => $lectureid,
    'courseid' => $course->id,
    'sessionid' => $session->id,
]]);
