<?php
require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/user/lib.php');
require_login();

global $USER;
global $DB;

$website = optional_param('website', '', PARAM_URL);
$facebook = optional_param('facebook', '', PARAM_URL);
$slack = optional_param('slack', '', PARAM_URL);
$linkedin = optional_param('linkedin', '', PARAM_URL);
$instagram = optional_param('instagram', '', PARAM_URL);
$twitter = optional_param('twitter', '', PARAM_URL);
$youtube = optional_param('youtube', '', PARAM_URL);

$social = $DB->get_record('local_rainmake_backend_user_social', ['userid' => $USER->id]);
if ($social) {
    $social->website = $website;
    $social->facebook = $facebook;
    $social->slack = $slack;
    $social->linkedin = $linkedin;
    $social->instagram = $instagram;
    $social->twitter = $twitter;
    $social->youtube = $youtube;
    $DB->update_record('local_rainmake_backend_user_social', $social);
} else {
    $DB->insert_record('local_rainmake_backend_user_social', [
        'userid' => $USER->id,
        'website' => $website,
        'facebook' => $facebook,
        'slack' => $slack,
        'linkedin' => $linkedin,
        'instagram' => $instagram,
        'twitter' => $twitter,
        'youtube' => $youtube
    ]);
}

if(is_siteadmin()) {
    $url = new moodle_url('/theme/rainmake/adminprofile.php');
} else {
    $url = new moodle_url('/theme/rainmake/profile.php');
}
redirect($url, 'Profile updated!');