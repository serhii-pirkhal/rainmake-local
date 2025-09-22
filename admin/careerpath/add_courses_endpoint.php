<?php

use local_rainmake_backend\dto\CreateCourseData;
use local_rainmake_backend\dto\CreateCourseMeta;

require_once(__DIR__ . '/../../../../config.php');
require_once(__DIR__ . '/../course/create_course_action.php');
require_login();
require_sesskey();

$careerPathId = required_param('careerpath_id', PARAM_INT);
$courses = $_POST['courses'];

foreach ($courses as $cKey => $courseD) {
    if(clean_param($courseD['remove'], PARAM_BOOL)) {
        $DB->delete_records('local_rainmake_backend_careerpath_courses', array('course_id' => $cKey));
        continue;
    }elseif (clean_param($courseD['add'], PARAM_BOOL)) {
        $record = (object)[
            'course_id' => $cKey,
            'careerpath_id' => $careerPathId,
            'timecreated' => time(),
        ];
        $DB->insert_record('local_rainmake_backend_careerpath_courses', $record);
        continue;
    }
}

redirect($_SERVER['HTTP_REFERER']);
