<?php
declare(strict_types=1);

namespace App\Ai\Tools;

use App\Ai\Tool;

final class ListDirectoryTool implements Tool
{
    public function __construct(private readonly ProjectFiles $files)
    {
    }

    public function name(): string
    {
        return 'list_project_directory';
    }

    public function definition(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $this->name(),
                'description' => 'Показывает содержимое папки репозитория. Путь относительно корня, "" или "." — корень.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'path' => ['type' => 'string', 'description' => 'Папка, например "" или "docker"'],
                    ],
                    'required' => ['path'],
                ],
            ],
        ];
    }

    public function execute(array $arguments): string
    {
        $path = (string) ($arguments['path'] ?? '');
        $entries = $this->files->listDirectory($path === '.' ? '' : $path);

        return json_encode($entries, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }
}
