<?php
declare(strict_types=1);

namespace App\Ai;

/**
 * Контракт провайдера LLM. Агенты работают только с этим интерфейсом,
 * поэтому Ollama можно заменить на другой провайдер без изменений в агентах.
 */
interface LlmProvider
{
    /**
     * Отправляет историю диалога модели и возвращает её ответ.
     *
     * @param list<array<string, mixed>> $messages история в формате сообщений Ollama/OpenAI
     * @param list<array<string, mixed>> $tools   описания инструментов (function calling)
     * @return array<string, mixed> сообщение ассистента: role, content, опционально tool_calls
     */
    public function chat(array $messages, array $tools = []): array;
}
