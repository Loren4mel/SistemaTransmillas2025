<?php
/**
 * ErrorHandler - Manejador centralizado de errores y excepciones
 * 
 * Proporciona manejo uniforme de errores para aplicaciones web y AJAX
 */

class ErrorHandler
{
    private static $captured_errors = [];

    /**
     * Configura los manejadores de errores y excepciones
     */
    public static function setup()
    {
        ini_set('display_errors', 1);
        error_reporting(E_ALL);
        mysqli_report(MYSQLI_REPORT_ERROR);

        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
        register_shutdown_function([self::class, 'handleFatalError']);
    }

    /**
     * Maneja errores capturados
     */
    public static function handleError($errno, $errstr, $errfile, $errline)
    {
        self::$captured_errors[] = [
            'type' => $errno,
            'message' => $errstr,
            'file' => $errfile,
            'line' => $errline
        ];
        return true;
    }

    /**
     * Maneja excepciones no capturadas
     */
    public static function handleException($exception)
    {
        error_log("Excepcion no capturada: " . $exception->getMessage());
        
        if (self::isAjaxRequest()) {
            self::sendJsonResponse([
                'error' => $exception->getMessage(), 
                'trace' => $exception->getTraceAsString()
            ], 500);
        } else {
            echo "<h1>Error</h1><pre>" . $exception->getMessage() . "\n" . $exception->getTraceAsString() . "</pre>";
        }
        exit;
    }

    /**
     * Maneja errores fatales
     */
    public static function handleFatalError()
    {
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            error_log("Error fatal: " . $error['message']);
            
            if (self::isAjaxRequest()) {
                ob_clean();
                header('Content-Type: application/json');
                echo json_encode([
                    'fatal_error' => $error['message'], 
                    'file' => $error['file'], 
                    'line' => $error['line']
                ]);
            } else {
                echo "<h1>Error fatal</h1><pre>" . $error['message'] . " en " . $error['file'] . ":" . $error['line'] . "</pre>";
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
     * Obtiene errores capturados
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
