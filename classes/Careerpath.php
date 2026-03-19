<?php

namespace local_rainmake_backend;
use core\check\performance\debugging;
use moodle_url;
use context_course;

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/course/lib.php');
require_login();

class Careerpath
{
    private \moodle_database $DB;

    public function __construct()
    {
        global $DB;
        $this->DB = $DB;
    }

    public function getCareerpathsCount($filters = null, $search = null): int
    {
        $where = "t.type = 'careerpath'";
        $params = [];

        if (!empty($search)) {
            $where .= " AND (c.fullname LIKE :search1 OR c.shortname LIKE :search2)";
            $params['search1'] = $params['search2'] = '%' . $search . '%';
        }

        if (!empty($filters['category'])) {
            $where .= " AND c.category = :category";
            $params['category'] = $filters['category'];
        }

        $sql = "
        SELECT COUNT(DISTINCT c.id)
        FROM {course} AS c
        JOIN {local_rainmake_backend_course_types} AS t ON c.id = t.course_id
        WHERE $where
        ";
        return $this->DB->count_records_sql($sql, $params);
    }
    public function getCareerpaths(int $page = 1, int $perpage = 10, $filters = null, $sort = null, $search = null): array
    {
        global $DB;

        $offset = ($page - 1) * $perpage;
        $where = "c.id != 0";
        $params = [];

        if (!empty($search)) {
            $where .= " AND (c.fullname LIKE :search1 OR c.shortname LIKE :search2)";
            $params['search1'] = $params['search2'] = '%' . $search . '%';
        }

        if (!empty($filters['category'])) {
            $where .= " AND c.category = :category";
            $params['category'] = $filters['category'];
        }

        if(empty($sort)) {
            $sort = 'id_desc';
        }
        switch ($sort) {
            case 'name_asc':
                $order = 'c.fullname ASC';
                break;
            case 'name_desc':
                $order = 'c.fullname DESC';
                break;
            case 'id_desc':
                $order = 'c.id DESC';
                break;
            case 'id_asc':
                $order = 'c.id ASC';
                break;
            default:
                $order = 'c.id DESC';
                break;
        }


        $sql = "SELECT c.id, c.fullname, c.shortname, c.summary
            FROM {course} AS c
            JOIN {local_rainmake_backend_course_types} AS t ON c.id = t.course_id
            WHERE $where 
            AND t.type = 'careerpath'
            ORDER BY $order";


        $courses = $DB->get_records_sql($sql, $params, $offset, $perpage);

        $fs = get_file_storage();
        foreach ($courses as $course) {
            $context = context_course::instance($course->id);
            $files = $fs->get_area_files($context->id, 'local_rainmake_backend', 'courseimage', $course->id, 'timemodified DESC', false);

            // Moodle sometimes stores a "directory placeholder" file with filename='.' in area files.
            // Skip it so we don't generate a broken thumbnail URL.
            $file = null;
            foreach ($files as $candidate) {
                if (!$candidate) continue;
                if ($candidate->get_filename() === '.') continue;
                $file = $candidate;
                break;
            }

            if ($file) {
                $course->img = moodle_url::make_pluginfile_url(
                    $file->get_contextid(),
                    $file->get_component(),
                    $file->get_filearea(),
                    $file->get_itemid(),
                    $file->get_filepath(),
                    $file->get_filename()
                )->out();
            }
            $course->link = PageRegistry::get_url('student_career_path', ['id' => $course->id]);
        }

        return array_values($courses);
    }

    public function getMyCareerpaths($page = 1, $perpage = 10, $filters = null, $sort = null, $search = null): array
    {
        global $DB, $USER;

        $offset = ($page - 1) * $perpage;
        $where = "c.id != 0";
        $params = [];

        if (!empty($search)) {
            $where .= " AND c.fullname LIKE :search1";
            $params['search1'] = '%' . $search . '%';
        }

        if (!empty($filters['category'])) {
            $where .= " AND c.category = :category";
            $params['category'] = $filters['category'];
        }

        $where .= " AND ue.userid = :userid";
        $params['userid'] = $USER->id;

        if (empty($sort)) {
            $sort = 'id_desc';
        }
        switch ($sort) {
            case 'name_asc':
                $order = 'c.fullname ASC';
                break;
            case 'name_desc':
                $order = 'c.fullname DESC';
                break;
            case 'id_desc':
                $order = 'c.id DESC';
                break;
            case 'id_asc':
                $order = 'c.id ASC';
                break;
            default:
                $order = 'c.id DESC';
                break;
        }

        $sql = "SELECT c.id, c.fullname, c.shortname, c.summary
        FROM {course} AS c
        JOIN {local_rainmake_backend_course_types} AS t ON c.id = t.course_id
        JOIN {enrol} AS e ON e.courseid = c.id
        JOIN {user_enrolments} AS ue ON ue.enrolid = e.id
        WHERE $where 
            AND t.type = 'careerpath'
        ORDER BY $order";

        $courses = $DB->get_records_sql($sql, $params, $offset, $perpage);

        $fs = get_file_storage();
        foreach ($courses as $course) {
            $context = context_course::instance($course->id);
            $files = $fs->get_area_files($context->id, 'local_rainmake_backend', 'courseimage', $course->id, 'timemodified DESC', false);

            // Moodle sometimes stores a "directory placeholder" file with filename='.' in area files.
            // Skip it so we don't generate a broken thumbnail URL.
            $file = null;
            foreach ($files as $candidate) {
                if (!$candidate) continue;
                if ($candidate->get_filename() === '.') continue;
                $file = $candidate;
                break;
            }

            if ($file) {
                $course->img = moodle_url::make_pluginfile_url(
                    $file->get_contextid(),
                    $file->get_component(),
                    $file->get_filearea(),
                    $file->get_itemid(),
                    $file->get_filepath(),
                    $file->get_filename()
                )->out();
            }
            $course->link = PageRegistry::get_url('student_career_path', ['id' => $course->id]);
        }

        return array_values($courses);
    }
}
