<?php
declare(strict_types=1);

namespace App\Ai\Tools;

use App\Ai\Tool;

final class ReadFileTool implements Tool
{
    public function __construct(private readonly ProjectFiles $files)
    {
    }

    public function name(): string
    {
        return 'read_project_file';
    }

    public function definition(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $this->name(),
                'description' => 'Читает текстовый файл репозитория проекта EIDOC. Путь относительно корня проекта.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'path' => ['type' => 'string', 'description' => 'Путь к файлу, например README.md'],
                    ],
                    'required' => ['path'],
                ],
            ],
        ];
    }

    public function execute(array $arguments): string
    {
        return $this->files->readFile((string) ($arguments['path'] ?? ''));
    }
}
