/**
 * INKTRACK - Lógica de Rastreamento Client-Side
 *
 * Este script gerencia o disparo de eventos de rastreamento da Meta com base em interações do usuário.
 * Ele envia eventos tanto para o client-side (via fbq) quanto para o server-side (via nossa API REST).
 */
(function(window, document) {
    'use strict';

    // Namespace para funções públicas
    const INKTRACK = window.INKTRACK || {};
    window.INKTRACK = INKTRACK;

    // Verifica a configuração obrigatória vinda do WordPress
    if (typeof INKTRACK_CONFIG === 'undefined') {
        console.error('INKTRACK Erro Crítico: Objeto de configuração (INKTRACK_CONFIG) não definido.');
        return;
    }

    /**
     * Função auxiliar para logar no console apenas quando o debug está ativo.
     * @param {string} level - O nível do log (log, warn, error).
     * @param {string} message - A mensagem a ser exibida.
     * @param {*} [data] - Dados opcionais para logar.
     */
    const logDebug = (level, message, data) => {
        if (!INKTRACK_CONFIG.debug) {
            return;
        }
        const styles = {
            log: 'color: #0073aa;',
            warn: 'color: #ffb900;',
            error: 'color: #dc3232;',
            server: 'color: #4ab866;'
        };
        const style = styles[level] || styles.log;
        
        // Garante que um método válido do console seja usado. 'server' é apenas para estilização.
        const consoleMethod = (level === 'warn' || level === 'error') ? level : 'log';

        if (data) {
            console[consoleMethod](`%c${message}`, style, data);
        } else {
            console[consoleMethod](`%c${message}`, style);
        }
    };

    /**
     * Gera um ID de evento único para deduplicação.
     * @returns {string}
     */
    const gerarIdEvento = () => {
        return `client.${Date.now()}.${Math.random().toString(36).substring(2, 9)}`;
    };

    /**
     * Função principal de rastreamento.
     * @param {string} nomeEvento - O nome do evento da Meta (ex: 'AddToCart').
     * @param {object} [parametros={}] - Parâmetros do evento (ex: { value: 10.00, currency: 'BRL' }).
     */
    INKTRACK.track = function(nomeEvento, parametros = {}) {
        if (!nomeEvento) {
            logDebug('error', 'INKTRACK Erro: O nome do evento é obrigatório.');
            return;
        }

        if (typeof fbq !== 'function') {
            logDebug('warn', 'INKTRACK Aviso: fbq não está definido. O Pixel da Meta pode não estar carregado.');
        }

        const idEvento = gerarIdEvento();

        // 1. Disparo Client-Side (Navegador)
        if (typeof fbq === 'function') {
            const ehEventoPadrao = INKTRACK_CONFIG.eventosPadrao.includes(nomeEvento);
            const funcaoTrack = ehEventoPadrao ? 'track' : 'trackCustom';
            const parametrosCliente = { ...parametros, eventID: idEvento };

            logDebug('log', `INKTRACK (Cliente - ${funcaoTrack}): ${nomeEvento}`, parametrosCliente);
            fbq(funcaoTrack, nomeEvento, parametrosCliente);
        }

        // 2. Disparo Server-Side (via API de Conversões)
        const payloadServidor = {
            event_name: nomeEvento,
            event_id: idEvento,
            event_source_url: window.location.href,
            params: parametros,
        };

        // Usa sendBeacon se disponível para maior confiabilidade ao sair da página
        if (navigator.sendBeacon) {
            navigator.sendBeacon(INKTRACK_CONFIG.endpointApi, JSON.stringify(payloadServidor));
            logDebug('server', `INKTRACK (Servidor Beacon): ${nomeEvento}`, payloadServidor);
        } else {
            fetch(INKTRACK_CONFIG.endpointApi, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(payloadServidor),
                keepalive: true // Importante para requisições durante a saída da página
            }).then(response => {
                logDebug('server', `INKTRACK (Servidor Fetch): ${nomeEvento}`, payloadServidor);
            }).catch(error => {
                logDebug('error', 'INKTRACK (Servidor) Erro de Fetch:', error);
            });
        }
    };

    /**
     * Gerencia o rastreamento declarativo via atributos data-*.
     * @param {Event} e - O evento de clique.
     */
    const manipularCliqueRastreavel = function(e) {
        const elementoRastreavel = e.target.closest('[data-inktrack-event], [data-inktrack-events]');

        if (!elementoRastreavel) {
            return;
        }

        try {
            // Gerencia múltiplos eventos primeiro
            if (elementoRastreavel.dataset.inktrackEvents) {
                const eventosString = elementoRastreavel.dataset.inktrackEvents.replace(/'/g, '"');
                const arrayEventos = JSON.parse(eventosString);
                if (Array.isArray(arrayEventos)) {
                    arrayEventos.forEach(dadosEvento => {
                        if (dadosEvento.eventName) {
                            INKTRACK.track(dadosEvento.eventName, dadosEvento.params || {});
                        }
                    });
                }
            }
            // Gerencia um único evento
            else if (elementoRastreavel.dataset.inktrackEvent) {
                const nomeEvento = elementoRastreavel.dataset.inktrackEvent;
                let parametros = {};
                if (elementoRastreavel.dataset.inktrackParams) {
                    const parametrosString = elementoRastreavel.dataset.inktrackParams.replace(/'/g, '"');
                    parametros = JSON.parse(parametrosString);
                }
                INKTRACK.track(nomeEvento, parametros);
            }
        } catch (error) {
            logDebug('error', 'INKTRACK Erro: Não foi possível processar os atributos data. Verifique se o JSON é válido.', error);
        }
    };

    // Escuta por cliques em todo o documento
    document.addEventListener('click', manipularCliqueRastreavel, true);

})(window, document);
