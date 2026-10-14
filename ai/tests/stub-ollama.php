<?php
declare(strict_types=1);

/**
 * Заглушка протокола Ollama /api/chat для проверки кода без реальной модели.
 * Запуск: php -S 127.0.0.1:11999 ai/tests/stub-ollama.php
 *
 * Логика: если в истории ещё нет результата инструмента, просит прочитать README.md;
 * когда результат есть, возвращает финальный ответ, включающий его первую строку.
 */

header('Content-Type: application/json');

if (($_SERVER['REQUEST_URI'] ?? '') !== '/api/chat') {
    http_response_code(404);
    echo json_encode(['error' => 'not found']);
    return;
}

$request = json_decode((string) file_get_contents('php://input'), true);
$messages = $request['messages'] ?? [];

$toolResult = null;
foreach ($messages as $message) {
    if (($message['role'] ?? '') === 'tool') {
        $toolResult = (string) $message['content'];
    }
}

if ($toolResult === null) {
    echo json_encode([
        'model' => $request['model'] ?? 'stub',
        'message' => [
            'role' => 'assistant',
            'content' => '',
            'tool_calls' => [[
                'function' => [
                    'name' => 'read_project_file',
                    // Намеренно передаём путь с выходом за корень, чтобы проверить защиту.
                    'arguments' => ['path' => '../../../../etc/passwd'],
                ],
            ]],
        ],
        'done' => true,
    ]);
    return;
}

$firstLine = strtok($toolResult, "\n") ?: '';
echo json_encode([
    'model' => $request['model'] ?? 'stub',
    'message' => ['role' => 'assistant', 'content' => 'Инструмент ответил: ' . $firstLine],
    'done' => true,
]);
