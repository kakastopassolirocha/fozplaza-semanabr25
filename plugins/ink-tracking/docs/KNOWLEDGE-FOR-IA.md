# Base de Conhecimento INKTRACK para IA

## 1. Visão Geral do Projeto

**Nome do Projeto:** INKTRACK
**Versão Atual:** 3.0
**Propósito Principal:** Uma biblioteca de rastreamento avançado para WordPress, focada em maximizar a qualidade e a confiabilidade dos dados enviados para a API de Conversões da Meta (Facebook/Instagram), com ênfase em rastreamento cross-domain e enriquecimento de dados do usuário.

**Tecnologias:**
*   **Backend:** PHP (orientado a objetos, encapsulado na classe `\INKTRACK\src\InkTrack`).
*   **Frontend (WordPress):** JavaScript (`assets/js/inktrack.js`), interagindo com o DOM e o `fbq()`.
*   **Frontend (Externo):** JavaScript puro e autônomo (`assets/js/inktrack-externo.js`).
*   **Persistência:** Tabela SQL personalizada no banco de dados do WordPress (`wp_inktrack_users`).
*   **Comunicação:** API REST do WordPress.

---

## 2. Arquitetura e Componentes Principais

A biblioteca é composta por 4 arquivos principais que trabalham em conjunto:

1.  **`ink-tracking.php` (Inicializador):**
    *   Ponto de entrada do plugin.
    *   Carrega a classe principal `InkTrack.php`.
    *   Instancia a classe: `new \INKTRACK\src\InkTrack();`, que ativa toda a funcionalidade.
    *   Expõe a função global `INKTRACK_get_external_id()` para uso em temas.

2.  **`src/InkTrack.php` (O Cérebro - Backend):**
    *   **Classe principal:** `InkTrack`.
    *   **Configuração:** O método `carregar_configuracao()` é o centro nevrálgico onde todas as chaves de API, tokens e configurações (como modo debug) são definidas.
    *   **Hooks do WordPress:** `configurar_hooks()` integra a biblioteca com o ciclo de vida do WordPress (`init`, `wp_head`, `rest_api_init`, etc.).
    *   **Lógica de Rastreamento Server-Side:** Orquestra o envio de eventos para a API de Conversões da Meta.
    *   **Manipulação de Banco de Dados:** Contém a lógica para criar, atualizar (`dbDelta`) e interagir com a tabela `wp_inktrack_users` (usando `REPLACE INTO` para salvar dados).
    *   **Provedor de API REST:** Define e gerencia todos os endpoints que servem de ponte de comunicação para os scripts frontend.

3.  **`assets/js/inktrack.js` (Script do Site Principal):**
    *   Opera no site WordPress principal.
    *   **Rastreamento Declarativo:** Adiciona um ouvinte de clique global que procura por atributos `data-inktrack-event` e `data-inktrack-events` para rastrear interações sem a necessidade de código JS customizado.
    *   **Rastreamento Programático:** Expõe a função global `INKTRACK.track(eventName, params)` para disparar eventos dinamicamente.
    *   **Comunicação:** Envia os dados do evento para o endpoint `/evento` da API do WordPress usando `navigator.sendBeacon` (preferencialmente) ou `fetch`.

4.  **`assets/js/inktrack-externo.js` (Script de Sítios Externos):**
    *   **Autônomo e Robusto:** Projetado para ser injetado em qualquer site de terceiros.
    *   **Gerenciamento de Identidade:** Sua função mais crítica. Ele encontra ou cria um `external_id` usando uma hierarquia de métodos (URL -> LocalStorage -> Cookie -> IP Lookup -> Geração) para garantir a continuidade do rastreamento.
    *   **Comunicação Exclusiva com a API WP:** Ele **NÃO** envia dados diretamente para a Meta. Toda a comunicação (verificação de elegibilidade, sincronização de usuário, enriquecimento, envio de eventos) é feita com os endpoints da API REST do WordPress.
    *   **Exposição de Função:** Também expõe `INKTRACK.track(eventName, params)` para rastrear conversões no site externo.

---

## 3. Fluxo de Dados e Conceitos Chave

### 3.1. O Identificador Único: `external_id`

