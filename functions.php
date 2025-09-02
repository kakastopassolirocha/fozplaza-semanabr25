<?php
define('THEMEROOT_URI', get_stylesheet_directory_uri()."/");
define("THEMEROOT_DIR", get_stylesheet_directory()."/");
define('THEMEROOT_DEV', get_stylesheet_directory_uri()."/dev/");
define('THEMEROOT_DIST', get_stylesheet_directory_uri()."/dist/");
define('THEMEROOT_TRASH', get_stylesheet_directory_uri()."/dev/trash/");

require_once(get_template_directory().'/inc/disable.php');
require_once(get_template_directory().'/inc/enable.php');
require_once(get_template_directory().'/inc/styles-and-scripts.php');

//Desativa barra admin no frontend
add_filter('show_admin_bar', '__return_false');

require_once(get_template_directory().'/plugins/ink-tracking/ink-tracking.php');
?>