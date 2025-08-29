<?php
// * THEME SUPPORT
add_action( "after_setup_theme", function(){
    // Enables menu management in WordPress
    add_theme_support('menus');

    // Enable the option for a custom logo
    add_theme_support('custom-logo');

    /**
     * Let WordPress manage the document title.
     * By adding theme support, we declare that this theme does not use a
     * hard-coded <title> tag in the document head, and expect WordPress to
     * provide it for us.
     */
    add_theme_support('title-tag');
});

// Esse código garante que, durante o processo de upload, o WordPress aceite arquivos SVG e retorne a extensão e o tipo corretos do arquivo, evitando problemas na verificação de segurança.
add_filter('wp_check_filetype_and_ext', function($data, $file, $filename, $mimes) {
    $filetype = wp_check_filetype($filename, $mimes);
    return [
        'ext'             => $filetype['ext'],
        'type'            => $filetype['type'],
        'proper_filename' => $data['proper_filename']
    ];
}, 10, 4);

// Este código adiciona o tipo MIME necessário para o upload de arquivos SVG no WordPress. O WordPress usa uma lista de tipos de arquivos permitidos (mimes) para validar uploads, e o tipo image/svg+xml é adicionado à lista, permitindo o envio de arquivos SVG.
function cc_mime_types($mimes) {
    $mimes['svg'] = 'image/svg+xml';
    return $mimes;
}
add_filter('upload_mimes', 'cc_mime_types');
?>