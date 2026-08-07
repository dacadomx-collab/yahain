---
modulo: MOD_OPERADOR_COGNITIVO_OMNICANAL
nombre: Operador Cognitivo Omnicanal (AI Runtime Operator)
tipo: Blueprint de Construcción — Checklist Táctico
alcance: Genérico / Agnóstico de Stack Cliente (WordPress, HTML puro, React, o cualquier lenguaje)
nucleo_inferencia: PHP 8.x / MariaDB (servidor DCD LABS)
clasificacion: Molde Reutilizable — Santuario_Genesis
version: 1.0
fecha: 2026-07-22
autoridad: Arquitecto (DCD LABS)
tono: Calma Ejecutiva (Executive Calm)
---

# MOD_OPERADOR_COGNITIVO_OMNICANAL

> Este documento es la fuente de verdad para construir, desde cero, el módulo de Operador Cognitivo Omnicanal en cualquier proyecto clonado del Santuario_Genesis. No es un chatbot: es un **AI Runtime Operator** — un motor de inferencia centralizado que atiende múltiples canales de entrada (WhatsApp, Telegram, Widget Web) bajo un único contrato de datos, sin exponer nunca la lógica de negocio, las llaves de API ni los prompts del sistema al servidor del cliente.

---

## 1. Filosofía y Arquitectura

### 1.1 Principio rector: Fricción Cero Operativa

El aislamiento de la Propiedad Intelectual (IP) es la premisa de diseño, no un añadido posterior. El conocimiento transaccional (archivos `.md`) puede vivir físicamente en el servidor del cliente, pero la inferencia, las llaves maestras y los prompts del sistema residen exclusivamente en el motor central (DCD LABS). El cliente nunca se conecta directamente a un proveedor de LLM: se conecta únicamente a un Gateway propio, autenticado criptográficamente.

### 1.2 Matriz de Aislamiento (Proxy / Puente)

| Componente | Ubicación Física | Responsabilidad |
|---|---|---|
| Frontend Widget / Webhook de Redes | Servidor del Cliente / Meta / Telegram | Captura la intención del usuario, muestra los mensajes. Interfaz agnóstica de stack. |
| Base de Conocimiento (`.md`) | Servidor del Cliente | Contiene la verdad del negocio del cliente. Consumida solo por el puente local. |
| Puente Proxy Local (PHP, ligero) | Servidor del Cliente | Inyecta la firma HMAC, lee el `.md` local con caché de memoria, dispara la llamada saliente hacia el Gateway central. |
| Motor Central de Inferencia | Servidor Linux (núcleo) | Valida la firma HMAC, aplica RAG sobre el `.md`, inyecta la personalidad del proyecto, despacha al LLM y retorna JSON. |

El puente del cliente **no razona**: solo empaqueta, firma y reenvía. Toda decisión cognitiva ocurre del otro lado del túnel.

### 1.3 Contrato JSON Unificado — Omnichannel Canonical Message Contract (OCMC)

Independientemente del canal de origen, todo evento entrante debe normalizarse a esta forma antes de tocar el motor de inferencia:

```json
{
  "ocmc_version": "1.0",
  "channel": "whatsapp | telegram | web_widget",
  "channel_message_id": "id_original_del_proveedor",
  "tenant_slug": "slug_del_proyecto_cliente",
  "contact": {
    "external_id": "identificador_en_el_canal_origen",
    "display_name": "nombre_visible_o_null"
  },
  "session_id": "uuid_de_sesion_omnicanal",
  "message": {
    "type": "text | image | audio | document | interactive",
    "text": "contenido_normalizado_o_null",
    "media_url": "url_o_null"
  },
  "received_at": "ISO8601_UTC",
  "raw_payload_ref": "referencia_opcional_al_payload_crudo_para_auditoria"
}
```

El normalizador de cada canal (WhatsApp Cloud API, Telegram Bot API, Widget Web) es responsable de producir este contrato exacto. El motor central de inferencia nunca conoce el formato nativo de cada proveedor — solo consume OCMC.

---

## 2. Checklist de Ejecución — Fases Operativas

> Regla de avance: ninguna fase se marca cerrada sin verificación funcional. No saltar fases (Mandamiento de Ejecución Determinística).

### Fase 0 — Aprovisionamiento Fricción Cero (Onboarding en 3 Clics)

> Esta fase precede a la Fase 1: es el proceso interno de AXON/ACADEP para generar y entregar el artefacto que el cliente instalará. Ocurre enteramente en el Dashboard TDTM — el cliente no ve, ni necesita entender, nada de lo descrito aquí.

