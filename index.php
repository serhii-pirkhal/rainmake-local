<?php
require('../../config.php');
require_login();

$PAGE->set_url(new moodle_url('/local/rainmake_backend/index.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_title('Rainmake Backend');
$PAGE->set_heading('Rainmake Backend Test Page');

echo $OUTPUT->header();
echo $OUTPUT->heading('Custom Backend Active');
echo $OUTPUT->footer();
