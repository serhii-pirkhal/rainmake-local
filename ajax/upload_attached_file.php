<?php

require_once(__DIR__ . '/../../../config.php');
require_login();
require_sesskey();

$lectureid = required_param('lectureid', PARAM_INT);

if (!isset($_FILES['lecturefile'])) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded']);
    exit;
}

$file = $_FILES['lecturefile'];
$filename = clean_param($file['name'], PARAM_FILE);

if ($file['size'] > 100 * 1024 * 1024) {
    echo json_encode(['success' => false, 'message' => 'File is too large']);
    exit;
}

$fs = get_file_storage();
$context = context_system::instance();

$fs->delete_area_files($context->id, 'local_rainmake_backend', 'lecture_file', $lectureid);

$file_record = [
    'contextid' => $context->id,
    'component' => 'local_rainmake_backend',
    'filearea'  => 'lecture_file',
    'itemid'    => $lectureid,
    'filepath'  => '/',
    'filename'  => $filename
];

try {
    $storedfile = $fs->create_file_from_pathname($file_record, $file['tmp_name']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to save file']);
    exit;
}

$fileurl = moodle_url::make_pluginfile_url(
    $storedfile->get_contextid(),
    $storedfile->get_component(),
    $storedfile->get_filearea(),
    $storedfile->get_itemid(),
    $storedfile->get_filepath(),
    $storedfile->get_filename()
)->out(false);

echo json_encode(['success' => true, 'url' => $fileurl, 'filename' => $filename]);
exit;
