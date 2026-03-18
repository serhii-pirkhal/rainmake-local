<?php
require('../../../../config.php');
require_login();
confirm_sesskey();
global $DB;

$grades = $_POST['grades']?: null;
if (!$grades) {
    redirect(
        new moodle_url('/theme/rainmake/admin/gradebook.php'),
        'the grades are empty',
        null,
        \core\output\notification::NOTIFY_INFO
    );
}
$grades = clean_param_array($grades, PARAM_TEXT, true);
$transaction = $DB->start_delegated_transaction();
foreach ($grades as $grade) {
    $id = isset($grade['id']) ? (int)$grade['id'] : 0;
    $itemid = isset($grade['itemid']) ? (int)$grade['itemid'] : 0;
    $userid = isset($grade['userid']) ? (int)$grade['userid'] : 0;
    $finalgrade = array_key_exists('finalgrade', $grade) ? $grade['finalgrade'] : null;
    $feedback = array_key_exists('feedback', $grade) ? $grade['feedback'] : null;

    if ($id > 0) {
        $DB->update_record('grade_grades', (object)$grade);
        continue;
    }

    if ($itemid <= 0 || $userid <= 0) {
        continue;
    }

    // Insert a new grade record if none exists yet.
    $existing = $DB->get_record('grade_grades', ['itemid' => $itemid, 'userid' => $userid], 'id', IGNORE_MISSING);
    if ($existing) {
        $grade['id'] = (int)$existing->id;
        $DB->update_record('grade_grades', (object)$grade);
        continue;
    }

    $rec = (object)[
        'itemid' => $itemid,
        'userid' => $userid,
        'rawgrade' => ($finalgrade === '' || $finalgrade === null) ? null : (float)$finalgrade,
        'finalgrade' => ($finalgrade === '' || $finalgrade === null) ? null : (float)$finalgrade,
        'feedback' => ($feedback === null) ? '' : (string)$feedback,
        'feedbackformat' => 0,
        'timecreated' => time(),
        'timemodified' => time(),
    ];
    $DB->insert_record('grade_grades', $rec);
}
$transaction->allow_commit();

redirect (
    new moodle_url('/theme/rainmake/admin/gradebook.php'),
    'The grades is successfully changed!',
    null,
    \core\output\notification::NOTIFY_SUCCESS
);
