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

    /**
     * Count grades across the current user's courses.
     *
     * @param bool $onlyLow   If true, only count grades <= 60.
     * @param array|null $filters Optional filters: ['course' => int, 'student' => int].
     */
    public function getGradesCount(bool $onlyLow = false, ?array $filters = null): int
    {
        // Determine courses in scope (optionally filtered by course id).
        if (!empty($filters['course'])) {
            $courses = $this->DB->get_records('course', ['id' => $filters['course']]);
        } else {
            $courses = enrol_get_my_courses();
        }

        $count = 0;
        foreach ($courses as $course) {
            $items = $this->DB->get_records('grade_items', ['courseid' => $course->id]);
            foreach ($items as $item) {
                if ($onlyLow) {
                    $conditions = 'itemid = ? AND finalgrade <= ?';
                    $params = [$item->id, 60];
                    // Optional student filter.
                    if (!empty($filters['student'])) {
                        $conditions .= ' AND userid = ?';
                        $params[] = $filters['student'];
                    }
                    $gradesCount = $this->DB->count_records_select('grade_grades', $conditions, $params);
                } else {
                    $params = ['itemid' => $item->id];
                    if (!empty($filters['student'])) {
                        $params['userid'] = $filters['student'];
                    }
                    $gradesCount = $this->DB->count_records('grade_grades', $params);
                }
                $count += $gradesCount;
            }
        }
        return $count;
    }

    public function getGrades($page, $perPage, $onlyLow = false, $filters = null): array
    {
        $courses = null;
        if (!empty($filters) && !empty($filters['course'])) {
            $courses = $this->DB->get_records('course', ['id' => $filters['course']]);
        } else {
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
                else if (!empty($filters) && !empty($filters['student'])) {
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
                $numericgrade = isset($grade->finalgrade) ? (float)$grade->finalgrade : null;
                $gradelabel = null;
                if ($numericgrade !== null) {
                    if ($numericgrade >= 90) {
                        $gradelabel = 'A';
                    } else if ($numericgrade >= 80) {
                        $gradelabel = 'B';
                    } else if ($numericgrade >= 70) {
                        $gradelabel = 'C';
                    } else if ($numericgrade >= 60) {
                        $gradelabel = 'D';
                    } else {
                        $gradelabel = 'F';
                    }
                }
                $grades[] = [
                    'grade' => (int)$numericgrade,
                    'gradelabel' => $gradelabel,
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
