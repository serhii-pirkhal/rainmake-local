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
        $courses = $this->get_courses_in_scope($filters);
        $studentid = !empty($filters['student']) ? (int)$filters['student'] : null;

        $count = 0;
        foreach ($courses as $course) {
            $items = $this->DB->get_records('grade_items', ['courseid' => $course->id]);
            if (empty($items)) {
                continue;
            }
            $enrolled = $this->get_enrolled_user_ids((int)$course->id, $studentid);
            if (empty($enrolled)) {
                continue;
            }

            foreach ($items as $item) {
                if ($onlyLow) {
                    // Only count users whose grade (if present) is low; users without a grade are excluded.
                    [$insql, $inparams] = $this->DB->get_in_or_equal($enrolled, SQL_PARAMS_NAMED, 'u');
                    $params = array_merge(['itemid' => $item->id, 'maxgrade' => 60], $inparams);
                    $sql = "SELECT COUNT(1)
                              FROM {grade_grades} gg
                             WHERE gg.itemid = :itemid
                               AND gg.finalgrade <= :maxgrade
                               AND gg.userid $insql";
                    $count += (int)$this->DB->count_records_sql($sql, $params);
                } else {
                    $count += count($enrolled);
                }
            }
        }
        return $count;
    }

    public function getGrades($page, $perPage, $onlyLow = false, $filters = null): array
    {
        $courses = $this->get_courses_in_scope($filters);
        $studentid = !empty($filters['student']) ? (int)$filters['student'] : null;

        // Build a flat list of (course,item,user) rows, then slice for pagination.
        $rows = [];
        foreach ($courses as $course) {
            $items = $this->DB->get_records('grade_items', ['courseid' => $course->id]);
            if (empty($items)) {
                continue;
            }
            $enrolled = $this->get_enrolled_user_ids((int)$course->id, $studentid);
            if (empty($enrolled)) {
                continue;
            }
            foreach ($items as $item) {
                foreach ($enrolled as $uid) {
                    // Optional low-grade filter: only include when there is a low grade record.
                    if ($onlyLow) {
                        $gg = $this->DB->get_record_select(
                            'grade_grades',
                            'itemid = ? AND userid = ? AND finalgrade <= ?',
                            [$item->id, $uid, 60],
                            'id, userid, finalgrade, feedback',
                            IGNORE_MISSING
                        );
                        if (!$gg) {
                            continue;
                        }
                    }
                    $rows[] = ['courseid' => (int)$course->id, 'itemid' => (int)$item->id, 'userid' => (int)$uid];
                }
            }
        }

        $offset = max(0, ((int)$page - 1) * (int)$perPage);
        $slice = array_slice($rows, $offset, (int)$perPage);

        $grades = [];
        foreach ($slice as $r) {
            $item = $this->DB->get_record('grade_items', ['id' => $r['itemid']]);
            if (!$item) {
                continue;
            }
            $user = $this->DB->get_record('user', ['id' => $r['userid']], 'id, firstname, lastname');
            $course = $this->DB->get_record('course', ['id' => $r['courseid']], 'id, shortname');
            $category = $this->DB->get_record('course_categories', ['id' => $item->categoryid], 'id, name');

            $grade = $this->DB->get_record('grade_grades', ['itemid' => $item->id, 'userid' => $r['userid']], 'id, userid, finalgrade, feedback', IGNORE_MISSING);
            $numericgrade = ($grade && isset($grade->finalgrade)) ? (float)$grade->finalgrade : null;
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
                'grade' => $numericgrade !== null ? (int)$numericgrade : null,
                'gradelabel' => $gradelabel,
                'gradeid' => $grade ? $grade->id : 0,
                'feedback' => $grade ? $grade->feedback : '',
                'userid' => $r['userid'],
                'username' => ($user ? ($user->firstname . ' ' . $user->lastname) : ''),
                'courseid' => $r['courseid'],
                'coursename' => $course?->shortname,
                'categoryid' => $item->categoryid,
                'categoryname' => $category?->name,
                'itemname' => $item->itemname,
                'itemid' => $item->id,
                'itemtype' => $item->itemtype,
                'isoverall' => $item->itemtype == 'course',
            ];
        }

        return $grades;
    }

    private function get_courses_in_scope(?array $filters): array {
        if (!empty($filters['course'])) {
            return $this->DB->get_records('course', ['id' => (int)$filters['course']]);
        }
        return enrol_get_my_courses();
    }

    /**
     * List enrolled (active) user IDs for a course.
     * If $studentid is provided, returns only that ID if enrolled, else [].
     */
    private function get_enrolled_user_ids(int $courseid, ?int $studentid = null): array {
        $params = ['courseid' => $courseid];
        $sql = "SELECT DISTINCT u.id
                  FROM {enrol} e
                  JOIN {user_enrolments} ue ON ue.enrolid = e.id AND ue.status = 0
                  JOIN {user} u ON u.id = ue.userid AND u.deleted = 0
                 WHERE e.courseid = :courseid";
        if (!empty($studentid)) {
            $sql .= " AND u.id = :studentid";
            $params['studentid'] = $studentid;
        }
        $recs = $this->DB->get_records_sql($sql, $params);
        return array_values(array_map(fn($r) => (int)$r->id, $recs));
    }
}
