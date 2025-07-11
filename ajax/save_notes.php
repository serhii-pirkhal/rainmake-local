<?php
require_once(__DIR__ . '/../../../config.php');
require_login();
require_sesskey();

$lectureid = required_param('lectureid', PARAM_INT);
$description = required_param('notes', PARAM_TEXT);

$DB->update_record('local_rainmake_backend_lectures', [
    'id' => $lectureid,
    'notes' => $description
]);

echo json_encode(['success' => true]);
exit;
