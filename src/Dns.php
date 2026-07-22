<?php

declare(strict_types=1);

namespace Webability\Api;

/**
 * Módulo DNS: zonas y registros del cliente. Envuelve /v1/dns/*.
 *
 * Los arrays asociativos que entran y salen de este módulo usan exactamente
 * los mismos nombres de campo que el JSON de la API (rrtype, rrtypename,
 * primaryns, defaultttl, etc.) — no se convierten a otro estilo.
 */
final class Dns
{
    public function __construct(private readonly Wa $api)
    {
    }

    /**
     * Lista las zonas (dominios) del cliente. GET /v1/dns/zone
     *
     * @return array{status: string, zones: array, count: int}
     */
    public function listZones(): array
    {
        return $this->api->get('/v1/dns/zone')->decode();
    }

    /**
     * Obtiene una zona (por clave numérica o por nombre de dominio) junto con
     * sus registros. GET /v1/dns/zone/{key|domain}
     *
     * @return array{status: string, zone: array, records: array, ns: array}
     */
    public function getZone(string $keyOrDomain): array
    {
        return $this->api->get('/v1/dns/zone/' . rawurlencode($keyOrDomain))->decode();
    }

    /**
     * Crea una nueva zona. POST /v1/dns/zone
     *
     * @return array{status: string, key: int, name: string}
     */
    public function addZone(string $name): array
    {
        return $this->api->post('/v1/dns/zone', ['name' => $name])->decode();
    }

    /**
     * Agrega un registro a una zona. POST /v1/dns/zone/{key}/record
     *
     * $record: ['name' => ..., 'rrtype' => ..., 'ttl' => ..., 'data' => ...,
     *   'priority' => ?, 'weight' => ?, 'port' => ?, 'tag' => ?]
     *
     * @param array<string, mixed> $record
     * @return array{status: string, key: int, zone: int}
     */
    public function addRecord(int $zoneKey, array $record): array
    {
        return $this->api->post("/v1/dns/zone/{$zoneKey}/record", $record)->decode();
    }

    /**
     * Modifica un registro existente. PUT /v1/dns/record/{key}
     *
     * $fields: array asociativo con SOLO los campos a cambiar
     *   (name, ttl, data, priority, weight, port, tag, status) — los que no
     *   incluyas no se tocan.
     *
     * @param array<string, mixed> $fields
     * @return array{status: string, key: int}
     */
    public function updateRecord(int $recordKey, array $fields): array
    {
        return $this->api->put("/v1/dns/record/{$recordKey}", $fields)->decode();
    }

    /**
     * Elimina un registro. DELETE /v1/dns/record/{key}
     *
     * @return array{status: string, key: int}
     */
    public function deleteRecord(int $recordKey): array
    {
        return $this->api->delete("/v1/dns/record/{$recordKey}")->decode();
    }

    /**
     * Elimina una zona y todos sus registros. DELETE /v1/dns/zone/{key}
     *
     * @return array{status: string, key: int, name: string}
     */
    public function deleteZone(int $zoneKey): array
    {
        return $this->api->delete("/v1/dns/zone/{$zoneKey}")->decode();
    }
}