**Nota de gobernanza sobre el `shared_secret` generado:** el secreto descrito en esta fase es un **token de túnel escopado por tenant** (identifica y autentica a *ese* cliente frente al Gateway), no una credencial maestra. Las llaves reales de los proveedores de LLM (OpenAI/Claude/etc.) nunca salen del motor central y jamás forman parte de este artefacto — ver punto 0.4. Esta distinción es lo que permite que el artefacto se empaquete con su secreto de túnel embebido sin violar la Bóveda de Secretos (Mandamiento 12), que rige sobre credenciales maestras y llaves de API de terceros.

- [ ] **0.1 Generación de Credenciales por Tenant**
  - [ ] Al registrar un nuevo proyecto/cliente en el Dashboard TDTM, generar automáticamente un `X-Tenant-Id` (slug único, legible, derivado del nombre del proyecto + sufijo aleatorio para evitar colisiones).
  - [ ] Generar en el mismo acto un `shared_secret` aleatorio criptográficamente seguro (mínimo 256 bits de entropía), asociado 1:1 al `X-Tenant-Id`.
  - [ ] Persistir el `shared_secret` cifrado (AES-256-GCM, vía el mecanismo de bóveda del motor central) en la tabla de tenants — nunca en texto plano, ni siquiera en la base de datos interna.
  - [ ] Marcar el registro del tenant como `provisioning_status = pending` hasta que el artefacto sea generado y confirmado como desplegado.

- [ ] **0.2 Empaquetado Automático (El Instalador)**
  - [ ] El Dashboard TDTM debe exponer una función de "Compilar Túnel de Conexión" que tome como entrada el `X-Tenant-Id` y su `shared_secret` recién generados.
  - [ ] Compilar dos variantes de artefacto de entrega, según el nivel de acceso del cliente al hosting:
    - [ ] **Artefacto A — `proxy_bridge.php` comprimido en ZIP:** copia de la clase `ProxyBridge` (Fase 1) con el `X-Tenant-Id` y el `shared_secret` inyectados en su bloque de configuración local en el momento de la compilación — no en el repositorio fuente del molde, que permanece genérico. Se entrega listo para subir vía FTP/SFTP a la raíz del hosting del cliente.
    - [ ] **Artefacto B — snippet `<script>` autoejecutable:** para clientes sin acceso a backend PHP propio (landing estática, WordPress sin plugin custom), un tag `<script>` que apunta al Widget Web servido desde infraestructura de AXON, con el `X-Tenant-Id` embebido como atributo de datos (`data-tenant-id`) — el `shared_secret` en este caso **nunca** viaja al navegador; la autenticación del túnel ocurre servidor a servidor entre la infraestructura del widget alojado y el Gateway central.
  - [ ] Cada artefacto compilado queda registrado con un hash de verificación y una fecha de expiración de instalación (ventana razonable, ej. 72h) — si no se confirma el despliegue en ese plazo, el `shared_secret` se rota automáticamente antes de reintentar.

- [ ] **0.3 Despliegue Rápido — Los 3 Clics**
  - [ ] **Clic 1 — Registrar:** el operador de AXON da de alta al prospecto/cliente en el Dashboard TDTM (nombre, dominio, canal(es) deseados). Esto dispara 0.1 y habilita el espacio del tenant.
  - [ ] **Clic 2 — Generar y Compilar:** el operador confirma los canales activos (WhatsApp/Telegram/Widget Web) y el Dashboard ejecuta 0.2, produciendo el artefacto correspondiente listo para descarga.
  - [ ] **Clic 3 — Inyectar:** según el caso:
    - [ ] Si AXON gestiona el hosting o tiene credenciales FTP autorizadas del cliente, el propio Dashboard sube el Artefacto A vía FTP automatizado a la ruta acordada.
    - [ ] Si no, se entrega al cliente únicamente el tag `<script>` (Artefacto B) para que lo pegue en su HTML o en el editor de su WordPress — sin exponerlo a ningún archivo de configuración, credencial, ni paso técnico adicional.
  - [ ] Al completarse el paso 3, el Dashboard marca `provisioning_status = active` tras recibir el primer *heartbeat* válido del túnel (ver 0.4).

