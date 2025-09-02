/**
 * INKTRACK - Lógica de Rastreamento Autônoma para Site Externo
 *
 * Este script autônomo injeta o pixel da Meta, gerencia de forma inteligente a identidade do usuário
 * (validando, persistindo e criando IDs), o inicializa com Advanced Matching, dispara um evento
 * PageView e fornece funções para rastreamento e enriquecimento de dados contínuos.
 */
(function (window, document) {
    'use strict';

    // --- Configuração ---
    const MODO_DEBUG = false; // Altere para false em produção
    const PIXEL_ID = '1429042787694485'; // Pixel_FozPlaza2024 - Exposto conforme solicitado.
    const URL_BASE_API = 'https://fozplaza.com.br/wp-json/inktrack/v1';
    const ENDPOINT_EVENTO = `${URL_BASE_API}/evento`;
    const ENDPOINT_ENRIQUECER = `${URL_BASE_API}/usuario/enriquecer`;
    const ENDPOINT_SINCRONIZAR = `${URL_BASE_API}/usuario/sincronizar`;
    const ENDPOINT_CHECK_ELIGIBILITY = `${URL_BASE_API}/status/check-eligibility`;
    const ENDPOINT_LOOKUP_BY_IP = `${URL_BASE_API}/usuario/lookup-by-ip`;
    const CHAVE_ID_EXTERNO = 'ink_external_id';
    const CHAVE_COOKIE = 'ink_uid';
    const CHAVE_COOKIE_FBC = '_fbc';
    const CHAVE_COOKIE_FBP = '_fbp';
    const EVENTOS_PADRAO = [
        'PageView', 'pageview', 'ViewPage', 'Lead', 'InitiateCheckout', 'AddToCart',
        'AddPaymentInfo', 'Purchase', 'ViewContent', 'Search', 'ViewSearchResults',
        'AddToWishlist', 'CompletePayment', 'Contact'
    ];

    // --- Namespace ---
    const INKTRACK = window.INKTRACK || {};
    window.INKTRACK = INKTRACK;

    /**
     * Função auxiliar para logar no console apenas quando o debug está ativo.
     */
    const logDebugExterno = (level, message, data) => {
        if (!MODO_DEBUG) return;
        const styles = {
            log: 'color: #0073aa;', warn: 'color: #ffb900;', error: 'color: #dc3232;',
            enrich: 'color: #ff6f61;', init: 'color: #4CAF50; font-weight: bold;',
            identity: 'color: #9C27B0;', omni: 'color: #f36c21; font-weight: bold;'
        };

        // Garante que estamos usando um método válido do console, como 'log', 'warn', 'error'.
        // Se 'level' for um nível personalizado como 'init', usamos 'log' como fallback.
        const consoleMethod = (console[level] && typeof console[level] === 'function') ? level : 'log';

        const style = styles[level] || styles.log;
        const prefix = `[INKTRACK]`;

        if (data) {
            console[consoleMethod](`%c${prefix} ${message}`, style, data);
        } else {
            console[consoleMethod](`%c${prefix} ${message}`, style);
        }
    };

    // --- Funções de Ajuda (Cookies) ---
    const setCookie = (name, value, days) => {
        let expires = "";
        if (days) {
            const date = new Date();
            date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
            expires = "; expires=" + date.toUTCString();
        }
        document.cookie = name + "=" + (value || "") + expires + "; path=/; SameSite=Lax; Secure";
    };

    const getCookie = (name) => {
        const nameEQ = name + "=";
        const ca = document.cookie.split(';');
        for (let i = 0; i < ca.length; i++) {
            let c = ca[i];
            while (c.charAt(0) === ' ') c = c.substring(1, c.length);
            if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length, c.length);
        }
        return null;
    };

    /**
     * Sincroniza o ID do usuário com o backend, validando-o se existir
     * ou criando um novo registro se não existir.
     * Retorna os dados do usuário para o Advanced Matching.
     * @param {string} id - O ID externo a ser sincronizado.
     * @returns {Promise<object|null>} Os dados do usuário para init ou null em caso de falha.
     */
    async function sincronizarUsuario(id) {
        logDebugExterno('identity', `Sincronizando ID: ${id} com o servidor...`);
        try {
            const response = await fetch(ENDPOINT_SINCRONIZAR, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ external_id: id })
            });
            if (!response.ok) {
                throw new Error(`API retornou status ${response.status}`);
            }
            const data = await response.json();
            if (data.success && data.userData) {
                logDebugExterno('identity', 'Sincronização bem-sucedida.', data.userData);

                // Prioridade 1: Injetar fbc/fbp do servidor no navegador do cliente.
                if (data.userData.fbc || data.userData.fbp) {
                    logDebugExterno('identity', 'Valores de fbc/fbp recebidos do servidor. Injetando no navegador...');
                    if (data.userData.fbc) setCookie(CHAVE_COOKIE_FBC, data.userData.fbc, 90);
                    if (data.userData.fbp) setCookie(CHAVE_COOKIE_FBP, data.userData.fbp, 730);
                }

                return data.userData;
            }
            throw new Error('Resposta da API de sincronização inválida.');
        } catch (error) {
            logDebugExterno('error', 'Falha ao sincronizar usuário.', error);
            // Retorna um objeto base para que o rastreamento não pare.
            return { external_id: id };
        }
    }

    /**
     * Gerencia a identidade do usuário de forma autônoma.
     * Prioridade: URL > LocalStorage > Cookie > Geração.
     * Valida o ID da URL com o backend.
     * @returns {Promise<{id: string, userData: object}>} O ID final e os dados do usuário.
     */
    async function gerenciarIdentidadeUsuario() {
        const parametrosUrl = new URLSearchParams(window.location.search);
        let id = parametrosUrl.get(CHAVE_ID_EXTERNO);
        const isIdValido = (id) => id && id.length >= 30;

        // 1. Prioridade: ID na URL
        if (isIdValido(id)) {
            logDebugExterno('identity', `ID encontrado na URL: ${id}. Validando...`);
            const userData = await sincronizarUsuario(id);
            // Se a sincronização validou (retornou dados), usamos este ID.
            if (userData && Object.keys(userData).length > 1) { // >1 para garantir que não é só o external_id
                logDebugExterno('identity', 'ID da URL validado com sucesso.');
                localStorage.setItem(CHAVE_ID_EXTERNO, id);
                setCookie(CHAVE_COOKIE, id, 730);
                return { id, userData };
            }
            logDebugExterno('warn', 'ID da URL inválido ou não encontrado no banco de dados. Descartando.');
        } else if (id) {
            logDebugExterno('warn', `ID da URL encontrado "${id}" é inválido (curto demais). Descartando.`);
        }

        // 2. LocalStorage
        id = localStorage.getItem(CHAVE_ID_EXTERNO);
        if (isIdValido(id)) {
            logDebugExterno('identity', `ID encontrado no LocalStorage: ${id}`);
            const userData = await sincronizarUsuario(id);
            return { id, userData };
        }

        // 3. Cookie
        id = getCookie(CHAVE_COOKIE);
        if (isIdValido(id)) {
            logDebugExterno('identity', `ID encontrado no Cookie: ${id}`);
            localStorage.setItem(CHAVE_ID_EXTERNO, id); // Atualiza o localStorage
            const userData = await sincronizarUsuario(id);
            return { id, userData };
        }

        // 4. Busca por IP via API
        try {
            logDebugExterno('identity', 'Nenhuma identidade local encontrada. Tentando busca por IP...');
            const response = await fetch(ENDPOINT_LOOKUP_BY_IP);
            if (response.ok) {
                const data = await response.json();
                if (data.success && isIdValido(data.external_id)) {
                    id = data.external_id;
                    logDebugExterno('identity', `ID encontrado via IP: ${id}. Persistindo localmente.`);
                    localStorage.setItem(CHAVE_ID_EXTERNO, id);
                    setCookie(CHAVE_COOKIE, id, 730);
                    const userData = await sincronizarUsuario(id);
                    return { id, userData };
                }
            }
        } catch (error) {
            logDebugExterno('warn', 'Busca por IP falhou ou não retornou resultados.', error);
        }

        // 5. Geração
        id = 'uid.c.' + Date.now() + '.' + Math.random().toString(36).substring(2, 15);
        logDebugExterno('identity', `Nenhum ID encontrado. Gerando novo ID: ${id}`);
        localStorage.setItem(CHAVE_ID_EXTERNO, id);
        setCookie(CHAVE_COOKIE, id, 730);
        const userData = await sincronizarUsuario(id);
        return { id, userData };
    }

    /**
     * Injeta o script base da Meta (fbevents.js) no <head> da página.
     */
    function injetarScriptMeta() {
        if (window.fbq) {
            logDebugExterno('init', 'Script da Meta (fbq) já existe. Pulando injeção.');
            return;
        }
        logDebugExterno('init', 'Injetando script base da Meta (fbevents.js)...');
        !function (f, b, e, v, n, t, s) {
            if (f.fbq) return; n = f.fbq = function () {
                n.callMethod ?
                    n.callMethod.apply(n, arguments) : n.queue.push(arguments)
            };
            if (!f._fbq) f._fbq = n; n.push = n; n.loaded = !0; n.version = '2.0';
            n.queue = []; t = b.createElement(e); t.async = !0;
            t.src = v; s = b.getElementsByTagName(e)[0];
            s.parentNode.insertBefore(t, s)
        }(window, document, 'script',
            'https://connect.facebook.net/en_US/fbevents.js');
    }

    /**
     * Verifica com o backend se o visitante atual é elegível para rastreamento.
     * @returns {Promise<boolean>} True se for elegível, false caso contrário.
     */
    async function verificarElegibilidade() {
        try {
            const response = await fetch(ENDPOINT_CHECK_ELIGIBILITY);
            if (!response.ok) {
                // Fail-open: Se a API falhar, consideramos elegível para não quebrar o rastreamento.
                logDebugExterno('warn', 'API de elegibilidade falhou. Assumindo que o usuário é elegível (fail-open).');
                return true;
            }
            const data = await response.json();
            if (data.eligible === false) {
                logDebugExterno('warn', `Usuário não é elegível para rastreamento. Razão: ${data.reason}. Abortando.`);
                return false;
            }
            return true;
        } catch (error) {
            logDebugExterno('error', 'Erro ao verificar elegibilidade. Assumindo que o usuário é elegível (fail-open).', error);
            return true;
        }
    }

    /**
     * Função principal de inicialização.
     */
    async function inicializar() {
        const elegivel = await verificarElegibilidade();
        if (!elegivel) {
            return; // Interrompe toda a execução se o usuário não for elegível.
        }

        injetarScriptMeta();

        const { id, userData } = await gerenciarIdentidadeUsuario();

        if (!id) {
            logDebugExterno('error', 'Inicialização abortada: Não foi possível obter ou gerar um ID de usuário.');
            return;
        }

        logDebugExterno('init', `Inicializando Pixel ID: ${PIXEL_ID}`, userData);
        window.fbq('init', PIXEL_ID, userData);

        logDebugExterno('init', 'Disparando evento PageView...');
        INKTRACK.track('PageView', {}, id); // Passa o ID diretamente para evitar re-busca

        // Tenta enriquecer proativamente com o histórico de checkout do localStorage
        enriquecerComHistoricoCheckout(userData);

        // Sincroniza os cookies da Meta (fbc/fbp) com o backend após o PageView.
        sincronizarCookiesMetaComBackend(userData);

        // Configura os ouvintes para enriquecimento de dados de formulário
        configurarOuvintesEnriquecimento();

        // Inicia as integrações específicas
        configurarOuvinteOmnibeesDataLayer();
    }

    // --- Integração Específica: OMNIBEES ---

    /**
     * Remove chaves de um objeto cujo valor seja null, undefined ou uma string vazia.
     * Modifica o objeto diretamente (in-place).
     * @param {object} obj - O objeto a ser limpo.
     */
    function limparParametrosInvalidos(obj) {
        if (!obj) return;
        Object.keys(obj).forEach(key => {
            if (obj[key] === null || obj[key] === undefined || obj[key] === '') {
                delete obj[key];
            }
        });
    }

    /**
     * Tenta enriquecer os dados do usuário de forma proativa, usando o histórico de checkout
     * que a Omnibees armazena no localStorage.
     * Esta função preenche apenas os campos que ainda não temos no nosso backend.
     * @param {object} dadosUsuarioAtual - Os dados do usuário já obtidos do nosso servidor.
     */
    function enriquecerComHistoricoCheckout(dadosUsuarioAtual) {
        if (window.location.hostname !== 'book.omnibees.com') {
            return;
        }

        try {
            let historico = null;
            // Encontra a chave do histórico, que pode ter um ID dinâmico.
            for (let i = 0; i < localStorage.length; i++) {
                const chave = localStorage.key(i);
                if (chave.startsWith('checkoutHistory_')) {
                    historico = JSON.parse(localStorage.getItem(chave));
                    break;
                }
            }

            if (!historico || !Array.isArray(historico) || historico.length === 0) {
                return;
            }

            logDebugExterno('omni', 'Histórico de checkout encontrado no localStorage. Tentando enriquecimento proativo.', historico);

            let emailHist = null, nomeHist = null, sobrenomeHist = null, telefoneHist = null;

            // Itera de trás para frente para pegar os dados mais recentes primeiro.
            for (let i = historico.length - 1; i >= 0; i--) {
                const entrada = historico[i];
                if (!emailHist && entrada['input-email']) emailHist = entrada['input-email'];
                if (!nomeHist && entrada['input-name']) nomeHist = entrada['input-name'];
                if (!sobrenomeHist && entrada['input-lastname']) sobrenomeHist = entrada['input-lastname'];

                // Monta o telefone se ainda não o tivermos.
                if (!telefoneHist && entrada['input-phone'] && entrada['phone-country-select']) {
                    const telefoneCompleto = `${entrada['phone-country-select']}${entrada['input-phone']}`;
                    telefoneHist = telefoneCompleto.replace(/\D/g, '');
                }

                // Se já preenchemos todos, podemos parar.
                if (emailHist && nomeHist && sobrenomeHist && telefoneHist) break;
            }

            const dadosParaEnriquecer = {};
            // Compara com os dados atuais e adiciona apenas o que está faltando.
            if (emailHist && !dadosUsuarioAtual.em) dadosParaEnriquecer.em = emailHist;
            if (nomeHist && !dadosUsuarioAtual.fn) dadosParaEnriquecer.fn = nomeHist;
            if (sobrenomeHist && !dadosUsuarioAtual.ln) dadosParaEnriquecer.ln = sobrenomeHist;
            if (telefoneHist && !dadosUsuarioAtual.ph) dadosParaEnriquecer.ph = telefoneHist;

            // Se encontramos algo novo para adicionar, enviamos.
            if (Object.keys(dadosParaEnriquecer).length > 0) {
                logDebugExterno('omni', 'Enriquecimento proativo: enviando dados do histórico para preencher lacunas.', dadosParaEnriquecer);

                // Usamos fetch aqui para poder capturar e logar a resposta do servidor,
                // já que esta chamada não ocorre durante a saída da página.
                const id = getCookie(CHAVE_COOKIE) || localStorage.getItem(CHAVE_ID_EXTERNO);
                if (id) {
                    fetch(ENDPOINT_ENRIQUECER, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ external_id: id, userData: dadosParaEnriquecer }),
                        keepalive: true // Garante o envio mesmo se a página estiver descarregando
                    })
                        .then(response => response.json())
                        .then(data => {
                            logDebugExterno('omni', 'Resposta do servidor ao enriquecimento proativo:', data);
                        })
                        .catch(error => {
                            logDebugExterno('error', 'Erro na chamada de enriquecimento proativo.', error);
                        });
                }
            }

        } catch (error) {
            logDebugExterno('error', 'Falha ao processar histórico de checkout do localStorage.', error);
        }
    }

    /**
     * Configura um ouvinte para o DataLayer da Omnibees.
     * Este código é específico para o domínio book.omnibees.com.
     * Ele intercepta os pushes para o dataLayer para rastrear eventos de ecommerce.
     */
    function configurarOuvinteOmnibeesDataLayer() {
        // Garante que este código rode apenas no domínio da Omnibees
        if (window.location.hostname !== 'book.omnibees.com') {
            return;
        }

        logDebugExterno('omni', 'Integração Omnibees DataLayer ativada.');

        // Garante que window.dataLayer exista
        window.dataLayer = window.dataLayer || [];

        // Função para processar um evento do DataLayer
        const processarEvento = (evento) => {
            if (!evento || !evento.event) return;

            // Evento: Search
            if (evento.event === 'ecommerceSearch') {
                logDebugExterno('omni', 'Evento ecommerceSearch da Omnibees detectado.', evento);

                const parametros = {
                    content_type: 'hotel',
                    checkin_date: evento.checkIn,
                    checkout_date: evento.checkOut,
                    num_adults: evento.numberAdults,
                    num_children: evento.numberChildren,
                    destination: 'Foz do Iguaçu, Paraná', // Fixo conforme solicitado
                    region: 'Foz do Iguaçu, PR',        // Fixo conforme solicitado
                    city: 'Foz do Iguaçu',
                    country: 'Brazil',
                    hotel_id: evento.hotelId,
                    currency: evento.ecommerce ? evento.ecommerce.currency : 'BRL'
                };

                limparParametrosInvalidos(parametros);
                logDebugExterno('omni', 'Disparando eventos "Search" e "BuscaData".', parametros);
                INKTRACK.track('Search', parametros);
                INKTRACK.track('BuscaData', parametros);
                INKTRACK.track('ViewContent', parametros);

                // Evento: Checkout (dividido em dois passos)
            } else if (evento.event === 'ecommerceCheckout') {

                // Passo 2: Dados do usuário preenchidos -> InitiateCheckout
                if (evento.userEmail) {
                    logDebugExterno('omni', 'Evento ecommerceCheckout (Passo 2) detectado.', evento);

                    // Aproveita para enriquecer o perfil do usuário, validando dados mascarados
                    const dadosParaEnriquecer = {
                        em: evento.userEmail,
                        fn: evento.firstName,
                        ln: evento.lastName
                    };
                    const telefone = evento.ecommerce?.checkout?.guestData?.phone;
                    if (telefone && !telefone.includes('*')) {
                        // Remove todos os caracteres que não são dígitos
                        dadosParaEnriquecer.ph = telefone.replace(/\D/g, '');
                    }
                    enriquecerDadosUsuario(dadosParaEnriquecer);

                    const parametros = {
                        value: evento.ecommerce?.value,
                        currency: evento.ecommerce?.currency,
                        content_type: 'hotel',
                        destination: 'Foz do Iguaçu, Paraná', // Fixo conforme solicitado
                        region: 'Foz do Iguaçu, PR',        // Fixo conforme solicitado
                        city: 'Foz do Iguaçu',
                        country: 'Brazil',
                        hotel_id: evento.hotelId,
                        content_ids: evento.ecommerce?.items?.map(item => item.item_id),
                        contents: evento.ecommerce?.items?.map(item => ({
                            id: item.item_id,
                            quantity: item.quantity,
                            item_price: item.price
                        })),
                        num_items: evento.ecommerce?.items?.reduce((acc, item) => acc + item.quantity, 0),
                        checkin_date: evento.checkIn,
                        checkout_date: evento.checkOut,
                        num_adults: evento.numberAdults,
                        num_children: evento.numberChildren
                    };

                    limparParametrosInvalidos(parametros);
                    logDebugExterno('omni', 'Disparando evento "InitiateCheckout".', parametros);
                    INKTRACK.track('InitiateCheckout', parametros);

                    // Passo 1: Chegada na página de checkout -> AddToCart
                } else {
                    logDebugExterno('omni', 'Evento ecommerceCheckout (Passo 1) detectado.', evento);

                    const parametros = {
                        value: evento.ecommerce?.value,
                        currency: evento.ecommerce?.currency,
                        content_type: 'hotel',
                        destination: 'Foz do Iguaçu, Paraná', // Fixo conforme solicitado
                        region: 'Foz do Iguaçu, PR',        // Fixo conforme solicitado
                        city: 'Foz do Iguaçu',
                        country: 'Brazil',
                        hotel_id: evento.hotelId,
                        content_ids: evento.ecommerce?.items?.map(item => item.item_id),
                        contents: evento.ecommerce?.items?.map(item => ({
                            id: item.item_id,
                            quantity: item.quantity,
                            item_price: item.price
                        })),
                        checkin_date: evento.checkIn,
                        checkout_date: evento.checkOut,
                        num_adults: evento.numberAdults,
                        num_children: evento.numberChildren
                    };

                    limparParametrosInvalidos(parametros);
                    logDebugExterno('omni', 'Disparando evento "AddToCart".', parametros);
                    INKTRACK.track('AddToCart', parametros);
                }
                // Evento: Purchase
            } else if (evento.event === 'ecommercePurchase') {
                logDebugExterno('omni', 'Evento ecommercePurchase detectado.', evento);

                // Garante que o perfil do usuário está enriquecido com os dados finais da compra
                const dadosParaEnriquecer = {
                    em: evento.userEmail,
                    fn: evento.firstName,
                    ln: evento.lastName
                };
                const telefone = evento.userTel;
                if (telefone && !telefone.includes('*')) {
                    dadosParaEnriquecer.ph = telefone.replace(/\D/g, '');
                }
                enriquecerDadosUsuario(dadosParaEnriquecer);

                const parametros = {
                    value: evento.ecommerce?.value,
                    currency: evento.ecommerce?.currency,
                    order_id: evento.ecommerce?.transaction_id, // Parâmetro padrão para compras
                    transaction_id: evento.ecommerce?.transaction_id, // Parâmetro padrão para compras
                    content_type: 'hotel',
                    content_ids: evento.ecommerce?.items?.map(item => item.item_id),
                    contents: evento.ecommerce?.items?.map(item => ({
                        id: item.item_id,
                        quantity: item.quantity,
                        item_price: item.price
                    })),
                    num_items: evento.ecommerce?.items?.reduce((acc, item) => acc + item.quantity, 0),
                    checkin_date: evento.checkIn,
                    checkout_date: evento.checkOut,
                    num_adults: evento.numberAdults,
                    num_children: evento.numberChildren,
                    destination: 'Foz do Iguaçu, Paraná',
                    region: 'Foz do Iguaçu, PR',
                    city: 'Foz do Iguaçu',
                    country: 'Brazil',
                    hotel_id: evento.hotelId,
                    payment_type: evento.ecommerce?.payment_type
                };

                limparParametrosInvalidos(parametros);
                logDebugExterno('omni', 'Disparando evento "Purchase".', parametros);
                INKTRACK.track('Purchase', parametros);
            }
        };

        // Processa eventos que já possam estar no dataLayer quando o script carregar
        window.dataLayer.forEach(processarEvento);

        // Substitui o método 'push' original para interceptar novos eventos
        const originalPush = window.dataLayer.push;
        window.dataLayer.push = function (...args) {
            args.forEach(processarEvento);
            return originalPush.apply(window.dataLayer, args);
        };
    }

    // --- Funções Públicas e de Apoio ---

    /**
     * Prioridade 2: Verifica os cookies _fbc e _fbp no navegador e, se forem novos
     * ou diferentes dos que vieram do servidor, envia-os para o backend para
     * enriquecer o perfil do usuário.
     * @param {object} dadosServidor - Os dados recebidos do servidor durante a sincronização inicial.
     */
    function sincronizarCookiesMetaComBackend(dadosServidor) {
        const fbcNavegador = getCookie(CHAVE_COOKIE_FBC);
        const fbpNavegador = getCookie(CHAVE_COOKIE_FBP);

        const dadosParaSincronizar = {};

        // Compara o cookie FBC do navegador com o que veio do servidor.
        // Se for diferente ou não existia no servidor, adiciona para sincronização.
        if (fbcNavegador && fbcNavegador !== dadosServidor.fbc) {
            dadosParaSincronizar.fbc = fbcNavegador;
        }

        // Faz o mesmo para o cookie FBP.
        if (fbpNavegador && fbpNavegador !== dadosServidor.fbp) {
            dadosParaSincronizar.fbp = fbpNavegador;
        }

        // Se houver algum cookie novo para sincronizar, envia para o endpoint de enriquecimento.
        if (Object.keys(dadosParaSincronizar).length > 0) {
            logDebugExterno('enrich', 'Novos valores de fbc/fbp detectados no navegador. Sincronizando com o backend...', dadosParaSincronizar);
            enriquecerDadosUsuario(dadosParaSincronizar);
        } else {
            logDebugExterno('log', 'Cookies fbc/fbp do navegador já estão em sincronia com o backend.');
        }
    }

    INKTRACK.track = function (nomeEvento, parametros = {}, idExternoResolvido = null) {
        const id = idExternoResolvido || getCookie(CHAVE_COOKIE) || localStorage.getItem(CHAVE_ID_EXTERNO);
        if (!id) {
            logDebugExterno('error', 'Não é possível rastrear o evento sem um ID externo.');
            return;
        }

        const idEvento = `externo.${Date.now()}.${Math.random().toString(36).substring(2, 9)}`;
        logDebugExterno('log', `Rastreando evento: ${nomeEvento}`, { idEvento, ...parametros });

        if (typeof window.fbq === 'function') {
            const ehEventoPadrao = EVENTOS_PADRAO.includes(nomeEvento);
            const funcaoTrack = ehEventoPadrao ? 'trackSingle' : 'trackSingleCustom';

            window.fbq(funcaoTrack, PIXEL_ID, nomeEvento, parametros, { eventID: idEvento });
            logDebugExterno('log', `(Client-Side) Evento '${nomeEvento}' enviado via fbq com a função '${funcaoTrack}'.`);
        } else {
            logDebugExterno('warn', `(Client-Side) window.fbq não encontrado.`);
        }

        // Garante que estamos enviando os valores mais atuais de fbc e fbp para o servidor
        const fbcAtual = getCookie(CHAVE_COOKIE_FBC);
        const fbpAtual = getCookie(CHAVE_COOKIE_FBP);

        const payloadServidor = {
            event_name: nomeEvento, event_id: idEvento,
            event_source_url: window.location.href, params: parametros,
            external_id: id,
            fbc: fbcAtual || undefined, // Envia undefined se for nulo para não poluir o payload
            fbp: fbpAtual || undefined
        };
        navigator.sendBeacon(ENDPOINT_EVENTO, JSON.stringify(payloadServidor));
        logDebugExterno('log', `(Server-Side) Evento '${nomeEvento}' enviado para a API.`);
    };

    function enriquecerDadosUsuario(dadosUsuario) {
        const id = getCookie(CHAVE_COOKIE) || localStorage.getItem(CHAVE_ID_EXTERNO);
        if (!id) return;
        logDebugExterno('enrich', 'Enriquecendo dados do usuário (via sendBeacon)...', dadosUsuario);

        // Usamos sendBeacon para as chamadas de enriquecimento durante o fluxo (checkout/purchase)
        // pois elas podem ocorrer perto de um redirecionamento ou fechamento de página.
        const payload = JSON.stringify({
            external_id: id, userData: dadosUsuario
        });

        try {
            if (!navigator.sendBeacon(ENDPOINT_ENRIQUECER, payload)) {
                logDebugExterno('error', 'Falha ao enviar beacon de enriquecimento. O navegador pode ter bloqueado ou a fila estava cheia.');
            }
        } catch (e) {
            logDebugExterno('error', 'Exceção ao tentar enviar beacon de enriquecimento.', e);
        }
    }

    function configurarOuvintesEnriquecimento() {
        const campos = [
            { seletor: 'input[name*="email"]', chave: 'em', tipo: 'texto' },
            { seletor: 'input[name*="phone"]', chave: 'ph', tipo: 'telefone' },
            { seletor: 'input[name*="first_name"]', chave: 'fn', tipo: 'texto' },
            { seletor: 'input[name*="last_name"]', chave: 'ln', tipo: 'texto' }
        ];
        campos.forEach(({ seletor, chave, tipo }) => {
            document.addEventListener('blur', function (e) {
                if (e.target.matches(seletor)) {
                    let valor = e.target.value.trim();
                    if (tipo === 'texto') {
                        valor = valor.toLowerCase();
                    } else if (tipo === 'telefone') {
                        valor = valor.replace(/\D/g, '');
                    }
                    if (valor) enriquecerDadosUsuario({ [chave]: valor });
                }
            }, true);
        });
    }

    // Roda o inicializador
    inicializar();

})(window, document);