*   É a espinha dorsal do rastreamento cross-domain.
*   Formato: `uid.[prefixo].[timestamp].[uuid]`. O prefixo `srv` indica criado no servidor, e `uid.c` indica criado no cliente (script externo).
*   **Persistência:**
    *   No WordPress: É armazenado em um cookie HTTPOnly e seguro (`INKTRACK_external_id`).
    *   No site externo: É armazenado em `localStorage` e em um cookie regular (`ink_uid`) para máxima resiliência.

### 3.2. A Tabela `wp_inktrack_users`

*   **Fonte da Verdade:** Armazena o perfil de cada visitante identificado.
*   **Dados Brutos:** Crucialmente, os dados de PII (Personally Identifiable Information) como e-mail (`em`), telefone (`ph`), nome (`fn`) e sobrenome (`ln`) são armazenados em seu **formato original, não hasheado**.
*   **Hashing "Just-in-Time":** O hashing SHA-256 desses dados PII ocorre **APENAS** no backend (`src/InkTrack.php`) no exato momento em que os dados são preparados para serem enviados à API da Meta. Isso cumpre as exigências da Meta sem perder a utilidade dos dados brutos para outros fins.
*   **Outros Campos:** Também armazena dados contextuais como `ip`, `user_agent`, `country`, e os cookies da Meta `fbc` e `fbp`.

### 3.3. Os Endpoints da API REST (`/wp-json/inktrack/v1`)

*   **/evento** (`POST`):
    *   Recebe payloads de eventos dos scripts JS (tanto do principal quanto do externo).
    *   Busca todos os dados enriquecidos do usuário no banco de dados usando o `external_id`.
    *   Realiza o hashing Just-in-Time.
    *   Envia o evento completo para a API de Conversões da Meta.

*   **/usuario/enriquecer** (`POST`):
    *   Usado pelo script externo para enviar dados PII **brutos** capturados de formulários ou os cookies `fbc`/`fbp` atualizados.
    *   A função do backend simplesmente pega esses dados e os salva na tabela `wp_inktrack_users` usando `REPLACE INTO`.

*   **/usuario/sincronizar** (`POST`):
    *   Peça central para o script externo.
    *   Recebe um `external_id`.
    *   Valida se o usuário existe. Se não, cria um novo registro com os dados contextuais da requisição.
    *   Busca todos os dados PII e contextuais associados a esse ID, **hasheia o PII**, e retorna o objeto `userData` completo, pronto para ser usado no `fbq('init')`.

*   **/status/check-eligibility** (`GET`):
    *   Permite que o script externo verifique se um visitante deve ser rastreado.
    *   Executa a lógica de `eh_trafego_indesejado()` (bot) e `eh_usuario_da_america_do_sul()` (geo).
    *   Retorna `{"eligible": true/false}`.

*   **/usuario/lookup-by-ip** (`GET`):
    *   Último recurso para o script externo encontrar uma identidade.
    *   Busca na DB pelo `external_id` mais recente associado ao IP do visitante.
    *   Retorna `{"success": true, "external_id": "..."}` se encontrar.

---

## 4. Lógica de Modificações e Manutenção

*   **Para adicionar um novo filtro de bot:** Edite o array `$lista_bots` no método `eh_trafego_indesejado()` em `src/InkTrack.php`.
*   **Para alterar a região geográfica:** Modifique a lógica no método `eh_usuario_da_america_do_sul()` em `src/InkTrack.php`.
*   **Para adicionar uma nova coluna na tabela de usuários:**
    1.  Adicione a coluna na definição SQL dentro do método `criar_tabela_personalizada()` em `src/InkTrack.php`.
    2.  Incremente o número da versão no método `executar_rotina_instalacao()` (ex: de `'2.2'` para `'2.3'`). O `dbDelta` cuidará da atualização da tabela.
    3.  Atualize o método `salvar_dados_visitante()` para incluir a nova coluna e seu formato.
*   **Para depurar:**
    *   **Backend:** Mude `'debug' => true` em `carregar_configuracao()` e monitore o `debug.log` do PHP.
    *   **Frontend Externo:** Mude `const MODO_DEBUG = true;` em `inktrack-externo.js` e monitore o console do navegador.

Esta base de conhecimento deve fornecer a qualquer IA uma compreensão profunda e abrangente da arquitetura, fluxo de dados e filosofia do projeto INKTRACK, permitindo a colaboração efetiva em futuras manutenções e evoluções.
