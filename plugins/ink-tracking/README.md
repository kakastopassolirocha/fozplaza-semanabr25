# Manual de Uso: Biblioteca INKTRACK v3.0

## 1. Visão Geral

A INKTRACK é uma biblioteca robusta para WordPress projetada para resolver quatro desafios principais do marketing digital moderno:

1.  **Rastreamento de Eventos Confiável (Server-Side e Client-Side):** Garante que os eventos da Meta (Facebook/Instagram) sejam registrados da forma mais confiável possível, usando rastreamento duplo (navegador e servidor) com deduplicação automática de eventos.

2.  **Ponte de Dados Cross-Domain com Armazenamento Seguro:** Atua como uma ponte de dados entre o seu site WordPress e sistemas de terceiros (como motores de reserva), salvando informações de usuários de forma segura em uma tabela dedicada no banco de dados.
    *   **Identificador Único e Persistente:** Um ID de visitante (`ink_external_id`) é criado e persistido de forma inteligente através de cookies e `localStorage`, permitindo rastrear a jornada do usuário entre domínios. O sistema possui uma lógica robusta de recuperação de ID, incluindo busca por IP.
    *   **Captura de Dados Brutos:** Captura dados de PII (e-mail, telefone, nome) em seu formato original (bruto), vindos de formulários no site principal ou em sistemas externos.
    *   **Banco de Dados Dedicado e Abrangente:** Os dados são salvos em uma **tabela personalizada (`wp_inktrack_users`)**, que armazena um perfil completo do visitante, incluindo dados de PII, geolocalização (`country`), dados técnicos (`ip`, `user_agent`) e cookies de rastreamento da Meta (`fbc`, `fbp`). A tabela é totalmente isolada, garantindo performance e segurança.
    *   **Hashing "Just-in-Time":** A transformação dos dados em hash (SHA256) ocorre **apenas no momento do envio para a Meta API**, nunca no banco de dados. Isso garante que você retenha os dados originais para outros usos (com a devida permissão) e cumpra os requisitos da Meta.

3.  **Filtro Inteligente de Tráfego:** Identifica e ignora ativamente o tráfego de bots, crawlers e visitantes de regiões geográficas fora do seu interesse.
    *   **Anti-Bot:** Bloqueia uma lista extensa de bots conhecidos (Google, Ahrefs, Semrush, GPTBot, etc.).
    *   **Filtro Geográfico:** Utiliza uma API de geolocalização para restringir o rastreamento a visitantes de uma região específica (configurado para a América do Sul), economizando recursos e limpando sua base de dados.

4.  **Enriquecimento Automático de Dados:** Melhora significativamente a qualidade da correspondência de eventos (Event Match Quality) da Meta.
    *   **Dados Geográficos:** Envia o código do país do usuário (`country`) junto com cada evento.
    *   **Sincronização de Cookies (`fbc`/`fbp`):** O script externo sincroniza de forma inteligente os cookies de clique (`_fbc`) e de navegador (`_fbp`) entre o cliente e o servidor, garantindo que esses importantes identificadores estejam sempre presentes nos eventos enviados ao servidor.

A biblioteca é centralizada em uma classe PHP (`src/InkTrack.php`) e oferece dois scripts JavaScript otimizados para cada ambiente de uso.

---

## 2. Instalação

1.  **Copie os Arquivos:** Copie toda a pasta `ink-tracking/` para o diretório do seu tema WordPress (`/wp-content/themes/seu-tema/`).
2.  **Inclua a Biblioteca:** No arquivo `functions.php` do seu tema, adicione a seguinte linha:
    ```php
    require_once get_template_directory() . '/ink-tracking/ink-tracking.php';
    ```
3.  **Criação e Atualização Automática da Tabela:** A tabela `wp_inktrack_users` será **criada ou atualizada automaticamente** no primeiro carregamento do site. A biblioteca gerencia as versões da tabela (usando `dbDelta`), adicionando colunas conforme necessário em futuras atualizações, sem perda de dados.

