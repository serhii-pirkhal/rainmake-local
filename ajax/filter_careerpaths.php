<?php
define('AJAX_SCRIPT', true);
require_once(__DIR__ . '/../../../config.php');
require_login();

$coursemanager = new \local_rainmake_backend\Course();
$careerpathmanager = new \local_rainmake_backend\Careerpath();

$PAGE->set_context(context_system::instance());

global $DB, $OUTPUT;
$pages = \local_rainmake_backend\PageRegistry::get_all_pages();
$sesskey = sesskey();

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

$careerpathrecords = $careerpathmanager->getCareerpaths($page, $perPage, $filters, $sort, $search);
$careerpaths = [];
foreach ($careerpathrecords as $careerpath) {
    $course = $DB->get_record('course', ['id' => $careerpath->id], 'id, category, fullname, shortname, summary', MUST_EXIST);
    $resource = $coursemanager->courseResource($course);
    $resource->actionmenuid = 'careerpath-action-menu-' . $resource->id;
    $resource->pages = $pages;
    $resource->sesskey = $sesskey;
    $careerpaths[] = $resource;
}
$careerpaths = array_values($careerpaths);
$total = $careerpathmanager->getCareerpathsCount($filters, $search);
$hasmore = ($page * $perPage) < $total;

echo json_encode([
    'success' => true,
    'html' => $OUTPUT->render_from_template('theme_rainmake/admin/careerpath_list', [
        'careerpaths' => $careerpaths,
        'pages' => $pages,
        'sesskey' => $sesskey,
    ]),
    'pagination' => '',
    'hasMore' => $hasmore,
    'nextPage' => $page + 1,
]);
