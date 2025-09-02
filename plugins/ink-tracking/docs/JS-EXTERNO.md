# Documentação Técnica: `inktrack-externo.js`

Este documento fornece uma visão técnica detalhada do script `inktrack-externo.js`, projetado para operar de forma autônoma em sites de terceiros (como motores de reserva, plataformas de checkout, etc.) e se comunicar com a API da biblioteca INKTRACK no seu site WordPress.

## 1. Objetivo e Filosofia

O `inktrack-externo.js` foi criado para ser **robusto, autônomo e resiliente**. Sua principal missão é garantir que o rastreamento do usuário continue sem interrupções quando ele sai do seu domínio principal.

As filosofias centrais são:
*   **Nunca Perder a Identidade:** O script emprega múltiplas estratégias para encontrar, validar ou criar um identificador de usuário (`external_id`).
*   **Fail-Open (Falha Aberta):** Em caso de falha na comunicação com as APIs de verificação (como a de elegibilidade), o script assume uma postura otimista e continua o processo de rastreamento para não perder dados de conversão.
*   **Comunicação Centralizada:** Toda a comunicação é feita com os endpoints da API REST do seu site WordPress, que atua como o "cérebro" e o proxy para a API de Conversões da Meta.
*   **Enriquecimento Contínuo:** O script não apenas rastreia eventos, mas também enriquece ativamente o perfil do usuário no banco de dados do seu WordPress com informações valiosas (PII, cookies da Meta).

---

## 2. Configuração Inicial

Antes de injetar o script, é crucial configurar duas constantes no topo do arquivo:

```javascript
// Dentro de assets/js/inktrack-externo.js

// --- Configuração ---
const MODO_DEBUG = true; // Altere para false em produção
const PIXEL_ID = 'SEU_PIXEL_ID_AQUI'; // Insira o mesmo Pixel ID do WordPress
const URL_BASE_API = 'https://SEU-DOMINIO.com.br/wp-json/inktrack/v1'; // Altere esta linha!
```

*   `MODO_DEBUG`: Quando `true`, ativa logs detalhados e coloridos no console do navegador, essenciais para a depuração.
*   `PIXEL_ID`: O ID do seu Pixel da Meta. Deve ser o mesmo usado no site WordPress.
*   `URL_BASE_API`: A URL completa para a raiz da API INKTRACK no seu site WordPress.

---

## 3. O Fluxo de Execução (Passo a Passo)

Quando o script `inktrack-externo.js` é carregado em uma página, ele segue uma sequência lógica de inicialização (`inicializar()`):

### Passo 1: Verificação de Elegibilidade

*   **O que faz:** Realiza uma chamada `fetch` para o endpoint `/status/check-eligibility`.
*   **Como funciona:** O backend do WordPress (em `src/InkTrack.php`) usa essa chamada para verificar o `User-Agent` e o IP do visitante contra a lista de bots e o filtro geográfico.
*   **Resultado:**
    *   Se a API responder `{"eligible": false}`, o script exibe um aviso no console (se em modo debug) e **interrompe toda a sua execução**. Isso impede que bots ou tráfego indesejado sejam processados.
    *   Se a API falhar ou responder `{"eligible": true}`, a execução continua.

### Passo 2: Injeção do Pixel da Meta

*   **O que faz:** A função `injetarScriptMeta()` verifica se o objeto `window.fbq` já existe.
*   **Como funciona:** Se `fbq` não existir, ela cria o elemento `<script>` e o injeta no `<head>` da página, carregando o `fbevents.js` do Facebook. Isso garante que o pixel esteja disponível para as próximas etapas.

### Passo 3: Gerenciamento de Identidade do Usuário (O Coração do Script)

*   **O que faz:** A função `gerenciarIdentidadeUsuario()` é chamada para encontrar o `external_id` do visitante. Esta é a parte mais crítica e resiliente do script.
*   **Como funciona:** Ela busca o ID na seguinte ordem de prioridade:
    1.  **Parâmetro de URL (`ink_external_id`):** Se um ID válido (comprimento >= 30 caracteres) for encontrado na URL, ele é imediatamente enviado ao endpoint `/usuario/sincronizar` para validação. Se o backend o reconhecer, ele é usado.
    2.  **LocalStorage:** Se não houver um ID válido na URL, ele procura no `localStorage` do navegador pela chave `ink_external_id`.
    3.  **Cookie:** Se não encontrar no `localStorage`, ele procura por um cookie chamado `ink_uid`.
    4.  **Busca por IP (IP Lookup):** Se nenhuma identidade local for encontrada, o script faz uma chamada ao endpoint `/usuario/lookup-by-ip`. O backend então procura na tabela `wp_inktrack_users` pelo `external_id` mais recente associado ao IP do visitante atual.
    5.  **Geração:** Se todas as etapas acima falharem, o script gera um novo ID localmente (com o prefixo `uid.c.*` para indicar "criado no cliente") e o assume como a identidade do usuário.
