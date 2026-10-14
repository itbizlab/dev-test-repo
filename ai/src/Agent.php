<?php
declare(strict_types=1);

namespace App\Ai;

use RuntimeException;

/**
 * Агентский цикл: модель думает, при необходимости вызывает инструменты,
 * результаты возвращаются в историю, и так до финального ответа без tool_calls.
 */
final class Agent
{
    /** @var array<string, Tool> */
    private array $tools = [];

    /**
     * @param list<Tool> $tools
     */
    public function __construct(
        private readonly LlmProvider $llm,
        array $tools,
        private readonly int $maxSteps = 6,
        private readonly ?string $systemPrompt = null,
    ) {
        foreach ($tools as $tool) {
            $this->tools[$tool->name()] = $tool;
        }
    }

    public function run(string $task): string
    {
        $messages = [];
        if ($this->systemPrompt !== null) {
            $messages[] = ['role' => 'system', 'content' => $this->systemPrompt];
        }
        $messages[] = ['role' => 'user', 'content' => $task];

        $definitions = array_values(array_map(static fn (Tool $t): array => $t->definition(), $this->tools));

        for ($step = 0; $step < $this->maxSteps; $step++) {
            $reply = $this->llm->chat($messages, $definitions);
            $messages[] = $reply;

            $calls = $reply['tool_calls'] ?? [];
            if (!is_array($calls) || $calls === []) {
                return trim((string) ($reply['content'] ?? ''));
            }

            foreach ($calls as $call) {
                $name = (string) ($call['function']['name'] ?? '');
                $arguments = $this->normalizeArguments($call['function']['arguments'] ?? []);

                $messages[] = [
                    'role' => 'tool',
                    'tool_name' => $name,
                    'content' => $this->execute($name, $arguments),
                ];
            }
        }

        throw new RuntimeException("Агент не дал финальный ответ за {$this->maxSteps} шагов");
    }

    /**
     * @param array<string, mixed> $arguments
     */
    private function execute(string $name, array $arguments): string
    {
        if (!isset($this->tools[$name])) {
            return json_encode(['error' => "Неизвестный инструмент: {$name}"], JSON_UNESCAPED_UNICODE);
        }

        try {
            return $this->tools[$name]->execute($arguments);
        } catch (\Throwable $e) {
            // Ошибку отдаём модели, чтобы она могла исправить вызов, а не падать целиком.
            return json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * Аргументы могут прийти объектом или JSON-строкой, зависит от модели.
     *
     * @return array<string, mixed>
     */
    private function normalizeArguments(mixed $arguments): array
    {
        if (is_string($arguments)) {
            $arguments = json_decode($arguments, true);
        }

        return is_array($arguments) ? $arguments : [];
    }
}
