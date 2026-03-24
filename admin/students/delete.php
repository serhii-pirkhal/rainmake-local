<?php
require('../../../../config.php');
require_login();
confirm_sesskey();

$id = required_param('id', PARAM_INT);

if ($id <= 0) {
    redirect(new moodle_url('/theme/rainmake/admin/students.php'));
}

if ($USER->id == $id) {
    redirect(
        new moodle_url('/theme/rainmake/admin/students.php'),
        'You cannot delete your own account from this page.',
        null,
        \core\output\notification::NOTIFY_ERROR
    );
}

$user = $DB->get_record('user', ['id' => $id], 'id, username', IGNORE_MISSING);
if (!$user || $user->username === 'guest' || $user->username === 'admin') {
    redirect(
        new moodle_url('/theme/rainmake/admin/students.php'),
        'This user cannot be deleted from this page.',
        null,
        \core\output\notification::NOTIFY_ERROR
    );
}

$user->deleted = 1;
$user->email = md5((string)time() . '_' . $user->id) . '@example.invalid';
$user->username = 'deleteduser_' . $user->id . '_' . time();
$DB->update_record('user', $user);

redirect(
    new moodle_url('/theme/rainmake/admin/students.php'),
    'Student deleted successfully.',
    null,
    \core\output\notification::NOTIFY_SUCCESS
);

