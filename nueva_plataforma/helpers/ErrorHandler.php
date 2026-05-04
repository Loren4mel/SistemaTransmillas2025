<?php
/**
 * ErrorHandler - Manejador centralizado de errores y excepciones
 *
 * Proporciona manejo uniforme de errores para aplicaciones web y AJAX.
 * En producción, registra los detalles internos y devuelve mensajes genéricos al cliente.
 */

class ErrorHandler
{
    private static $captured_errors = [];

    /**
     * Configura los manejadores de errores y excepciones
     */
    public static function setup()
    {
        ini_set('display_errors', 0);
        error_reporting(E_ALL);
        mysqli_report(MYSQLI_REPORT_ERROR);

        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
        register_shutdown_function([self::class, 'handleFatalError']);
    }

    /**
     * Maneja errores capturados. Los registra internamente y en el log,
     * pero nunca los muestra al cliente.
     */
    public static function handleError($errno, $errstr, $errfile, $errline)
    {
        self::$captured_errors[] = [
            'type' => $errno,
            'message' => $errstr,
            'file' => $errfile,
            'line' => $errline
        ];
        error_log("Error PHP [$errno]: $errstr en $errfile:$errline");
        return true;
    }

    /**
     * Maneja excepciones no capturadas.
     * Registra el detalle completo internamente, pero devuelve un mensaje genérico al cliente.
     */
    public static function handleException($exception)
    {
        error_log("Excepcion no capturada: " . $exception->getMessage() . "\n" . $exception->getTraceAsString());

        if (self::isAjaxRequest()) {
            self::sendJsonResponse([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        } else {
            if (ob_get_level()) ob_clean();
            http_response_code(500);
            echo "<h1>Error interno</h1><p>Ha ocurrido un error inesperado. Contacte al administrador.</p>";
        }
        exit;
    }

    /**
     * Maneja errores fatales.
     * Registra el detalle completo internamente, pero devuelve un mensaje genérico al cliente.
     */
    public static function handleFatalError()
    {
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            error_log("Error fatal: " . $error['message'] . " en " . $error['file'] . ":" . $error['line']);

            if (self::isAjaxRequest()) {
                if (ob_get_level()) ob_clean();
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Error interno del servidor'
                ]);
            } else {
                if (ob_get_level()) ob_clean();
                http_response_code(500);
                echo "<h1>Error interno</h1><p>Ha ocurrido un error inesperado. Contacte al administrador.</p>";
            }
            exit;
        }
    }

    /**
     * Envía respuesta JSON
     */
    public static function sendJsonResponse($data, $statusCode = 200)
    {
        if (ob_get_level())
            ob_clean();
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * Obtiene errores capturados (para logging interno)
     */
    public static function getCapturedErrors()
    {
        return self::$captured_errors;
    }

    /**
     * Verifica si es una petición AJAX
     */
    private static function isAjaxRequest()
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    }
}
