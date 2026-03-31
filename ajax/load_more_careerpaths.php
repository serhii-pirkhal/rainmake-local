<?php
define('AJAX_SCRIPT', true);
require_once(__DIR__ . '/../../../config.php');
require_login();

$PAGE->set_context(context_system::instance());

$page = optional_param('page', 1, PARAM_INT);
$perpage = optional_param('perPage', 10, PARAM_INT);

$careerpathmanager = new \local_rainmake_backend\Careerpath();
$careerpaths = $careerpathmanager->getCareerpaths($page, $perpage);
$total = $careerpathmanager->getCareerpathsCount();
$hasmore = ($page * $perpage) < $total;

echo json_encode([
    'success' => true,
    'html' => $OUTPUT->render_from_template('theme_rainmake/course_careerpath_cards', [
        'careerpaths' => $careerpaths,
    ]),
    'hasMore' => $hasmore,
    'nextPage' => $page + 1,
]);
