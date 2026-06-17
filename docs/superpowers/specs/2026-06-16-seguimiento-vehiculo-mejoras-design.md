# Design Spec: Mejoras al Seguimiento de Vehículos

**Date:** 2026-06-16
**Status:** Approved
**Scope:** 3 related changes to the SeguimientoVehiculo module

---

## Overview

Three improvements to the vehicle tracking module:
1. Estado General column — replace pill badges with full-cell pastel backgrounds
2. New combined History + Status popup triggered from the estado general cell
3. Odometer popup — add Chart.js trend graph, date filter, and summary stats

All changes use the existing pastel color palette (commit `2c9b438`) and reusable patterns from the codebase.

---

## Change 1: Estado General Column — Full-Cell Background

### Problem
The `.estado-*` pill badges (`border-radius: 20px; padding: 6px 14px`) wrap text awkwardly on multi-word labels like "FUERA DE SERVICIO" when the column is narrow. The word-wrap is needed for responsiveness, but the thin pill shape doesn't accommodate it well.

### Solution
Replace the `<span>` pill badges with full-cell background colors applied to the `<td>` element via DataTable's `createdRow` or a `render` function.

### Implementation

**A) Remove pills, add column render in JS**

In `seguimiento_vehiculo.js`, change the estado_general column from using pre-rendered `estado_general_badge` HTML to raw `estado_general` value, and apply background via `createdRow`:

```javascript
// Column 4 (0-indexed) gets estado_general value, NOT badge HTML
{ data: 'estado_general', render: function(d) { return d; } }

// createdRow applies TD background based on estado_general value
createdRow: function(row, data) {
    // Existing row color logic...

    // Estado general cell background
    var estadoCell = $(row).find('td').eq(4); // column index 4
    var estado = data.estado_general;
    if (estado === 'OPTIMO') {
        estadoCell.css({ 'background-color': '#e8f2ec', 'color': '#1e7f4f', 'font-weight': '700' });
    } else if (estado === 'CON_NOVEDADES') {
        estadoCell.css({ 'background-color': '#fff4e5', 'color': '#b54708', 'font-weight': '700' });
    } else if (estado === 'FUERA_DE_SERVICIO') {
        estadoCell.css({ 'background-color': '#fdecec', 'color': '#b42318', 'font-weight': '700' });
    }
}
```

**B) Remove pill CSS from index.php**

Delete the `.estado-optimo`, `.estado-novedades`, `.estado-fuera-servicio` rules (lines 67-91). Or repurpose them as cell-level classes.

**C) Keep server-side badge for other uses**

The `badgeEstadoGeneral()` method in the model remains — it may be used elsewhere (e.g., inside the history popup). The DataTable row enrichment still sets `estado_general_badge` for potential reuse, but the JS stops consuming it.

**D) Make the cell clickable**

Add a click handler to the estado general column cells that opens the new history popup:

```javascript
$('#tablaVehiculos').on('click', 'td:nth-child(5)', function() {
    var data = tabla.row($(this).closest('tr')).data();
    if (data) abrirHistorialEstado(data.idvehiculos);
});
```

Add a subtle cursor hint via CSS: `#tablaVehiculos td:nth-child(5) { cursor: pointer; }`

CSS hover state: slightly darken the background on hover (e.g., `filter: brightness(0.95)`).

### Colors (pastel palette)

| Estado | Background | Text |
|--------|-----------|------|
| ÓPTIMO | `#e8f2ec` | `#1e7f4f` |
| CON NOVEDADES | `#fff4e5` | `#b54708` |
| FUERA DE SERVICIO | `#fdecec` | `#b42318` |

---

## Change 2: Combined History + Status Popup

### Problem
There is currently no way to see the event history of a vehicle or the latest observation reason from the main table. The `observaciones` field from the latest `seguimiento_vehiculo` record is loaded but never displayed.

### Solution
A new popup (`historial_estado`) opened by clicking the estado general cell. It shows:
- A top card with the latest non-PREOPERACIONAL observation
- Date and event-type filters
- A paginated history table

### Layout

```
┌─────────────────────────────────────────────────┐
│  Historial de Estado — ABC-123                   │
├─────────────────────────────────────────────────┤
│  ┌── Última Observación ────────────────────┐   │
│  │  Estado: [CON NOVEDADES]   2025-06-14     │   │
│  │  "Se detectó fuga de aceite..."            │   │
│  │  Registrado por: José | REVISIÓN SST | Km  │   │
│  └───────────────────────────────────────────┘   │
│                                                  │
│  [Desde: date] [Hasta: date] [Tipo: dropdown] [Filtrar] │
│                                                  │
│  ┌──────────────────────────────────────────┐   │
│  │ Fecha | Tipo | Estado | Observación | Km | Foto │
│  │ ...rows...                                 │   │
│  └──────────────────────────────────────────┘   │
│            Página 1 de N                         │
└─────────────────────────────────────────────────┘
```

### Data fields in table

