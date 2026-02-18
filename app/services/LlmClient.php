<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Cliente para APIs de chat (Groq, OpenAI).
 * Formato compatible con OpenAI.
 */
final class LlmClient
{
    private const GROQ_URL = 'https://api.groq.com/openai/v1/chat/completions';
    private const OPENAI_URL = 'https://api.openai.com/v1/chat/completions';

    public function __construct(
        private string $apiKey,
        private string $provider = 'groq',
        private string $model = 'llama-3.1-8b-instant',
    ) {
    }

    /**
     * Envía un mensaje al modelo y devuelve la respuesta.
     * @param array<int, array{role: string, content: string}> $messages
     */
    public function chat(array $messages, int $maxTokens = 800, float $temperature = 0.2): ?string
    {
        $url = $this->provider === 'openai' ? self::OPENAI_URL : self::GROQ_URL;

        $body = json_encode([
            'model' => $this->model,
            'messages' => $messages,
            'max_tokens' => $maxTokens,
            'temperature' => $temperature,
        ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        $ctx = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\nAuthorization: Bearer " . $this->apiKey,
                'content' => $body,
                'timeout' => 30,
                'ignore_errors' => true,
            ],
        ]);

        $response = @file_get_contents($url, false, $ctx);
        if ($response === false) {
            return null;
        }

        $data = json_decode($response, true);
        if (!is_array($data)) {
            return null;
        }
        if (isset($data['error']['message'])) {
            error_log('[LlmClient] API error: ' . $data['error']['message']);
            return null;
        }
        $content = isset($data['choices'][0]['message']['content'])
            ? $data['choices'][0]['message']['content']
            : null;
        return is_string($content) ? trim($content) : null;
    }
}
