<?php
require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/user/lib.php');
require_login();

global $USER;
global $DB;

$oldPassword = required_param('old_password', PARAM_TEXT);
$password = required_param('password', PARAM_TEXT);
$confirmPassword = required_param('confirm_password', PARAM_TEXT);

if(is_siteadmin()) {
    $url = new moodle_url('/theme/rainmake/adminprofile.php');
} else {
    $url = new moodle_url('/theme/rainmake/profile.php');
}

$fulluser = $DB->get_record(
    'user',
    ['id' => $USER->id],
    'id, auth, username, password, confirmed, suspended, deleted, mnethostid',
    MUST_EXIST
);

if (!validate_internal_user_password($fulluser, $oldPassword)) {
    redirect($url, 'Old password is incorrect.', null, 'error');
}

if($password != $confirmPassword) {
    redirect($url, 'Passwords do not match!', null, 'error');
}

update_internal_user_password($fulluser, $password);

redirect($url, 'Password updated!');