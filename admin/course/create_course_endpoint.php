<?php

use local_rainmake_backend\dto\CreateCourseData;
use local_rainmake_backend\dto\CreateCourseMeta;

require_once(__DIR__ . '/../../../../config.php');
require_once(__DIR__ . '/create_course_action.php');
require_login();
require_sesskey();
global $SESSION;

$course = new CreateCourseData();
$course->fullname    = required_param('title', PARAM_TEXT);
$course->category  = required_param('coursecategory', PARAM_INT);
$course->id    = optional_param('id', null, PARAM_INT);

$meta = new CreateCourseMeta();
$meta->topic = optional_param('coursetopic', '', PARAM_TEXT);
$meta->language = optional_param('courselanguage', '', PARAM_TEXT);
$meta->level = optional_param('courselevel', '', PARAM_TEXT);

$courseid = createCourseAction($course, $meta);

$SESSION->new_course_id = $courseid;
redirect(new moodle_url('/theme/rainmake/admin/createcourse/practice.php', ['id' => $courseid]));
