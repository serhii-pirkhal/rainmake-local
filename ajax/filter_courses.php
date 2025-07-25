<?php
define('AJAX_SCRIPT', true);
require_once(__DIR__ . '/../../../config.php');
require_login();

$course = new \local_rainmake_backend\Course();

$PAGE->set_context(context_system::instance());

global $DB, $OUTPUT;

$filters = [];
$page = optional_param('page', 1, PARAM_INT);
$perPage = optional_param('perPage', 10, PARAM_INT);
$filters = array();
$fCategory = optional_param('category', null, PARAM_TEXT);
$sort = optional_param('sort', null, PARAM_TEXT);
$search = optional_param('search', '', PARAM_TEXT);
if ($fCategory) {
    $filters['category'] = $fCategory;
}

$courses = $course->getMyCourses($page, $perPage, $filters, $sort, $search);
$courses = array_values($courses);

echo json_encode([
    'success' => true,
    'html' => $OUTPUT->render_from_template('theme_rainmake/admin/courses_list', ['courses' => $courses]),
    'pagination' => '',
]);
