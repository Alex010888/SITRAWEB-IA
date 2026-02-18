<?php
/**
 * JWT Service (PHP Nativo - sin dependencias)
 * 
 * Implementación minimalista de JWT (HS256) para autenticación.
 * Basado en el estándar RFC 7519.
 */

namespace App\Services;

class JwtService
{
    private string $secret;
    private int $expiration;

    public function __construct()
    {
        $this->secret = env('JWT_SECRET', 'default_secret_change_this');
        $this->expiration = (int)env('JWT_EXPIRATION', 3600);
    }

    /**
     * Genera un JWT
     * 
     * @param array $payload Datos a incluir en el token (user_id, rol, etc.)
     * @return string JWT token
     */
    public function encode(array $payload): string
    {
        // Header
        $header = [
            'alg' => 'HS256',
            'typ' => 'JWT'
        ];

        // Payload con timestamps
        $now = time();
        $payload['iat'] = $now; // Issued at
        $payload['exp'] = $now + $this->expiration; // Expiration

        // Codificar
        $headerEncoded = $this->base64UrlEncode(json_encode($header));
        $payloadEncoded = $this->base64UrlEncode(json_encode($payload));

        // Signature
        $signature = hash_hmac('sha256', "{$headerEncoded}.{$payloadEncoded}", $this->secret, true);
        $signatureEncoded = $this->base64UrlEncode($signature);

        return "{$headerEncoded}.{$payloadEncoded}.{$signatureEncoded}";
    }

    /**
     * Decodifica y valida un JWT
     * 
     * @param string $token JWT token
     * @return array|null Payload si es válido, null si no
     */
    public function decode(string $token): ?array
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            return null;
        }

        [$headerEncoded, $payloadEncoded, $signatureEncoded] = $parts;

        // Verificar firma
        $signature = $this->base64UrlDecode($signatureEncoded);
        $expectedSignature = hash_hmac('sha256', "{$headerEncoded}.{$payloadEncoded}", $this->secret, true);

        if (!hash_equals($signature, $expectedSignature)) {
            return null; // Firma inválida
        }

        // Decodificar payload
        $payload = json_decode($this->base64UrlDecode($payloadEncoded), true);

        if (!is_array($payload)) {
            return null;
        }

        // Verificar expiración
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return null; // Token expirado
        }

        return $payload;
    }

    /**
     * Base64 URL-safe encode
     */
    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Base64 URL-safe decode
     */
    private function base64UrlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
