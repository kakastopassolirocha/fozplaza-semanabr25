<?php
/**
 * Plugin Name: INKTRACK - Advanced Meta Tracking
 * Description: A robust tracking library for Meta Pixel and Conversions API with cross-domain and user enrichment capabilities.
 * Version: 2.0.0
 * Author: Your Name
 */

// Block direct access.
if (!defined('ABSPATH')) {
    exit;
}

// Include the main tracking class.
require_once __DIR__ . '/src/InkTrack.php';

// Instantiate the class to fire up the library.
new \INKTRACK\src\InkTrack();

/**
 * Retorna o ID externo do INKTRACK de forma segura.
 * Lê o valor do cookie que a biblioteca já criou.
 *
 * @return string O ID externo do visitante ou uma string vazia.
 */
function INKTRACK_get_external_id() {
    $nome_cookie = 'INKTRACK_external_id';
    if (isset($_COOKIE[$nome_cookie])) {
        // Usa a função sanitize_text_field para segurança.
        return sanitize_text_field($_COOKIE[$nome_cookie]);
    }
    return '';
}