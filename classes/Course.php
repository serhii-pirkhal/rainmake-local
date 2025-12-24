<?php

namespace local_rainmake_backend;
use moodle_url;
use context_course;

require_once(__DIR__ . '/../../../config.php');
require_login();

class Course {
    private \moodle_database $DB;

    public function __construct()
    {
        global $DB;
        $this->DB = $DB;
    }


    public function getMyCoursesCount($filters = null, $search = null): int
    {
        global $DB;

        $where = "format = :format";
        $params = ['format' => 'topics'];

        if (!empty($search)) {
            $where .= " AND (c.fullname LIKE :search1 OR c.shortname LIKE :search2)";
            $params['search1'] = $params['search2'] = '%' . $search . '%';
        }

        if (!empty($filters['category'])) {
            $where .= " AND c.category = :category";
            $params['category'] = $filters['category'];
        }

        $sql = "SELECT COUNT(c.id)
            FROM {course} AS c
            JOIN {local_rainmake_backend_course_types} AS t ON c.id = t.course_id
            WHERE $where 
            AND t.type = 'course'";

        return $DB->count_records_sql($sql, $params);
    }
    public function getMyCourse(int $courseid): ?object
    {
        $course = $this->DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
        if (!$course) {
            return null;
        }
        return $this->courseResource($course);
    }
    public function getMyCourses($page = 1, $perpage = 10, $filters = null, $sort = null, $search = null): array
    {
        global $DB;

        $offset = ($page - 1) * $perpage;
        $where = "format = :format";
        $params = ['format' => 'topics'];

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
        }


        $sql = "SELECT c.id, c.category, c.fullname, c.shortname 
            FROM {course} AS c
            JOIN {local_rainmake_backend_course_types} AS t ON c.id = t.course_id
            WHERE $where 
            AND t.type = 'course'
            ORDER BY $order";

        $courses = $DB->get_records_sql($sql, $params, $offset, $perpage);

        foreach ($courses as &$course) {
            $course = $this->courseResource($course);
        }
        unset($course);

        return array_values($courses);
    }

    public function courseResource($course) {
        $fs = get_file_storage();
        $course->lectures_finished = 0;
        $course->lectures_total = 0;
        $course->duration = 0;
        $course->sessions_count = 0;
        $course->category = $this->DB->get_record('course_categories', array('id' => $course->category), 'id, name, description, coursecount');
        $context = context_course::instance($course->id);
        $course->usercount = count_enrolled_users($context);
        $course->modulescount = $this->DB->count_records('course_modules', array('course' => $course->id));

        $context = context_course::instance($course->id);
        $files = $fs->get_area_files($context->id, 'local_rainmake_backend', 'courseimage', $course->id, 'timemodified DESC', false);

        if ($file = reset($files)) {
            $course->img = moodle_url::make_pluginfile_url(
                $file->get_contextid(),
                $file->get_component(),
                $file->get_filearea(),
                $file->get_itemid(),
                $file->get_filepath(),
                $file->get_filename()
            )->out();
        }

        $sessions = $this->DB->get_records('local_rainmake_backend_sessions', ['courseid' => $course->id]);

        foreach ($sessions as &$session) {
            $session->finished = 0;
            $session->opened = false;
            $session->duration = 0;
            $lectures = $this->DB->get_records('local_rainmake_backend_lectures', ['sessionid' => $session->id]);

            foreach ($lectures as $index => &$lecture) {
                $lecture->video = $this->DB->get_record('local_rainmake_backend_lecture_video', ['lectureid' => $lecture->id]);

                $files = $this->DB->get_records('local_rainmake_backend_lecture_files', ['lectureid' => $lecture->id]);
                $lecture->files = [];
                foreach ($files as $f) {
                    $stored = $fs->get_file($context->id, 'local_rainmake_backend', 'lecture_files', $lecture->id, '/', $f->filename);
                    if (!$stored) continue;

                    $lecture->files[] = [
                        'name' => $f->filename,
                        'size' => display_size($stored->get_filesize()),
                        'url'  => moodle_url::make_pluginfile_url(
                            $stored->get_contextid(), $stored->get_component(),
                            $stored->get_filearea(), $stored->get_itemid(),
                            $stored->get_filepath(), $stored->get_filename()
                        )->out(false),
                        'icon' => $OUTPUT->pix_icon(file_file_icon($stored), 'file')
                    ];
                }
                $lecture->attached_count = count($lecture->files);
                $lecture->index = $index;
                $duration = $lecture->video ? (int)$lecture->video->duration : 0;
                $session->duration += $duration;
                $course->duration += $duration;
                $lecture->duration = $this->getDuration($duration);

                $lectureViews = $this->DB->get_records('local_rainmake_backend_lecture_views', ['lectureid' => $lecture->id]);
                if($lectureViews) {
                    $course->lectures_finished++;
                }
            }

            $session->total = count($lectures);
            $course->lectures_total += $session->total;
            $session->lectures_count = count($lectures);
            $session->lectures = array_values($lectures);
            $session->duration = $this->getDuration($session->duration);
        }

        $course->sessions_count = count($sessions);

        $course->duration = $this->getDuration($course->duration);

        return $course;
    }

    private function getDuration($duration) {
        $hours = floor($duration / 3600);
        $minutes = floor(($duration % 3600) / 60);

        if ($hours > 0 && $minutes > 0) {
            $formatted = "{$hours}h {$minutes}m";
        } elseif ($hours > 0) {
            $formatted = "{$hours}h";
        } else {
            $formatted = "{$minutes}m";
        }

        return $formatted;
    }
}
