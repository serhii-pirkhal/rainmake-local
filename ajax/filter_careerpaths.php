<?php
define('AJAX_SCRIPT', true);
require_once(__DIR__ . '/../../../config.php');
require_login();

$coursemanager = new \local_rainmake_backend\Course();
$careerpathmanager = new \local_rainmake_backend\Careerpath();

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

$careerpaths = $careerpathmanager->getCareerpaths($page, $perPage, $filters, $sort, $search);
$courses = [];
foreach ($careerpaths as $careerpath) {
    $course = $DB->get_record('course', ['id' => $careerpath->id], 'id, category, fullname, shortname', MUST_EXIST);
    $courses[] = $coursemanager->courseResource($course);
}
$courses = array_values($courses);

echo json_encode([
    'success' => true,
    'html' => $OUTPUT->render_from_template('theme_rainmake/admin/courses_list', ['courses' => $courses]),
    'pagination' => '',
]);
