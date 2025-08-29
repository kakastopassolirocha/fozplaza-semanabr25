<!DOCTYPE html>
<html <?php language_attributes();?>>

<head>
    <meta charset="<?php bloginfo( 'charset' );?>">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0" />

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Amiko:wght@400;600;700&display=swap" rel="stylesheet">

    <!-- Favicon padrão para navegadores -->
    <link rel="icon" href="<?=THEMEROOT_DIST?>favicon/favicon.ico" type="image/x-icon">

    <!-- Favicon para navegadores modernos (formato SVG) -->
    <link rel="icon" href="<?=THEMEROOT_DIST?>favicon/favicon.svg" type="image/svg+xml">

    <!-- Apple Touch Icon para iOS -->
    <link rel="apple-touch-icon" href="<?=THEMEROOT_DIST?>favicon/apple-touch-icon.png">

    <!-- Favicon em tamanhos específicos para dispositivos Android e outros -->
    <link rel="icon" href="<?=THEMEROOT_DIST?>favicon/favicon-96x96.png" sizes="96x96" type="image/png">

    <!-- Ícones de aplicativo da web (Progressive Web App - PWA) -->
    <link rel="icon" href="<?=THEMEROOT_DIST?>favicon/web-app-manifest-192x192.png" sizes="192x192" type="image/png">
    <link rel="icon" href="<?=THEMEROOT_DIST?>favicon/web-app-manifest-512x512.png" sizes="512x512" type="image/png">

    <!-- Manifesto para aplicativos da web (PWA) -->
    <link rel="manifest" href="<?=THEMEROOT_URI?>site.webmanifest">

    <!-- Google Tag Manager -->
    <script>
    (function(w, d, s, l, i) {
        w[l] = w[l] || [];
        w[l].push({
            'gtm.start': new Date().getTime(),
            event: 'gtm.js'
        });
        var f = d.getElementsByTagName(s)[0],
            j = d.createElement(s),
            dl = l != 'dataLayer' ? '&l=' + l : '';
        j.async = true;
        j.src =
            'https://www.googletagmanager.com/gtm.js?id=' + i + dl;
        f.parentNode.insertBefore(j, f);
    })(window, document, 'script', 'dataLayer', 'GTM-MZJ3M5X9');
    </script>
    <!-- End Google Tag Manager -->

    <?php wp_head(); ?>
</head>

<body <?php body_class();?>>
    <!-- Google Tag Manager -->
    <script>
    (function(w, d, s, l, i) {
        w[l] = w[l] || [];
        w[l].push({
            'gtm.start': new Date().getTime(),
            event: 'gtm.js'
        });
        var f = d.getElementsByTagName(s)[0],
            j = d.createElement(s),
            dl = l != 'dataLayer' ? '&l=' + l : '';
        j.async = true;
        j.src =
            'https://www.googletagmanager.com/gtm.js?id=' + i + dl;
        f.parentNode.insertBefore(j, f);
    })(window, document, 'script', 'dataLayer', 'GTM-MZJ3M5X9');
    </script>
    <!-- End Google Tag Manager -->

    <a href="https://wa.me/5545999400770?text=Oiii,%20vim%20da%20promoção%20da%20Black%20Friday%20do%20Foz%20Plaza,%20quero%20mais%20informações"
        target="_blank" id="whatsapp-fixed">
    </a>

    <?php require_once(get_template_directory().'/tema/head.php'); ?>