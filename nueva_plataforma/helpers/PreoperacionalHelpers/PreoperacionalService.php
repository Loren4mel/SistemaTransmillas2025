<?php
/**
 * PreoperacionalService - Maneja la lógica de negocio del preoperacional
 * 
 * Esta clase centraliza todas las operaciones de negocio relacionadas
 * con el preoperacional, separándolas del controlador.
 */

require_once __DIR__ . '/../../model/PreoperacionalModel.php';

class PreoperacionalService
{
    private $model;

    public function __construct()
    {
        $this->model = new PreoperacionalModel();
    }

    /**
     * Procesa el guardado de un registro preoperacional (nuevo o actualización)
     */
    public function guardarRegistro($postData, $files)
    {
        $estado = $postData['estado'] ?? '';
        $dataJson = $postData['data'] ?? '';
        $idVehiculo = !empty($postData['param1']) ? (int) $postData['param1'] : null;
        $tipoVehiculo = $postData['param2'] ?? '';
        $idUsuario = (int) ($postData['user'] ?? $_SESSION['usuario_id']);
        $fechaHora = date('Y-m-d H:i:s');
        $observaciones = $postData['param7'] ?? '';
        $accionCorrectiva = $postData['param8'] ?? '';
        $responsable = $postData['param9'] ?? '';
        $temperatura = $postData['param19'] ?? '';
        $kilometraje = (int) ($postData['param12'] ?? 0);
        $limpiomaleta = $postData['param21'] ?? '';
        $descValidada = $postData['param10'] ?? '';
        $idPre = (int) ($postData['param11'] ?? 0);

        // Procesar imagen de kilometraje
        $imagenKilo = $this->procesarImagenKilometraje($files);

        if ($idPre > 0) {
            // Actualización (validación)
            return $this->actualizarRegistro(
                $idPre, $dataJson, $descValidada, $estado, 
                $accionCorrectiva, $responsable, $temperatura, 
                $kilometraje, $idVehiculo, $imagenKilo
            );
        } else {
            // Nuevo registro
            return $this->insertarRegistro(
                $idVehiculo, $tipoVehiculo, $fechaHora, $idUsuario,
                $dataJson, $observaciones, $accionCorrectiva,
                $responsable, $temperatura, $kilometraje,
                $limpiomaleta, $imagenKilo, $estado, $files
            );
        }
    }

    /**
     * Busca datos de precarga para un usuario en una fecha específica
     */
    public function buscarDatosPrecarga($idUser, $fecha, $campo)
    {
        if ($campo !== 'preencuesta') {
            return null;
        }
        return $this->model->obtenerDatosParaPrecarga($idUser, $fecha, $campo);
    }

    /**
     * Obtiene los datos del vehículo y usuario
     */
    public function obtenerDatosVehiculoYUsuario($idUsuario, $idVehiculo = null)
    {
        return $this->model->obtenerDatosVehiculoYUsuario($idUsuario, $idVehiculo);
    }

    /**
     * Obtiene un registro por fecha
     */
    public function obtenerRegistroPorFecha($idUsuario, $fecha)
    {
        return $this->model->obtenerRegistroPorFecha($idUsuario, $fecha);
    }

    /**
     * Obtiene un registro por ID
     */
    public function obtenerRegistroPorId($idPre)
    {
        return $this->model->obtenerRegistroPorId($idPre);
    }

    // ==================== MÉTODOS PRIVADOS ====================

    /**
     * Procesa la imagen de kilometraje
     */
    private function procesarImagenKilometraje($files)
    {
        if (isset($files['param30']) && $files['param30']['error'] === UPLOAD_ERR_OK) {
            $nombreArchivo = date("Y-m-d-H-i-s") . "_" . $files['param30']['name'];
            $ruta = "./preoperacional/" . $nombreArchivo;
            if (move_uploaded_file($files['param30']['tmp_name'], $ruta)) {
                return $ruta;
            }
        }
        return '';
    }

    /**
     * Actualiza un registro existente
     */
    private function actualizarRegistro(
        $idPre, $dataJson, $descValidada, $estado,
        $accionCorrectiva, $responsable, $temperatura,
        $kilometraje, $idVehiculo, $imagenKilo
    ) {
        global $_SESSION;
        
        $datosActualizar = [
            'prefechavalidacion' => date('Y-m-d H:i:s'),
            'predatosvalidados' => $dataJson,
            'pre_descvalidada' => $descValidada,
            'pre_iduservalida' => $_SESSION['usuario_id'],
            'preestado' => ($estado == 'covid19') ? 'Validado Covid19' : 'Validado',
            'pre_correctiva' => $accionCorrectiva,
            'pre_responsable' => $responsable,
            'pre_temperatura' => $temperatura,
            'pre_kilrecorridos' => $kilometraje
        ];
        
        $ok = $this->model->actualizarPreoperacional($idPre, $datosActualizar);
        
        if ($ok && $kilometraje > 0 && $idVehiculo > 0) {
            $this->model->actualizarKilometrajeVehiculo($idVehiculo, $kilometraje);
        }
        
        if ($imagenKilo) {
            $this->model->actualizarImagenKilo($idPre, $imagenKilo);
        }

        return ['success' => true, 'message' => 'Preoperacional actualizado correctamente'];
    }

    /**
     * Inserta un nuevo registro
     */
    private function insertarRegistro(
        $idVehiculo, $tipoVehiculo, $fechaHora, $idUsuario,
        $dataJson, $observaciones, $accionCorrectiva,
        $responsable, $temperatura, $kilometraje,
        $limpiomaleta, $imagenKilo, $estado, $files
    ) {
        $datosInsert = [
            'prevehiculo' => $idVehiculo,
            'pretipovehiculo' => $tipoVehiculo,
            'prefechaingreso' => $fechaHora,
            'preidusuario' => $idUsuario,
            'preencuesta' => $dataJson,
            'pre_obsevaciones' => $observaciones,
            'pre_correctiva' => $accionCorrectiva,
            'pre_responsable' => $responsable,
            'pre_temperatura' => $temperatura,
            'pre_kilrecorridos' => $kilometraje,
            'pre_limpiomaleta' => $limpiomaleta,
            'pre_img_kilo' => $imagenKilo,
            'preestado' => ($estado == 'covid19') ? 'covid19' : 'pendiente'
        ];

        $idInsertado = $this->model->insertarPreoperacional($datosInsert);
        
        if ($idInsertado) {
            // Imagen de temperatura
            if (isset($files['param20']) && $files['param20']['error'] === UPLOAD_ERR_OK) {
                $this->model->guardarImagen($files['param20'], $idInsertado, 2);
            }
            
            if ($kilometraje > 0 && $idVehiculo > 0) {
                $this->model->actualizarKilometrajeVehiculo($idVehiculo, $kilometraje);
            }
        }

        return ['success' => true, 'message' => 'Preoperacional guardado correctamente'];
    }
}
