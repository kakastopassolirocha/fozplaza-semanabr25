<?php

namespace INKTRACK\src;

// Bloqueia o acesso direto.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Classe principal para a funcionalidade INKTRACK.
 */
class InkTrack {

    /**
     * Array de configuração.
     *
     * @var array
     */
    private $configuracao;

    /**
     * O ID de evento único para a visualização de página atual.
     *
     * @var string
     */
    private $id_evento_pageview;

    /**
     * Cache para os dados de geolocalização do visitante para evitar chamadas múltiplas.
     *
     * @var array|null
     */
    private $dados_geograficos_visitante = null;

    /**
     * Construtor.
     */
    public function __construct() {
        $this->carregar_configuracao();
        $this->configurar_hooks();
    }

    /**
     * Carrega as configurações.
     */
    private function carregar_configuracao() {
        $this->configuracao = [
            'debug' => false, // Pode tornar condicional, ex: com base em WP_DEBUG
            'pixel_id' => '1429042787694485', // Pixel_FozPlaza2024
            'api_token' => 'EAARnl9wWOUwBO7tsrKPC3KnlVeYSwL1JVsAilRv5NteNy8v6HuVqhxu6BEj47kT7jbImiXAkdOUsEyJyT8O0M3P0AL7LPxDZA7sPwBSp9GWAKQLZCquh8n5QJnJsV45U4njvcYymEJN0zwmjUgEkXlCicc11TSWcrDZCHEe1pZCL5SuE4E4XuqTI5ScKZCxnFigZDZD',
            'ipinfo_token' => '00d54338b9f225', // Token para a API de geolocalização por IP
            'meta_api_versao' => 'v21.0',
            'codigo_evento_teste' => $this->dominio_contem(['.local', '.inkweb']) ? 'TEST41250' : '',
            'eventos_padrao' => [
                'PageView', 'pageview', 'ViewPage', 'Lead', 'InitiateCheckout', 'AddToCart',
                'AddPaymentInfo', 'Purchase', 'ViewContent', 'Search', 'ViewSearchResults',
                'AddToWishlist', 'CompletePayment', 'Contact'
            ],
            'nome_cookie' => 'INKTRACK_external_id',
        ];
    }

    /**
     * Configura os hooks do WordPress.
     */
    private function configurar_hooks() {
        add_action('wp_enqueue_scripts', [$this, 'enfileirar_scripts']);
        add_action('rest_api_init', [$this, 'registrar_rotas_api']);
        add_action('wp_head', [$this, 'disparar_rastreamento_pageview'], 5); // Prioridade alta
        add_action('init', [$this, 'executar_rotina_instalacao']);
    }

    /**
     * Gerenciador principal para o rastreamento de page view, chamado no wp_head.
     */
    public function disparar_rastreamento_pageview() {
        $this->registrar_log('Iniciando verificação de rastreamento de PageView...');

        if (is_admin() || wp_doing_ajax() || wp_is_json_request()){
             $this->registrar_log('Rastreamento ignorado: É uma requisição de admin, AJAX ou JSON.');
            return;
        }

        if($this->eh_trafego_indesejado()){
            $this->registrar_log('Rastreamento ignorado: User Agent identificado como bot.', $_SERVER['HTTP_USER_AGENT'] ?? 'N/A');
            return;
        }

        if(!$this->eh_usuario_da_america_do_sul()){
            $this->registrar_log('Rastreamento ignorado: Usuário fora da América do Sul.');
            return;
        }

        $this->registrar_log('Verificações passaram. Prosseguindo com o rastreamento de PageView.');

        // 1. Gera um ID de evento único para este carregamento de página.
        $this->id_evento_pageview = 'srv.' . time() . '.' . wp_generate_uuid4();

        // 2. Injeta o script base do pixel para o rastreamento client-side (RECOMENDADO PRIMEIRO).
        $this->adicionar_script_base_meta_pixel();
        
        // 3. Dispara o evento server-side em seguida.
        $this->disparar_pageview_servidor();
    }

