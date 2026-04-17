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

        // Procesar imágenes
        $imagenKilo = $this->procesarImagenKilometraje($files);
        $imagenInspeccion = $this->procesarImagenInspeccionInicial($files, $dataJson);

        if ($idPre > 0) {
            // Actualización (validación)
            return $this->actualizarRegistro(
                $idPre, $dataJson, $descValidada, $estado,
                $accionCorrectiva, $responsable, $temperatura,
                $kilometraje, $idVehiculo, $imagenKilo, $imagenInspeccion
            );
        } else {
            // Nuevo registro
            return $this->insertarRegistro(
                $idVehiculo, $tipoVehiculo, $fechaHora, $idUsuario,
                $dataJson, $observaciones, $accionCorrectiva,
                $responsable, $temperatura, $kilometraje,
                $limpiomaleta, $imagenKilo, $estado, $files, $imagenInspeccion
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

    /**
     * Obtiene el último registro de un usuario
     */
    public function obtenerUltimoRegistro($idUsuario)
    {
        return $this->model->obtenerUltimoRegistro($idUsuario);
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
     * Procesa la imagen de inspección inicial cuando se desmarca el checkbox
     */
    private function procesarImagenInspeccionInicial($files, $dataJson)
    {
        // Verificar si hay datos de encuesta
        $data = json_decode($dataJson, true);
        if (!$data) return '';

        // Buscar si inspec_1 NO está marcado (checkbox desmarcado = valor no presente o null)
        if (isset($data['inspec_1']) && ($data['inspec_1'] === null || $data['inspec_1'] === '0' || !isset($data['inspec_1']))) {
            // Buscar archivo de foto con nombre inspec_1_foto
            if (isset($files['inspec_1_foto']) && $files['inspec_1_foto']['error'] === UPLOAD_ERR_OK) {
                $nombreArchivo = "inspeccion_" . date("Y-m-d-H-i-s") . "_" . $files['inspec_1_foto']['name'];
                $ruta = "./preoperacional/" . $nombreArchivo;
                if (move_uploaded_file($files['inspec_1_foto']['tmp_name'], $ruta)) {
                    return $ruta;
                }
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
        $kilometraje, $idVehiculo, $imagenKilo, $imagenInspeccion
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

        // Guardar imagen de inspección si existe
        if ($imagenInspeccion) {
            $this->model->guardarImagen($imagenInspeccion, $idPre, 3); // Tipo 3 = inspección
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
        $limpiomaleta, $imagenKilo, $estado, $files, $imagenInspeccion
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

        // Si hay imagen de inspección, agregarla a observaciones
        if ($imagenInspeccion) {
            $datosInsert['pre_obsevaciones'] = $observaciones . "\n[FOTO INSPECCIÓN: " . $imagenInspeccion . "]";
        }

        $idInsertado = $this->model->insertarPreoperacional($datosInsert);

        if ($idInsertado) {
            // Imagen de temperatura
            if (isset($files['param20']) && $files['param20']['error'] === UPLOAD_ERR_OK) {
                $this->model->guardarImagen($files['param20'], $idInsertado, 2);
            }

            // Imagen de kilometraje
            if ($imagenKilo) {
                $this->model->actualizarImagenKilo($idInsertado, $imagenKilo);
            }

            // Imagen de inspección inicial
            if ($imagenInspeccion) {
                $this->model->guardarImagen($imagenInspeccion, $idInsertado, 3); // Tipo 3 = inspección
            }

            if ($kilometraje > 0 && $idVehiculo > 0) {
                $this->model->actualizarKilometrajeVehiculo($idVehiculo, $kilometraje);
            }
        }

        return ['success' => true, 'message' => 'Preoperacional guardado correctamente'];
    }
}