- [ ] **0.4 Consumo de Tokens AURA (Motor Central Linux)**
  - [ ] Desde el primer mensaje procesado, todo el consumo de tokens de inferencia se registra y factura contra el `X-Tenant-Id`, nunca contra una llave visible al cliente.
  - [ ] Las llaves reales de los proveedores de LLM viven exclusivamente en el motor central (Linux, DCD LABS) y son compartidas entre todos los tenants a nivel de infraestructura — el cliente jamás recibe, ve, ni puede extraer una llave de OpenAI/Claude/equivalente, ni siquiera inspeccionando el artefacto instalado en su servidor.
  - [ ] El artefacto instalado en el cliente solo posee su `shared_secret` de túnel — revocable y rotable de forma independiente, sin impacto en el resto de tenants ni en las llaves maestras.
  - [ ] Registrar el consumo de tokens por tenant en la telemetría central para fines de facturación y de la Matriz Financiera del proyecto (ver `05_MATRIZ_FINANCIERA_Y_VENTAS.md`), sin exponer ese detalle en ninguna respuesta JSON hacia el servidor del cliente.

### Fase 1 — Infraestructura del Cliente (El Escudo y el Proxy)

- [ ] Crear la clase `ProxyBridge` (PHP 8.x estricto, sin dependencias externas, solo cURL nativo) en el servidor del cliente.
- [ ] Implementar lectura de archivos `.md` locales con `file_get_contents()` protegida por `LOCK_EX` en escritura concurrente.
- [ ] Implementar caché en memoria volátil de dos niveles:
  - [ ] **Nivel APCu** para el contenido parseado del `.md` (clave por hash de ruta + mtime, invalidación automática si el archivo cambia).
  - [ ] **Nivel OPcache** (opcional, alto rendimiento): transformar el `.md` en un array PHP precompilado (`knowledge_base_arrays/*.php`) y precalentarlo vía `opcache_compile_file()` en un script `preload.php`.
- [ ] Configurar `php.ini` de producción del cliente (si el hosting lo permite) con `opcache.validate_timestamps=0` únicamente si existe un proceso de invalidación manual post-deploy; de lo contrario, mantener validación de timestamps activa para evitar servir conocimiento obsoleto.
- [ ] Implementar generación de firma `HMAC-SHA256` sobre el payload saliente usando un `shared_secret` almacenado fuera del árbol web (nunca hardcodeado, nunca en `.env` accesible vía HTTP).
- [ ] Empaquetar en el payload saliente: mensaje del usuario (ya normalizado a OCMC), contenido relevante del `.md`, y metadatos de sesión.
- [ ] Disparar la petición saliente vía cURL nativo hacia el Gateway central, con:
  - [ ] Timeout de conexión estricto (≤ 3s).
  - [ ] Timeout de lectura estricto (≤ 8s, por debajo de la ventana de tolerancia de reintentos de canales como WhatsApp).
  - [ ] Manejo de excepciones try/catch completo — ningún fallo de red debe producir un error 500 visible al usuario final; degradar a un mensaje de espera controlado.
- [ ] Verificar que el puente no contiene lógica de negocio, prompts, ni credenciales de LLM — su única responsabilidad es leer, firmar y reenviar.

### Fase 2 — Base de Datos Omnicanal (Core Central)

- [ ] Diseñar y desplegar las tablas relacionales en `snake_case`, validadas contra el Codex del proyecto antes de ejecutar cualquier `CREATE TABLE`:
  - [ ] `omnichannel_channels` — catálogo de canales activos por tenant (`whatsapp`, `telegram`, `web_widget`), credenciales cifradas de cada integración, estado (`active`/`inactive`).
  - [ ] `omnichannel_sessions` — hilo de conversación por contacto/canal, con `tenant_id`, `channel_id`, `contact_id`, timestamps de inicio/última actividad, estado (`open`/`closed`).
  - [ ] `omnichannel_contacts` — identidad del usuario final por canal, con `external_id` (id nativo del canal), `display_name`, `tenant_id`.
  - [ ] `omnichannel_messages` — historial de mensajes, con `session_id`, `direction` (`inbound`/`outbound`), `content`, `channel_message_id` (para idempotencia), `created_at`.
- [ ] Confirmar con el Arquitecto si estas tablas son nuevas o si existe un mapeo a estructuras ya existentes en el Codex del proyecto (Mandamiento de Inmutabilidad del Sistema — no crear tablas sin autorización explícita).
- [ ] Añadir índices de idempotencia sobre `channel_message_id` para prevenir procesamiento duplicado de eventos reenviados por el proveedor.
- [ ] Documentar el schema resultante en el pilar de Codex correspondiente del proyecto clonado.

### Fase 3 — Gateway y Contrato OCMC

- [ ] Crear el endpoint público único de entrada (Gateway) que resuelve el tenant vía `slug` recibido en la cabecera de autenticación.
- [ ] Implementar un normalizador por canal, cada uno responsable de transformar el payload nativo del proveedor al contrato OCMC (sección 1.3):
  - [ ] Normalizador WhatsApp Cloud API.
  - [ ] Normalizador Telegram Bot API.
  - [ ] Normalizador Widget Web (JSON directo, ya cercano a OCMC por diseño).
