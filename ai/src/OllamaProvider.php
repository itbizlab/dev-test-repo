<?php
declare(strict_types=1);

namespace App\Ai;

use RuntimeException;

/**
 * Провайдер для локальной модели Ollama через нативный API /api/chat.
 */
final class OllamaProvider implements LlmProvider
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $model,
        private readonly float $temperature = 0.2,
        private readonly int $timeoutSeconds = 300,
    ) {
    }

    public static function fromEnv(): self
    {
        $baseUrl = getenv('OLLAMA_BASE_URL') ?: 'http://ollama:11434';
        $model = getenv('OLLAMA_MODEL') ?: 'qwen2.5:1.5b';
        $timeout = (int) (getenv('OLLAMA_TIMEOUT') ?: 300);

        return new self(rtrim($baseUrl, '/'), $model, 0.2, $timeout);
    }

    public function chat(array $messages, array $tools = []): array
    {
        $payload = [
            'model' => $this->model,
            'messages' => $messages,
            'stream' => false,
            'options' => ['temperature' => $this->temperature],
        ];
        if ($tools !== []) {
            $payload['tools'] = $tools;
        }

        $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        $ch = curl_init($this->baseUrl . '/api/chat');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeoutSeconds,
        ]);

        $raw = curl_exec($ch);
        if ($raw === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new RuntimeException("Ollama недоступна ({$this->baseUrl}): {$error}");
        }
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        $data = json_decode((string) $raw, true);
        if ($status >= 400 || !is_array($data)) {
            $detail = is_array($data) && isset($data['error']) ? $data['error'] : (string) $raw;
            throw new RuntimeException("Ollama вернула ошибку {$status}: {$detail}");
        }
        if (!isset($data['message']) || !is_array($data['message'])) {
            throw new RuntimeException('Ollama вернула ответ без поля message');
        }

        return $data['message'];
    }
}
