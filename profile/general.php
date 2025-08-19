<?php
require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/user/lib.php');
require_login();

$email = required_param('email', PARAM_TEXT);
$firstname = required_param('firstname', PARAM_TEXT);
$lastname = required_param('lastname', PARAM_TEXT);
$username = required_param('username', PARAM_TEXT);
$phone1 = optional_param('phone1', null, PARAM_TEXT);
$institution = optional_param('institution', null, PARAM_TEXT);
$department = optional_param('department', null, PARAM_TEXT);

global $USER;
global $DB;

$user = new stdClass();
$user->id = $USER->id;
$user->email = $email;
$user->firstname = $firstname;
$user->lastname  = $lastname;
$user->username  = $username;
$user->phone1  = $phone1;
$user->institution  = $institution;
$user->department  = $department;

user_update_user($user, false, true);

if(is_siteadmin()) {
    $url = new moodle_url('/theme/rainmake/adminprofile.php');
} else {
    $url = new moodle_url('/theme/rainmake/profile.php');
}

if (!empty($_FILES['userpicture']) && $_FILES['userpicture']['error'] === UPLOAD_ERR_OK) {

    $tmpname = $_FILES['userpicture']['tmp_name'];
    $orig    = $_FILES['userpicture']['name'];

    if (filesize($tmpname) > 1024 * 1024) {
        redirect($url, 'Image is too large (max 1MB).', null, \core\output\notification::NOTIFY_ERROR);
    }
    if (!@getimagesize($tmpname)) {
        redirect($url, 'Uploaded file is not an image.', null, \core\output\notification::NOTIFY_ERROR);
    }

    $ext = pathinfo($orig, PATHINFO_EXTENSION);
    $tempdir = make_temp_directory('userpix');
    $fullpath = $tempdir . '/' . $USER->id . '_' . time() . (!empty($ext) ? ('.' . $ext) : '');

    if (!move_uploaded_file($tmpname, $fullpath)) {
        redirect($url, 'Failed to move uploaded file.', null, \core\output\notification::NOTIFY_ERROR);
    }

    $context = context_user::instance($USER->id, MUST_EXIST);
    $newiconid = process_new_icon($context, 'user', 'icon', 0, $fullpath);

    @unlink($fullpath);

    if ($newiconid) {
        $DB->set_field('user', 'picture', $newiconid, ['id' => $USER->id]);

        $fresh = \core_user::get_user($USER->id);
        \core\session\manager::set_user($fresh);
    } else {
        redirect($url, 'Image processing failed.', null, \core\output\notification::NOTIFY_ERROR);
    }
}

$fresh = \core_user::get_user($USER->id);
\core\session\manager::set_user($fresh);
$USER = $fresh;


redirect($url, 'Profile updated!');