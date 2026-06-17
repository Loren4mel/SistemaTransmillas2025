# SeguimientoVehiculo Mejoras — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Three improvements: full-cell pastel backgrounds for estado general column, new combined history + status popup, and odometer popup with Chart.js trend graph + date filters.

**Architecture:** All changes are localized to 7 files in the existing MVC pattern. Backend adds 2 model methods + 2 controller routes. Frontend adds Chart.js from CDN and new JS functions for the history popup and chart rendering.

**Tech Stack:** PHP 8.x, MySQL, jQuery, Bootstrap 5.3, DataTables 1.13, Chart.js 4.x (CDN), SweetAlert2

---

### Task 1: Change 1 — Estado General Column Full-Cell Background

**Files:**
- Modify: `nueva_plataforma/assets/js/seguimiento_vehiculo.js`
- Modify: `nueva_plataforma/view/SeguimientoVehiculo/index.php`

- [ ] **Step 1: Update JS column config and createdRow**

In `seguimiento_vehiculo.js`, change column 4 (estado_general) from using `estado_general_badge` HTML to raw `estado_general` value. Update `createdRow` to apply full-cell background colors instead of relying on span badges.

- [ ] **Step 2: Update index.php CSS and add Chart.js CDN**

Remove the `.estado-optimo`, `.estado-novedades`, `.estado-fuera-servicio` pill CSS rules. Add cell cursor pointer style for clickable estado column. Add Chart.js CDN script tag. Add the `abrirHistorialEstado` function reference in the onclick handler.

- [ ] **Step 3: Commit Change 1**

Commit with message describing the full-cell background change.

---

### Task 2: Change 2 — Combined History + Status Popup Backend

**Files:**
- Modify: `nueva_plataforma/model/SeguimientoVehiculoModel.php`
- Modify: `nueva_plataforma/controller/SeguimientoVehiculoController.php`
- Create: `nueva_plataforma/view/SeguimientoVehiculo/popups/historial_estado.php`

- [ ] **Step 1: Add model methods**

Add `getUltimaObservacionNoPreop(int $idVehiculo): ?array` — gets latest non-PREOPERACIONAL event.
Add `getHistorialEstado(int $idVehiculo, string $desde, string $hasta, string $tipo): array` — gets filtered event history with responsable name.

- [ ] **Step 2: Add controller routes**

Add `accion=get_historial_estado` route (returns JSON).
Add `form_popup&tipo=historial_estado` case (returns HTML popup).

- [ ] **Step 3: Create historial_estado.php popup**

PHP file that renders the top observation card, filters, and history table. Initial data loaded server-side. JS handles re-filtering via AJAX.

- [ ] **Step 4: Add JS functions for history popup**

Add `abrirHistorialEstado(id)`, `filtrarHistorialEstado()`, and photo click handler to `seguimiento_vehiculo.js`.

- [ ] **Step 5: Commit Change 2**

---

### Task 3: Change 3 — Odometer Popup with Chart.js

**Files:**
- Modify: `nueva_plataforma/model/SeguimientoVehiculoModel.php`
- Modify: `nueva_plataforma/controller/SeguimientoVehiculoController.php`
- Modify: `nueva_plataforma/view/SeguimientoVehiculo/popups/historial_kilometraje.php`
- Modify: `nueva_plataforma/assets/js/seguimiento_vehiculo.js`

- [ ] **Step 1: Update model method with date/fuente filters**

Update `getHistorialKilometraje()` to accept optional `$desde`, `$hasta`, `$fuente` params with WHERE clauses.

- [ ] **Step 2: Update controller route**

Update `get_historial_km` to accept and pass filter params. Return JSON with `{ resumen, historial, grafico }`.

- [ ] **Step 3: Rewrite historial_kilometraje.php popup**

Add summary cards, date/fuente filters, chart canvas, and keep the records table.

- [ ] **Step 4: Add JS chart and filter logic**

Add `filtrarHistorialKm()`, Chart.js instantiation in modal `shown.bs.modal`, chart destroy/recreate on filter.

- [ ] **Step 5: Commit Change 3**

---

### Task 4: Verification

- [ ] **Step 1: Manual verification checklist**

Walk through the 12 verification items from the spec.
