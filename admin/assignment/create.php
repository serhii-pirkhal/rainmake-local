<?php
require('../../../../config.php');
require_login();
require_sesskey();
global $DB;

$upload_dir = $CFG->dirroot . '/local/rainmake_backend/uploads';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

$tasks = $_POST['tasks'] ?? null;
if (!$tasks || !is_array($tasks)) {
    redirect(
        new moodle_url('/theme/rainmake/admin/assignments/tasks.php'),
        'Invalid assignment data.',
        null,
        \core\output\notification::NOTIFY_ERROR
    );
}

$transaction = $DB->start_delegated_transaction();
foreach ($tasks as $task) {
    $name = clean_param($task['name'] ?? '', PARAM_TEXT);
    $feedback = clean_param($task['feedback'] ?? '', PARAM_TEXT);
    $courseid = clean_param($task['course'] ?? 0, PARAM_INT);
    $useremails = clean_param($task['users'] ?? '', PARAM_RAW);

    if (empty($name) || $courseid <= 0) {
        $transaction->rollback(new moodle_exception('invalidparameter', 'error'));
    }

    $taskr = (object)[
        'name' => $name,
        'course_id' => $courseid,
        'feedback' => $feedback,
    ];
    $taskid = $DB->insert_record('assignment_tasks', $taskr);

    // Parse tokens separated by spaces/commas; each can be an email or a username.
    $emails = preg_split('/[\s,]+/', trim($useremails), -1, PREG_SPLIT_NO_EMPTY);
    $studentsr = [];
    foreach ($emails as $token) {
        $token = trim($token);
        if ($token === '') {
            continue;
        }
        $user = null;
        if (strpos($token, '@') !== false) {
            // Looks like an email.
            $email = clean_param($token, PARAM_EMAIL);
            if ($email === '') {
                continue;
            }
            $user = $DB->get_record('user', ['email' => $email], 'id');
        } else {
            // Treat as username.
            $username = clean_param($token, PARAM_USERNAME);
            if ($username === '') {
                continue;
            }
            $user = $DB->get_record('user', ['username' => $username], 'id');
        }

        if ($user) {
            $studentsr[] = (object)[
                'task_id' => $taskid,
                'user_id' => $user->id,
            ];
        }
    }
    if (!empty($studentsr)) {
        $DB->insert_records('assignment_tasks_students', $studentsr);
    }

    if (!empty($_FILES['materials']['tmp_name']) && is_array($_FILES['materials']['tmp_name'])) {
        foreach ($_FILES['materials']['tmp_name'] as $index => $tmp_name) {
            if ($tmp_name && $_FILES['materials']['error'][$index] === UPLOAD_ERR_OK) {
                $original_name = $_FILES['materials']['name'][$index];
                $safe_name = preg_replace('/[^a-zA-Z0-9._-]/', '_', $original_name);
                $destination = $upload_dir . '/' . $safe_name;

                if (move_uploaded_file($tmp_name, $destination)) {
                    $file_record = (object)[
                        'task_id' => $taskid,
                        'filepath' => 'local/rainmake_backend/uploads/' . $safe_name,
                    ];
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
