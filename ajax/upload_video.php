<?php

require_once(__DIR__ . '/../../../config.php');
require_login();
require_sesskey();

$lectureid = required_param('lectureid', PARAM_INT);
$duration = required_param('duration', PARAM_INT);

if (!isset($_FILES['videofile'])) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded']);
    exit;
}

$fs = get_file_storage();
$context = context_system::instance();

$file = $_FILES['videofile'];
$filename = clean_param($file['name'], PARAM_FILE);

$fs->delete_area_files($context->id, 'local_rainmake_backend', 'lecture_video', $lectureid);

$file_record = [
    'contextid' => $context->id,
    'component' => 'local_rainmake_backend',
    'filearea'  => 'lecture_video',
    'itemid'    => $lectureid,
    'filepath'  => '/',
    'filename'  => $filename
];

$storedfile = $fs->create_file_from_pathname($file_record, $file['tmp_name']);

$fileurl = moodle_url::make_pluginfile_url(
    $storedfile->get_contextid(),
    $storedfile->get_component(),
    $storedfile->get_filearea(),
    $storedfile->get_itemid(),
    $storedfile->get_filepath(),
    $storedfile->get_filename()
)->out(false);

$thumbnail = null;

$existing = $DB->get_record('local_rainmake_backend_lecture_video', ['lectureid' => $lectureid]);
$now = time();

$record = [
    'lectureid' => $lectureid,
    'filename' => $filename,
    'duration' => $duration,
    'thumbnail' => $thumbnail,
    'url' => $fileurl,
    'timemodified' => $now,
];

if ($existing) {
    $record['id'] = $existing->id;
    $DB->update_record('local_rainmake_backend_lecture_video', $record);
} else {
    $record['timecreated'] = $now;
    $DB->insert_record('local_rainmake_backend_lecture_video', $record);
}

echo json_encode(['success' => true, 'url' => $fileurl]);
exit;
