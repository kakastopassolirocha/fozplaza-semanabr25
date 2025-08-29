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

require_once(get_template_directory().'/controller/cadastro.php');

// Faz uma lógica para verificar se o usuário está logado e é o usuário super admin
if (is_user_logged_in() && is_super_admin() && !is_admin() && isset($_GET['listar-usuarios'])) {
    global $wpdb;
    $users = $wpdb->get_results("
        SELECT u.ID, u.user_login, um1.meta_value as first_name, um2.meta_value as ddd, um3.meta_value as phone
        FROM $wpdb->users u
        LEFT JOIN $wpdb->usermeta um1 ON u.ID = um1.user_id AND um1.meta_key = 'first_name'
        LEFT JOIN $wpdb->usermeta um2 ON u.ID = um2.user_id AND um2.meta_key = '_ddd'
        LEFT JOIN $wpdb->usermeta um3 ON u.ID = um3.user_id AND um3.meta_key = '_phone'
        ORDER BY u.user_registered DESC
    ");

    if ($users) {
        foreach ($users as $user) {
            $nome = ucwords(strtolower($user->first_name));
            echo "{ \"nome\": \"{$nome}\", \"ddd\": \"{$user->ddd}\", \"phone\": \"{$user->phone}\" }";
            //Se não for o último usuário, adiciona uma vírgula
            if ($user != end($users)) {
                echo ',<br>';
            }
        }
    } else {
        echo 'Nenhum usuário encontrado.';
    }
    exit;
}
?>