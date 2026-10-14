<?php
declare(strict_types=1);

/**
 * Тесты без внешних зависимостей: php ai/tests/run.php
 */

require __DIR__ . '/../src/autoload.php';

use App\Ai\Agent;
use App\Ai\LlmProvider;
use App\Ai\Tools\ListDirectoryTool;
use App\Ai\Tools\ProjectFiles;
use App\Ai\Tools\ReadFileTool;

$failures = 0;
$passed = 0;

function check(string $name, bool $ok, string $details = ''): void
{
    global $failures, $passed;
    if ($ok) {
        $passed++;
        echo "  ok   {$name}\n";
    } else {
        $failures++;
        echo "  FAIL {$name}" . ($details !== '' ? " — {$details}" : '') . "\n";
    }
}

/** Провайдер-заглушка: отдаёт заранее заданные ответы и запоминает запросы. */
final class ScriptedLlm implements LlmProvider
{
    /** @var list<array<string, mixed>> */
    public array $seen = [];

    /** @param list<array<string, mixed>> $replies */
    public function __construct(private array $replies)
    {
    }

    public function chat(array $messages, array $tools = []): array
    {
        $this->seen[] = $messages;

        return array_shift($this->replies) ?? ['role' => 'assistant', 'content' => ''];
    }
}

$root = dirname(__DIR__, 2);
$files = new ProjectFiles($root);

echo "ProjectFiles\n";
$readme = $files->readFile('README.md');
check('читает файл внутри проекта', str_contains($readme, 'dev-test-repo'));

foreach (['../../etc/passwd', '/etc/passwd', 'ai/../../etc/passwd'] as $bad) {
    try {
        $files->readFile($bad);
        check("блокирует выход за корень: {$bad}", false, 'исключение не выброшено');
    } catch (RuntimeException) {
        check("блокирует выход за корень: {$bad}", true);
    }
}

$listing = $files->listDirectory('');
check('листинг корня содержит docker-compose.yml', in_array('docker-compose.yml', $listing, true));
check('листинг не содержит .git', !in_array('.git/', $listing, true));

echo "Agent\n";
$llm = new ScriptedLlm([
    ['role' => 'assistant', 'content' => '', 'tool_calls' => [[
        'function' => ['name' => 'read_project_file', 'arguments' => ['path' => 'README.md']],
    ]]],
    ['role' => 'assistant', 'content' => 'Готово: проект dev-test-repo.'],
]);
$agent = new Agent($llm, [new ListDirectoryTool($files), new ReadFileTool($files)], 4, 'sys');
$answer = $agent->run('что в README?');
check('возвращает финальный ответ модели', $answer === 'Готово: проект dev-test-repo.', $answer);
check('результат инструмента передан модели', isset($llm->seen[1]) && end($llm->seen[1])['role'] === 'tool');
check('системный промпт первым в истории', ($llm->seen[0][0]['role'] ?? '') === 'system');

$llm = new ScriptedLlm([
    ['role' => 'assistant', 'content' => '', 'tool_calls' => [[
        'function' => ['name' => 'delete_everything', 'arguments' => []],
    ]]],
    ['role' => 'assistant', 'content' => 'ok'],
]);
(new Agent($llm, [new ReadFileTool($files)], 3))->run('x');
$toolMsg = end($llm->seen[1]);
check('неизвестный инструмент даёт ошибку модели, а не падение', str_contains((string) $toolMsg['content'], 'Неизвестный инструмент'));

$llm = new ScriptedLlm([
    ['role' => 'assistant', 'content' => '', 'tool_calls' => [[
        'function' => ['name' => 'read_project_file', 'arguments' => '{"path":"../../etc/passwd"}'],
    ]]],
    ['role' => 'assistant', 'content' => 'ok'],
]);
(new Agent($llm, [new ReadFileTool($files)], 3))->run('x');
$toolMsg = end($llm->seen[1]);
check('аргументы-строкой декодируются и защита путей работает', str_contains((string) $toolMsg['content'], 'вне проекта'));

$looping = new ScriptedLlm(array_fill(0, 5, ['role' => 'assistant', 'content' => '', 'tool_calls' => [[
    'function' => ['name' => 'list_project_directory', 'arguments' => ['path' => '']],
]]]));
try {
    (new Agent($looping, [new ListDirectoryTool($files)], 3))->run('x');
    check('ограничение числа шагов', false, 'исключение не выброшено');
} catch (RuntimeException $e) {
    check('ограничение числа шагов', str_contains($e->getMessage(), '3 шагов'));
}

echo "\nПройдено: {$passed}, провалено: {$failures}\n";
exit($failures === 0 ? 0 : 1);
