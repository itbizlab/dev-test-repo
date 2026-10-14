<?php
declare(strict_types=1);

// Минимальный PSR-4 автозагрузчик для пространства App\Ai (без composer).
spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\Ai\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }
    $file = __DIR__ . '/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($file)) {
        require $file;
    }
});