- [ ] Validar que ningún normalizador altere las propiedades del contrato OCMC una vez definido (Contrato de API Estricto) — cualquier campo nuevo requiere versión incremental (`ocmc_version`).
- [ ] Persistir el evento normalizado en `omnichannel_messages` antes de despachar al motor de inferencia, garantizando trazabilidad incluso si la inferencia falla.
- [ ] Retornar al canal de origen únicamente lo que ese canal exige como acuse de recibo (ver Fase 5, handshake y ventanas de timeout).

### Fase 4 — Motor de Encolamiento Asíncrono

- [ ] Adoptar el patrón **Queue-First**: el Gateway responde en milisegundos (`HTTP 200 OK`, ausente de procesamiento pesado) y delega la inferencia a un worker en segundo plano.
- [ ] Elegir mecanismo de cola según la infraestructura disponible del cliente/hosting:
  - [ ] Redis (preferido si hay acceso a un proceso persistente o VPS).
  - [ ] Tabla de persistencia transaccional + Worker CLI (fallback si el hosting es compartido y no permite procesos daemon — patrón ya usado en `knowledge_candidates`/`extraer_aprendizaje.php` de este mismo ecosistema).
- [ ] Implementar verificación de idempotencia antes de encolar: comprobar `channel_message_id` contra el TTL de la cola o contra `omnichannel_messages` para descartar reenvíos duplicados (entrega "al menos una vez" es el comportamiento estándar de estos proveedores).
- [ ] Implementar el Worker CLI que:
  - [ ] Consume el mensaje encolado.
  - [ ] Ejecuta la inferencia cognitiva (RAG sobre el `.md`, despacho al LLM).
  - [ ] Realiza la llamada saliente de respuesta hacia el canal original (API de envío de WhatsApp/Telegram, o push al Widget Web vía polling/SSE).
  - [ ] Registra telemetría de latencia y estado en el log de actividad del sistema.
- [ ] Verificar que el Worker corre exclusivamente vía CLI (`php_sapi_name() !== 'cli'` → rechazo), nunca accesible vía HTTP directo.

### Fase 5 — Blindaje Perimetral y Seguridad

- [ ] Implementar validación de firma criptográfica en cada evento entrante:
  - [ ] WhatsApp: `X-Hub-Signature-256`, calculada con `hash_hmac('sha256', $rawBody, $appSecret)` sobre el cuerpo crudo (`php://input`), comparada con `hash_equals()`.
  - [ ] Túnel interno Cliente → Gateway: firma HMAC-SHA256 de tres factores (`X-Tenant-Id`, `X-Signature`, `X-Timestamp` con ventana de tolerancia ±300s) + anti-replay (nonce con TTL corto en APCu o equivalente).
- [ ] Implementar el handshake de verificación de webhook (`GET` con `hub.mode`, `hub.verify_token`, `hub.challenge`) respondiendo en texto plano y comparando el token con `hash_equals()` (protección contra ataques de temporización).
- [ ] Evaluar si el hosting del cliente requiere mTLS saliente hacia Meta; si aplica, actualizar el almacén de confianza con la Autoridad de Certificación vigente del proveedor (verificar fecha de vigencia antes de asumir el certificado histórico).
- [ ] Implementar validación opcional de rango de IP de origen mediante coincidencia CIDR (`ip2long()` + máscara de bits) contra la lista oficial de rangos publicada por el proveedor del canal, refrescada periódicamente — nunca hardcodeada de forma permanente.
- [ ] Si el hosting del cliente presenta estrangulamiento de red conocido hacia el proveedor del canal (latencias o bloqueos intermitentes en el handshake), documentar la opción de un Servidor Puente (proxy inverso en un proveedor de infraestructura alterno) como mitigación, sin asumirlo como requisito por defecto — es una decisión de infraestructura caso por caso, no un paso obligatorio de este blueprint.
- [ ] Confirmar que ninguna credencial (App Secret, `shared_secret`, tokens de canal) queda hardcodeada en código — todo vía variable de entorno inyectada en servidor.

### Fase 6 — Integración al Dashboard TDTM

