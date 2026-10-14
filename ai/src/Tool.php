<?php
declare(strict_types=1);

namespace App\Ai;

/**
 * Инструмент, который агент может вызвать. Возвращает строку, которая уходит модели.
 */
interface Tool
{
    public function name(): string;

    /**
     * Описание в формате function calling (Ollama/OpenAI).
     *
     * @return array<string, mixed>
     */
    public function definition(): array;

    /**
     * @param array<string, mixed> $arguments
     */
    public function execute(array $arguments): string;
}
