<?php
require_once(__DIR__ . '/../../../../config.php');
require_login();
require_sesskey();

$fullname    = required_param('title', PARAM_TEXT);
$description = optional_param('description', null, PARAM_TEXT);
$courseid    = optional_param('id', null, PARAM_INT);
$audience = optional_param_array('audience', [], PARAM_TEXT);
$requirements = optional_param_array('requirements', [], PARAM_TEXT);
$will_teach = optional_param_array('will_teach', [], PARAM_TEXT);

require_once($CFG->dirroot . '/course/lib.php');

$course = (object)[
    'fullname' => $fullname,
    'summary' => $description,
    'category' => 1,
];

if ($courseid) {
    $existing = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
    $course->id = $courseid;
    $course->format = $existing->format ?? 'topics';
    update_course($course);

    $courseid = $course->id;
} else {
    $newcourse = create_course($course);
    $DB->insert_record('local_rainmake_backend_course_types', [
        'course_id' => $newcourse->id,
        'type' => "careerpath",
        'timecreated' => time()
    ]);
    $courseid = $newcourse->id;
}

// Move temporary thumbnail to course if exists
// First check POST parameter (from form), then session (for backward compatibility)
$tempThumbnailData = optional_param('temp_thumbnail_data', '', PARAM_RAW);
$tempFileInfo = null;

if (!empty($tempThumbnailData)) {
    $decoded = json_decode($tempThumbnailData, true);
    if (is_array($decoded) && isset($decoded['contextid'])) {
        $tempFileInfo = $decoded;
    }
} else if (isset($SESSION->temp_course_thumbnail) && !empty($SESSION->temp_course_thumbnail)) {
    $tempFileInfo = $SESSION->temp_course_thumbnail;
}

if ($tempFileInfo) {
    $fs = get_file_storage();

    try {
        // Get the temporary file
        $tempFile = $fs->get_file(
            $tempFileInfo['contextid'],
            $tempFileInfo['component'],
            $tempFileInfo['filearea'],
            $tempFileInfo['itemid'],
            $tempFileInfo['filepath'],
            $tempFileInfo['filename']
        );

        if ($tempFile && $tempFile->get_id()) {
            // Get course context - context is created automatically after create_course()
            $courseContext = context_course::instance($courseid);

            // Delete any existing course image
            $fs->delete_area_files($courseContext->id, 'local_rainmake_backend', 'courseimage', $courseid);

            // Copy temporary file to course
            $filerecord = [
                'contextid' => $courseContext->id,
                'component' => 'local_rainmake_backend',
                'filearea'  => 'courseimage',
                'itemid'    => $courseid,
                'filepath'  => '/',
                'filename'  => $tempFileInfo['filename'],
            ];

            $newFile = $fs->create_file_from_storedfile($filerecord, $tempFile);

            if ($newFile && $newFile->get_id()) {
                // Clean up temporary file only after successful copy
                $tempFile->delete();
                // Clear session data if it exists
                if (isset($SESSION->temp_course_thumbnail)) {
                    unset($SESSION->temp_course_thumbnail);
                }
            }
        }
    } catch (Exception $e) {
        // Log error but don't fail course creation
        debugging('Failed to move temporary thumbnail: ' . $e->getMessage(), DEBUG_NORMAL);
    }
}

$record = (object)[
    'courseid'         => $courseid,
    'topic'            => optional_param('coursetopic', '', PARAM_TEXT),
    'language'         => optional_param('courselanguage', '', PARAM_TEXT),
    'level'            => optional_param('courselevel', '', PARAM_TEXT),
    'timecreated'      => time(),
];

$will_teach_json = json_encode(array_values(array_filter($will_teach)));
$audience_json = json_encode(array_values(array_filter($audience)));
$requirements_json = json_encode(array_values(array_filter($requirements)));


$meta = $DB->get_record('local_rainmake_backend_coursemeta', ['courseid' => $courseid]);
if ($meta) {
    $record->id = $meta->id;
    $record->target = $audience_json;
    $record->will_teach = $will_teach_json;
    $record->requirements = $requirements_json;
    $DB->update_record('local_rainmake_backend_coursemeta', $record);
} else {
    $DB->insert_record('local_rainmake_backend_coursemeta', [
        'courseid' => $courseid,
        'target' => $audience_json,
        'will_teach' => $will_teach_json,
        'requirements' => $requirements_json,
        'description' => $description,
        'timecreated' => time(),
    ]);
}

$instance = $DB->get_record('enrol', array(
    'courseid' => $course->id,
    'enrol' => 'self'
));

$enrol = enrol_get_plugin('self');

if ($instance) {
    $fields = array(
        'status' => ENROL_INSTANCE_ENABLED,
        'roleid' => 5,
        'password' => '',
        'enrolperiod' => 0,
        'expirynotify' => 0,
        'notifyall' => 0,
        'customint1' => 0,
        'customint2' => 0,
        'customint3' => 0,
        'customint4' => 0,
        'customint5' => 0,
        'customint6' => 1
    );

    $enrol->update_instance($instance, (object)$fields);
    echo "Self enrollment enabled and updated";
} else {
    if ($enrol) {
        $instanceid = $enrol->add_instance($course, array(
            'status' => ENROL_INSTANCE_ENABLED,
            'roleid' => 5,
            'enrolperiod' => 0,
            'expirynotify' => 0,
            'notifyall' => 0,
            'password' => '',
            'customint1' => 0,
            'customint2' => 0,
            'customint3' => 0,
            'customint4' => 0,
            'customint5' => 0,
            'customint6' => 1
        ));
        echo "Self enrollment created";
    }
}

$SESSION->new_course_id = $courseid;

redirect(new moodle_url('/theme/rainmake/admin/createcareerpath/createcourse/basic.php', ['id' => $courseid]));