| Column | Source | Format |
|--------|--------|--------|
| Fecha | `fecha_registro` | `d-m-Y H:i` |
| Tipo | `tipo_evento` | Colored badge |
| Estado | `estado_general` | Colored badge |
| Observación | `observaciones` | Truncated to 100 chars, expandable on click |
| Km | `kilometraje` | Formatted number |
| Foto | `foto_evidencia` | 📷 icon if non-null, clickable to view image |
| Foto Odom. | `img_kilometraje` | 📷 icon if non-null, clickable to view image |

### Top card — Latest Observation

Shows the most recent `seguimiento_vehiculo` record where `tipo_evento != 'PREOPERACIONAL'`.
If no non-preop event exists, show the most recent event of any type with a note: "Sin eventos no-preoperacionales registrados."

Fields displayed:
- Estado actual (badge)
- Fecha del evento
- Observación completa (not truncated)
- Nombre del responsable (JOIN `usuarios` on `id_responsable`)
- Tipo de evento
- Kilometraje

### Filters

- **Desde** (date input): default = today − 30 days
- **Hasta** (date input): default = today
- **Tipo de Evento** (select): Todos, REVISIÓN SST, MANTENIMIENTO, PREOPERACIONAL, TRASLADO ADMIN, POST SINIESTRO, OTRO

### Backend

#### New model method: `getHistorialEstado(int $idVehiculo, string $desde, string $hasta, string $tipo): array`

```sql
SELECT sv.*, u.usu_nombre as responsable_nombre
FROM seguimiento_vehiculo sv
LEFT JOIN usuarios u ON u.idusuarios = sv.id_responsable
WHERE sv.id_vehiculo = ?
  AND sv.fecha_registro BETWEEN ? AND ?
  [AND sv.tipo_evento = ?]  -- if $tipo != 'Todos'
ORDER BY sv.fecha_registro DESC
```

Returns all matching rows. No pagination needed server-side — client-side DataTable or simple limit is sufficient given typical volumes.

**Date handling:** The `hasta` parameter should be treated as inclusive of the full day. In SQL, append ` 23:59:59` to the `hasta` value before using it in `BETWEEN` to ensure records with timestamps on the last day are included.

```php
$hastaDateTime = $hasta . ' 23:59:59';
// Use $hastaDateTime in BETWEEN clause
```

#### New model method: `getUltimaObservacionNoPreop(int $idVehiculo): ?array`

Same query but `WHERE tipo_evento != 'PREOPERACIONAL'` and `LIMIT 1`.

#### New controller action

- `GET accion=get_historial_estado&id=X&desde=Y&hasta=Z&tipo=T` → returns JSON with `{ ultima_obs: {...}, eventos: [...] }`
- `GET accion=form_popup&tipo=historial_estado&id=X` → returns HTML for the popup (server-rendered with initial data, then JS handles re-filtering)

#### New popup file

`nueva_plataforma/view/SeguimientoVehiculo/popups/historial_estado.php`

### JS changes in `seguimiento_vehiculo.js`

- New function `abrirHistorialEstado(id)` — opens popup, fetches data, renders top card + table
- New function `filtrarHistorialEstado()` — re-fetches with updated date/tipo params
- Photo click handler — opens image in a lightbox or new tab

---

## Change 3: Odometer Popup — Chart + Filters

### Problem
The current `historial_kilometraje.php` popup is a plain table. Users can't see trends, filter by date, or get summary stats.

### Solution
Keep the table but add above it:
1. Three summary stat cards
2. A Chart.js line chart
3. Date + fuente filters

### Layout

```
┌─────────────────────────────────────────────────┐
│  Historial de Kilometraje — ABC-123              │
├─────────────────────────────────────────────────┤
│  ┌────────┐ ┌──────────┐ ┌────────────────┐    │
│  │ 85.430 │ │ +2.850   │ │ 95 km/día      │    │
│  │ Km Act │ │ Recorrido │ │ Promedio Diario│    │
│  └────────┘ └──────────┘ └────────────────┘    │
│                                                  │
│  [Desde: date] [Hasta: date] [Fuente: sel] [Filtrar] │
│                                                  │
│  ┌── 📈 Tendencia de Kilometraje ────────────┐  │
│  │         (Chart.js line chart)              │  │
│  │  🔵 Evento  🟢 Preop  🟠 Aceite           │  │
│  └───────────────────────────────────────────┘  │
│                                                  │
│  ┌──────────────────────────────────────────┐   │
│  │ Fecha | Fuente | Kilometraje | Detalle     │   │
│  │ ...same table as before, now filtered...    │   │
│  └──────────────────────────────────────────┘   │
└─────────────────────────────────────────────────┘
```

### Summary Cards

| Card | Calculation | Color |
|------|-------------|-------|
| Km Actual | Latest km reading from any source | Navy `#0c4582` |
| Km Recorridos | latest_km − earliest_km in period | Green `#1e7f4f` |
| Promedio Diario | km_recorridos / days_in_period | Orange `#b54708` |

If not enough data: show "—" or "Sin datos suficientes".

### Chart.js Implementation

