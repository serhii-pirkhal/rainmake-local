<?php

use local_rainmake_backend\dto\CreateCourseData;
use local_rainmake_backend\dto\CreateCourseMeta;

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->dirroot . '/course/lib.php');

function createCourseAction(CreateCourseData $courseData, CreateCourseMeta $metaData): int
{
    global $DB, $SESSION, $USER;
    $course = (object)[
        'fullname' => $courseData->fullname,
        'category' => $courseData->category,
    ];

    if ($courseData->id) {
        $existing = $DB->get_record('course', ['id' => $courseData->id], '*', MUST_EXIST);
        $course->id = $courseData->id;
        $course->format = $existing->format ?? 'topics';
        update_course($course);

        $courseid = $course->id;
    } else {
        $newcourse = create_course($course);
        $DB->insert_record('local_rainmake_backend_course_types', [
            'course_id' => $newcourse->id,
            'type' => "course",
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
        'topic'            => $metaData->topic,
        'language'         => $metaData->language,
        'level'            => $metaData->level,
        'timecreated'      => time(),
    ];

    $existingmeta = $DB->get_record('local_rainmake_backend_coursemeta', ['courseid' => $courseid]);
    if ($existingmeta) {
        $record->id = $existingmeta->id;
        $DB->update_record('local_rainmake_backend_coursemeta', $record);
    } else {
        $DB->insert_record('local_rainmake_backend_coursemeta', $record);
    }

    return $courseid;
}
