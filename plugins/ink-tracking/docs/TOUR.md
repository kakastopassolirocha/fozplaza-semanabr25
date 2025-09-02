# Tour Guiado pela Biblioteca INKTRACK v3.0

Bem-vindo a um tour detalhado sobre o funcionamento interno da biblioteca INKTRACK. Diferente do `README.md`, que foca em *como usar*, este documento foca em *como funciona*, seguindo a sequência de eventos desde a inicialização até o rastreamento cross-domain.

---

### Parte 1: A Inicialização no WordPress (O "Big Bang")

Tudo começa no arquivo `functions.php` do seu tema, no momento em que você adiciona a linha:

```php
require_once get_template_directory() . '/ink-tracking/ink-tracking.php';
```

1.  **Carregamento Inicial:** O arquivo `ink-tracking.php` é carregado. Ele faz duas coisas:
    *   Carrega o cérebro da operação, o arquivo `src/InkTrack.php`.
    *   Instancia a classe principal: `new \INKTRACK\src\InkTrack();`.
    *   Disponibiliza a função global `INKTRACK_get_external_id()`.

2.  **O Construtor `__construct()` é Disparado:** A criação do objeto `new InkTrack()` aciona o método construtor da classe. Ele executa duas tarefas essenciais:
    *   `$this->carregar_configuracao();`: Carrega todas as configurações (Pixel ID, Tokens, etc.) para uso na classe.
    *   `$this->configurar_hooks();`: "Pluga" a biblioteca no WordPress, agendando métodos para rodar em momentos específicos (`init`, `wp_head`, `wp_enqueue_scripts`, `rest_api_init`).

Neste ponto, a biblioteca está "viva" e pronta para agir.

---

### Parte 2: Instalação e Filtros (Os Guardiões)

Assim que o WordPress dispara o hook `init`, duas ações importantes acontecem antes de qualquer rastreamento.

1.  **Rotina de Instalação/Atualização:**
    *   O método `executar_rotina_instalacao()` é chamado.
    *   Ele verifica a versão da base de dados (`inktrack_db_version` no `wp_options`).
    *   Se a versão for antiga ou inexistente, ele chama a função `criar_tabela_personalizada()`, que usa a poderosa função `dbDelta()` do WordPress para criar ou **atualizar a estrutura da tabela `wp_inktrack_users` sem perder dados**.

2.  **Começa o Carregamento da Página (`wp_head`):**
    *   O WordPress agora chama o método `disparar_rastreamento_pageview()`, que orquestra toda a lógica de rastreamento.

3.  **Filtro 1: É Tráfego de Admin/Sistema?**
    *   A primeira verificação é se a requisição é para uma página de admin, uma chamada AJAX ou JSON. Se sim, a execução é interrompida.

4.  **Filtro 2: É um Bot? (O Guardião Silencioso)**
    *   A função `$this->eh_trafego_indesejado()` é chamada. Ela compara o `User-Agent` da requisição com uma lista interna de bots conhecidos (Googlebot, GPTBot, etc.). Se houver uma correspondência, a execução é interrompida.

5.  **Filtro 3: É de uma Região Relevante? (O Porteiro Geográfico)**
    *   A função `$this->eh_usuario_da_america_do_sul()` é chamada.
    *   Ela invoca `$this->obter_dados_geograficos()`, que faz uma consulta à API `ipinfo.io` usando o IP do visitante para obter o país e o continente. O resultado é salvo em cache para a requisição.
    *   Se o continente não for `SA` (América do Sul), a execução é interrompida.
    *   **Lógica "Fail-Open":** Se a API de geolocalização falhar, a verificação é ignorada e o rastreamento continua, priorizando a captura do evento.

**Resultado dos Filtros:** Se uma requisição for de um bot ou de fora da região, nenhum ID de evento é gerado, nenhum cookie é criado e nenhuma chamada é feita à API da Meta. O tráfego indesejado é completamente ignorado.

---

### Parte 3: Rastreamento de PageView no WordPress (A Mágica Automática)

Se a requisição passar por **todos os filtros**, o rastreamento de `PageView` acontece em duas frentes, de forma otimizada:

1.  **Geração do ID de Evento:** Um ID de evento único é gerado para o `PageView` (ex: `srv.12345.abcde`).

2.  **Ação #1: Lado do Cliente (Client-Side) - Rápido e Imediato:**
    *   `adicionar_script_base_meta_pixel()` é chamado.
    *   Ele busca os dados de PII (e-mail, nome, etc.) que já possam existir no banco de dados para aquele visitante.
    *   Os dados de PII são **hasheados**, e junto com o `external_id` e o `country`, são injetados diretamente na chamada `fbq('init', 'PIXEL_ID', { ... });`. Isso enriquece o `init` com Advanced Matching desde o primeiro momento.
    *   O script do Pixel da Meta é injetado.
    *   O evento `fbq('track', 'PageView', { eventID: 'srv.12345.abcde' });` é disparado no navegador.

