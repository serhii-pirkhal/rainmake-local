<?php
require_once(__DIR__ . '/../../../config.php');
require_login();
require_sesskey();

require_once($CFG->libdir . '/filelib.php');

global $USER, $SESSION;

$courseid = optional_param('courseid', 0, PARAM_INT);
$isTemporary = ($courseid == 0);

$fs = get_file_storage();

if ($isTemporary) {
    // Temporary upload - store in user context with custom filearea
    $context = context_user::instance($USER->id);
    $itemid = 0; // Use 0 for temporary files

    // Delete any existing temporary thumbnail for this user
    $fs->delete_area_files($context->id, 'local_rainmake_backend', 'temp_courseimage', $itemid);

    // Store temporary file info in session
    if (!isset($SESSION->temp_course_thumbnail)) {
        $SESSION->temp_course_thumbnail = [];
    }
} else {
    // Regular upload - store in course context
    $context = context_course::instance($courseid);
    $itemid = $courseid;
    $fs->delete_area_files($context->id, 'local_rainmake_backend', 'courseimage', $courseid);
}

if (!empty($_FILES['courseimage']['tmp_name']) && is_uploaded_file($_FILES['courseimage']['tmp_name'])) {
    if ($_FILES['courseimage']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode([
            'success' => false,
            'error' => 'Upload error: ' . $_FILES['courseimage']['error']
        ]);
        exit;
    }

    $filename = clean_param($_FILES['courseimage']['name'], PARAM_FILE);

    if ($isTemporary) {
        // Store in user context temporarily
        $filerecord = [
            'contextid' => $context->id,
            'component' => 'local_rainmake_backend',
            'filearea'  => 'temp_courseimage',
            'itemid'    => $itemid,
            'filepath'  => '/',
            'filename'  => $filename,
        ];

        $storedfile = $fs->create_file_from_pathname($filerecord, $_FILES['courseimage']['tmp_name']);

        if ($storedfile && $storedfile->get_id()) {
            // Store file info in session for later retrieval
            $SESSION->temp_course_thumbnail = [
                'contextid' => $storedfile->get_contextid(),
                'component' => $storedfile->get_component(),
                'filearea' => $storedfile->get_filearea(),
                'itemid' => $storedfile->get_itemid(),
                'filepath' => $storedfile->get_filepath(),
                'filename' => $storedfile->get_filename(),
            ];

            // Create a temporary URL using data URI or file path
            $url = moodle_url::make_pluginfile_url(
                $storedfile->get_contextid(),
                $storedfile->get_component(),
                $storedfile->get_filearea(),
                $storedfile->get_itemid(),
                $storedfile->get_filepath(),
                $storedfile->get_filename()
            )->out();

            echo json_encode([
                'success' => true,
                'imageurl' => $url,
                'temporary' => true,
                'fileinfo' => [
                    'contextid' => $storedfile->get_contextid(),
                    'component' => $storedfile->get_component(),
                    'filearea' => $storedfile->get_filearea(),
                    'itemid' => $storedfile->get_itemid(),
                    'filepath' => $storedfile->get_filepath(),
                    'filename' => $storedfile->get_filename(),
                ]
            ]);
            exit;
        }
    } else {
        // Regular course upload
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
}

echo json_encode(['success' => false]);
