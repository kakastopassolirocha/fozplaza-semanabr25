<?php
/**
 ** DESATIVA TODOS OS EMAILS PADRÃO DO WORDPRESS
 */
// Desativa o envio do e-mail de notificação quando a senha do usuário é alterada
add_filter( 'send_password_change_email', '__return_false' );
// Desativa o envio de e-mail de notificação quando o e-mail do usuário é alterado
add_filter( 'send_email_change_email', '__return_false' );
// Desativa o envio de e-mail de notificação quando um novo usuário é registrado
add_filter( 'wp_new_user_notification', '__return_false' );

/**
 * Remove oEmbeds
 * Tira a incorporação automática de vídeos e conteúdos externos ao colar uma URL no editor
 */
remove_action('wp_head', 'wp_oembed_add_discovery_links', 10);
remove_action('wp_head', 'wp_oembed_add_host_js');

// Remove filtros SVG duotone pra gutenberg, isso reduz html no front-end
remove_action('wp_body_open', 'wp_global_styles_render_svg_filters');

// Remove o link do RSD que é usado para ferramentas de edição remota, como o Windows Live Writer. Se você não precisa dessas ferramentas externas, pode removê-lo com segurança.
remove_action('wp_head', 'rsd_link');

// Remove o link relacional "start" do head, usado para navegação entre posts relacionados. É uma otimização de SEO, mas pode ser removido se você não utiliza navegação relacional entre posts.
remove_action('wp_head', 'start_post_rel_link', 10, 0);

// Remove links de feeds RSS adicionais (como para categorias ou posts específicos). Se você não usa feeds RSS ou prefere manter apenas o feed global do site, isso pode ser removido.
remove_action('wp_head', 'feed_links_extra', 3);
// Remove o RSS completamente
// ! ISSO IMPACTA EM SEO
remove_action('wp_head', 'feed_links', 2);

// Remove os shortlinks gerados pelo WordPress (ex: ?p=123). Eles são usados para fornecer URLs curtos de posts, mas se você não precisa desses links, isso pode ser removido
remove_action('wp_head', 'wp_shortlink_wp_head', 10, 0);
remove_action('template_redirect', 'wp_shortlink_header', 11);

// Remove os links que expõem a REST API no head e nos headers HTTP. Se você não está usando a API para integrar o WordPress com aplicativos externos, essa remoção melhora a segurança.
remove_action('wp_head', 'rest_output_link_wp_head', 10);
remove_action('template_redirect', 'rest_output_link_header', 11);

// Remove a versão do WP do <head>
remove_action('wp_head', 'wp_generator');

// ! ISSO IMPACTA EM SEO
// Remove os links de navegação para o próximo e anterior post no head. Isso geralmente é usado para SEO e navegadores prefetching, mas pode ser removido se você não precisar.
remove_action('wp_head', 'adjacent_posts_rel_link_wp_head', 10, 0);

// Remove o wlwmanifest.xml, um arquivo usado pelo Windows Live Writer. Se você não utiliza essa ferramenta (que é obsoleta), isso pode ser removido.
remove_action('wp_head', 'wlwmanifest_link');

// Remove o suporte a emojis embutido do WordPress. Isso reduz o carregamento de scripts e estilos desnecessários se você não estiver utilizando emojis em seu site.
remove_action('wp_head', 'print_emoji_detection_script', 7);
remove_action('admin_print_scripts', 'print_emoji_detection_script');
remove_action('wp_print_styles', 'print_emoji_styles');
remove_action('admin_print_styles', 'print_emoji_styles');
remove_filter('the_content_feed', 'wp_staticize_emoji');
remove_filter('comment_text_rss', 'wp_staticize_emoji');
remove_filter('wp_mail', 'wp_staticize_emoji_for_email');

// Remove o CSS gerado pelo editor de blocos (Gutenberg), como as variáveis de cores e estilos globais
remove_action('wp_enqueue_scripts', 'wp_enqueue_global_styles');
remove_action('wp_footer', 'wp_enqueue_global_styles', 1 );

// Desativa o XML-RPC, uma interface que permite interagir com o WordPress remotamente. Isso é importante para melhorar a segurança, já que o XML-RPC é uma porta de entrada comum para ataques de força bruta.
add_filter('xmlrpc_enabled', '__return_false');
add_filter('xmlrpc_methods', '__return_false');

// Remove os ícones gerados automaticamente pelo WordPress, como o favicon e ícones de aplicativos. Se você está gerenciando seus próprios ícones manualmente, isso pode ser removido.
remove_action('wp_head', 'wp_site_icon', 99);

// Remove os estilos da biblioteca de blocos e do tema clássico. Isso reduz o tamanho dos arquivos CSS carregados, útil se você não está utilizando o editor de blocos do WordPress.
function remove_block_library_css() {
  wp_dequeue_style('wp-block-library');
}
add_action('wp_enqueue_scripts', 'remove_block_library_css', 100);
// Remove classic theme styles
function remove_classic_theme_styles() {
  wp_dequeue_style('classic-theme-styles');
}
add_action('wp_enqueue_scripts', 'remove_classic_theme_styles', 100);

// Desativa os endpoints da API REST que expõem informações de usuários, o que ajuda a proteger seu site contra tentativas de coleta de informações de usuários.
add_filter('rest_endpoints', function($endpoints) {
  if (isset($endpoints['/wp/v2/users'])) {
    unset($endpoints['/wp/v2/users']);
  }
  if (isset($endpoints['/wp/v2/users/(?P<id>[\d]+)'])) {
    unset($endpoints['/wp/v2/users/(?P<id>[\d]+)']);
  }
  return $endpoints;
});

// Desativar o Dashicons para usuário não administradores
add_action('wp_enqueue_scripts', function() {
    if (!is_admin() && !is_super_admin()) {
        wp_deregister_style('dashicons');
        wp_deregister_style('admin-bar');
        wp_deregister_script( "admin-bar" );
    }
});

// O Heartbeat API é usado para comunicação em tempo real com o servidor, o que pode impactar a performance. Desativa no front-end
add_action('init', function() {
    if(!is_admin())
    {
        wp_deregister_script('heartbeat');
    }
}, 1);


// Se você não precisa do autosave no front-end, pode desativá-lo para reduzir requisições AJAX:
add_action('wp_print_scripts', function() {
    wp_deregister_script('autosave');
});

// Se você não usa comentários ou pingbacks, pode desativá-los completamente no front-end para reduzir requisições e scripts relacionados:
add_filter('comments_open', '__return_false', 20, 2);
add_filter('pings_open', '__return_false', 20, 2);

// Se você não precisa do suporte a embeds no front-end, pode desativá-lo completamente:
add_action('wp_footer', function() {
    wp_deregister_script('wp-embed');
});
?>