4.  **Configure suas Credenciais:** Abra `src/InkTrack.php` e insira suas credenciais no método `carregar_configuracao()`:
    ```php
// ... dentro da classe InkTrack ...
private function carregar_configuracao() {
    $this->configuracao = [
        'debug' => true, // ATIVE (true) PARA DESENVOLVIMENTO, DESATIVE (false) PARA PRODUÇÃO
        'pixel_id' => 'SEU_PIXEL_ID_AQUI',
        'api_token' => 'SEU_TOKEN_DA_API_DE_CONVERSOES_AQUI',
        'ipinfo_token' => 'SEU_TOKEN_DA_IPINFO.IO_AQUI',
        'meta_api_versao' => 'v21.0', // Versão da API de Conversões
        // ... outras configurações ...
    ];
}
// ...
    ```

---

## 3. Como Usar (No Tema WordPress)

Para rastrear a maioria das interações no seu site, você **não precisa escrever JavaScript**. Tudo é feito de forma declarativa, diretamente no HTML.

### Rastreando um Único Evento

Use o atributo `data-inktrack-event` em qualquer elemento clicável (link, botão, div, etc.).

**Exemplo: Rastrear um clique no botão de contato.**
```html
<a href="/contato" data-inktrack-event="Contact">
    Fale Conosco
</a>
```

### Rastreando um Evento com Parâmetros

Use `data-inktrack-params` para enviar dados junto com o evento. O valor deve ser um JSON válido. Aspas duplas são o padrão, mas o script também aceita aspas simples.

```html
<button
    data-inktrack-event="AddToCart"
    data-inktrack-params='{
        "content_name": "Suíte Presidencial",
        "content_ids": ["suite-pres-01"],
        "content_type": "product",
        "value": 750.50,
        "currency": "BRL"
    }'>
    Adicionar ao Carrinho
</button>
```

### Rastreando Múltiplos Eventos em um Clique

Use `data-inktrack-events` (no plural) com um array de objetos JSON para disparar vários eventos de uma só vez.

```html
<button
    data-inktrack-events='[
        {
            "eventName": "InitiateCheckout",
            "params": { "currency": "BRL", "value": 750.50 }
        },
        {
            "eventName": "CustomEvent_CheckoutStarted",
            "params": { "step": "initial" }
        }
    ]'>
    Finalizar Compra
</button>
```

### Rastreamento Programático (Avançado)

Se precisar disparar um evento dinamicamente via JavaScript, use a função global `INKTRACK.track()`.

```javascript
// Exemplo: Disparar um evento de busca
document.getElementById('search-form').addEventListener('submit', function() {
    const query = document.getElementById('search-input').value;
    // A função INKTRACK.track cuida do envio client-side e server-side.
    INKTRACK.track('Search', { search_string: query });
});
```

Ou no elemento HTML no `onClick`:
```html
<a href="#" class="hover:text-accent-400 transition-colors" onclick="INKTRACK.track('Teste', {
                    data: 'bla bla bla',
                    value: 750.50,
                    currency: 'BRL'
                })">
```

### Obtendo o ID do Visitante (Avançado)

A biblioteca expõe uma função global `INKTRACK_get_external_id()` no PHP para que você possa recuperar o identificador único do visitante atual para integrações personalizadas.

```php
// No seu tema ou plugin:
$visitor_id = '';
if (function_exists('INKTRACK_get_external_id')) {
    $visitor_id = INKTRACK_get_external_id();
}
// Agora você pode usar $visitor_id
```

---

## 4. Como Usar (No Site Externo - Ex: Omnibees)

O script `assets/js/inktrack-externo.js` foi projetado para ser injetado em sites de terceiros. Ele é autônomo e inteligente.

### Passo 1: Passar o Identificador do Usuário (Automático)

Quando o usuário é redirecionado do seu site para o site externo, a biblioteca **automaticamente** anexa o identificador único (`ink_external_id`) como um parâmetro na URL. Você só precisa garantir que os links para o sistema externo não removam parâmetros de URL.

**Exemplo de URL de redirecionamento que será gerada:**
`https://booking.omnibees.com/fozplaza?algum_parametro=1&ink_external_id=uid.1678886400.a1b2c3d4e5f6`

### Passo 2: Configurar e Injetar o Script Externo

No painel de administração do site externo (ex: Omnibees), injete o script `inktrack-externo.js` em todas as páginas.

**Importante:** Você precisa **alterar a URL base da API e o Pixel ID** dentro do arquivo `assets/js/inktrack-externo.js` para apontar para o seu domínio e seu pixel.

