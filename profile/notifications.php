<?php
require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/user/lib.php');
require_login();

global $USER;
global $DB;

$buy_course = optional_param('buy_course', 0, PARAM_INT);
$write_review = optional_param('write_review', 0, PARAM_INT);
$comment_course = optional_param('comment_course', 0, PARAM_INT);
$download_notes = optional_param('download_notes', 0, PARAM_INT);
$reply_comment = optional_param('reply_comment', 0, PARAM_INT);
$people_visited = optional_param('people_visited', 0, PARAM_INT);
$download_attach = optional_param('download_attach', 0, PARAM_INT);

$notifications = $DB->get_record('local_rainmake_backend_user_notification', ['userid' => $USER->id]);
if ($notifications) {
    $notifications->buy_course = $buy_course;
    $notifications->write_review = $write_review;
    $notifications->comment_course = $comment_course;
    $notifications->download_notes = $download_notes;
    $notifications->reply_comment = $reply_comment;
    $notifications->people_visited = $people_visited;
    $notifications->download_attach = $download_attach;
    $DB->update_record('local_rainmake_backend_user_notification', $notifications);
} else {
    $DB->insert_record('local_rainmake_backend_user_notification', [
        'userid' => $USER->id,
        'buy_course' => $buy_course,
        'write_review' => $write_review,
        'comment_course' => $comment_course,
        'download_notes' => $download_notes,
        'reply_comment' => $reply_comment,
        'people_visited' => $people_visited,
        'download_attach' => $download_attach
    ]);
}

redirect(new moodle_url('/theme/rainmake/adminprofile.php'), 'Profile updated!');