- [ ] Exponer en el panel de administración del ecosistema una vista de canales activos por tenant, consumiendo `omnichannel_channels`.
- [ ] Mostrar métricas de volumen y estado por canal (mensajes entrantes/salientes, sesiones abiertas, tiempo de respuesta) sin exponer en el JSON de respuesta ningún campo de telemetría interna reservado (latencias de red, estado de PDO, tokens en vuelo) salvo que la ley de observabilidad vigente del ecosistema lo autorice explícitamente en entornos no productivos.
- [ ] Conectar el estado de conexión de cada canal (`active`/`inactive`, último heartbeat) a un indicador visual en el dashboard.
- [ ] Verificar que todo `fetch()` del dashboard hacia los endpoints de este módulo maneja `401` redirigiendo a login — CORS nunca sustituye autenticación real.
- [ ] Validar, antes de cerrar el hito, que las latencias de encolamiento y despacho del módulo se observan saludables desde el panel de observabilidad del ecosistema (si el proyecto clonado cuenta con uno).

---

## 3. Artefactos de Código e Infraestructura (Inyección Directa)

> Esta sección traduce el checklist conceptual de la Fase 1, 2, 4 y 5 en artefactos ejecutables listos para adaptar al `X-Tenant-Id` real del proyecto. Todo bloque aquí es **genérico por diseño**: ningún hostname, dominio, `shared_secret` ni credencial real de AXON_DCD debe sustituir los placeholders al momento de sembrar este archivo en un proyecto clonado. Un desarrollador o IA externa sin contexto previo debe poder copiar, adaptar el placeholder y desplegar en menos de una hora.

### 3.1 Bloque SQL DDL — Schema Omnicanal (Fase 2)

MariaDB/MySQL estricto, `snake_case`, con `CREATE TABLE IF NOT EXISTS` para permitir ejecución idempotente en entornos ya parcialmente aprovisionados. Incluye dos tablas adicionales respecto al checklist original (`omnichannel_message_attachments` y `omnichannel_webhooks`) requeridas para que el código de las secciones 3.2–3.4 tenga soporte real de adjuntos y de registro de eventos entrantes crudos.

```sql
-- ============================================================
-- MOD_OPERADOR_COGNITIVO_OMNICANAL — Schema Base (snake_case)
-- Ejecutar solo tras validación contra el Codex del proyecto.
-- ============================================================

CREATE TABLE IF NOT EXISTS omnichannel_channels (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id           VARCHAR(64)     NOT NULL,
    channel_type        ENUM('whatsapp','telegram','web_widget') NOT NULL,
    channel_label       VARCHAR(120)    NOT NULL,
    credentials_encrypted TEXT          NOT NULL COMMENT 'Cifrado AES-256-GCM, nunca texto plano',
    status              ENUM('active','inactive') NOT NULL DEFAULT 'inactive',
    created_at          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_tenant_channel (tenant_id, channel_type),
    INDEX idx_channel_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS omnichannel_contacts (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id           VARCHAR(64)     NOT NULL,
    channel_id          BIGINT UNSIGNED NOT NULL,
    external_id         VARCHAR(190)    NOT NULL COMMENT 'ID nativo del canal de origen',
    display_name        VARCHAR(190)    NULL,
    created_at          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_channel_external (channel_id, external_id),
    INDEX idx_contact_tenant (tenant_id),
    CONSTRAINT fk_contact_channel FOREIGN KEY (channel_id)
        REFERENCES omnichannel_channels(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS omnichannel_sessions (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id           VARCHAR(64)     NOT NULL,
    channel_id          BIGINT UNSIGNED NOT NULL,
    contact_id          BIGINT UNSIGNED NOT NULL,
    session_uuid        CHAR(36)        NOT NULL,
    status              ENUM('open','closed') NOT NULL DEFAULT 'open',
    started_at          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_activity_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_session_uuid (session_uuid),
    INDEX idx_session_contact (contact_id),
    INDEX idx_session_status (status, last_activity_at),
    CONSTRAINT fk_session_channel FOREIGN KEY (channel_id)
        REFERENCES omnichannel_channels(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_session_contact FOREIGN KEY (contact_id)
        REFERENCES omnichannel_contacts(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS omnichannel_messages (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id           VARCHAR(64)     NOT NULL,
    session_id          BIGINT UNSIGNED NOT NULL,
    channel_message_id  VARCHAR(190)    NOT NULL COMMENT 'ID nativo del proveedor, para idempotencia',
    direction            ENUM('inbound','outbound') NOT NULL,
    message_type        ENUM('text','image','audio','document','interactive') NOT NULL DEFAULT 'text',
    content              TEXT            NULL,
    ocmc_payload         JSON            NULL COMMENT 'Payload OCMC completo, para auditoría',
    processing_status    ENUM('queued','processing','delivered','failed') NOT NULL DEFAULT 'queued',
    created_at            DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_channel_message (channel_message_id),
    INDEX idx_message_session (session_id, created_at),
    INDEX idx_message_status (processing_status),
    CONSTRAINT fk_message_session FOREIGN KEY (session_id)
        REFERENCES omnichannel_sessions(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS omnichannel_message_attachments (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    message_id          BIGINT UNSIGNED NOT NULL,
    media_type           VARCHAR(60)     NOT NULL COMMENT 'mime type o categoría del proveedor',
    media_url             VARCHAR(500)    NULL,
    media_ref             VARCHAR(190)    NULL COMMENT 'ID de media nativo del proveedor si aplica',
    created_at             DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_attachment_message (message_id),
    CONSTRAINT fk_attachment_message FOREIGN KEY (message_id)
        REFERENCES omnichannel_messages(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS omnichannel_webhooks (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id           VARCHAR(64)     NOT NULL,
    channel_type        ENUM('whatsapp','telegram','web_widget') NOT NULL,
    raw_payload          JSON            NOT NULL COMMENT 'Payload crudo del proveedor, previo a normalización OCMC',
    signature_valid       TINYINT(1)      NOT NULL DEFAULT 0,
    received_at            DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_webhook_tenant (tenant_id, received_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 3.2 Clase PHP `ProxyBridge.php` (Fase 1)

PHP 8.x estricto, cero dependencias externas, cURL nativo. Lectura del `.md` con caché APCu por `mtime` y fallback directo a disco con `LOCK_EX`. Firma HMAC-SHA256 de cuatro cabeceras. Timeouts estrictos y degradación controlada.

```php
<?php
declare(strict_types=1);

