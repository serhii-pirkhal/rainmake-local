<?php
require_once(__DIR__ . '/../../../config.php');
require_login();
require_sesskey();

$courseid = required_param('courseid', PARAM_INT);
$sectionname = required_param('sectionname', PARAM_TEXT);

global $DB;

$sortorder = $DB->get_field_sql(
    "SELECT MAX(sortorder) FROM {local_rainmake_backend_sessions} WHERE courseid = ?",
    [$courseid]
);
$sortorder = ($sortorder !== null) ? $sortorder + 1 : 1;

$record = (object)[
    'courseid' => $courseid,
    'title' => $sectionname,
    'sortorder' => $sortorder,
    'timecreated' => time(),
];

$DB->insert_record('local_rainmake_backend_sessions', $record);

echo json_encode(['success' => true]);
exit;
