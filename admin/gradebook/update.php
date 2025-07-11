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
    $DB->update_record('grade_grades', $grade);
}
$transaction->allow_commit();

redirect (
    new moodle_url('/theme/rainmake/admin/gradebook.php'),
    'The grades is successfully changed!',
    null,
    \core\output\notification::NOTIFY_SUCCESS
);
