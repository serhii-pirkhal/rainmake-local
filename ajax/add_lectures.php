<?php
require_once(__DIR__ . '/../../../config.php');
require_login();
require_sesskey();

$sessionid = required_param('sessionid', PARAM_INT);
$name = required_param('name', PARAM_TEXT);

global $DB;

$record = (object)[
    'sessionid' => $sessionid,
    'title' => $name,
    'timecreated' => time()
];

$id = $DB->insert_record('local_rainmake_backend_lectures', $record);

echo json_encode(['success' => true, 'id' => $id]);
exit;
