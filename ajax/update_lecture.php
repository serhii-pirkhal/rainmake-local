<?php
require_once(__DIR__ . '/../../../config.php');
require_login();
require_sesskey();

$lectureid = required_param('lectureId', PARAM_INT);
$name = required_param('name', PARAM_TEXT);

$DB->update_record('local_rainmake_backend_lectures', [
    'id' => $lectureid,
    'title' => $name
]);

echo json_encode(['success' => true]);
exit;