3.  **Ação #2: Lado do Servidor (Server-Side) - Robusto e Completo:**
    *   `disparar_pageview_servidor()` é chamado.
    *   Ele coleta um conjunto completo de dados contextuais do visitante (`ip`, `user_agent`, `fbc`, `fbp`, `country`).
    *   Ele chama `salvar_dados_visitante()`, que usa `REPLACE INTO` para inserir ou atualizar o registro completo do visitante na tabela `wp_inktrack_users`, garantindo que os dados mais recentes estejam sempre salvos.
    *   Ele prepara um payload de evento para a API de Conversões, combinando os dados contextuais com os dados de PII (que são novamente hasheados aqui).
    *   O payload é enviado para a API da Meta usando o mesmo `eventID` do cliente, permitindo a deduplicação.

---

### Parte 4: O Script Externo (A Inteligência Autônoma)

Este é o cenário quando o usuário navega para um site de terceiros (ex: motor de reservas) onde o script `inktrack-externo.js` foi injetado.

1.  **O Script Inicia: Verificação de Elegibilidade**
    *   A primeira coisa que o script faz é uma chamada para o endpoint `/status/check-eligibility` do seu WordPress.
    *   Este endpoint executa os mesmos filtros de **bot** e **geográfico**.
    *   Se o visitante não for elegível, a API retorna `{"eligible": false}` e o script **interrompe toda a sua execução** ali mesmo.

2.  **Injeção do Pixel:** Se for elegível, o script injeta o `fbevents.js` (o Pixel da Meta) na página.

3.  **Gerenciamento de Identidade (O Coração do Script):**
    *   O script precisa desesperadamente de um `external_id`. Ele tenta encontrá-lo nesta ordem:
        a.  **URL:** Procura o `ink_external_id` nos parâmetros da URL. Se encontrar, ele o envia ao endpoint `/usuario/sincronizar` para validação.
        b.  **LocalStorage:** Se não estiver na URL, procura no `localStorage` do navegador.
        c.  **Cookie:** Se não estiver no `localStorage`, procura em um cookie.
        d.  **Busca por IP:** Se não encontrar nenhuma identidade local, ele faz uma chamada ao endpoint `/usuario/lookup-by-ip`, perguntando ao servidor: "Você tem algum `external_id` recente para este IP?".
        e.  **Geração:** Se tudo falhar, ele gera um novo ID (`uid.c.*`).
    *   Qualquer ID encontrado ou gerado é persistido no `localStorage` e em cookies para futuras visitas.

4.  **Sincronização e `fbq('init')`:**
    *   O ID final é enviado ao endpoint `/usuario/sincronizar`.
    *   O servidor recebe o ID, busca no banco todos os dados de PII e contextuais associados a ele, **hasheia** os dados de PII, e os retorna para o script.
    *   O script externo recebe esses dados e os usa para fazer a chamada `fbq('init', 'PIXEL_ID', { ... });`, enriquecendo o rastreamento com Advanced Matching.
    *   Imediatamente após o `init`, ele dispara o evento `PageView`.

5.  **Sincronização de Cookies (`fbc`/`fbp`):**
    *   O `fbq('init')` pode ter criado ou atualizado os cookies `_fbc` e `_fbp` no navegador.
    *   O script compara os valores atuais desses cookies com os que vieram do servidor. Se forem diferentes, ele os envia para o endpoint `/usuario/enriquecer` para garantir que o banco de dados do servidor tenha sempre os cookies da Meta mais recentes.

---

### Parte 5: Enriquecimento e Conversão (Fechando o Ciclo)

1.  **Enriquecimento Automático de Dados:**
    *   O script "ouve" o preenchimento de campos de formulário (e-mail, telefone, nome).
    *   Quando um usuário preenche um campo e sai dele, o script captura o valor **BRUTO** (ex: "fulano@email.com").
    *   Ele envia este dado bruto para o endpoint `/usuario/enriquecer`.
    *   O servidor recebe o dado e o salva na coluna correspondente (`em`, `ph`, etc.) na tabela `wp_inktrack_users`, associado ao `external_id` do visitante. Nenhum hashing acontece aqui.

2.  **Rastreamento de Evento de Conversão (Ex: `Purchase`):**
    *   Na página de confirmação de compra, você chama `INKTRACK.track('Purchase', { ... });`.
    *   O script gera um novo ID de evento (ex: `externo.67890.fghij`).
    *   **Ação Client-Side:** Ele dispara o evento no navegador: `fbq('trackSingle', 'PIXEL_ID', 'Purchase', { ..., eventID: 'externo.67890.fghij' });`.
    *   **Ação Server-Side:** Em paralelo, ele monta um payload JSON e o envia para o endpoint `/evento`. O payload contém o nome do evento, os parâmetros, o `eventID` e, crucialmente, o `external_id` do usuário.

3.  **O Servidor Finaliza o Trabalho:**
    *   O endpoint `/evento` recebe a requisição.
    *   Ele usa o `external_id` para buscar todos os dados enriquecidos (PII, `fbc`, `fbp`, `country`, etc.) do banco de dados.
    *   Ele combina esses dados com os dados da requisição atual.
    *   **Agora, e somente agora**, ele pega os dados de PII brutos, os **normaliza e hasheia**.
    *   Ele envia o evento completo e enriquecido para a API de Conversões da Meta, usando o mesmo `eventID` para garantir a deduplicação.

Este ciclo garante que, independentemente de onde a conversão ocorra, o evento enviado ao servidor seja o mais rico possível, maximizando a Qualidade de Correspondência de Eventos.
