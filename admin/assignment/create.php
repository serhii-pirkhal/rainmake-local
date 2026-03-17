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

$uploadedpaths = [];
if (!empty($_FILES['materials']['tmp_name']) && is_array($_FILES['materials']['tmp_name'])) {
    foreach ($_FILES['materials']['tmp_name'] as $index => $tmp_name) {
        if ($tmp_name && $_FILES['materials']['error'][$index] === UPLOAD_ERR_OK) {
            $original_name = $_FILES['materials']['name'][$index] ?? ('file_' . $index);
            $safe_name = preg_replace('/[^a-zA-Z0-9._-]/', '_', $original_name);
            // Avoid collisions.
            $safe_name = time() . '_' . $index . '_' . $safe_name;
            $destination = $upload_dir . '/' . $safe_name;
            if (move_uploaded_file($tmp_name, $destination)) {
                $uploadedpaths[] = 'local/rainmake_backend/uploads/' . $safe_name;
            }
        }
    }
}

$transaction = $DB->start_delegated_transaction();
foreach ($tasks as $task) {
    $name = clean_param($task['name'] ?? '', PARAM_TEXT);
    $feedback = clean_param($task['feedback'] ?? '', PARAM_TEXT);
    $courseid = clean_param($task['course'] ?? 0, PARAM_INT);
    $coursesraw = clean_param($task['courses'] ?? '', PARAM_RAW);
    $curriculumraw = clean_param($task['curriculum'] ?? '', PARAM_RAW);
    $useremails = clean_param($task['users'] ?? '', PARAM_RAW);

    $courseids = [];
    if ($coursesraw !== '') {
        $tokens = preg_split('/[\s,]+/', trim($coursesraw), -1, PREG_SPLIT_NO_EMPTY);
        foreach ($tokens as $t) {
            $id = clean_param($t, PARAM_INT);
            if ($id > 0) $courseids[$id] = $id;
        }
    }
    if ($courseid > 0) {
        $courseids[$courseid] = $courseid;
    }
    $courseids = array_values($courseids);
    if (empty($courseids)) {
        $courseid = 0;
    } else {
        // Keep a primary course in assignment_tasks for backwards compatibility / display.
        $courseid = (int)$courseids[0];
    }

    if (empty($name) || $courseid <= 0) {
        $transaction->rollback(new moodle_exception('invalidparameter', 'error'));
    }

    $taskr = (object)[
        'name' => $name,
        'course_id' => $courseid,
        'feedback' => $feedback,
    ];
    $taskid = $DB->insert_record('assignment_tasks', $taskr);

    // Link task to all selected courses.
    if (count($courseids) > 1) {
        $linkrecords = [];
        foreach ($courseids as $cid) {
            $linkrecords[] = (object)[
                'task_id' => $taskid,
                'course_id' => (int)$cid,
            ];
        }
        $DB->insert_records('assignment_tasks_courses', $linkrecords);
    } else {
        // Still insert link for primary course so queries can rely on the join table.
        $DB->insert_record('assignment_tasks_courses', (object)[
            'task_id' => $taskid,
            'course_id' => $courseid,
        ]);
    }

    // Persist exact curriculum selections (course/module/lecture) for display.
    $curritems = [];
    if ($curriculumraw !== '') {
        $tokens = preg_split('/[\s,]+/', trim($curriculumraw), -1, PREG_SPLIT_NO_EMPTY);
        foreach ($tokens as $tok) {
            $tok = trim($tok);
            if ($tok === '') continue;
            // Expected format: "course:ID" | "module:ID" | "lecture:ID".
            if (strpos($tok, ':') === false) continue;
            [$type, $idstr] = explode(':', $tok, 2);
            $type = clean_param($type, PARAM_ALPHA);
            $itemid = clean_param($idstr, PARAM_INT);
            if ($itemid <= 0) continue;
            if (!in_array($type, ['course', 'module', 'lecture'], true)) continue;

            $course_id = $courseid;
            $module_id = 0;
            $lecture_id = 0;
            $title = '';
            $subtitle = '';

            if ($type === 'course') {
                $c = $DB->get_record('course', ['id' => $itemid], 'id, fullname, shortname');
                if (!$c) continue;
                $course_id = (int)$c->id;
                $title = $c->fullname;
                $subtitle = $c->shortname ?? '';
            } else if ($type === 'module') {
                $s = $DB->get_record('local_rainmake_backend_sessions', ['id' => $itemid], 'id, courseid, title');
                if (!$s) continue;
                $course_id = (int)$s->courseid;
                $module_id = (int)$s->id;
                $title = $s->title;
                $subtitle = '';
            } else if ($type === 'lecture') {
                $l = $DB->get_record('local_rainmake_backend_lectures', ['id' => $itemid], 'id, sessionid, title');
                if (!$l) continue;
                $lecture_id = (int)$l->id;
                $title = $l->title;
                $subtitle = '';
                $s = $DB->get_record('local_rainmake_backend_sessions', ['id' => $l->sessionid], 'id, courseid');
                if (!$s) continue;
                $course_id = (int)$s->courseid;
                $module_id = (int)$s->id;
            }

            $curritems[] = (object)[
                'task_id' => $taskid,
                'item_type' => $type,
                'course_id' => $course_id,
                'module_id' => $module_id,
                'lecture_id' => $lecture_id,
                'title' => $title,
                'subtitle' => $subtitle,
                'timecreated' => time(),
            ];
        }
    }
    if (!empty($curritems)) {
        $DB->insert_records('assignment_tasks_curriculum', $curritems);
    } else {
        // Fallback: at least store the primary course.
        $c = $DB->get_record('course', ['id' => $courseid], 'id, fullname, shortname');
        if ($c) {
            $DB->insert_record('assignment_tasks_curriculum', (object)[
                'task_id' => $taskid,
                'item_type' => 'course',
                'course_id' => (int)$c->id,
                'module_id' => 0,
                'lecture_id' => 0,
                'title' => $c->fullname,
                'subtitle' => $c->shortname ?? '',
                'timecreated' => time(),
            ]);
        }
    }

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

    foreach ($uploadedpaths as $path) {
        $file_record = (object)[
            'task_id' => $taskid,
            'filepath' => $path,
        ];
        $DB->insert_record('assignment_tasks_files', $file_record);
    }
}
$transaction->allow_commit();

redirect (
    new moodle_url('/theme/rainmake/admin/assignments/tasks.php'),
    'The assignment is successfully added!',
    null,
    \core\output\notification::NOTIFY_SUCCESS
);