```javascript
// Dentro de assets/js/inktrack-externo.js

// --- Configuração ---
const MODO_DEBUG = true; // Altere para false em produção
const PIXEL_ID = 'SEU_PIXEL_ID_AQUI'; // Insira o mesmo Pixel ID do WordPress
const URL_BASE_API = 'https://SEU-DOMINIO.com.br/wp-json/inktrack/v1'; // Altere esta linha!
```

Depois de alterar, injete o script no site externo:
```html
<!-- Coloque no <head> ou no final do <body> -->
<script src="https://SEU-DOMINIO.com.br/wp-content/themes/SEU-TEMA/ink-tracking/assets/js/inktrack-externo.js" async defer></script>
```

### Como o Script Externo Gerencia a Identidade?

O script `inktrack-externo.js` foi desenhado para nunca perder o rastro do usuário. Ele busca a identidade na seguinte ordem de prioridade:
1.  **URL:** Procura pelo `ink_external_id` na URL. Se encontrar, valida-o com o seu servidor.
2.  **LocalStorage:** Se não achar na URL, procura no `localStorage` do navegador.
3.  **Cookie:** Se não achar no `localStorage`, procura em um cookie.
4.  **Busca por IP:** Se nenhuma identidade local for encontrada, ele pergunta à sua API se já existe um ID associado ao IP do visitante.
5.  **Geração:** Como último recurso, ele gera um novo ID, garantindo que o rastreamento sempre aconteça.

### Passo 3: Rastrear Eventos de Conversão

O script externo expõe a função global `INKTRACK.track()`. Você precisará usar a capacidade do site externo de injetar scripts inline para chamar esta função nos momentos certos (ex: na página de confirmação de compra).

**Exemplo: Rastrear uma compra na página de confirmação.**
```html
<!-- Script a ser injetado na página de "Reserva Confirmada" -->
<script>
    // Os detalhes da compra (valor, etc.) geralmente estão disponíveis
    // em um objeto JavaScript ou variáveis na página de confirmação.
    // Este é um exemplo ilustrativo.
    const purchaseDetails = {
        value: 750.50,
        currency: 'BRL',
        num_items: 1,
        content_ids: ['suite-pres-01'],
        transaction_id: 'ORDER-12345' // Opcional, mas recomendado
    };

    // Dispara o evento de compra para o nosso servidor.
    // A função INKTRACK.track cuida de tudo: envia o evento para o pixel (client-side)
    // e também para a API do seu WordPress (server-side).
    INKTRACK.track('Purchase', purchaseDetails);
</script>
```

### Funcionalidade de Auto-Enriquecimento de Dados

Esta é uma das funcionalidades mais poderosas. O script `inktrack-externo.js` já está pré-configurado para "ouvir" o preenchimento de campos comuns em formulários de checkout.

*   **Como funciona:** Quando o usuário preenche campos como nome (`first_name`), sobrenome (`last_name`), e-mail (`email`) ou telefone (`phone`) e sai do campo (evento `blur`), o script faz o seguinte:
    1.  Captura o valor.
    2.  Normaliza o valor (remove espaços, etc.).
    3.  Envia o dado **BRUTO** (não hasheado) para o endpoint de enriquecimento do seu WordPress, que o associa ao `ink_external_id` do usuário e o armazena no banco de dados.

*   **O que você precisa fazer:** Apenas garantir que os seletores CSS no final do arquivo `inktrack-externo.js` correspondem aos seletores dos campos do formulário no site externo.

    ```javascript
    // No final de assets/js/inktrack-externo.js
    function configurarOuvintesEnriquecimento() {
        const campos = [
            // Ajuste estes seletores para o site externo!
            { seletor: 'input[name*="email"]', chave: 'em' },
            { seletor: 'input[name*="phone"]', chave: 'ph' },
            { seletor: 'input[name*="first_name"]', chave: 'fn' },
            { seletor: 'input[name*="last_name"]', chave: 'ln' }
        ];
        // ... (lógica do ouvinte)
    }
    ```

---

## 5. Modo de Depuração

Para facilitar a verificação e o desenvolvimento, a biblioteca possui um modo de depuração integrado.

### Ativando a Depuração

