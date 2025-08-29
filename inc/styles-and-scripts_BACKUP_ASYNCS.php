<?php
add_action( 'wp_enqueue_scripts', function(){
    wp_enqueue_style('fozplaza', THEMEROOT_DIST .'css/fozplaza-bf-css.min.css', [], '1.0.0');

    // Trata e comprime SVG em uma tag de <img>
    wp_enqueue_script( 'svg-inject', THEMEROOT_DIST . 'js/libs/svg-inject.min.js', [], '1.2.3', false);
    
    // Classe scrollAnimations
    wp_enqueue_script( 'scroll-animations', THEMEROOT_DIST . 'js/libs/scrollAnimations.min.js', [], '1.0.0', false);
        
    //Registrar e carregar o jquery
    if (!wp_script_is('jquery', 'enqueued')) {
        wp_enqueue_script('jquery');
    }

    // Main.js
    wp_enqueue_script( 'fozplaza', THEMEROOT_DIST . 'js/app/fozplaza-bf-js.min.js', ['jquery'], '1.0.0', true);

    
    echo '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
    integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA=="
    crossorigin="anonymous" referrerpolicy="no-referrer" />';
    
    // Registra o jBox CSS
    wp_enqueue_style('jbox-css', THEMEROOT_DIST . 'css/libs/jBox.all.min.css');
    
    // Registra o jBox JS com dependência do jQuery
    // wp_register_script('jbox-js', 'https://cdn.jsdelivr.net/gh/StephanWagner/jBox@v1.3.3/dist/jBox.all.min.js', ['jquery-core'], '1.3.3', ['strategy' => 'async']);
    wp_enqueue_script('jbox-js', THEMEROOT_DIST . 'js/libs/jBox.all.min.js', ['jquery'], '1.3.3');
    // wp_script_add_data('jbox-js', 'async', true);
    // wp_enqueue_script( 'jbox-js');

    //Mascará campos de input
    // wp_enqueue_script( 'jquery-mask', THEMEROOT_DIST . 'js/libs/jquery.mask.min.js', ['jquery'], "1.14.16");
    
    // Swiper
    // wp_enqueue_style('swiper', THEMEROOT_DIST . 'css/libs/swiper-bundle.min.css', [], "11.1.14");
    // wp_enqueue_script('swiper', THEMEROOT_DIST . 'js/libs/swiper-bundle.min.js', [], "11.1.14", false);

    // Desregistra o jQuery padrão do WordPress
    // wp_deregister_script('jquery');
    
    // Registra o jQuery de forma assíncrona
    // wp_register_script('jquery', 'https://code.jquery.com/jquery-3.7.1.min.js', [], '3.7.1', true);
    // wp_script_add_data('jquery', 'async', true);

    
    // Adiciona script inline para garantir que o jBox só seja inicializado após o jQuery carregar
    // wp_add_inline_script('jbox-js', '
    //     if (typeof jQuery === "undefined") {
    //         document.addEventListener("DOMContentLoaded", function() {
    //             var checkJquery = setInterval(function() {
    //                 if (typeof jQuery !== "undefined") {
    //                     clearInterval(checkJquery);
    //                     // Aqui você pode inicializar qualquer código que dependa do jBox
    //                 }
    //             }, 100);
    //         });
    //     }
    // ');
});

// function load_jquery_async($tag, $handle) {
//     if ( (strpos($handle, 'jquery') !== false) || strpos($handle, 'jbox') !== false ) {
//         return str_replace(' src', ' defer src', $tag);
//     }
//     return $tag;
// }
// add_filter('script_loader_tag', 'load_jquery_async', 10, 2);
?>