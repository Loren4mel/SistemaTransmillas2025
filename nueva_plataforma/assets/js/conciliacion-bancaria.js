const ConciliacionBancaria = (() => {
    const urlController = 'ConciliacionBancariaController.php';
    let bancoActual = 'davivienda';
    let estadoActual = 'pendientes';
    let tablaMovimientos = null;
    let tablaGuias = null;
    let tablaFacturas = null;

    const seleccion = {
        movimientos: new Set(),
        guias: new Set(),
        facturas: new Set()
    };

    function postData(accion, extra = {}) {
        return Object.assign({ accion, banco: bancoActual }, extra);
    }

    function limpiarSeleccion() {
        seleccion.movimientos.clear();
        seleccion.guias.clear();
        seleccion.facturas.clear();
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function formatMoney(value) {
        const clean = String(value ?? '').replace(/[^\d.-]/g, '');
        const number = Number(clean);
        if (!Number.isFinite(number)) {
            return escapeHtml(value ?? '');
        }
        return number.toLocaleString('es-CO', {
            style: 'currency',
            currency: 'COP',
            maximumFractionDigits: 0
        });
    }

    function parseLista(value) {
        if (!value || value === 'Sin facturar') return [];
        let actual = value;
        for (let i = 0; i < 3; i += 1) {
            try {
                const decoded = JSON.parse(actual);
                if (Array.isArray(decoded)) return decoded.map(String).filter(Boolean);
                if (typeof decoded === 'string') {
                    actual = decoded;
                    continue;
                }
            } catch (e) {
                break;
            }
        }
        return [String(value)];
    }

    function renderLista(value, tipo) {
        const lista = parseLista(value);
        if (!lista.length) {
            return '<span class="empty-value">Sin asignar</span>';
        }
        return lista.map(item => `<span class="badge-soft ${tipo}">${escapeHtml(item)}</span>`).join(' ');
    }

    function renderCheckbox(tipo, valor) {
        const id = `${tipo}_${String(valor).replace(/[^a-zA-Z0-9_-]/g, '_')}`;
        return `
            <div class="form-check d-flex justify-content-center">
                <input class="form-check-input selector-${tipo}" type="checkbox"
                       id="${id}" value="${escapeHtml(valor)}">
            </div>`;
    }

    function dataSrc(res) {
        if (!res || !res.success) {
            const mensaje = res && res.mensaje ? res.mensaje : 'No se pudieron cargar los datos.';
            Swal.fire('Error', mensaje, 'error');
            return [];
        }
        return res.data || [];
    }

    function initTablas() {
        tablaMovimientos = $('#tablaMovimientos').DataTable({
            ajax: {
                url: urlController,
                type: 'POST',
                data: function () {
                    return postData('listar_movimientos', { estado: estadoActual });
                },
                dataSrc
            },
            pageLength: 25,
            order: [[2, 'desc']],
            columns: [
                {
                    data: 'id',
                    orderable: false,
                    searchable: false,
                    className: 'text-center',
                    render: data => renderCheckbox('movimiento', data)
                },
                { data: 'id' },
                { data: 'fecha' },
                {
                    data: 'descripcion',
                    className: 'text-wrap-cell',
                    render: data => escapeHtml(data)
                },
                { data: 'documento', defaultContent: '' },
                { data: 'canal', defaultContent: '' },
                { data: 'referencia', defaultContent: '' },
                { data: 'nit', defaultContent: '' },
                {
                    data: 'valor',
                    className: 'text-end',
                    render: data => formatMoney(data)
                },
                {
                    data: 'factura',
                    orderable: false,
                    render: data => renderLista(data, 'factura')
                },
                {
                    data: 'guia',
                    orderable: false,
                    render: data => renderLista(data, 'guia')
                },
                {
                    data: null,
                    orderable: false,
                    searchable: false,
                    className: 'text-center',
                    render: function (data, type, row) {
                        const guias = parseLista(row.guia);
                        if (!guias.length) return '<span class="empty-value">Actualice</span>';
                        return `
                            <button class="btn btn-sm btn-outline-success btn-icon btn-confirmar-pago"
                                    data-guias="${escapeHtml(JSON.stringify(guias))}"
                                    data-valor="${escapeHtml(row.valor ?? '')}"
                                    title="Confirmar transferencia">
                                <i class="fas fa-check"></i>
                            </button>`;
                    }
                },
                {
                    data: 'id',
                    orderable: false,
                    searchable: false,
                    className: 'text-center',
                    render: data => `
                        <button class="btn btn-sm btn-outline-danger btn-icon btn-remover"
                                data-id="${escapeHtml(data)}" title="Remover cruce">
                            <i class="fas fa-minus-circle"></i>
                        </button>`
                },
                {
                    data: 'id',
                    orderable: false,
                    searchable: false,
                    className: 'text-center',
                    render: data => `
                        <button class="btn btn-sm btn-danger btn-icon btn-eliminar"
                                data-id="${escapeHtml(data)}" title="Eliminar movimiento">
                            <i class="fas fa-trash-alt"></i>
                        </button>`
                }
            ],
            language: dataTablesEs()
        });

        tablaGuias = $('#tablaGuias').DataTable({
            ajax: {
                url: urlController,
                type: 'POST',
                data: function () {
                    return postData('listar_guias');
                },
                dataSrc
            },
            pageLength: 10,
            columns: [
                {
                    data: 'guia',
                    orderable: false,
                    searchable: false,
                    className: 'text-center',
                    render: data => renderCheckbox('guia', data)
                },
                {
                    data: 'guia',
                    render: function (data, type, row) {
                        const guia = escapeHtml(data);
                        return `<button type="button" class="btn btn-link p-0 btn-ver-servicio"
                                        data-servicio="${escapeHtml(row.id_servicio)}">${guia}</button>`;
                    }
                },
                { data: 'fecha' },
                { data: 'operador', defaultContent: '' },
                {
                    data: 'valor',
                    className: 'text-end',
                    render: data => formatMoney(data)
                },
                {
                    data: 'imagen',
                    orderable: false,
                    searchable: false,
                    render: function (data) {
                        if (!data) return '<span class="empty-value">Sin foto</span>';
                        const url = `/SistemaTransmillas2025/img_transacciones/${escapeHtml(data)}`;
                        return `<a href="${url}" target="_blank">Ver foto</a>`;
                    }
                }
            ],
            language: dataTablesEs()
        });

        tablaFacturas = $('#tablaFacturas').DataTable({
            ajax: {
                url: urlController,
                type: 'POST',
                data: function () {
                    return postData('listar_facturas');
                },
                dataSrc
            },
            pageLength: 10,
            order: [[2, 'desc']],
            columns: [
                {
                    data: 'factura',
                    orderable: false,
                    searchable: false,
                    className: 'text-center',
                    render: data => renderCheckbox('factura', data)
                },
                { data: 'factura' },
                { data: 'fecha' },
                { data: 'credito' },
                { data: 'nit', defaultContent: '' },
                {
                    data: 'valor',
                    className: 'text-end',
                    render: data => formatMoney(data)
                }
            ],
            language: dataTablesEs()
        });
    }

    function recargarTodo() {
        limpiarSeleccion();
        tablaMovimientos.ajax.reload();
        if (estadoActual === 'pendientes') {
            tablaGuias.ajax.reload();
            tablaFacturas.ajax.reload();
        }
        actualizarEstadoVisual();
    }

    function actualizarEstadoVisual() {
        const esPendiente = estadoActual === 'pendientes';
        $('.pendientes-only').toggle(esPendiente);
        $('.acciones-pendientes').toggle(esPendiente);

        const titulo = {
            pendientes: 'Movimientos pendientes',
            facturas: 'Historico de facturas',
            guias: 'Historico de guias'
        }[estadoActual] || 'Movimientos';
        $('#tituloMovimientos').text(titulo);
    }

    function asignarFacturas() {
        if (!seleccion.movimientos.size || !seleccion.facturas.size) {
            Swal.fire('Validacion', 'Seleccione al menos un movimiento y una factura.', 'warning');
            return;
        }

        Swal.fire({
            title: 'Comentario',
            input: 'text',
            inputPlaceholder: 'Ingrese el comentario de pago',
            showCancelButton: true,
            confirmButtonText: 'Actualizar',
            cancelButtonText: 'Cancelar',
            inputValidator: value => !value ? 'El comentario es obligatorio.' : undefined
        }).then(result => {
            if (!result.isConfirmed) return;

            $.ajax({
                url: urlController,
                type: 'POST',
                data: postData('asignar_facturas', {
                    movimientos: JSON.stringify(Array.from(seleccion.movimientos)),
                    facturas: JSON.stringify(Array.from(seleccion.facturas)),
                    descripcion: result.value
                }),
                success: manejarRespuestaAccion,
                error: () => Swal.fire('Error', 'No se pudo conectar con el servidor.', 'error')
            });
        });
    }

    function asignarGuias() {
        if (!seleccion.movimientos.size || !seleccion.guias.size) {
            Swal.fire('Validacion', 'Seleccione al menos un movimiento y una guia.', 'warning');
            return;
        }

        $.ajax({
            url: urlController,
            type: 'POST',
            data: postData('asignar_guias', {
                movimientos: JSON.stringify(Array.from(seleccion.movimientos)),
                guias: JSON.stringify(Array.from(seleccion.guias))
            }),
            success: manejarRespuestaAccion,
            error: () => Swal.fire('Error', 'No se pudo conectar con el servidor.', 'error')
        });
    }

    function manejarRespuestaAccion(res) {
        const respuesta = typeof res === 'object' ? res : JSON.parse(res || '{}');
        if (respuesta.success) {
            Swal.fire({
                icon: 'success',
                title: 'Listo',
                text: respuesta.mensaje || 'Operacion realizada correctamente.',
                timer: 1800,
                showConfirmButton: false
            });
            recargarTodo();
        } else {
            Swal.fire('Error', respuesta.mensaje || 'No se pudo completar la operacion.', 'error');
        }
    }

    function confirmarRemover(id) {
        Swal.fire({
            title: 'Remover cruce',
            text: 'El movimiento volvera a quedar disponible para conciliar.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Remover',
            cancelButtonText: 'Cancelar'
        }).then(result => {
            if (!result.isConfirmed) return;
            $.ajax({
                url: urlController,
                type: 'POST',
                data: postData('remover', { id }),
                success: manejarRespuestaAccion,
                error: () => Swal.fire('Error', 'No se pudo conectar con el servidor.', 'error')
            });
        });
    }

    function confirmarEliminar(id) {
        Swal.fire({
            title: 'Eliminar movimiento',
            text: 'Esta accion elimina el registro bancario.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Eliminar',
            cancelButtonText: 'Cancelar'
        }).then(result => {
            if (!result.isConfirmed) return;
            $.ajax({
                url: urlController,
                type: 'POST',
                data: postData('eliminar', { id }),
                success: manejarRespuestaAccion,
                error: () => Swal.fire('Error', 'No se pudo conectar con el servidor.', 'error')
            });
        });
    }

    function abrirConfirmacionPago(guias, valor) {
        $('#formConfirmarPago')[0].reset();
        $('#confirmarGuias').val(JSON.stringify(guias));
        $('#confirmarGuiasTexto').val(guias.join(', '));
        $('#valorConfirmado').val(valor || '');
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalConfirmarPago')).show();
    }

    function guardarConfirmacionPago() {
        const form = document.getElementById('formConfirmarPago');
        const datos = new FormData(form);
        datos.append('accion', 'confirmar_pago_guia');
        datos.append('banco', bancoActual);

        if (!$('#numeroTransaccion').val().trim() || !$('#valorConfirmado').val().trim()) {
            Swal.fire('Validacion', 'Complete numero de transaccion y valor confirmado.', 'warning');
            return;
        }

        $.ajax({
            url: urlController,
            type: 'POST',
            data: datos,
            cache: false,
            contentType: false,
            processData: false,
            success: function (res) {
                bootstrap.Modal.getOrCreateInstance(document.getElementById('modalConfirmarPago')).hide();
                manejarRespuestaAccion(res);
            },
            error: () => Swal.fire('Error', 'No se pudo conectar con el servidor.', 'error')
        });
    }

    function registrarEventos() {
        document.querySelectorAll('.conciliacion-sections .collapse').forEach(section => {
            section.addEventListener('shown.bs.collapse', function () {
                if (tablaMovimientos) tablaMovimientos.columns.adjust();
                if (tablaGuias) tablaGuias.columns.adjust();
                if (tablaFacturas) tablaFacturas.columns.adjust();
            });
        });

        $('#bancoTabs').on('click', '.nav-link', function () {
            $('#bancoTabs .nav-link').removeClass('active');
            $(this).addClass('active');
            bancoActual = $(this).data('banco');
            recargarTodo();
        });

        $('#estadoTabs').on('click', '.nav-link', function () {
            $('#estadoTabs .nav-link').removeClass('active');
            $(this).addClass('active');
            estadoActual = $(this).data('estado');
            recargarTodo();
        });

        $('#tablaMovimientos').on('change', '.selector-movimiento', function () {
            actualizarSet(seleccion.movimientos, this.value, this.checked);
        });

        $('#tablaGuias').on('change', '.selector-guia', function () {
            actualizarSet(seleccion.guias, this.value, this.checked);
        });

        $('#tablaFacturas').on('change', '.selector-factura', function () {
            actualizarSet(seleccion.facturas, this.value, this.checked);
        });

        $('#tablaMovimientos').on('click', '.btn-remover', function () {
            confirmarRemover($(this).data('id'));
        });

        $('#tablaMovimientos').on('click', '.btn-eliminar', function () {
            confirmarEliminar($(this).data('id'));
        });

        $('#tablaMovimientos').on('click', '.btn-confirmar-pago', function () {
            const guias = parseLista($(this).attr('data-guias'));
            abrirConfirmacionPago(guias, $(this).data('valor'));
        });

        $('#tablaGuias').on('click', '.btn-ver-servicio', function () {
            const servicio = $(this).data('servicio');
            const url = `https://sistema.transmillas.com/detalle_pop.php?id_param=${encodeURIComponent(servicio)}&tabla=Recogidas`;
            window.open(url, 'PopupWindow', 'width=900,height=650,scrollbars=yes,resizable=yes');
        });

        $('#btnAsignarFacturas').on('click', asignarFacturas);
        $('#btnAsignarGuias').on('click', asignarGuias);
        $('#btnGuardarConfirmacion').on('click', guardarConfirmacionPago);
    }

    function actualizarSet(set, value, checked) {
        if (checked) {
            set.add(String(value));
        } else {
            set.delete(String(value));
        }
    }

    function dataTablesEs() {
        return {
            decimal: '',
            emptyTable: 'Sin registros disponibles',
            info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
            infoEmpty: 'Mostrando 0 a 0 de 0 registros',
            infoFiltered: '(filtrado de _MAX_ registros)',
            lengthMenu: 'Mostrar _MENU_',
            loadingRecords: 'Cargando...',
            processing: 'Procesando...',
            search: 'Buscar:',
            zeroRecords: 'No se encontraron registros',
            paginate: {
                first: 'Primero',
                last: 'Ultimo',
                next: 'Siguiente',
                previous: 'Anterior'
            }
        };
    }

    function init() {
        initTablas();
        registrarEventos();
        actualizarEstadoVisual();
    }

    return { init };
})();

$(document).ready(function () {
    ConciliacionBancaria.init();
});
