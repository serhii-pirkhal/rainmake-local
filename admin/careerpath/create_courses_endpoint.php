<?php

use local_rainmake_backend\dto\CreateCourseData;
use local_rainmake_backend\dto\CreateCourseMeta;

require_once(__DIR__ . '/../../../../config.php');
require_once(__DIR__ . '/../course/create_course_action.php');
require_login();
require_sesskey();

$courses = $_POST['courses'];
$id = required_param('id', PARAM_INT);


foreach ($courses as $cKey => $courseD) {
    if(clean_param($courseD['remove'], PARAM_BOOL)) {
        $DB->delete_records('local_rainmake_backend_careerpath_courses', array('course_id' => $cKey));
        redirect($_SERVER['HTTP_REFERER']);
    }
    $course = new CreateCourseData();
    $course->fullname    = clean_param($courseD["fullname"], PARAM_TEXT);
    $course->category  = clean_param($courseD["category"], PARAM_INT);
    $course->id    = clean_param($cKey, PARAM_INT);

    $meta = new CreateCourseMeta();
    $meta->topic = clean_param($courseD["topic"], PARAM_TEXT);
    $meta->language = clean_param($courseD["language"], PARAM_TEXT);
    $meta->level = clean_param($courseD["level"], PARAM_TEXT);

    createCourseAction($course, $meta);
}

redirect(new moodle_url('/theme/rainmake/admin/createcareerpath/createcourse/practice.php', ['id' => $id]));
