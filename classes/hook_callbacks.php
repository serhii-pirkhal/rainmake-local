<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace local_rainmake_backend;

defined('MOODLE_INTERNAL') || die();

/**
 * Hook callbacks for rainmake_backend plugin
 *
 * @package    local_rainmake_backend
 * @copyright  2024
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_callbacks {

    /**
     * Callback to set custom redirect URL after login
     *
     * @param \core_user\hook\after_login_completed $hook
     */
    public static function after_login_completed(
        \core_user\hook\after_login_completed $hook,
    ): void {
        global $SESSION, $CFG, $USER;

        // Check if user has admin permissions (same as admin_dashboard page requires)
        $context = \context_system::instance();
        if (!has_capability('moodle/course:create', $context)) {
            // User doesn't have admin permissions, skip redirect
            return;
        }

        // Only redirect if wantsurl is not already set to a specific page
        // (to preserve user's intended destination if they were trying to access a specific page)
        if (empty($SESSION->wantsurl) || 
            $SESSION->wantsurl == $CFG->wwwroot . '/' || 
            $SESSION->wantsurl == $CFG->wwwroot . '/index.php' ||
            $SESSION->wantsurl == $CFG->wwwroot . '/my/') {
            
            // Set redirect to admin dashboard
            $dashboardurl = new \moodle_url('/theme/rainmake/admin/dashboard.php');
            $SESSION->wantsurl = $dashboardurl->out(false);
        }
    }

    /**
     * Callback to control access based on user roles
     * - Managers: only access rainmake theme pages, redirect root Moodle pages to admin dashboard
     * - Students: access rainmake theme pages except admin area
     *
     * @param \core\hook\output\before_http_headers $hook
     */
    public static function before_http_headers(
        \core\hook\output\before_http_headers $hook,
    ): void {
        global $PAGE, $CFG, $DB, $USER;

        // Skip for AJAX requests, CLI scripts, and web services
        if (AJAX_SCRIPT || CLI_SCRIPT || WS_SERVER) {
            return;
        }

        // Get current URL path from REQUEST_URI or PAGE
        if (isset($_SERVER['REQUEST_URI'])) {
            $currentpath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            // Remove query string if present
            if (strpos($currentpath, '?') !== false) {
                $currentpath = substr($currentpath, 0, strpos($currentpath, '?'));
            }
        } else {
            try {
                $currentpath = $PAGE->url->out(false);
            } catch (\Exception $e) {
                // If PAGE is not initialized, try to get from script name
                $currentpath = $_SERVER['SCRIPT_NAME'] ?? '/';
            }
        }
        
        // Normalize path (remove wwwroot if present)
        $wwwrootpath = parse_url($CFG->wwwroot, PHP_URL_PATH);
        if ($wwwrootpath && strpos($currentpath, $wwwrootpath) === 0) {
            $currentpath = substr($currentpath, strlen($wwwrootpath));
        }
        if (empty($currentpath) || $currentpath === '/') {
            $currentpath = '/index.php';
        }

        // Redirect non-logged-in users to login page for index.php
        // This replaces the code that was in index.php to prevent loss after Moodle updates
        if (($currentpath === '/index.php' || $currentpath === '/') && (!isloggedin() || isguestuser())) {
            redirect(new \moodle_url('/login/index.php'));
        }

        // Skip if user is not logged in or is guest (for other pages)
        if (!isloggedin() || isguestuser()) {
            return;
        }
        
        // Skip for static files (images, CSS, JS, etc.)
        $staticfileextensions = ['.css', '.js', '.png', '.jpg', '.jpeg', '.gif', '.svg', '.ico', '.woff', '.woff2', '.ttf', '.eot'];
        foreach ($staticfileextensions as $ext) {
            if (strpos($currentpath, $ext) !== false) {
                return;
            }
        }
        
        // Check if current page is from rainmake theme
        $israinmakepage = strpos($currentpath, '/theme/rainmake/') !== false;
        $israinmakeadmin = strpos($currentpath, '/theme/rainmake/admin/') !== false;

        // Get user roles in system context
        $systemcontext = \context_system::instance();
        $userroles = get_user_roles($systemcontext, $USER->id);
        
        // Check if user has manager role
        $managerroleid = $DB->get_field('role', 'id', ['shortname' => 'manager'], IGNORE_MISSING);
        $hasmanagerrole = false;
        if ($managerroleid) {
            foreach ($userroles as $role) {
                if ($role->roleid == $managerroleid) {
                    $hasmanagerrole = true;
                    break;
                }
            }
        }

        // Check if user has student role
        $studentroleid = $DB->get_field('role', 'id', ['shortname' => 'student'], IGNORE_MISSING);
        $hasstudentrole = false;
        if ($studentroleid) {
            foreach ($userroles as $role) {
                if ($role->roleid == $studentroleid) {
                    $hasstudentrole = true;
                    break;
                }
            }
        }

        // Only apply restrictions if user has manager or student role
        if (!$hasmanagerrole && !$hasstudentrole) {
            return;
        }

        // For managers: block access to /admin/ and /my/, only allow /theme/rainmake/
        if ($hasmanagerrole) {
            // Block access to /admin/ pages (but allow if it's part of theme/rainmake path)
            if (strpos($currentpath, '/admin/') !== false && !$israinmakepage) {
                redirect(new \moodle_url('/theme/rainmake/admin/dashboard.php'));
            }
            
            // Block access to /my/ pages
            if (strpos($currentpath, '/my/') !== false) {
                redirect(new \moodle_url('/theme/rainmake/admin/dashboard.php'));
            }
            
            // If not a rainmake theme page, redirect to admin dashboard
            if (!$israinmakepage) {
                // Allow only login, logout, lib, ajax, local rainmake backend, and course pages
                $allowedpaths = [
                    '/login/', 
                    '/logout/', 
                    '/lib/', 
                    '/ajax/',
                    '/local/rainmake_backend/',
                    '/course/',
                    '/pluginfile.php',
                    '/webservice/',
                    '/theme/rainmake/'
                ];
                $isallowed = false;
                foreach ($allowedpaths as $allowedpath) {
                    if (strpos($currentpath, $allowedpath) !== false) {
                        $isallowed = true;
                        break;
                    }
                }
                
                // Also allow specific root files that might be needed (but redirect index.php)
                $allowedfiles = ['/config.php', '/install.php', '/pluginfile.php'];
                foreach ($allowedfiles as $allowedfile) {
                    if (strpos($currentpath, $allowedfile) !== false) {
                        $isallowed = true;
                        break;
                    }
                }
                
                // For index.php or root, redirect to admin dashboard
                if ($currentpath === '/index.php' || $currentpath === '/') {
                    redirect(new \moodle_url('/theme/rainmake/admin/dashboard.php'));
                }
                
                if (!$isallowed) {
                    redirect(new \moodle_url('/theme/rainmake/admin/dashboard.php'));
                }
            }
        }

        // For students: only allow rainmake theme pages (except admin area)
        // Block access to root Moodle pages, /admin/, /my/, and rainmake admin area
        if ($hasstudentrole && !$hasmanagerrole) {
            // Block access to /admin/ pages
            if (strpos($currentpath, '/admin/') !== false) {
                if (file_exists($CFG->dirroot . '/theme/rainmake/dashboard.php')) {
                    redirect(new \moodle_url('/theme/rainmake/dashboard.php'));
                } else if (file_exists($CFG->dirroot . '/theme/rainmake/courses.php')) {
                    redirect(new \moodle_url('/theme/rainmake/courses.php'));
                }
            }
            
            // Block access to /my/ pages
            if (strpos($currentpath, '/my/') !== false) {
                if (file_exists($CFG->dirroot . '/theme/rainmake/dashboard.php')) {
                    redirect(new \moodle_url('/theme/rainmake/dashboard.php'));
                } else if (file_exists($CFG->dirroot . '/theme/rainmake/courses.php')) {
                    redirect(new \moodle_url('/theme/rainmake/courses.php'));
                }
            }
            
            // Block access to rainmake admin area
            if ($israinmakeadmin) {
                // Redirect to a safe page (dashboard or courses page)
                if (file_exists($CFG->dirroot . '/theme/rainmake/dashboard.php')) {
                    redirect(new \moodle_url('/theme/rainmake/dashboard.php'));
                } else if (file_exists($CFG->dirroot . '/theme/rainmake/courses.php')) {
                    redirect(new \moodle_url('/theme/rainmake/courses.php'));
                }
            }
            
            // Block access to root Moodle pages (redirect to rainmake pages)
            if (!$israinmakepage) {
                // Allow login, logout, lib, ajax, local rainmake backend, course pages, and pluginfile
                $allowedpaths = [
                    '/login/', 
                    '/logout/', 
                    '/lib/', 
                    '/ajax/',
                    '/local/rainmake_backend/',
                    '/course/',
                    '/pluginfile.php',
                    '/webservice/',
                    '/theme/rainmake/'
                ];
                $isallowed = false;
                foreach ($allowedpaths as $allowedpath) {
                    if (strpos($currentpath, $allowedpath) !== false) {
                        $isallowed = true;
                        break;
                    }
                }
                
                if (!$isallowed) {
                    // Redirect to rainmake dashboard or courses
                    if (file_exists($CFG->dirroot . '/theme/rainmake/dashboard.php')) {
                        redirect(new \moodle_url('/theme/rainmake/dashboard.php'));
                    } else if (file_exists($CFG->dirroot . '/theme/rainmake/courses.php')) {
                        redirect(new \moodle_url('/theme/rainmake/courses.php'));
                    }
                }
            }
        }
    }
}