/**
 * ProxyBridge — Puente Local del AI Runtime Operator.
 * Vive en el servidor del cliente. No razona, no decide: solo lee, firma y reenvía.
 * Molde genérico — sustituir los placeholders TENANT_ID / SHARED_SECRET / GATEWAY_URL
 * al momento de compilar el artefacto por tenant (ver Fase 0.2).
 */
final class ProxyBridge
{
    private const GATEWAY_URL     = 'https://[GATEWAY_HOST]/api/public/ai_runtime_gateway.php';
    private const TENANT_ID       = '[X_TENANT_ID]';
    private const SHARED_SECRET   = '[SHARED_SECRET]'; // Token de túnel escopado por tenant — no una llave maestra.
    private const CONNECT_TIMEOUT = 3;
    private const READ_TIMEOUT    = 8;
    private const APCU_TTL        = 300;

    public function __construct(private readonly string $knowledgeMdPath)
    {
    }

    /**
     * Punto de entrada: recibe el mensaje de usuario ya en formato OCMC parcial
     * (channel, contact, session_id, message) y retorna la respuesta del motor central.
     */
    public function forward(array $ocmcMessage): array
    {
        try {
            $knowledge = $this->readKnowledgeBase();
            $body = json_encode([
                'ocmc_version' => '1.0',
                'tenant_slug'  => self::TENANT_ID,
                'knowledge'    => $knowledge,
            ] + $ocmcMessage, JSON_THROW_ON_ERROR);

            return $this->dispatch($body);
        } catch (\Throwable $e) {
            error_log('[ProxyBridge] Degradación controlada: ' . $e->getMessage());
            return [
                'status'  => 'degraded',
                'message' => 'El operador está procesando tu solicitud, en un momento continuamos.',
            ];
        }
    }

    /**
     * Lectura del .md con caché APCu por mtime. Fallback a disco con LOCK_EX
     * si APCu no está disponible o hay cache miss.
     */
    private function readKnowledgeBase(): string
    {
        if (!is_readable($this->knowledgeMdPath)) {
            throw new \RuntimeException('Archivo de conocimiento no accesible: ' . $this->knowledgeMdPath);
        }

        $mtime   = (string) filemtime($this->knowledgeMdPath);
        $cacheKey = 'proxy_bridge_md_' . self::TENANT_ID . '_' . md5($this->knowledgeMdPath) . '_' . $mtime;

        if (function_exists('apcu_fetch')) {
            $hit = apcu_fetch($cacheKey, $success);
            if ($success) {
                return $hit;
            }
        }

        $fh = fopen($this->knowledgeMdPath, 'rb');
        if ($fh === false) {
            throw new \RuntimeException('No fue posible abrir el archivo de conocimiento.');
        }

        try {
            if (!flock($fh, LOCK_EX)) {
                throw new \RuntimeException('No fue posible bloquear el archivo de conocimiento.');
            }
            $content = stream_get_contents($fh);
            flock($fh, LOCK_UN);
        } finally {
            fclose($fh);
        }

        if ($content === false) {
            throw new \RuntimeException('Lectura fallida del archivo de conocimiento.');
        }

        if (function_exists('apcu_store')) {
            apcu_store($cacheKey, $content, self::APCU_TTL);
        }

        return $content;
    }

