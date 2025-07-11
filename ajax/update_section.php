<?php
require_once(__DIR__ . '/../../../config.php');
require_login();
require_sesskey();

$sessionid = required_param('sessionid', PARAM_INT);
$sectionname = required_param('sectionname', PARAM_TEXT);

$record = $DB->get_record('local_rainmake_backend_sessions', ['id' => $sessionid], '*', MUST_EXIST);
$record->title = $sectionname;

$DB->update_record('local_rainmake_backend_sessions', $record);

echo json_encode(['success' => true]);
exit;
