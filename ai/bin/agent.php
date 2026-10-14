<?php
declare(strict_types=1);

/**
 * Запуск агента на локальной модели Ollama.
 *
 *   php ai/bin/agent.php "Какие сервисы описаны в docker-compose.yml?"
 *
 * Переменные окружения: OLLAMA_BASE_URL, OLLAMA_MODEL, OLLAMA_TIMEOUT, AGENT_PROJECT_ROOT.
 */

require __DIR__ . '/../src/autoload.php';

use App\Ai\Agent;
use App\Ai\OllamaProvider;
use App\Ai\Tools\ListDirectoryTool;
use App\Ai\Tools\ProjectFiles;
use App\Ai\Tools\ReadFileTool;

$task = trim((string) ($argv[1] ?? ''));
if ($task === '') {
    fwrite(STDERR, "Использование: php ai/bin/agent.php \"<задача>\"\n");
    exit(2);
}

$root = getenv('AGENT_PROJECT_ROOT') ?: dirname(__DIR__, 2);
$files = new ProjectFiles($root);

$agent = new Agent(
    llm: OllamaProvider::fromEnv(),
    tools: [new ListDirectoryTool($files), new ReadFileTool($files)],
    maxSteps: 6,
    systemPrompt: 'Ты помощник разработчика проекта EIDOC. Отвечай по-русски. '
        . 'Для ответов о коде и конфигурации сначала посмотри файлы через инструменты, '
        . 'не выдумывай содержимое. Инструменты только читают репозиторий.',
);

try {
    echo $agent->run($task), PHP_EOL;
} catch (Throwable $e) {
    fwrite(STDERR, 'Ошибка агента: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
