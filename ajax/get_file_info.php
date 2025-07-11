<?php
require_once(__DIR__ . '/../../../config.php');
require_login();
require_sesskey();

$lectureid = required_param('id', PARAM_INT);
$record = $DB->get_record('local_rainmake_lecture_files', ['lectureid' => $lectureid]);

$data = [
    'success' => true,
    'lectureid' => $lectureid,
    'filename' => null,
    'fileurl' => null
];

if ($record) {
    $context = context_system::instance(); // или context_module::instance(...) — зависит от хранения
    $fs = get_file_storage();
    $files = $fs->get_area_files($context->id, 'local_rainmake', 'lecturefile', $lectureid, 'itemid', false);

    if (!empty($files)) {
        $file = reset($files);
        $data['filename'] = $file->get_filename();
        $data['fileurl'] = moodle_url::make_pluginfile_url(
            $file->get_contextid(),
            $file->get_component(),
            $file->get_filearea(),
            $file->get_itemid(),
            $file->get_filepath(),
            $file->get_filename()
        )->out();
    }
}

header('Content-Type: application/json');
echo json_encode($data);
