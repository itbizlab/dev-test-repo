<?php
declare(strict_types=1);

namespace App\Ai\Tools;

use App\Ai\Tool;
use RuntimeException;

/**
 * Общая логика доступа к файлам проекта. Все пути ограничены корнем проекта,
 * директория .git и вложенные симлинки за его пределы не выходят.
 */
final class ProjectFiles
{
    private const MAX_BYTES = 20000;
    private const MAX_ENTRIES = 200;
    private const IGNORED_DIRS = ['.git', 'node_modules', 'vendor'];

    private readonly string $root;

    public function __construct(string $root)
    {
        $real = realpath($root);
        if ($real === false) {
            throw new RuntimeException("Корень проекта не найден: {$root}");
        }
        $this->root = $real;
    }

    public function resolve(string $relative): string
    {
        $relative = ltrim(str_replace('\\', '/', $relative), '/');
        $path = realpath($this->root . '/' . $relative);
        if ($path === false || ($path !== $this->root && !str_starts_with($path, $this->root . '/'))) {
            throw new RuntimeException("Путь недоступен или вне проекта: {$relative}");
        }

        return $path;
    }

    public function readFile(string $relative): string
    {
        $path = $this->resolve($relative);
        if (!is_file($path)) {
            throw new RuntimeException("Это не файл: {$relative}");
        }
        $content = (string) file_get_contents($path);
        if (strlen($content) > self::MAX_BYTES) {
            return substr($content, 0, self::MAX_BYTES) . "\n...[обрезано, файл больше " . self::MAX_BYTES . ' байт]';
        }

        return $content;
    }

    /**
     * @return list<string> относительные пути файлов и папок (папки с суффиксом /)
     */
    public function listDirectory(string $relative): array
    {
        $dir = $this->resolve($relative);
        if (!is_dir($dir)) {
            throw new RuntimeException("Это не папка: {$relative}");
        }

        $entries = [];
        $items = scandir($dir) ?: [];
        foreach ($items as $item) {
            if ($item === '.' || $item === '..' || in_array($item, self::IGNORED_DIRS, true)) {
                continue;
            }
            $full = $dir . '/' . $item;
            $rel = ltrim(substr($full, strlen($this->root)), '/');
            $entries[] = is_dir($full) ? $rel . '/' : $rel;
            if (count($entries) >= self::MAX_ENTRIES) {
                break;
            }
        }
        sort($entries);

        return $entries;
    }
}
