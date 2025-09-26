<?php

use local_rainmake_backend\dto\CreateCourseData;
use local_rainmake_backend\dto\CreateCourseMeta;

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->dirroot . '/course/lib.php');

function createCourseAction(CreateCourseData $courseData, CreateCourseMeta $metaData): int
{
    global $DB, $SESSION;
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
