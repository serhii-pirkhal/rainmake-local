<?php
require('../../../../config.php');
require_login();
global $DB;

$upload_dir = $CFG->dirroot . '/local/rainmake/uploads';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}
$tasks = $_POST['tasks']?: null;
if (!$tasks) {
    redirect(
        new moodle_url('/theme/rainmake/admin/assignments/tasks.php'),
        'The assignment info is invalid!',
        null,
        \core\output\notification::NOTIFY_INFO
    );
}
$tasks = clean_param_array($tasks, PARAM_TEXT, true);
$transaction = $DB->start_delegated_transaction();
foreach ($tasks as $task) {
    $taskr = array();
    $taskr['feedback'] = $task['feedback'];
    $taskr['name'] = $task['name'];
    $taskr['course_id'] = $task['course'];
    $taskid = $DB->insert_record('assignment_tasks', $taskr);
    $emails = explode(" ", $task['users']);
    $studentsr = array();
    foreach ($emails as $email) {
        $studentsr[] = [
            'task_id' => $taskid,
            'user_id' => $DB->get_record('user', array('email' => $email), "id")->id
        ];
    }
    $DB->insert_records('assignment_tasks_students', $studentsr);
    if (!empty($_FILES['materials']['tmp_name'])) {
        foreach ($_FILES['materials']['tmp_name'] as $index => $tmp_name) {
            if ($_FILES['materials']['error'][$index] === UPLOAD_ERR_OK) {
                $original_name = $_FILES['materials']['name'][$index];
                $safe_name = preg_replace('/[^a-zA-Z0-9._-]/', '_', $original_name);
                $destination = $upload_dir . '/' . $safe_name;

                if (move_uploaded_file($tmp_name, $destination)) {
                    $file_record = new stdClass();
                    $file_record->task_id = $taskid;
                    $file_record->filepath = 'local/rainmake/uploads/' . $safe_name;

                    $DB->insert_record('assignment_tasks_files', $file_record);
                }
            }
        }
    }
}
$transaction->allow_commit();

redirect (
    new moodle_url('/theme/rainmake/admin/assignments/tasks.php'),
    'The assignment is successfully added!',
    null,
    \core\output\notification::NOTIFY_SUCCESS
);