    /**
     * Injeta o código base do Pixel da Meta no head da página.
     */
    private function adicionar_script_base_meta_pixel() {
        $pixel_id = $this->configuracao['pixel_id'];
        if (empty($pixel_id)) {
            return;
        }

        $id_externo = $this->obter_ou_definir_id_externo();
        $dados_usuario_para_init = $this->obter_dados_usuario_para_init($id_externo);

        ?>
        <!-- Código do Pixel da Meta (INKTRACK) -->
        <script>
            !function(f,b,e,v,n,t,s)
            {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
            n.callMethod.apply(n,arguments):n.queue.push(arguments)};
            if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
            n.queue=[];t=b.createElement(e);t.async=!0;
            t.src=v;s=b.getElementsByTagName(e)[0];
            s.parentNode.insertBefore(t,s)}(window, document,'script',
            'https://connect.facebook.net/en_US/fbevents.js');
            fbq('init', '<?php echo esc_js($pixel_id); ?>', <?php echo wp_json_encode($dados_usuario_para_init); ?>);
            fbq('track', 'PageView', {eventID: '<?php echo esc_js($this->id_evento_pageview); ?>'});
        </script>
        <noscript>
            <img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id=<?php echo esc_attr($pixel_id); ?>&ev=PageView&noscript=1&eid=<?php echo esc_attr($this->id_evento_pageview); ?>" />
        </noscript>
        <!-- Fim do Código do Pixel da Meta -->
        <?php
    }

    /**
     * Dispara o evento de PageView do lado do servidor.
     */
    private function disparar_pageview_servidor() {
        $id_externo = $this->obter_ou_definir_id_externo();

        // 1. Coleta os dados contextuais mais recentes da requisição atual.
        $dados_contextuais = [
            'ip' => $this->obter_ip_cliente(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'fbc' => $_COOKIE['_fbc'] ?? null,
            'fbp' => $_COOKIE['_fbp'] ?? null,
        ];
        $dados_geo = $this->obter_dados_geograficos();
        if (!empty($dados_geo['country_code'])) {
            $dados_contextuais['country'] = strtolower($dados_geo['country_code']);
        }
        
        // 2. Busca os dados de PII que já existem no banco.
        $dados_existentes_db = $this->obter_dados_completos_usuario($id_externo);
        
        // 3. Mescla os dados, garantindo que os contextuais (mais recentes) sobrescrevam os antigos se houver conflito.
        $dados_completos_para_salvar = array_merge($dados_existentes_db, $dados_contextuais);
        $dados_completos_para_salvar['external_id'] = $id_externo;

        // 4. Salva o perfil completo e atualizado no banco, preservando os dados de PII.
        $this->salvar_dados_visitante($dados_completos_para_salvar);

        // --- Preparação para o evento da Meta ---
        
        // Usa o mesmo perfil completo para o evento.
        $dados_para_evento = array_filter($dados_completos_para_salvar);

        // --- Dados do Evento ---
        $dados_evento = [
            'event_name' => 'PageView',
            'event_time' => time(),
            'action_source' => 'website',
            'event_source_url' => $this->obter_url_atual(),
            'event_id' => $this->id_evento_pageview,
            'user_data' => $this->preparar_dados_para_meta($dados_para_evento), // Normaliza e Hasheia aqui!
        ];

        $this->registrar_log('Payload do evento de servidor (PageView) preparado.', $dados_evento);
        $this->enviar_evento_servidor($dados_evento);
    }


    /**
     * Enfileira scripts e estilos.
     */
    public function enfileirar_scripts() {
        
        $url_script = get_stylesheet_directory_uri() . '/plugins/ink-tracking/assets/js/inktrack.js';

        $versao = file_exists($url_script) ? filemtime($url_script) : '1.0.0';

        wp_register_script(
            'inktrack-js',
            $url_script,
            [], // dependências
            $versao,
            true // no rodapé
        );

        // Localiza o script para passar dados do PHP para o JS
        wp_localize_script('inktrack-js', 'INKTRACK_CONFIG', [
            'pixelId' => $this->configuracao['pixel_id'],
            'endpointApi' => home_url('/wp-json/inktrack/v1/evento'),
            'eventosPadrao' => $this->configuracao['eventos_padrao'],
            'debug' => $this->configuracao['debug'],
        ]);

        wp_enqueue_script('inktrack-js');
    }

    /**
     * Registra as rotas da API.
     */
    public function registrar_rotas_api() {
        register_rest_route('inktrack/v1', '/evento', [
            'methods' => 'POST',
            'callback' => [$this, 'manipular_requisicao_evento'],
            'permission_callback' => '__return_true', // Acessível publicamente
        ]);

        register_rest_route('inktrack/v1', '/usuario/enriquecer', [
            'methods' => 'POST',
            'callback' => [$this, 'manipular_requisicao_enriquecimento_usuario'],
            'permission_callback' => '__return_true', // Acessível publicamente
        ]);

        register_rest_route('inktrack/v1', '/usuario/sincronizar', [
            'methods' => 'POST',
            'callback' => [$this, 'manipular_requisicao_sincronizacao'],
            'permission_callback' => '__return_true', // Acessível publicamente
        ]);

        register_rest_route('inktrack/v1', '/status/check-eligibility', [
            'methods' => 'GET',
            'callback' => [$this, 'manipular_verificacao_elegibilidade'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('inktrack/v1', '/usuario/lookup-by-ip', [
            'methods' => 'GET',
            'callback' => [$this, 'manipular_lookup_por_ip'],
            'permission_callback' => '__return_true',
        ]);
    }

    /**
     * Gerencia o endpoint da API /evento.
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function manipular_requisicao_evento(\WP_REST_Request $request) {
        if($this->eh_trafego_indesejado()){
            $this->registrar_log('Requisição de evento ignorada: User Agent identificado como bot.', $_SERVER['HTTP_USER_AGENT'] ?? 'N/A');
            return new \WP_REST_Response(['success' => true, 'message' => 'Evento ignorado (bot).'], 200); // Retorna sucesso para não indicar um problema
        }

        $payload = json_decode($request->get_body(), true);

        if (json_last_error() !== JSON_ERROR_NONE || !isset($payload['event_name']) || !isset($payload['event_id'])) {
            return new \WP_REST_Response(['success' => false, 'message' => 'JSON inválido ou campos obrigatórios ausentes.'], 400);
        }

        // --- Dados do Usuário ---
        $dados_usuario = [
            'ip' => $this->obter_ip_cliente(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'fbp' => $_COOKIE['_fbp'] ?? null,
            'fbc' => $_COOKIE['_fbc'] ?? null,
        ];

        // Tenta obter o external_id do payload para eventos via API
        if (!empty($payload['external_id'])) {
            $id_externo = sanitize_text_field($payload['external_id']);
            
            // 1. Obtém todos os dados que já temos no banco.
            $dados_db = $this->obter_dados_completos_usuario($id_externo);

            // 2. Coleta os dados de contexto mais recentes da requisição atual.
            //    Os valores de fbc e fbp vêm do payload do JS, que são os mais atuais.
            $dados_contextuais = [
                'ip' => $this->obter_ip_cliente(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'fbc' => $payload['fbc'] ?? null,
                'fbp' => $payload['fbp'] ?? null,
            ];
            $dados_geo = $this->obter_dados_geograficos();
            if (!empty($dados_geo['country_code'])) {
                $dados_contextuais['country'] = strtolower($dados_geo['country_code']);
            }

            // 3. Mescla, dando prioridade aos dados contextuais (mais recentes).
            $dados_usuario_completos = array_merge($dados_db, $dados_contextuais);
            $dados_usuario_completos['external_id'] = $id_externo;

            // 4. Salva o perfil completo e atualizado, garantindo que o usuário exista e esteja atualizado.
            // Esta era a etapa que faltava e que causava a perda de dados.
            $this->salvar_dados_visitante($dados_usuario_completos);

        } else {
            $dados_usuario_completos = $dados_usuario;
        }

        // Adiciona dados geográficos, se disponíveis (fallback se não houver external_id)
        if (empty($dados_usuario_completos['country'])) {
            $dados_geo = $this->obter_dados_geograficos();
            if (!empty($dados_geo['country_code'])) {
                $dados_usuario_completos['country'] = strtolower($dados_geo['country_code']);
            }
        }

        // Remove valores nulos, normaliza e hasheia os dados PII
        $dados_usuario_finais = $this->preparar_dados_para_meta(array_filter($dados_usuario_completos));

        // --- Dados do Evento ---
        $dados_evento = [
            'event_name' => sanitize_text_field($payload['event_name']),
            'event_time' => time(),
            'action_source' => 'website',
            'event_source_url' => esc_url_raw($payload['event_source_url']),
            'event_id' => sanitize_text_field($payload['event_id']),
            'user_data' => $dados_usuario_finais,
            'custom_data' => $payload['params'] ?? [],
        ];

        $sucesso = $this->enviar_evento_servidor($dados_evento);

        if ($sucesso) {
            return new \WP_REST_Response(['success' => true, 'message' => 'Evento processado.'], 200);
        } else {
            return new \WP_REST_Response(['success' => false, 'message' => 'Falha ao enviar evento para a Meta.'], 500);
        }
    }

    /**
     * Gerencia o endpoint da API /usuario/enriquecer.
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function manipular_requisicao_enriquecimento_usuario(\WP_REST_Request $request) {
        $this->registrar_log('--- Início: Requisição de Enriquecimento de Usuário ---');
        
        $raw_body = $request->get_body();
        $this->registrar_log('Corpo da requisição recebido (raw):', $raw_body);

        $payload = json_decode($raw_body, true);

        if (json_last_error() !== JSON_ERROR_NONE || !isset($payload['external_id']) || !isset($payload['userData'])) {
            $this->registrar_log('Falha no enriquecimento: JSON inválido ou campos obrigatórios ausentes.');
            return new \WP_REST_Response(['success' => false, 'message' => 'JSON inválido ou campos obrigatórios ausentes.'], 400);
        }
        $this->registrar_log('Payload decodificado com sucesso:', $payload);

        $id_externo = sanitize_text_field($payload['external_id']);
        
        // Validação do comprimento do external_id
        if (strlen($id_externo) < 30) {
            $this->registrar_log('Falha no enriquecimento: External ID inválido (curto).', ['external_id' => $id_externo]);
            return new \WP_REST_Response(['success' => false, 'message' => 'External ID inválido.'], 400);
        }

        $dados_usuario_payload = $payload['userData'];

        // 1. Busca os dados que já existem no banco para evitar sobrescrevê-los.
        $this->registrar_log('Buscando dados existentes para o external_id:', $id_externo);
        $dados_existentes_db = $this->obter_dados_completos_usuario($id_externo);
        $this->registrar_log('Dados existentes encontrados no DB:', $dados_existentes_db);

        // 2. Prepara os novos dados a partir do payload.
        $novos_dados_para_salvar = ['external_id' => $id_externo];
        $chaves_permitidas = ['em', 'ph', 'fn', 'ln', 'fbc', 'fbp'];
        foreach ($dados_usuario_payload as $chave => $valor) {
            if (in_array($chave, $chaves_permitidas) && !empty($valor)) {
                $novos_dados_para_salvar[$chave] = sanitize_text_field($valor);
            }
        }
        $this->registrar_log('Novos dados extraídos do payload para salvar:', $novos_dados_para_salvar);


        // 3. Mescla os dados existentes com os novos, dando prioridade aos novos.
        $dados_completos_para_salvar = array_merge($dados_existentes_db, $novos_dados_para_salvar);
        $this->registrar_log('Dados mesclados prontos para salvar:', $dados_completos_para_salvar);
        
        // 4. Salva o registro completo e consolidado.
        if (count($dados_completos_para_salvar) > 1) { // Garante que não salvemos apenas com external_id
            $this->salvar_dados_visitante($dados_completos_para_salvar);
            $this->registrar_log('Sucesso: Dados do usuário salvos no banco de dados.');
        } else {
            $this->registrar_log('Aviso: Nenhum dado novo para salvar, pulando a escrita no DB.');
        }

        $this->registrar_log('--- Fim: Requisição de Enriquecimento de Usuário ---');
        return new \WP_REST_Response(['success' => true, 'message' => 'Requisição de enriquecimento processada.'], 200);
    }

    /**
     * Gerencia a sincronização de um usuário.
     * Verifica se um external_id existe. Se não, cria um novo registro.
     * Retorna sempre os dados de usuário formatados para `fbq('init')`.
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function manipular_requisicao_sincronizacao(\WP_REST_Request $request) {
        if($this->eh_trafego_indesejado()){
            $this->registrar_log('Requisição de sincronização ignorada: User Agent identificado como bot.', $_SERVER['HTTP_USER_AGENT'] ?? 'N/A');
            return new \WP_REST_Response(['success' => false, 'message' => 'Requisição ignorada (bot).'], 403);
        }

        global $wpdb;
        $nome_tabela = $wpdb->prefix . 'inktrack_users';
        $payload = json_decode($request->get_body(), true);

        if (json_last_error() !== JSON_ERROR_NONE || empty($payload['external_id'])) {
            return new \WP_REST_Response(['success' => false, 'message' => 'JSON inválido ou external_id ausente.'], 400);
        }

        $id_externo = sanitize_text_field($payload['external_id']);
        
        // Validação do comprimento do external_id
        if (strlen($id_externo) < 30) {
            $this->registrar_log('Tentativa de sincronização com external_id inválido (curto).', ['external_id' => $id_externo]);
            // Importante: Não retorne um erro aqui. A lógica do JS externo espera que a sincronização
            // continue, e se o ID for inválido, ele irá gerar um novo e tentar sincronizar novamente.
            // Apenas registramos e deixamos o processo seguir, mas o ID inválido não será salvo.
            // A função `salvar_dados_visitante` já tem uma verificação, mas aqui garantimos que não continue.
            // Vamos retornar um objeto de usuário vazio para que o lado do cliente saiba que a validação falhou.
            return new \WP_REST_Response(['success' => true, 'userData' => ['external_id' => $id_externo]], 200);
        }

        // Verifica se o usuário já existe.
        $usuario_existente_dados = $wpdb->get_row(
            $wpdb->prepare("SELECT id, fbc, fbp FROM $nome_tabela WHERE external_id = %s", $id_externo),
            ARRAY_A
        );

        // Se o usuário não existe, o criamos com os dados de contexto.
        if (!$usuario_existente_dados) {
            $dados_contexto = [
                'external_id' => $id_externo,
                'ip' => $this->obter_ip_cliente(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'fbc' => $_COOKIE['_fbc'] ?? null,
                'fbp' => $_COOKIE['_fbp'] ?? null,
            ];

            // Proteção para não criar registros vazios.
            // Um usuário real sempre terá um IP e um User Agent.
            if (empty($dados_contexto['ip']) || empty($dados_contexto['user_agent'])) {
                $this->registrar_log('Tentativa de criar usuário sem IP ou User Agent. Abortado.', $dados_contexto);
                // Retorna um objeto de usuário vazio para não quebrar o fluxo do cliente.
                return new \WP_REST_Response(['success' => true, 'userData' => ['external_id' => $id_externo]], 200);
            }
            
            // Adiciona dados geográficos, se disponíveis
            $dados_geo = $this->obter_dados_geograficos();
            if (!empty($dados_geo['country_code'])) {
                $dados_contexto['country'] = strtolower($dados_geo['country_code']);
            }

            $this->salvar_dados_visitante(array_filter($dados_contexto));
            $this->registrar_log('Novo usuário sincronizado e criado no banco de dados.', ['external_id' => $id_externo]);
        } else {
             $this->registrar_log('Usuário existente sincronizado.', ['external_id' => $id_externo]);
        }
        
        // Independentemente de criar ou não, sempre buscamos os dados mais recentes para o init.
        // Se for um novo usuário, a função retornará apenas o `external_id` hasheado.
        // Se for um usuário existente com dados PII, retornará os dados PII hasheados.
        $dados_init = $this->obter_dados_usuario_para_init($id_externo);

        // Adiciona os cookies fbc e fbp à resposta se eles existirem no banco de dados.
        // Estes são enviados não-hasheados, conforme a documentação da Meta.
        if (!empty($usuario_existente_dados['fbc'])) {
            $dados_init['fbc'] = $usuario_existente_dados['fbc'];
        }
        if (!empty($usuario_existente_dados['fbp'])) {
            $dados_init['fbp'] = $usuario_existente_dados['fbp'];
        }

        // O pixel ID é constante e não precisa mais ser enviado, o script externo o terá.
        return new \WP_REST_Response(['success' => true, 'userData' => $dados_init], 200);
    }

    /**
     * Verifica se o visitante atual é elegível para rastreamento.
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function manipular_verificacao_elegibilidade(\WP_REST_Request $request) {
        // Verifica se é tráfego de um bot indesejado.
        if($this->eh_trafego_indesejado()){
            $this->registrar_log('Verificação de elegibilidade: Falso (Bot detectado).', $_SERVER['HTTP_USER_AGENT'] ?? 'N/A');
            return new \WP_REST_Response(['eligible' => false, 'reason' => 'bot'], 200);
        }

        // Verifica se o usuário está fora da região geográfica permitida.
        if(!$this->eh_usuario_da_america_do_sul()){
            $this->registrar_log('Verificação de elegibilidade: Falso (Fora da América do Sul).');
            return new \WP_REST_Response(['eligible' => false, 'reason' => 'geo'], 200);
        }

        // Se passou por todas as verificações, o usuário é elegível.
        $this->registrar_log('Verificação de elegibilidade: Verdadeiro.');
        return new \WP_REST_Response(['eligible' => true], 200);
    }

    /**
     * Procura o external_id mais recente associado ao IP do visitante.
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function manipular_lookup_por_ip(\WP_REST_Request $request) {
        global $wpdb;
        $nome_tabela = $wpdb->prefix . 'inktrack_users';
        $ip_cliente = $this->obter_ip_cliente();

        if (in_array($ip_cliente, ['127.0.0.1', '::1', '177.220.176.169'])) {
             return new \WP_REST_Response(['success' => false, 'message' => 'IP local ou de teste não é pesquisável.'], 400);
        }

        // Busca o external_id do registro com o IP correspondente e a data de atualização mais recente.
        $id_externo = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT external_id FROM $nome_tabela WHERE ip = %s ORDER BY data_atualizacao DESC LIMIT 1",
                $ip_cliente
            )
        );

        if ($id_externo) {
            $this->registrar_log('Lookup por IP: Encontrado external_id.', ['ip' => $ip_cliente, 'external_id' => $id_externo]);
            return new \WP_REST_Response(['success' => true, 'external_id' => $id_externo], 200);
        } else {
            $this->registrar_log('Lookup por IP: Nenhum external_id encontrado para o IP.', ['ip' => $ip_cliente]);
            return new \WP_REST_Response(['success' => false, 'message' => 'Nenhum usuário encontrado com este IP.'], 404);
        }
    }

    /**
     * Roda a rotina de instalação uma única vez para garantir que a tabela exista e esteja atualizada.
     */
    public function executar_rotina_instalacao() {
        // A flag 'inktrack_db_version' com a versão garante que a tabela seja atualizada se mudarmos a estrutura.
        // '1.0': É um valor padrão. Se a chave 'inktrack_db_version' não existir (o que acontece na primeira vez que o plugin roda), a função retornará '1.0'.
        $versao_atual = get_option('inktrack_db_version', '1.0');
        
        if (version_compare($versao_atual, '2.2', '<')) {
            self::criar_tabela_personalizada();
            update_option('inktrack_db_version', '2.2');
        }
    }

    /**
     * Cria e atualiza a tabela personalizada para rastrear dados de usuários.
     * A função dbDelta do WordPress lida com a criação inicial e as atualizações de estrutura.
     */
    public static function criar_tabela_personalizada() {
        global $wpdb;
        $nome_tabela = $wpdb->prefix . 'inktrack_users';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $nome_tabela (
            id BIGINT(20) NOT NULL AUTO_INCREMENT,
            external_id VARCHAR(255) NOT NULL,
            em VARCHAR(255) NULL,
            ph VARCHAR(255) NULL,
            fn VARCHAR(255) NULL,
            ln VARCHAR(255) NULL,
            country VARCHAR(10) NULL,
            ip VARCHAR(100) NULL,
            user_agent TEXT NULL,
            fbc TEXT NULL,
            fbp TEXT NULL,
            data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY external_id (external_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Insere ou atualiza os dados de um visitante na tabela personalizada.
     *
     * @param array $dados Os dados a serem salvos. Deve incluir 'external_id'.
     */
    private function salvar_dados_visitante(array $dados) {
        global $wpdb;
        $nome_tabela = $wpdb->prefix . 'inktrack_users';

        if (empty($dados['external_id'])) {
            return;
        }

        $this->registrar_log('Salvando/Atualizando dados do visitante no banco de dados...', $dados);

        // Define os formatos de cada coluna para segurança
        $formatos = [
            'external_id' => '%s',
            'em' => '%s',
            'ph' => '%s',
            'fn' => '%s',
            'ln' => '%s',
            'country' => '%s',
            'ip' => '%s',
            'user_agent' => '%s',
            'fbc' => '%s',
            'fbp' => '%s',
        ];
        
        // Filtra os dados para garantir que apenas as colunas certas sejam usadas
        $dados_filtrados = array_intersect_key($dados, $formatos);
        
        // Filtra os formatos para corresponder aos dados que estamos inserindo
        $formatos_filtrados = array_values(array_intersect_key($formatos, $dados_filtrados));
        
        $wpdb->replace($nome_tabela, $dados_filtrados, $formatos_filtrados);
    }

    /**
     * Envia o evento para la API de Conversões da Meta.
     *
     * @param array $dados_evento
     * @return bool
     */
    private function enviar_evento_servidor(array $dados_evento) {
        if (empty($this->configuracao['pixel_id']) || empty($this->configuracao['api_token'])) {
            return false;
        }

        $api_url = sprintf(
            'https://graph.facebook.com/%s/%s/events?access_token=%s',
            $this->configuracao['meta_api_versao'],
            $this->configuracao['pixel_id'],
            $this->configuracao['api_token']
        );

        $payload = [
            'data' => [$dados_evento],
        ];

        // Adiciona o código de teste ao payload raiz, se estiver configurado
        if (!empty($this->configuracao['codigo_evento_teste'])) {
            $payload['test_event_code'] = $this->configuracao['codigo_evento_teste'];
        }

        $this->registrar_log('Enviando evento para a API da Meta...', $payload);

        $args = [
            'body' => json_encode($payload),
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'timeout' => 15,
        ];

        $response = wp_remote_post($api_url, $args);

        // O corpo da resposta pode conter informações úteis de depuração, mesmo em caso de sucesso.
        $corpo_resposta = wp_remote_retrieve_body($response);
        $this->registrar_log('Resposta da API da Meta recebida.', [
            'codigo_resposta' => wp_remote_retrieve_response_code($response),
            'corpo_resposta' => json_decode($corpo_resposta) ?? $corpo_resposta
        ]);

        if (is_wp_error($response)) {
            // O log de erro já acontece acima, mas mantemos este para o caso de debug=false
            error_log('INKTRACK Erro de API: ' . $response->get_error_message());
            return false;
        }

        $codigo_resposta = wp_remote_retrieve_response_code($response);

        if ($codigo_resposta >= 200 && $codigo_resposta < 300) {
            return true;
        } else {
            // O corpo da resposta já foi logado acima, mas logamos novamente em 'error_log' para garantir visibilidade
            error_log('INKTRACK Erro de API: API da Meta retornou status ' . $codigo_resposta . ' - ' . $corpo_resposta);
            return false;
        }
    }

    /**
     * Obtém ou define o cookie external_id.
     *
     * @return string O external_id.
     */
    private function obter_ou_definir_id_externo() {
        $nome_cookie = $this->configuracao['nome_cookie'];
        if (isset($_COOKIE[$nome_cookie])) {
            $id_externo_cookie = sanitize_text_field($_COOKIE[$nome_cookie]);
            // Valida o comprimento do ID do cookie
            if (strlen($id_externo_cookie) >= 30) {
                return $id_externo_cookie;
            } else {
                // Se o ID do cookie for inválido, registra um aviso e gera um novo.
                $this->registrar_log('ID externo do cookie é inválido (curto). Gerando um novo.', ['invalid_id' => $id_externo_cookie]);
            }
        }

        $id_externo = 'uid.' . time() . '.' . wp_generate_uuid4();
        $expiracao = time() + (86400 * 730); // 730 dias (2 anos)

        setcookie($nome_cookie, $id_externo, [
            'expires' => $expiracao,
            'path' => '/',
            'domain' => defined('COOKIE_DOMAIN') ? COOKIE_DOMAIN : '',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);

        $_COOKIE[$nome_cookie] = $id_externo; // Torna disponível imediatamente
        return $id_externo;
    }

    /**
     * Recupera os dados completos de um usuário da tabela personalizada.
     *
     * @param string $id_externo
     * @return array
     */
    private function obter_dados_completos_usuario($id_externo) {
        global $wpdb;
        $nome_tabela = $wpdb->prefix . 'inktrack_users';
        
        // Seleciona todos os campos de dados do usuário
        $dados_usuario = $wpdb->get_row(
            $wpdb->prepare("SELECT em, ph, fn, ln, country, fbc, fbp FROM $nome_tabela WHERE external_id = %s", $id_externo),
            ARRAY_A // Retorna como um array associativo
        );

        if (empty($dados_usuario)) {
            return [];
        }
        
        // Remove quaisquer chaves que sejam nulas ou vazias
        return array_filter($dados_usuario);
    }

    /**
     * Obtém os dados do usuário formatados para a chamada fbq('init').
     * Esta função coleta os dados brutos, aplica a normalização e o hashing SHA-256 internamente,
     * e retorna os dados prontos para serem usados, garantindo consistência com o evento do servidor.
     *
     * @param string $id_externo
     * @return array
     */
    private function obter_dados_usuario_para_init($id_externo) {
        // 1. Coletar todos os dados brutos que temos no banco.
        $dados_completos_db = $this->obter_dados_completos_usuario($id_externo);

        // 2. Adicionar/sobrescrever com dados geográficos da requisição atual, que são mais atuais.
        $dados_geo = $this->obter_dados_geograficos();
        if (!empty($dados_geo['country_code'])) {
            // A função de normalização cuidará de converter para minúsculas
            $dados_completos_db['country'] = $dados_geo['country_code'];
        }
        
        // 3. Normalizar e hashear os dados de PII usando a mesma função do servidor.
        // A função passará fbc/fbp sem modificação.
        $dados_preparados = $this->preparar_dados_para_meta($dados_completos_db);

        // 4. Montar o payload final para fbq('init'), mesclando o ID externo (não hasheado)
        $payload_final_init = ['external_id' => $id_externo];
        $payload_final_init = array_merge($payload_final_init, $dados_preparados);

        $this->registrar_log('Payload para fbq("init") preparado.', $payload_final_init);

        return $payload_final_init;
    }

    /**
     * Orquestrador que primeiro normaliza e depois aplica o hashing nos dados do usuário.
     *
     * @param array $dados_usuario Dados do usuário (brutos).
     * @return array Dados do usuário prontos para serem enviados à Meta.
     */
    private function preparar_dados_para_meta(array $dados_usuario) {
        // Renomeia as chaves para o padrão da Meta (não são hasheadas)
        if (isset($dados_usuario['ip'])) {
            $dados_usuario['client_ip_address'] = $dados_usuario['ip'];
            unset($dados_usuario['ip']);
        }
        if (isset($dados_usuario['user_agent'])) {
            $dados_usuario['client_user_agent'] = $dados_usuario['user_agent'];
            unset($dados_usuario['user_agent']);
        }

        $dados_normalizados = $this->normalizar_dados_usuario($dados_usuario);
        
        $chaves_para_hashear = ['em', 'ph', 'fn', 'ln', 'country'];

        foreach ($chaves_para_hashear as $chave) {
            if (!empty($dados_normalizados[$chave])) {
                $dados_normalizados[$chave] = hash('sha256', $dados_normalizados[$chave]);
            }
        }

        return $dados_normalizados;
    }

    /**
     * Aplica as regras de formatação da Meta nos dados brutos do usuário.
     *
     * @param array $dados_usuario Dados do usuário (brutos).
     * @return array Dados do usuário normalizados.
     */
    private function normalizar_dados_usuario(array $dados_usuario) {
        // em (email)
        if (!empty($dados_usuario['em'])) {
            $dados_usuario['em'] = strtolower(trim($dados_usuario['em']));
        }
        // ph (telefone)
        if (!empty($dados_usuario['ph'])) {
            $dados_usuario['ph'] = preg_replace('/[^0-9]/', '', $dados_usuario['ph']);
        }
        // fn (primeiro nome)
        if (!empty($dados_usuario['fn'])) {
            $dados_usuario['fn'] = strtolower(trim($dados_usuario['fn']));
        }
        // ln (sobrenome)
        if (!empty($dados_usuario['ln'])) {
            $dados_usuario['ln'] = strtolower(trim($dados_usuario['ln']));
        }
        // country (país)
        if (!empty($dados_usuario['country'])) {
            $dados_usuario['country'] = strtolower(trim($dados_usuario['country']));
        }
        return $dados_usuario;
    }
    
    /**
     * Obtém o endereço de IP do cliente, respeitando headers de proxy.
     *
     * @return string
     */
    private function obter_ip_cliente() {
        // Se $this->dominio_contem(['.local', '.inkweb']) && $this->configuracao['debug'] retorna 177.220.176.169
        if ($this->dominio_contem(['.local', '.inkweb']) && $this->configuracao['debug']) {
            return '177.220.176.169';
        }

        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }

    /**
     * Verifica se o domínio atual contém um termo específico.
     *
     * @param string|array $termo O termo ou termos para verificar.
     * @return bool
     */
    private function dominio_contem($termo) {
        if (!isset($_SERVER['HTTP_HOST'])) {
            return false;
        }

        $url = $_SERVER['HTTP_HOST'];
        if (is_array($termo)) {
            foreach ($termo as $str) {
                if (strpos($url, $str) !== false) {
                    return true;
                }
            }
            return false;
        }
        return strpos($url, $termo) !== false;
    }

    /**
     * Obtém a URL da página atual.
     *
     * @return string
     */
    private function obter_url_atual() {
        return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    }

    /**
     * Verifica, via API externa, se o IP do usuário é da América do Sul.
     * Utiliza um método de cache para evitar múltiplas chamadas de API.
     *
     * @return bool
     */
    private function eh_usuario_da_america_do_sul() {
        $dados_geo = $this->obter_dados_geograficos();
        
        // Se a API falhou, $dados_geo estará vazio e a verificação falhará, retornando true (fail-open)
        if (empty($dados_geo)) {
            return true;
        }

        return ($dados_geo['continent_code'] ?? '') === 'SA';
    }

    /**
     * Obtém os dados de geolocalização do usuário via API e os armazena em cache na requisição.
     *
     * @return array Os dados da API ou um array vazio em caso de falha.
     */
    private function obter_dados_geograficos() {
        // Se já fizemos a chamada nesta requisição, retorna o resultado em cache.
        if ($this->dados_geograficos_visitante !== null) {
            return $this->dados_geograficos_visitante;
        }

        $ip_cliente = $this->obter_ip_cliente();
        $token = $this->configuracao['ipinfo_token'];

        if (empty($token) || in_array($ip_cliente, ['127.0.0.1', '::1'])) {
            $this->dados_geograficos_visitante = []; // Cacheia um resultado vazio
            return $this->dados_geograficos_visitante;
        }

        // TODO: Ver se realmente tem limite
        // $url = "https://ipinfo.io/{$ip_cliente}?token={$token}"; // Traz uma busca mais completa com cidade e tudo mais, mas acho que tem limite
        $url = "https://api.ipinfo.io/lite/{$ip_cliente}?token={$token}";
        $this->registrar_log('Consultando API de geolocalização', ['url' => $url]);

        $resposta = wp_remote_get($url, ['timeout' => 5]);

        if (is_wp_error($resposta) || wp_remote_retrieve_response_code($resposta) !== 200) {
            $mensagem_erro = is_wp_error($resposta) ? $resposta->get_error_message() : 'Código de status HTTP: ' . wp_remote_retrieve_response_code($resposta);
            $this->registrar_log('INKTRACK Aviso API GeoIP: Falha na requisição.', ['erro' => $mensagem_erro]);
            
            $this->dados_geograficos_visitante = [];
            return $this->dados_geograficos_visitante;
        }

        $corpo = wp_remote_retrieve_body($resposta);
        $dados = json_decode($corpo, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->registrar_log('INKTRACK Aviso API GeoIP: Falha ao decodificar JSON.', ['corpo' => $corpo]);
            $this->dados_geograficos_visitante = [];
            return $this->dados_geograficos_visitante;
        }
        
        // A resposta da API padrão é um objeto JSON. Adicionamos 'country_code' para consistência interna.
        if (!empty($dados['country'])) {
            // Garante que o código do país esteja sempre no formato ISO 3166-1 alpha-2.
            $dados['country_code'] = $this->converter_nome_pais_para_codigo($dados['country']);
        }

        $this->registrar_log('Dados geográficos obtidos com sucesso', $dados);
        
        $this->dados_geograficos_visitante = $dados ?? [];
        return $this->dados_geograficos_visitante;
    }

    /**
     * Converte o nome completo de um país para seu código ISO 3166-1 alpha-2.
     * Se o valor já for um código de 2 letras, apenas o padroniza para minúsculas.
     *
     * @param string $nome_ou_codigo_pais O nome completo ou o código do país.
     * @return string O código de 2 letras do país (em minúsculo) ou o valor original se não for um nome mapeado.
     */
    private function converter_nome_pais_para_codigo($nome_ou_codigo_pais) {
        $identificador_pais = strtolower(trim($nome_ou_codigo_pais));

        // Se já for um código alpha-2, apenas o retorna.
        if (strlen($identificador_pais) === 2) {
            return $identificador_pais;
        }

        $mapa_paises = [
            // América do Sul
            'argentina' => 'ar',
            'bolivia' => 'bo',
            'brazil' => 'br',
            'brasil' => 'br',
            'chile' => 'cl',
            'chili' => 'cl',
            'colombia' => 'co',
            'ecuador' => 'ec',
            'equador' => 'ec',
            'guyana' => 'gy',
            'paraguay' => 'py',
            'paraguai' => 'py',
            'peru' => 'pe',
            'suriname' => 'sr',
            'uruguay' => 'uy',
            'uruguai' => 'uy',
            'venezuela' => 've',
            'falkland islands (malvinas)' => 'fk',
            'french guiana' => 'gf',
        ];

        // Retorna o código do mapa ou o identificador original se não for um nome completo conhecido.
        return $mapa_paises[$identificador_pais] ?? $identificador_pais;
    }

    /**
     * Registra uma mensagem no log de erros do PHP se o modo de depuração estiver ativo.
     *
     * @param string $mensagem A mensagem a ser registrada.
     * @param mixed|null $dados Dados adicionais para incluir no log.
     */
    private function registrar_log(string $mensagem, $dados = null) {
        if ($this->configuracao['debug']) {
            $log_entry = "INKTRACK Debug: " . $mensagem;
            if ($dados !== null) {
                // Se os dados forem um array ou objeto, formata como JSON para melhor legibilidade.
                if (is_array($dados) || is_object($dados)) {
                     $log_entry .= " | Dados: " . json_encode($dados, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                } else {
                    $log_entry .= " | Dados: " . print_r($dados, true);
                }
            }
            error_log($log_entry);
        }
    }

    /**
     * Verifica se a requisição atual é de um bot conhecido para evitar rastreamento.
     *
     * @return bool
     */
    private function eh_trafego_indesejado() {
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        if (empty($user_agent)) {
            return false; // Não podemos determinar, então não bloqueamos
        }

        $lista_bots = [
            // Crawlers de SEO e agregadores
            'AhrefsBot', 'SemrushBot', 'MegaIndex', 'YandexBot', 'bingbot', 'AdsBot',
            'Googlebot', 'GoogleOther', 'MJ12bot', 'DotBot', 'PetalBot', 'FAST-WebCrawler', 'BingPreview',
            // Bots de IA
            'GPTBot', 'ChatGPT-User', 'ClaudeBot', 'Google-Extended', 'anthropic-ai',
            // Social e outros
            'facebookexternalhit', 'WhatsApp', 'Twitterbot',
            // Proxies e ferramentas de privacidade
            'Chrome Privacy Preserving Prefetch Proxy',
        ];

        foreach ($lista_bots as $bot) {
            if (stripos(strtolower($user_agent), strtolower($bot)) !== false) {
                // Se encontrarmos qualquer uma dessas strings no User-Agent, consideramos um bot.
                return true;
            }
        }

        return false;
    }
}
