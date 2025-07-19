<?php

require_once(__DIR__ . '/../../../config.php');
require_login();
require_sesskey();

$lectureid = required_param('lectureid', PARAM_INT);

$lecture = $DB->get_record('local_rainmake_backend_lectures', ['id' => $lectureid], '*', MUST_EXIST);
$video = $DB->get_record('local_rainmake_backend_lecture_video', ['lectureid' => $lectureid]);
$files = $DB->get_records('local_rainmake_backend_lecture_files', ['lectureid' => $lectureid]);

$context = context_system::instance();
$fs = get_file_storage();
$preparedFiles = [];

foreach ($files as $f) {
    $stored = $fs->get_file($context->id, 'local_rainmake_backend', 'lecture_files', $lectureid, '/', $f->filename);
    if (!$stored) continue;

    $preparedFiles[] = [
        'name' => $f->filename,
        'size' => display_size($stored->get_filesize()),
        'url'  => moodle_url::make_pluginfile_url(
            $stored->get_contextid(), $stored->get_component(),
            $stored->get_filearea(), $stored->get_itemid(),
            $stored->get_filepath(), $stored->get_filename()
        )->out(false),
        'icon' => $OUTPUT->pix_icon(file_file_icon($stored), 'file') // если хочешь иконку
    ];
}

echo json_encode([
    'success' => true,
    'data' => [
        'title' => $lecture->title,
        'description' => format_text($lecture->description, FORMAT_HTML),
        'notes' => format_text($lecture->notes, FORMAT_HTML),
        'video_url' => $video ? $video->url : '',
        'files' => $preparedFiles
    ]
]);
exit;