    /**
     * Firma HMAC-SHA256 de tres factores + nonce anti-replay, y despacho cURL nativo.
     */
    private function dispatch(string $body): array
    {
        $timestamp = (string) time();
        $nonce     = bin2hex(random_bytes(16));
        $signature = hash_hmac('sha256', $timestamp . $nonce . $body, self::SHARED_SECRET);

        $ch = curl_init(self::GATEWAY_URL);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => self::CONNECT_TIMEOUT,
            CURLOPT_TIMEOUT        => self::READ_TIMEOUT,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'X-Tenant-Id: ' . self::TENANT_ID,
                'X-Signature: ' . $signature,
                'X-Timestamp: ' . $timestamp,
                'X-Nonce: ' . $nonce,
            ],
        ]);

        $response  = curl_exec($ch);
        $errno     = curl_errno($ch);
        $error     = curl_error($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno !== 0) {
            throw new \RuntimeException('Fallo de red hacia el Gateway central: ' . $error);
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            throw new \RuntimeException('Gateway central respondió con estado ' . $httpCode);
        }

        $decoded = json_decode((string) $response, true, 512, JSON_THROW_ON_ERROR);
        return is_array($decoded) ? $decoded : [];
    }
}
```

### 3.3 Handshake y Firma de Seguridad — Meta WhatsApp Cloud API (Fase 5)

Dos funciones independientes: verificación del handshake `GET` de suscripción y validación de `X-Hub-Signature-256` en cada evento `POST` entrante. Ambas usan `hash_equals()` para evitar ataques de canal lateral por temporización.

```php
<?php
declare(strict_types=1);

/**
 * Endpoint público de verificación de webhook (GET) — api/public/whatsapp_webhook.php
 * Meta convierte automáticamente los puntos de sus parámetros a guiones bajos
 * en $_GET (hub.mode → hub_mode) al poblar la superglobal en PHP.
 */
function handleMetaWebhookHandshake(string $expectedVerifyToken): void
{
    $mode      = $_GET['hub_mode'] ?? '';
    $token     = $_GET['hub_verify_token'] ?? '';
    $challenge = $_GET['hub_challenge'] ?? '';

    if ($mode === 'subscribe' && hash_equals($expectedVerifyToken, (string) $token)) {
        header('Content-Type: text/plain; charset=utf-8');
        http_response_code(200);
        echo $challenge;
        exit;
    }

    http_response_code(403);
    exit;
}

/**
 * Validación de firma criptográfica de eventos entrantes (POST).
 * Debe evaluarse SIEMPRE contra el cuerpo crudo (php://input), nunca contra
 * una re-serialización del payload — cualquier reordenamiento de propiedades
 * o escape distinto corrompe la coincidencia de bytes de la firma de Meta.
 */
function validateMetaSignature(string $appSecret): bool
{
    $signatureHeader = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
    if ($signatureHeader === '') {
        return false;
    }

    $parts = explode('=', $signatureHeader, 2);
    if (count($parts) !== 2 || strtolower($parts[0]) !== 'sha256') {
        return false;
    }

    $receivedHash   = $parts[1];
    $rawBody        = file_get_contents('php://input');
    $calculatedHash = hash_hmac('sha256', (string) $rawBody, $appSecret);

    return hash_equals($calculatedHash, $receivedHash);
}
```

### 3.4 Worker CLI de Encolamiento Asíncrono (Fase 4)

Ejecutable exclusivamente vía CLI (`403` inmediato si se invoca por HTTP). Consume mensajes en `processing_status = 'queued'` de `omnichannel_messages`, invoca al motor central AURA y despacha la respuesta al canal de origen. Diseñado como fallback de hosting compartido sin daemon persistente (patrón ya validado en `extraer_aprendizaje.php` de este ecosistema); si el entorno cuenta con Redis, sustituir el `SELECT ... FOR UPDATE` por el consumidor de cola equivalente sin alterar el resto del flujo.

```php
#!/usr/bin/env php
<?php
declare(strict_types=1);

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Acceso denegado: este script solo se ejecuta vía CLI.');
}

/**
 * Worker de despacho asíncrono — omnichannel_worker.php
 * Uso: php omnichannel_worker.php (bajo cron o supervisor de procesos)
 */

require_once __DIR__ . '/conexion.php'; // Database::getConnection() — PDO blindado del proyecto

