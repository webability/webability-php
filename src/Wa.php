<?php

declare(strict_types=1);

namespace Webability\Api;

/**
 * Cliente base para la API de WebAbility (https://api.webability.info).
 *
 * Firma cada request con HMAC-SHA256 (headers X-WA-Client, X-WA-Timestamp,
 * X-WA-Digest) — mismo esquema que el SDK de Go
 * (github.com/webability/webability-go/wa). El Token nunca viaja en el
 * request: solo se usa localmente para calcular el digest.
 *
 * Requiere ext-curl y ext-json (ambas casi siempre presentes por defecto).
 */

/** Error devuelto por la API en formato {status, code, message}. */
final class ApiError extends \RuntimeException
{
    public function __construct(
        public readonly int $statusCode,
        public readonly ?int $code,
        string $message,
    ) {
        parent::__construct(sprintf('wa api error %s: %s', $code ?? $statusCode, $message));
    }
}

/** Respuesta cruda de un request a la API. */
final class Response
{
    /** @param array<string, string> $headers */
    public function __construct(
        public readonly int $statusCode,
        public readonly array $headers,
        public readonly string $body,
    ) {
    }

    /** Decodifica el cuerpo JSON de la respuesta. */
    public function decode(): array
    {
        if ($this->body === '') {
            return [];
        }
        return json_decode($this->body, true, 512, JSON_THROW_ON_ERROR);
    }
}

/** Construye el mensaje canónico a firmar: "{METODO}|{PATH}|{TIMESTAMP}|{CLIENTID}". */
function buildMessage(string $method, string $path, string $timestamp, string $clientId): string
{
    return "{$method}|{$path}|{$timestamp}|{$clientId}";
}

final class Wa
{
    public function __construct(
        private readonly string $clientId,
        private readonly string $token,
        private readonly string $baseUrl = 'https://api.webability.info',
    ) {
    }

    public function clientId(): string
    {
        return $this->clientId;
    }

    /** Retorna hex(HMAC-SHA256($this->token, $message)). */
    public function digest(string $message): string
    {
        return hash_hmac('sha256', $message, $this->token);
    }

    /**
     * Firma y envía un request HTTP a la API.
     *
     * $path debe ser la ruta absoluta (ej: "/v1/dns/zone"), sin el host y sin
     * query string. $body, si no es null, se codifica como JSON y se envía
     * como cuerpo del request.
     */
    public function request(string $method, string $path, ?array $body = null): Response
    {
        $timestamp = (string) time();
        $message = buildMessage($method, $path, $timestamp, $this->clientId);

        $headers = [
            'X-WA-Client: ' . $this->clientId,
            'X-WA-Timestamp: ' . $timestamp,
            'X-WA-Digest: ' . $this->digest($message),
        ];

        $payload = null;
        if ($body !== null) {
            $headers[] = 'Content-Type: application/json';
            $payload = json_encode($body, JSON_THROW_ON_ERROR);
        }

        $ch = curl_init($this->baseUrl . $path);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_TIMEOUT => 30,
        ]);
        if ($payload !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        }

        $raw = curl_exec($ch);
        if ($raw === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \RuntimeException("enviando request: {$error}");
        }

        $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);

        $rawHeaders = substr($raw, 0, $headerSize);
        $responseBody = substr($raw, $headerSize);
        $responseHeaders = self::parseHeaders($rawHeaders);

        $result = new Response($statusCode, $responseHeaders, $responseBody);

        if ($statusCode >= 400) {
            $parsed = [];
            try {
                $parsed = $result->decode();
            } catch (\JsonException) {
                // body no era JSON; se ignora, cae al mensaje genérico de abajo
            }
            if (!empty($parsed['message'])) {
                throw new ApiError($statusCode, $parsed['code'] ?? null, $parsed['message']);
            }
            throw new \RuntimeException("error HTTP {$statusCode}");
        }

        return $result;
    }

    /** Envía un GET a $path. */
    public function get(string $path): Response
    {
        return $this->request('GET', $path);
    }

    /** Envía un POST a $path con body codificado en JSON. */
    public function post(string $path, ?array $body = null): Response
    {
        return $this->request('POST', $path, $body ?? []);
    }

    /** Envía un PUT a $path con body codificado en JSON. */
    public function put(string $path, ?array $body = null): Response
    {
        return $this->request('PUT', $path, $body ?? []);
    }

    /** Envía un DELETE a $path. */
    public function delete(string $path): Response
    {
        return $this->request('DELETE', $path);
    }

    /** @return array<string, string> */
    private static function parseHeaders(string $raw): array
    {
        $headers = [];
        $lines = explode("\r\n", trim($raw));
        foreach ($lines as $line) {
            $pos = strpos($line, ':');
            if ($pos === false) {
                continue;
            }
            $name = strtolower(trim(substr($line, 0, $pos)));
            $value = trim(substr($line, $pos + 1));
            $headers[$name] = $value;
        }
        return $headers;
    }
}
