<?php
add_action( 'wp_enqueue_scripts', function(){
    wp_enqueue_style('fozplaza', THEMEROOT_DIST .'css/fozplaza-bf-css.min.css', [], '1.0.0');

    // Trata e comprime SVG em uma tag de <img>
    wp_enqueue_script( 'svg-inject', THEMEROOT_DIST . 'js/libs/svg-inject.min.js', [], '1.2.3', false);
    
    // Classe scrollAnimations
    wp_enqueue_script( 'scroll-animations', THEMEROOT_DIST . 'js/libs/scrollAnimations.min.js', [], '1.0.0', false);
        
    // Main.js
    wp_enqueue_script( 'fozplaza', THEMEROOT_DIST . 'js/app/fozplaza-bf-js.min.js', ['jquery'], '1.0.0', true);
    wp_localize_script('fozplaza', 'backvars', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'dist' => THEMEROOT_DIST,
        'user_ip' => $_SERVER['REMOTE_ADDR'],
        'user_agent' => $_SERVER['HTTP_USER_AGENT']
    ]);

    
    echo '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
    integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA=="
    crossorigin="anonymous" referrerpolicy="no-referrer" />';
    
    // Registra o jBox CSS
    wp_enqueue_style('jbox-css', THEMEROOT_DIST . 'css/libs/jBox.all.min.css');
    
    // Registra o jBox JS com dependência do jQuery
    wp_enqueue_script('jbox-js', THEMEROOT_DIST . 'js/libs/jBox.all.min.js', ['jquery'], '1.3.3');
    
    //Mascará campos de input
    wp_enqueue_script( 'jquery-mask', THEMEROOT_DIST . 'js/libs/jquery.mask.min.js', array('jquery'), false, true);
    // wp_enqueue_script( 'jquery-mask', 'https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js', array('jquery'), false, true);

    // Bodymovin
    wp_enqueue_script('bodymovin', THEMEROOT_DIST . 'js/libs/lottie_light.5.12.2.min.js', [], '5.12.2');
    
    // Enfileirar o CSS do Flatpickr
    wp_enqueue_style('flatpickr-css', 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css');
    
    // Enfileirar o JS do Flatpickr
    wp_enqueue_script('flatpickr-js', 'https://cdn.jsdelivr.net/npm/flatpickr', [], null, true);
    wp_enqueue_script('flatpickr-pt-js', 'https://npmcdn.com/flatpickr/dist/l10n/pt.js', ['flatpickr-js'], null, true);

});