<?php

namespace local_rainmake_backend;
require_once(__DIR__ . '/../../../config.php');
require_login();

class Gradebook
{
    private \moodle_database $DB;

    public function __construct()
    {
        global $DB;
        $this->DB = $DB;
    }

    public function getGradesCount(): int
    {
        $courses = enrol_get_my_courses($onlylow=false);
        $count = 0;
        foreach ($courses as $course) {
            $items = $this->DB->get_records('grade_items', ['courseid' => $course->id]);
            foreach ($items as $item) {
                if($onlylow){
                    $gradesCount = $this->DB->count_records_select(
                        'grade_grades',
                        'itemid = ? AND finalgrade <= ?',
                        [$item->id, 60]
                    );
                }
                else{
                    $gradesCount = $this->DB->count_records(
                        'grade_grades',
                        ['itemid' => $item->id],
                    );
                }
                $count += $gradesCount;
            }
        }
        return $count;
    }

    public function getGrades($page, $perPage, $onlyLow = false, $filters = null): array
    {
        $courses = null;
        if($filters && $filters['course']){
            $courses = $this->DB->get_records('course', ['id' => $filters['course']]);
        }
        else{
            $courses = enrol_get_my_courses();
        }
        $grades = array();
        $gradesGrades = array();
        $perpage = $perPage * $page;
        $skipNum = 0;
        $skipNumSmall = 0;
        $brakeNum = 0;
        foreach ($courses as $course) {
            $items = $this->DB->get_records('grade_items', ['courseid' => $course->id]);
            foreach ($items as $item) {
                $gradesCount = $this->DB->count_records(
                    'grade_grades',
                    ['itemid' => $item->id],
                );
                $perpage -= $gradesCount;
                $brakeNum++;
                if ($perpage <= 0) {
                    break 2;
                }
                if ($perpage >= $perPage) {
                    $skipNum++;
                } else if ($perpage + $gradesCount > $perPage) {
                    $skipNumSmall = $perPage - $perpage;
                }
            }
        }
        foreach ($courses as $course) {
            $cnd = ['courseid' => $course->id];
            $items = $this->DB->get_records('grade_items', $cnd);
            foreach ($items as $item) {
                if ($brakeNum == 0) {
                    break 2;
                }
                $brakeNum--;
                if ($skipNum > 0) {
                    $skipNum--;
                    continue;
                }
                if($onlyLow){
                    $gradesGrades[] = [
                        'item' => $item,
                        'grades' => $this->DB->get_records_select(
                            'grade_grades',
                            'itemid = ? AND finalgrade <= ?',
                            [$item->id, 60],
                            '',
                            'id, userid, finalgrade, feedback'
                        )
                    ];
                }
                else if($filters && $filters['student']){
                    $gradesGrades[] = [
                        'item' => $item,
                        'grades' => $this->DB->get_records(
                            'grade_grades',
                            ['itemid' => $item->id,
                            'userid' => $filters['student']],
                            '',
                            'id, userid, finalgrade, feedback'
                        )
                    ];
                }
                else {
                    $gradesGrades[] = [
                        'item' => $item,
                        'grades' => $this->DB->get_records(
                            'grade_grades',
                            ['itemid' => $item->id],
                            '',
                            'id, userid, finalgrade, feedback'
                        )
                    ];
                }
            }
        }
        foreach ($gradesGrades as $gradesGrade) {
            $item = $gradesGrade['item'];
            foreach ($gradesGrade['grades'] as $grade) {
                $user = $this->DB->get_record('user', array('id' => $grade->userid))?: null;
                $course = $this->DB->get_record('course', array('id' => $item->courseid))?: null;
                $category = $this->DB->get_record('course_categories', array('id' => $item->categoryid))?: null;
                $grades[] = [
                    'grade' => (int)$grade->finalgrade,
                    'gradeid' => $grade->id,
                    'feedback' => $grade->feedback,
                    'userid' => $grade->userid,
                    'username' => $user->firstname . ' ' . $user->lastname,
                    'courseid' => $item->courseid,
                    'coursename' => $course?->shortname,
                    'categoryid' => $item->categoryid,
                    'categoryname' => $category?->name,
                    'itemname' => $item->itemname,
                    'itemtype' => $item->itemtype,
                    'isoverall' => $item->itemtype == 'course',
                ];
            }
        }
        if ($perpage < 0) {
            $grades = array_slice($grades, 0, $perpage);
        }
        $grades = array_slice($grades, $skipNumSmall);
        return $grades;
    }
}