*   **Persistência:** Assim que um ID é validado ou gerado, ele é salvo tanto no `localStorage` quanto em um cookie para garantir a persistência entre sessões e páginas.

### Passo 4: Sincronização e `fbq('init')`

*   **O que faz:** Com um `external_id` em mãos, a função `sincronizarUsuario()` é chamada (geralmente dentro do `gerenciarIdentidadeUsuario`).
*   **Como funciona:** Ela envia o `external_id` para o endpoint `/usuario/sincronizar`. O backend busca no banco de dados todos os dados de PII (e-mail, nome, etc.) e contextuais (`country`, `fbc`, `fbp`) associados àquele ID, **hasheia os dados de PII**, e retorna um objeto `userData` completo.
*   **Resultado:** O script recebe o objeto `userData` e o usa para inicializar o Pixel da Meta: `window.fbq('init', PIXEL_ID, userData);`. Isso realiza o Advanced Matching, informando à Meta quem é o usuário.

### Passo 5: Rastreamento de `PageView` e Sincronização de Cookies

*   Imediatamente após o `init`, o script chama `INKTRACK.track('PageView', ...)` para registrar a visualização de página.
*   Em seguida, a função `sincronizarCookiesMetaComBackend()` é executada. Ela lê os cookies `_fbc` e `_fbp` do navegador (que podem ter sido criados ou atualizados pelo `fbq('init')`) e, se forem diferentes dos que o servidor já conhecia, envia-os para o endpoint `/usuario/enriquecer`. Isso mantém o perfil do usuário no backend sempre atualizado.

### Passo 6: Configuração dos Ouvintes de Enriquecimento

*   Finalmente, a função `configurarOuvintesEnriquecimento()` é chamada. Ela adiciona um ouvinte de evento `blur` ao documento para observar os campos de formulário.

---

## 4. Funções Expostas e Uso

O script expõe um namespace global `INKTRACK` com funções que podem ser chamadas a partir de outros scripts injetados no site externo.

### `INKTRACK.track(eventName, params)`

Esta é a função principal para rastrear eventos de conversão.

*   **`eventName` (string):** O nome do evento (ex: `'Purchase'`, `'InitiateCheckout'`).
*   **`params` (object):** Um objeto com os parâmetros do evento (ex: `{ value: 123.45, currency: 'BRL' }`).

**Exemplo de uso na página de confirmação de compra:**

```html
<script>
    // Supondo que os detalhes da compra estão disponíveis em um objeto.
    const detalhesCompra = {
        value: 750.50,
        currency: 'BRL',
        num_items: 1,
        content_ids: ['suite-pres-01'],
        transaction_id: 'ORDER-12345'
    };

    // A função INKTRACK.track cuida de tudo:
    // 1. Gera um event_id único.
    // 2. Dispara o evento no navegador via `fbq`.
    // 3. Envia o evento para o servidor WordPress via API.
    INKTRACK.track('Purchase', detalhesCompra);
</script>
```

### Auto-Enriquecimento de Formulários

Essa funcionalidade é automática, mas depende da configuração correta dos seletores CSS.

*   **Como funciona:** A função `configurarOuvintesEnriquecimento()` no final do script contém um array de objetos, cada um com um `seletor` e uma `chave`.
*   **O que fazer:** Você deve ajustar os valores de `seletor` para que correspondam exatamente aos seletores CSS dos campos de e-mail, telefone, etc., no formulário do site externo.

**Exemplo de configuração de seletores:**

```javascript
// No final de assets/js/inktrack-externo.js
function configurarOuvintesEnriquecimento() {
    const campos = [
        // Ajuste estes seletores para o site externo!
        { seletor: 'input[id="GuestEmail"]', chave: 'em' },
        { seletor: 'input[id="GuestPhone"]', chave: 'ph' },
        { seletor: 'input[name="GuestFirstName"]', chave: 'fn' },
        { seletor: 'input[name="GuestLastName"]', chave: 'ln' }
    ];
    // ... (lógica do ouvinte)
}
```

Quando um usuário preenche um desses campos e clica fora dele, o script captura o valor, o normaliza (remove espaços e converte para minúsculas) e o envia **bruto** para o endpoint `/usuario/enriquecer`, que o armazena no banco de dados do WordPress.
