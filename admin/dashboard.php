<?php
require_once('../../../config.php');
require_login();

global $DB;
global $USER;
$PAGE->set_context(context_system::instance());

$page = local_rainmake_backend\PageRegistry::setup_page($PAGE, 'admin_dashboard');

$stats = array();
$courses = enrol_get_my_courses();
$mycourses = array();
foreach ($courses as $course) {
    $ccontext = context_course::instance($course->id);
    if (has_capability('moodle/course:update', $ccontext)) {
        $mycourses[] = $course;
    }
}
$allTeachers = array();
foreach ($mycourses as $course) {
    $context = context_course::instance($course->id);
    $teachers = get_enrolled_users($context, 'moodle/course:update');
    foreach ($teachers as $teacher) {
        $allTeachers[$teacher->id] = $teacher;
    }
}
$studentroleid = $DB->get_field('role', 'id', ['shortname' => 'student'], MUST_EXIST);
$allStudents = array();
foreach ($mycourses as $course) {
    $context = context_course::instance($course->id);
    $students = get_role_users($studentroleid, $context);
    foreach ($students as $student) {
        $allStudents[$student->id] = $student;
    }
}
$stats['instructors'] = count($allTeachers);
$stats['activecourses'] = count($mycourses);
$stats['allcourses'] = count(enrol_get_all_users_courses($USER->id));
$stats['allstudents'] = count($allStudents);


$studentsChartHtml = $OUTPUT->render_chart(createStudentsChart($mycourses), false);
$lecturesChartHtml = $OUTPUT->render_chart(createLecturesChart(), false);
$ratingChartHtml = $OUTPUT->render_chart(createRatingChart(), false);
$overviewChartHtml = $OUTPUT->render_chart(createOverviewChart($mycourses), false);
$data = [
    'pagename' => 'Dashboard',
    'stats' => $stats,
    'studentsChart' => $studentsChartHtml,
    'lecturesChart' => $lecturesChartHtml,
    'ratingChart' => $ratingChartHtml,
    'overviewChart' => $overviewChartHtml,
];

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('theme_rainmake/admindashboard', $data);
echo $OUTPUT->footer();

function createStudentsChart($mycourses): \core\chart_line
{
    global $DB;
    $studentroleid = $DB->get_field('role', 'id', ['shortname' => 'student'], MUST_EXIST);
    $studentsAttended = array();
    $labels = array();
    $records = array();
    foreach ($mycourses as $course) {
        $records[] = $DB->get_records(
            'stats_daily',
            ['courseid' => $course->id, 'roleid' => $studentroleid],
            'timeend ASC',
            'timeend, stat1'
        );
    }
    foreach ($records as $record) {
        foreach ($record as $r) {
            $studentsAttended[] = (int)$r->stat1;
            $labels[] = userdate($r->timeend, '%Y-%m-%d', 99, false);
        }
    }

    $series = new \core\chart_series('Attendance', $studentsAttended);

    $series->set_color('#0085FF');
    $series->set_fill('start');

    $chart = new \core\chart_line();
    $chart->add_series($series);
    $chart->set_labels($labels);

    $chart->set_smooth(true);

    return $chart;
}

function createLecturesChart(): \core\chart_bar
{
    $barCount = 8;
    $values   = [];
    $labels   = [];
    for ($i = 1; $i <= $barCount; $i++) {
        $values[] = rand(50000, 100000);
        $labels[] = '';
    }

    $series = new \core\chart_series('', $values);
    $series->set_color('rgb(35, 189, 51)');

    $chart = new \core\chart_bar();
    $chart->add_series($series);
    $chart->set_labels($labels);
    return $chart;
}

function createRatingChart(): \core\chart_line
{
    $values = [];
    $labels = [];
    for ($i = 0; $i < 10; $i++) {
        $values[] = mt_rand(350, 450) / 100.0;
        $labels[] = '';
    }

    $series = new \core\chart_series('', $values);
    $series->set_color('rgb(255, 192, 16)');

    $chart = new \core\chart_line();
    $chart->add_series($series);
    $chart->set_labels($labels);
    return $chart;
}

function createOverviewChart($mycourses): \core\chart_line
{
    global $DB;
    $seriesBValues = [];
    $studentsAttended = array();
    $labels = array();
    $records = array();
    foreach ($mycourses as $course) {
        $records[] = $DB->get_records(
            'stats_daily',
            ['courseid' => $course->id],
            'timeend ASC',
            'timeend, stat1'
        );
    }
    foreach ($records as $record) {
        foreach ($record as $r) {
            $studentsAttended[] = (int)$r->stat1;
            $seriesBValues[] = rand(0, 10);
            $labels[] = userdate($r->timeend, '%Y-%m-%d', 99, false);
        }
    }

    $seriesA = new \core\chart_series('activity', $studentsAttended);
    $seriesA->set_color('rgb(0, 133, 255)');

    $seriesB = new \core\chart_series('Series B', $seriesBValues);
    $seriesB->set_color('rgb(0, 88, 169)');

    $chart = new \core\chart_line();
    $chart->set_title('Two‐Series Weekly Data (0 → 1,000,000)');
    $chart->add_series($seriesA);
    $chart->add_series($seriesB);
    $chart->set_labels($labels);
    $chart->set_smooth(true);
    return $chart;
}