function processQueuedMessages(PDO $pdo): void
{
    $pdo->beginTransaction();

    try {
        $stmt = $pdo->prepare(
            "SELECT id, tenant_id, session_id, ocmc_payload
             FROM omnichannel_messages
             WHERE processing_status = 'queued'
             ORDER BY created_at ASC
             LIMIT 10
             FOR UPDATE"
        );
        $stmt->execute();
        $rows = $stmt->fetchAll();

        if (empty($rows)) {
            $pdo->commit();
            return;
        }

        $ids = array_column($rows, 'id');
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $lock = $pdo->prepare(
            "UPDATE omnichannel_messages SET processing_status = 'processing' WHERE id IN ($placeholders)"
        );
        $lock->execute($ids);
        $pdo->commit();
    } catch (\Throwable $e) {
        $pdo->rollBack();
        error_log('[omnichannel_worker] Error al reservar lote: ' . $e->getMessage());
        return;
    }

    foreach ($rows as $row) {
        dispatchToAuraAndRespond($pdo, $row);
    }
}

function dispatchToAuraAndRespond(PDO $pdo, array $row): void
{
    try {
        $ocmc = json_decode((string) $row['ocmc_payload'], true, 512, JSON_THROW_ON_ERROR);

        // Invocación al motor central AURA (AiOrchestrator) — dispatch síncrono
        // dentro del worker, nunca dentro del ciclo de vida del webhook HTTP.
        $auraResponse = AiOrchestrator::dispatch($row['tenant_id'], $ocmc);

        deliverToOriginChannel($ocmc['channel'] ?? '', $ocmc, $auraResponse);

        $update = $pdo->prepare(
            "UPDATE omnichannel_messages SET processing_status = 'delivered' WHERE id = :id"
        );
        $update->execute(['id' => $row['id']]);
    } catch (\Throwable $e) {
        error_log('[omnichannel_worker] Fallo procesando mensaje ' . $row['id'] . ': ' . $e->getMessage());

        $update = $pdo->prepare(
            "UPDATE omnichannel_messages SET processing_status = 'failed' WHERE id = :id"
        );
        $update->execute(['id' => $row['id']]);
    }
}

function deliverToOriginChannel(string $channel, array $ocmc, array $auraResponse): void
{
    // Despacho saliente específico por canal (API de envío de WhatsApp/Telegram,
    // o publicación al Widget Web vía tabla de polling/SSE). Implementación
    // delegada al normalizador de cada canal — ver Fase 3.
}

$pdo = Database::getConnection();
processQueuedMessages($pdo);
```

---

## 4. Guía de Consumo Agnóstico del Widget Web

El cliente final puede operar en cualquier stack tecnológico. El widget de conversación debe entregarse como un artefacto de integración mínima, sin asumir un framework:

- **Vanilla JS / HTML puro / WordPress:** entrega de un único `<script>` con carga asíncrona (`defer` o `async`), que inyecta su propio contenedor en el DOM y se comunica exclusivamente vía `fetch()` con el Gateway. No requiere build step ni dependencias del lado del cliente.
- **React / Vue / frameworks SPA:** el mismo script se monta como un componente wrapper ligero o, alternativamente, se expone un endpoint de datos consumible vía `fetch()`/`axios` para que el equipo del cliente construya su propia UI sobre el contrato OCMC de respuesta.
- **Contrato de comunicación:** el widget nunca conoce las llaves de inferencia ni el `.md` de conocimiento — solo envía el mensaje del usuario y un identificador de sesión, y recibe la respuesta ya procesada. Esto mantiene el aislamiento de IP (sección 1.1) independientemente del stack elegido.
- **Requisito mínimo de hosting del cliente:** capacidad de servir un archivo estático (el script del widget) y, si el puente proxy vive en el mismo servidor, soporte PHP 8.x con cURL habilitado. No se requiere Node.js, contenedores, ni infraestructura adicional del lado del cliente.

---

## 5. Notas de Gobernanza

- Este documento vive en `knowledge/Santuario_Genesis/modulos/` como **molde agnóstico** — ningún dato real de un cliente o tenant específico debe incorporarse aquí (Mandato de Sincronización Génesis, Ley de Fricción Cero).
- Cualquier mejora futura a este módulo debe seguir el Protocolo de Actualización Secuencial en Cascada: primero estabilización operativa real, después propagación genérica al Santuario, después compilación monolítica del proyecto que lo consuma.
- Los nombres de tablas y campos aquí propuestos (`omnichannel_*`) son una referencia de diseño — su creación en un proyecto concreto requiere validación contra el Codex vigente de ese proyecto y autorización explícita antes de cualquier `CREATE TABLE`.
