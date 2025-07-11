<?php
require_once(__DIR__ . '/../../../config.php');
require_login();
require_sesskey();

require_once($CFG->libdir . '/filelib.php');

$courseid = required_param('courseid', PARAM_INT);
$context = context_course::instance($courseid);

$fs = get_file_storage();

$fs->delete_area_files($context->id, 'local_rainmake_backend', 'courseimage', $courseid);

if (!empty($_FILES['courseimage']['tmp_name']) && is_uploaded_file($_FILES['courseimage']['tmp_name'])) {
    if ($_FILES['courseimage']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode([
            'success' => false,
            'error' => 'Upload error: ' . $_FILES['courseimage']['error']
        ]);
        exit;
    }

    $filename = clean_param($_FILES['courseimage']['name'], PARAM_FILE);

    $filerecord = [
        'contextid' => $context->id,
        'component' => 'local_rainmake_backend',
        'filearea'  => 'courseimage',
        'itemid'    => $courseid,
        'filepath'  => '/',
        'filename'  => $filename,
    ];

    $storedfile = $fs->create_file_from_pathname($filerecord, $_FILES['courseimage']['tmp_name']);

    if ($storedfile) {
        $url = moodle_url::make_pluginfile_url(
            $storedfile->get_contextid(),
            $storedfile->get_component(),
            $storedfile->get_filearea(),
            $storedfile->get_itemid(),
            $storedfile->get_filepath(),
            $storedfile->get_filename()
        )->out();

        echo json_encode(['success' => true, 'imageurl' => $url]);
        exit;
    }
}

echo json_encode(['success' => false]);