**CDN:** `https://cdn.jsdelivr.net/npm/chart.js` (loaded in the popup or page-level)

**Chart type:** Line chart with:
- `fill: true` (area fill with low opacity)
- X-axis: dates (time cartesian axis)
- Y-axis: km values
- Single dataset: all km readings merged and sorted by date
- Point colors: mapped by fuente (Evento = `#0c4582`, Preoperacional = `#1e7f4f`, Cambio Aceite = `#b54708`)
- `pointBackgroundColor`: array matching point colors by source

**Responsive:** Chart.js `responsive: true`, resizes with modal. On narrow screens (mobile), stats cards stack vertically.

**Color palette for chart:**

| Fuente | Color | Hex |
|--------|-------|-----|
| Evento | Navy | `#0c4582` |
| Preoperacional | Green | `#1e7f4f` |
| Cambio Aceite | Orange | `#b54708` |

All are from the existing pastel/UI palette. Use semi-transparent fills: `rgba(12, 69, 130, 0.08)` etc.

### Filters

- **Desde** (date input): default = today − 30 days
- **Hasta** (date input): default = today
- **Fuente** (select): Todas, Evento, Preoperacional, Cambio Aceite

### Backend

#### Update model method: `getHistorialKilometraje(int $idVehiculo, ?string $desde = null, ?string $hasta = null, ?string $fuente = null): array`

Add optional WHERE clauses to the three UNION queries:
- `AND fecha_registro >= ? AND fecha_registro <= ?` on each source query
- Filter by fuente by only running the relevant query

When no filters are provided, behavior is identical to current (all records).

Same date handling as Change 2: append ` 23:59:59` to `hasta` for inclusive day matching.

#### Update controller action

`GET accion=get_historial_km&id_vehiculo=X[&desde=Y&hasta=Z&fuente=F]`

Pass filter params to updated model method. Returns JSON with `{ resumen: {...}, historial: [...], grafico: [...] }` where `grafico` has the data formatted for Chart.js consumption.

### JS changes

- `cargarPopup('historial_kilometraje', id, titulo)` loads the popup with default data
- `filtrarHistorialKm()` — re-fetches with date/fuente params, updates chart + table + cards
- Chart.js instance stored in a variable, destroyed and recreated on filter change
- Chart instantiated in `popupModal`'s `shown.bs.modal` event (needs rendered DOM dimensions)

---

## Files Affected

| File | Change |
|------|--------|
| `nueva_plataforma/assets/js/seguimiento_vehiculo.js` | Column render, cell click, chart logic, filter handlers |
| `nueva_plataforma/view/SeguimientoVehiculo/index.php` | Remove pill CSS, add cell cursor style, load Chart.js CDN |
| `nueva_plataforma/assets/css/usuarios.css` | No changes needed (pills removed, colors live in JS/inline) |
| `nueva_plataforma/model/SeguimientoVehiculoModel.php` | New methods: `getHistorialEstado()`, `getUltimaObservacionNoPreop()`. Update `getHistorialKilometraje()` with optional filters |
| `nueva_plataforma/controller/SeguimientoVehiculoController.php` | New routes: `get_historial_estado`, `form_popup&tipo=historial_estado`. Update `get_historial_km` with filter params |
| `nueva_plataforma/view/SeguimientoVehiculo/popups/historial_estado.php` | **New file** — combined history popup |
| `nueva_plataforma/view/SeguimientoVehiculo/popups/historial_kilometraje.php` | **Rewrite** — add cards, chart, filters, keep table |

---

## Edge Cases & Error Handling

- **No events for vehicle:** Show "Sin eventos registrados" in both popup card and table
- **Null foto_evidencia/img_kilometraje:** No icon shown, or show "—"
- **Date range with no results:** Show "Sin registros en este período" message
- **Chart with single data point:** Chart.js renders a single dot — acceptable. Show label "Datos insuficientes para tendencia" if < 2 points
- **Chart with no data:** Hide chart canvas, show "Sin datos de kilometraje en este período"
- **Very long observaciones:** Truncate in table with ellipsis + "Ver más" expand; show full in top card
- **Invalid date inputs:** Client-side validation (hasta >= desde). If invalid, don't submit.
- **Responsive:** Modal is `modal-lg`. On mobile (< 768px), summary cards stack, filters wrap, chart height reduces, table scrolls horizontally

---

## Testing / Verification

1. Estado general column shows full-cell pastel background for all 3 estados
2. "FUERA DE SERVICIO" text does not wrap awkwardly (full cell width available)
3. Clicking the estado general cell opens the history popup
4. History popup shows latest non-preop observation in the top card
5. History popup filters work: date range, event type
6. History popup shows all relevant columns including photo icons
7. Odometer popup shows summary cards with correct calculations
8. Odometer popup shows Chart.js line chart with correct trend
9. Odometer popup filters update chart, table, and summary cards
10. Chart colors match pastel palette
11. All modals close and reopen cleanly (no duplicate chart instances)
12. Mobile responsive: modals usable on narrow screens
