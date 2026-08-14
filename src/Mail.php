<?php

declare(strict_types=1);

namespace Webability\Api;

/**
 * 🚧 Stub — calca el contrato del SDK de Go (github.com/webability/webability-go/mail).
 *
 * La capa de transporte (Wa::request()/get()/post()) ya está implementada;
 * falta conectar send()/status() a ella. Las firmas de tipos y métodos ya
 * están fijadas para que la implementación futura sea un port directo de
 * mail.go, no un rediseño.
 */

final class Address
{
    public function __construct(
        public readonly string $email,
        public readonly string $name = '',
    ) {
    }
}

final class Recipient
{
    /** @param array<string, mixed> $vars */
    public function __construct(
        public readonly string $email,
        public readonly string $name = '',
        public readonly array $vars = [],
    ) {
    }
}

/** Campos para POST /v1/mail/send. */
final class SendRequest
{
    /**
     * @param string $template Si viene, es el id de una plantilla ya
     *   registrada y activa en templates_template bajo la cuenta que
     *   autentica el request — el servidor arma el correo con esa plantilla
     *   en vez de subject/html/text (que se ignoran si template viene). La
     *   personalización se hace con las vars de $to, sin ningún prefijo en
     *   los nombres — dentro del contenido de la plantilla (Consola →
     *   Correos → Plantillas) se acceden exactamente igual que en el envío
     *   ad-hoc: {{clave}} directo. La plantilla solo ve las vars, nunca el
     *   resto del mensaje (to, from, subject, etc.) — si necesitas imprimir
     *   alguno de esos datos dentro del cuerpo, agrégalo también a vars. El
     *   servidor valida que la plantilla exista y esté activa ANTES de
     *   encolar el correo: si no, send() lanza una excepción con el error
     *   de la API (códigos 3025/3026), no un envío "pending" fallido.
     * @param string[] $tags
     * @param bool $waitSend Si es true, el servidor espera (hasta ~20s) el
     *   resultado real del envío antes de responder, en vez de responder de
     *   inmediato con queueStatus="pending". Ver Mail::send().
     */
    public function __construct(
        public readonly Address $from,
        public readonly Recipient $to,
        public readonly string $subject = '',
        public readonly string $html = '',
        public readonly string $text = '',
        public readonly string $template = '',
        public readonly array $tags = [],
        public readonly bool $trackOpens = false,
        public readonly bool $trackClicks = false,
        public readonly bool $waitSend = false,
    ) {
    }
}

/** Estados posibles de queueStatus en SendResult y StatusResult. */
final class QueueStatus
{
    public const PENDING = 'pending';
    public const PROCESSING = 'processing';
    public const SENT = 'sent';
    public const ERROR = 'error';
}

/** Respuesta de Mail::send(). */
final class SendResult
{
    public function __construct(
        public readonly string $status,
        public readonly int $queueKey,
        public readonly string $queueStatus,
        public readonly string $to,
        public readonly string $errorDetail = '',
    ) {
    }
}

/** Respuesta de Mail::status(). */
final class StatusResult
{
    public function __construct(
        public readonly string $status,
        public readonly int $queueKey,
        public readonly string $queueStatus,
        public readonly string $errorDetail = '',
    ) {
    }
}

final class Mail
{
    public function __construct(private readonly Wa $api)
    {
    }

    /**
     * Envía un correo a un solo destinatario. POST /v1/mail/send
     *
     * 🚧 Pendiente de implementar (ver mail.go para el contrato de referencia).
     */
    public function send(SendRequest $req): SendResult
    {
        throw new \RuntimeException('Mail::send() aún no está implementado en el SDK de PHP.');
    }

    /**
     * Consulta el estatus real de un envío hecho con send(). GET /v1/mail/status/{queue_key}
     *
     * 🚧 Pendiente de implementar (ver mail.go para el contrato de referencia).
     */
    public function status(int $queueKey): StatusResult
    {
        throw new \RuntimeException('Mail::status() aún no está implementado en el SDK de PHP.');
    }
}
