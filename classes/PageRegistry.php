<?php
namespace local_rainmake_backend;
require_once(__DIR__ . '/../../../config.php');

defined('MOODLE_INTERNAL') || die();

class PageRegistry {

    private static $pages = null;
    private static $config_file = null;

    /**
     * Set custom config file path (optional)
     */
    public static function set_config_file($file_path) {
        self::$config_file = $file_path;
        self::$pages = null;
    }

    /**
     * Get all page definitions
     */
    public static function get_all_pages() {
        self::$pages = self::load_pages();
        return self::$pages;
    }

    /**
     * Load pages from config file
     */
    private static function load_pages() {
        global $CFG;

        // Default config file location
        $config_file = self::$config_file ?: $CFG->dirroot . '/local/rainmake_backend/config/pages.php';

        if (!file_exists($config_file)) {
            debugging("Page config file not found: $config_file", DEBUG_DEVELOPER);
            return [];
        }

        $pages = include($config_file);

        if (!is_array($pages)) {
            debugging("Page config file must return an array", DEBUG_DEVELOPER);
            return [];
        }

        return $pages;
    }

    /**
     * Get pages filtered by user permissions
     */
    public static function get_accessible_pages($category = null, $parent = null) {
        $all_pages = self::get_all_pages();
        $accessible = [];

        foreach ($all_pages as $key => $page) {
            // Filter by category if specified
            if ($category && $page['category'] !== $category) {
                continue;
            }

            // Filter by parent if specified
            if ($parent !== null && $page['parent'] !== $parent) {
                continue;
            }

            // Check permissions
            if (self::user_can_access($page)) {
                $accessible[$key] = $page;
            }
        }

        // Sort by order
        uasort($accessible, function($a, $b) {
            return $a['order'] - $b['order'];
        });

        return $accessible;
    }

    /**
     * Check if current user can access a page
     */
    public static function user_can_access($page) {
        if (is_siteadmin()) {
            return true;
        }

        if (empty($page['permissions'])) {
            return true; // No permissions required
        }

        $context = \context_system::instance();

        foreach ($page['permissions'] as $permission) {
            if (has_capability($permission, $context)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get page by key
     */
    public static function get_page($key) {
        $pages = self::get_all_pages();
        return isset($pages[$key]) ? $pages[$key] : null;
    }

    /**
     * Get page URL
     */
    public static function get_url($key, $params = []) {
        $page = self::get_page($key);
        if (!$page) {
            return null;
        }

        $url = new \moodle_url($page['url'], $params);
        return $url;
    }

    /**
     * Get navigation tree structure
     */
    public static function get_navigation_tree($category = null) {
        $pages = self::get_accessible_pages($category);
        $tree = [];

        // First pass: add root items
        foreach ($pages as $key => $page) {
            if ($page['parent'] === null) {
                $tree[$key] = $page;
                $tree[$key]['children'] = [];
            }
        }

        // Second pass: add children
        foreach ($pages as $key => $page) {
            if ($page['parent'] !== null && isset($tree[$page['parent']])) {
                $tree[$page['parent']]['children'][$key] = $page;
            }
        }

        return $tree;
    }

    /**
     * Reload pages from config (useful for development)
     */
    public static function reload_pages() {
        self::$pages = null;
        return self::get_all_pages();
    }
    public static function get_navigation_pages($navigation_name, $category = null, $parent = null) {
        $all_pages = self::get_all_pages();
        $nav_pages = [];

        foreach ($all_pages as $key => $page) {
            // Check if page is included in this navigation
            if (!self::page_in_navigation($page, $navigation_name)) {
                continue;
            }

            // Filter by category if specified
            if ($category && $page['category'] !== $category) {
                continue;
            }

            // Filter by parent if specified
            if ($parent !== null && $page['parent'] !== $parent) {
                continue;
            }

            // Check permissions
            if (self::user_can_access($page)) {
                $nav_pages[$key] = $page;
            }
        }

        // Sort by order
        uasort($nav_pages, function($a, $b) {
            return $a['order'] - $b['order'];
        });

        return $nav_pages;
    }
    private static function page_in_navigation($page, $navigation_name) {
        if (empty($page['navigation'])) {
            return false;
        }

        return in_array($navigation_name, $page['navigation']);
    }
    public static function get_default_page($nav=null)
    {
        $pages = self::get_all_pages();
        foreach ($pages as $page) {
            if(!array_key_exists('default', $page)) {
                continue;
            }
            if($page['default'] && self::user_can_access($page)) {
                if(!$nav){
                    return $page;
                }
                foreach ($page['navigation'] as $navitem){
                    if($navitem == $nav){
                        return $page;
                    }
                }
            }
        }
        return ['url' => '/'];
    }
    public static function setup_page($PAGE, $id)
    {
        $page = self::get_page($id);
        if(!self::user_can_access($page)) {
            $url = new \moodle_url(self::get_default_page()['url']);
            redirect($url, '', 0);
        }

        $PAGE->set_url($page['url']);
        $PAGE->set_title($page['title']);
        $PAGE->set_subpage($page['id']);
        $PAGE->set_pagelayout($page['layout']);
        return $page;
    }
}