1.  **No WordPress (Lado do Servidor e Cliente):**
    *   Abra o arquivo `src/InkTrack.php` e altere a chave `debug` no método `carregar_configuracao()`:
        *   `'debug' => true,`: Ativa o modo de depuração.
        *   `'debug' => false,`: Desativa o modo de depuração (recomendado para produção).

2.  **No Site Externo:**
    *   Edite o arquivo `assets/js/inktrack-externo.js` diretamente e altere a constante no topo: `const MODO_DEBUG = true;`.

### O que acontece quando a depuração está ativa?

1.  **Logs do Servidor (PHP):**
    *   A biblioteca registrará informações detalhadas no log de erros do seu servidor (geralmente um arquivo `debug.log` ou `error.log` na pasta `wp-content`).
    *   Você verá mensagens sobre:
        *   Quando um visitante é ignorado (e por qual filtro: bot ou geo).
        *   O payload exato que está sendo enviado para a API da Meta.
        *   A resposta completa (sucesso ou erro) recebida da API da Meta.
        *   Sincronização, enriquecimento e salvamento de dados do visitante no banco de dados.
        *   Consultas à API de GeoIP.

2.  **Logs do Navegador (JavaScript):**
    *   No console de ferramentas do desenvolvedor do seu navegador, você verá mensagens detalhadas e coloridas sobre:
        *   Eventos sendo disparados pelo cliente (`fbq`).
        *   Payloads sendo enviados para a API do seu site (`sendBeacon` ou `fetch`).
        *   Todo o ciclo de vida da identidade do usuário no script externo (de onde o ID foi pego, se foi validado, etc.).
        *   Avisos e erros.

---

## 6. Arquitetura e Manutenção

*   **Classe Principal (`src/InkTrack.php`):** Contém toda a lógica PHP.
    *   `carregar_configuracao()`: Ponto central para todas as credenciais e chaves de configuração.
    *   `configurar_hooks()`: Centraliza todos os `add_action`.
    *   `disparar_rastreamento_pageview()`: Orquestra os filtros e o rastreamento de PageView no carregamento da página.
    *   `executar_rotina_instalacao()`: Rotina que roda no `init` para verificar a versão da tabela e atualizá-la se necessário usando `dbDelta`.
    *   `obter_dados_geograficos()`: Busca e armazena em cache os dados de geolocalização do usuário.
    *   `eh_usuario_da_america_do_sul()`: Filtra o rastreamento por continente.
    *   `eh_trafego_indesejado()`: Filtra o tráfego de bots.
    *   `salvar_dados_visitante()`: Método central que usa `REPLACE INTO` para inserir/atualizar todos os dados do visitante na tabela personalizada.
    *   `preparar_dados_para_meta()`: Orquestrador que normaliza e depois hasheia os dados do usuário "Just-in-Time" antes de enviar à Meta.
    *   **Endpoints da API:**
        *   `manipular_requisicao_evento()`: Recebe eventos do JS (principal ou externo) e os envia para a API de Conversões.
        *   `manipular_requisicao_enriquecimento_usuario()`: Recebe os dados brutos do site externo e os salva no banco.
        *   `manipular_requisicao_sincronizacao()`: Valida um `external_id` vindo do script externo e retorna os dados de usuário (`userData`) para o `fbq('init')`.
        *   `manipular_verificacao_elegibilidade()`: Permite ao script externo verificar se o rastreamento deve prosseguir.
        *   `manipular_lookup_por_ip()`: Permite ao script externo encontrar um ID de usuário com base no IP.

*   **Tabela Personalizada (`wp_inktrack_users`):**
    *   Uma tabela totalmente independente que armazena um perfil completo de cada visitante, incluindo `external_id`, dados de PII não hasheados (`em`, `ph`, `fn`, `ln`), `country`, `ip`, `user_agent`, e os cookies `fbc` e `fbp`.

*   **Scripts JavaScript:**
    *   `assets/js/inktrack.js`: Script para o site WordPress. Lida com rastreamento declarativo (`data-*`) e programático (`INKTRACK.track()`).
    *   `assets/js/inktrack-externo.js`: Script autônomo e robusto para sites de terceiros. Gerencia a identidade do usuário, injeta o pixel, e se comunica com a API do WordPress.