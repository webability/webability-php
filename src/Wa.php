<?php

declare(strict_types=1);

namespace Webability\Api;

/**
 * 🚧 En construcción.
 *
 * Cliente base para la API de WebAbility (https://api.webability.info).
 * Seguirá el mismo esquema de autenticación que el cliente Go de referencia
 * (github.com/webability/webability-go): ClientID + Token, firma HMAC-SHA256 en los
 * headers X-WA-Client / X-WA-Timestamp / X-WA-Digest. El Token nunca viaja
 * en el request.
 */
final class Wa
{
    public function __construct(
        private readonly string $clientId,
        private readonly string $token,
        private readonly string $baseUrl = 'https://api.webability.info',
    ) {
    }
}
