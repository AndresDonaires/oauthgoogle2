<?php

namespace App\Services;

use App\Models\Sesion;
use Illuminate\Support\Facades\Http;
use Exception;

class GoogleCalendarService
{
    private const TIMEZONE = 'America/Lima';
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';
    private const CALENDAR_URL = 'https://www.googleapis.com/calendar/v3/calendars/primary/events';

    // ── Obtener un access token fresco usando el refresh token ────────────
    private function getAccessToken(string $refreshToken): string
    {
        $response = Http::post(self::TOKEN_URL, [
            'client_id'     => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'refresh_token' => $refreshToken,
            'grant_type'    => 'refresh_token',
        ]);

        if (!$response->successful() || !$response->json('access_token')) {
            throw new Exception('No se pudo refrescar el token de Google.');
        }

        return $response->json('access_token');
    }

    // ── Construir el cuerpo del evento ────────────────────────────────────
    // $contraparteNombre/$contraparteEmail son los datos de la otra persona
    // (la que NO es dueña del calendario en el que se crea el evento).
    private function buildEventBody(Sesion $sesion, string $contraparteNombre, string $contraparteEmail): array
    {
        return [
            'summary'     => "Sesión de Mentoría con {$contraparteNombre}",
            'description' => $sesion->observaciones ?? 'Sesión de mentoría agendada en la plataforma.',
            'start'       => [
                'dateTime' => $sesion->fecha . 'T' . $sesion->hora_inicio,
                'timeZone' => self::TIMEZONE,
            ],
            'end'         => [
                'dateTime' => $sesion->fecha . 'T' . $sesion->hora_fin,
                'timeZone' => self::TIMEZONE,
            ],
            'attendees' => [
                ['email' => $contraparteEmail, 'displayName' => $contraparteNombre],
            ],
            'reminders' => [
                'useDefault' => false,
                'overrides'  => [
                    ['method' => 'email',  'minutes' => 1440], // 24 h antes
                    ['method' => 'popup',  'minutes' => 30],   // 30 min antes
                ],
            ],
        ];
    }

    // ── Crear evento — retorna el eventId o null si falla ─────────────────
    public function crearEvento(
        string $refreshToken,
        Sesion $sesion,
        string $contraparteNombre,
        string $contraparteEmail
    ): ?string {
        try {
            $accessToken = $this->getAccessToken($refreshToken);

            $response = Http::withToken($accessToken)
                ->post(self::CALENDAR_URL . '?sendUpdates=all',
                    $this->buildEventBody($sesion, $contraparteNombre, $contraparteEmail)
                );

            return $response->successful() ? $response->json('id') : null;

        } catch (Exception) {
            return null;
        }
    }

    // ── Actualizar evento existente ────────────────────────────────────────
    public function actualizarEvento(
        string $eventId,
        string $refreshToken,
        Sesion $sesion,
        string $contraparteNombre,
        string $contraparteEmail
    ): void {
        try {
            $accessToken = $this->getAccessToken($refreshToken);

            Http::withToken($accessToken)
                ->put(self::CALENDAR_URL . "/{$eventId}?sendUpdates=all",
                    $this->buildEventBody($sesion, $contraparteNombre, $contraparteEmail)
                );

        } catch (Exception) {
            // Falla silenciosamente — la sesión ya se actualizó en BD
        }
    }

    // ── Eliminar evento ────────────────────────────────────────────────────
    public function eliminarEvento(string $eventId, string $refreshToken): void
    {
        try {
            $accessToken = $this->getAccessToken($refreshToken);

            Http::withToken($accessToken)
                ->delete(self::CALENDAR_URL . "/{$eventId}?sendUpdates=all");

        } catch (Exception) {
            // Falla silenciosamente
        }
    }
}
