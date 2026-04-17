<?php

if (!function_exists('component_log')) {
    function component_log(string $mensaje, array $contexto = []): void
    {
        $directorioLogs = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'logs';
        if (!is_dir($directorioLogs)) {
            @mkdir($directorioLogs, 0777, true);
        }

        $rutaLog = $directorioLogs . DIRECTORY_SEPARATOR . 'component_helper.log';
        $linea = '[' . date('Y-m-d H:i:s') . '] ' . $mensaje;

        if (!empty($contexto)) {
            $linea .= ' | ' . json_encode($contexto, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $linea .= PHP_EOL;
        @file_put_contents($rutaLog, $linea, FILE_APPEND);
    }
}

function component(string $name, array $data = []): string
{
    component_log('Inicio component()', ['name' => $name]);

    $basePaths = [
        __DIR__ . '/../view/Componentes',
        __DIR__ . '/../view/components',
    ];

    $componentPath = null;

    foreach ($basePaths as $basePath) {
        $candidate = $basePath . '/' . $name . '.php';
        component_log('Buscando componente', ['name' => $name, 'candidate' => $candidate, 'exists' => is_file($candidate)]);
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
    component_log('Antes de require componente', ['name' => $name, 'path' => $componentPath]);
    require $componentPath;
    component_log('Despues de require componente', ['name' => $name, 'path' => $componentPath]);

    return ob_get_clean();
}
