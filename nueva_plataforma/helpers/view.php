<?php

function component(string $name, array $data = []): string
{
    $basePaths = [
        __DIR__ . '/../view/Componentes',
        __DIR__ . '/../view/components',
    ];

    $componentPath = null;

    foreach ($basePaths as $basePath) {
        $candidate = $basePath . '/' . $name . '.php';
        if (is_file($candidate)) {
            $componentPath = $candidate;
            break;
        }
    }

    if ($componentPath === null) {
        throw new RuntimeException("No se encontro el componente '{$name}'.");
    }

    extract($data, EXTR_SKIP);

    ob_start();
    require $componentPath;

    return ob_get_clean();
}
