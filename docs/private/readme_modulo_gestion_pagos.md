# 📦 Módulo de Gestión de Pagos por Proyecto (PHP)

Este README consolida **toda la documentación funcional y técnica** del módulo de gestión de pagos diseñado a lo largo del proceso. Sirve como **documento maestro**, base de desarrollo, referencia para equipos y prompt integral para IA.

---

## 🎯 Visión general

El módulo permite gestionar **planes de pago, cuotas, pagos efectivos, recordatorios y reportes financieros**, tomando como **núcleo el proyecto**.

### Principios clave
- El **proyecto** es el centro de la lógica financiera
- Un cliente puede tener múltiples proyectos
- Cada proyecto puede tener plan y fechas distintas
- Las cuotas **no se eliminan**, solo cambian de estado
- Las cuotas **pagadas nunca se recalculan**
- Todo cambio queda **auditado**
- Arquitectura preparada para crecer como SaaS

---

## 💳 Planes de pago soportados

- **Mensual**: 12 cuotas automáticas
- **Anual**: 1 cuota automática

> El sistema está preparado para planes futuros (trimestral, semestral, personalizados).

---

## 🏗️ Modelo conceptual

### Relaciones
- Cliente → Proyectos (1:N)
- Proyecto → Plan de pago (1:1 activo)
- Proyecto → Cuotas (1:N)
- Cuota → Pagos (1:N)

El **proyecto** define:
- Fecha base de cobro
- Plan activo
- Calendario de cuotas

---

## 🗄️ Modelo de base de datos

### clients
Datos del cliente.

### projects (núcleo)
- client_id
- start_date
- current_plan_id
- status

### payment_plans
- frequency (monthly / yearly)
- installments
- amount

### installments (cuotas)
Estados:
- pendiente
- pagada
- vencida
- cancelada

Campos clave:
- project_id
- plan_id
- due_date
- amount
- status

### payments
Registra pagos efectivos.

### project_plan_history
Auditoría de cambios de plan.

### notifications_log
Registro de notificaciones enviadas.

---

## 👤 Historias de usuario

### HU-01 Crear proyecto con plan
Genera automáticamente las cuotas iniciales.

### HU-02 Ver calendario de cobros
Permite planificar cobranzas.

### HU-03 Registrar pago
Marca cuotas como pagadas.

### HU-04 Recordatorios automáticos
Envía emails 5 días antes del vencimiento.

### HU-05 Cambiar plan de pago
- Cancela cuotas futuras
- Mantiene cuotas pagadas
- Genera nuevas cuotas
- Registra historial

### HU-06 Ver historial de cambios
Auditoría completa.

### HU-07 Reportes financieros
Ingresos reales vs proyectados.

### HU-08 Cambiar fecha de inicio
Recalcula cuotas automáticamente.

### HU-09 Gestión de vencidos
Identificación de cuotas vencidas.

---

## 🔄 Flujo de recálculo de cuotas

Se ejecuta cuando:
- Se crea un proyecto
- Se cambia el plan
- Se cambia la fecha de inicio

### Pasos
1. Identificar evento
2. Obtener proyecto y plan
3. Consultar cuotas existentes
4. Conservar cuotas pagadas
5. Cancelar cuotas futuras no pagadas
6. Definir fecha base
7. Generar nuevas cuotas
8. Registrar historial
9. Preparar notificaciones

### Reglas de oro
- Nunca eliminar cuotas
- Nunca modificar cuotas pagadas
- Un único servicio central de recálculo

---

## 🧱 Arquitectura de servicios (PHP)

### ProjectBillingService
Orquestador principal del módulo.

### InstallmentGenerator
Generación de cuotas por plan.

### PlanChangeService
Caso de uso para cambio de plan.

### InstallmentRepository
Acceso a datos de cuotas.

### ProjectPlanHistoryService
Auditoría de cambios.

**Principio:** los controladores REST no contienen lógica de negocio.

---

## 🌐 Endpoints REST

### Proyectos
- POST /api/projects
- PATCH /api/projects/{id}/start-date
- PATCH /api/projects/{id}/change-plan

### Planes
- GET /api/payment-plans
- POST /api/payment-plans

### Cuotas
- GET /api/projects/{id}/installments
- GET /api/installments/upcoming
- GET /api/installments/overdue

### Pagos
- POST /api/installments/{id}/pay

### Reportes
- GET /api/reports/financial-summary
- GET /api/reports/income-comparison
- GET /api/reports/upcoming-installments

### Historial
- GET /api/projects/{id}/plan-history

---

## ⏰ Cron jobs y notificaciones

### Recordatorio 5 días antes
- Frecuencia: diaria
- Envía email
- Evita duplicados

### Marcar cuotas vencidas
- Frecuencia: diaria
- Cambia estado pendiente → vencida

### Servicios
- ReminderService
- EmailService
- InstallmentStatusService

Preparado para:
- WhatsApp
- SMS
- Escalamientos internos

---

## 📈 Beneficios del diseño

- Control financiero claro
- Alta trazabilidad
- Escalable y mantenible
- Ideal para SaaS
- Compatible con backend generador de APIs REST

---

## 🚀 Próximos pasos sugeridos

- Prorrateos y créditos
- Descuentos y promociones
- Dashboard financiero
- Webhooks y automatizaciones
- Integración con pasarelas de pago

---

**Este README es la referencia oficial del módulo de gestión de pagos por proyecto.**

