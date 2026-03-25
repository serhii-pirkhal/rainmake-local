<?php
require_once(__DIR__ . '/../../../../config.php');

// Check if this is a save_only request early to enable output buffering
$save_only = optional_param('save_only', 0, PARAM_INT);
if ($save_only) {
    // Start output buffering to capture any unexpected output
    ob_start();
}

require_login();
require_sesskey();

global $DB;

$courseid = required_param('id', PARAM_INT);
$welcome = optional_param('welcomemessage', '', PARAM_TEXT);
$congrats = optional_param('congratulationmessage', '', PARAM_TEXT);

$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$meta = $DB->get_record('local_rainmake_backend_coursemeta', ['courseid' => $courseid]);

if ($meta) {
    $meta->welcome = $welcome;
    $meta->congrats = $congrats;
    $DB->update_record('local_rainmake_backend_coursemeta', $meta);
} else {
    $DB->insert_record('local_rainmake_backend_coursemeta', [
        'courseid' => $courseid,
        'welcome' => $welcome,
        'congrats' => $congrats,
        'timecreated' => time(),
    ]);
}

// If save_only is set, return JSON response instead of redirecting
if ($save_only) {
    // Clean any captured output
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'courseid' => $courseid,
        'message' => 'Publish settings saved successfully'
    ]);
    exit;
}

redirect(new moodle_url('/theme/rainmake/careerpath.php', ['id' => $courseid]), 'Published', 2);