# 💳 Módulo de Gestión de Pagos por Proyecto

## 📋 Índice

- [Visión General](#-visión-general)
- [Características](#-características)
- [Arquitectura](#-arquitectura)
- [Modelo de Datos](#-modelo-de-datos)
- [API REST](#-api-rest)
- [Servicios de Negocio](#-servicios-de-negocio)
- [Cron Jobs](#-cron-jobs)
- [Instalación](#-instalación)
- [Ejemplos de Uso](#-ejemplos-de-uso)
- [Flujos de Trabajo](#-flujos-de-trabajo)

---

## 🎯 Visión General

El **Módulo de Gestión de Pagos por Proyecto** es un sistema completo de facturación y cobranza integrado en Data2Rest. Permite gestionar planes de pago, cuotas automáticas, pagos efectivos, recordatorios y reportes financieros, tomando como **núcleo el proyecto**.

### Principios Clave

- ✅ El **proyecto** es el centro de la lógica financiera
- ✅ Un cliente puede tener múltiples proyectos
- ✅ Cada proyecto puede tener plan y fechas distintas
- ✅ Las cuotas **no se eliminan**, solo cambian de estado
- ✅ Las cuotas **pagadas nunca se recalculan**
- ✅ Todo cambio queda **auditado**
- ✅ Arquitectura preparada para crecer como SaaS

---

## ✨ Características

### 💰 Gestión de Planes de Pago

- **Plan Mensual**: 12 cuotas automáticas
- **Plan Anual**: 1 cuota automática
- Soporte para planes personalizados futuros
- Configuración de montos y frecuencias

### 📊 Gestión de Cuotas

- Generación automática según el plan
- Estados: `pendiente`, `pagada`, `vencida`, `cancelada`
- Recálculo inteligente al cambiar plan o fecha
- Preservación de cuotas pagadas

### 🔔 Notificaciones Automáticas

- Recordatorios 5 días antes del vencimiento
- Notificaciones de cuotas vencidas
- Emails HTML profesionales
- Registro completo de envíos

### 📈 Reportes Financieros

- Resumen financiero general
- Ingresos reales vs proyectados
- Tasa de cobro
- Calendario de cobranzas
- Estadísticas por cliente

### 👥 Gestión de Clientes

- CRUD completo de clientes
- Múltiples proyectos por cliente
- Resumen financiero por cliente
- Soft delete

---

## 🏗️ Arquitectura

```
src/Modules/Billing/
├── Controllers/
│   ├── ClientController.php          # CRUD de clientes
│   ├── ProjectController.php         # Proyectos con billing
│   ├── PaymentPlanController.php     # Gestión de planes
│   ├── InstallmentController.php     # Gestión de cuotas
│   └── ReportController.php          # Reportes financieros
├── Services/
│   ├── InstallmentGenerator.php      # Generación de cuotas
│   ├── PlanChangeService.php         # Cambio de plan
│   ├── ReminderService.php           # Recordatorios
│   ├── EmailService.php              # Envío de emails
│   └── InstallmentStatusService.php  # Actualización de estados
└── Repositories/
    └── InstallmentRepository.php     # Acceso a datos de cuotas

scripts/
├── billing_send_reminders.php        # Cron: Recordatorios
└── billing_mark_overdue.php          # Cron: Marcar vencidas
```

### Principios de Diseño

- **Separación de responsabilidades**: Controladores delgados, servicios con lógica de negocio
- **Repositorios**: Encapsulan acceso a datos
- **Transacciones**: Operaciones críticas protegidas
- **Logging**: Todas las acciones importantes registradas
- **Validaciones**: En múltiples capas

---

## 🗄️ Modelo de Datos

### Tablas Principales

#### `clients`
Información de clientes.

```sql
CREATE TABLE clients (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT,
    phone TEXT,
    address TEXT,
    tax_id TEXT,
    status TEXT DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
)
```

#### `payment_plans`
Planes de pago disponibles.

```sql
CREATE TABLE payment_plans (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    frequency TEXT NOT NULL,        -- 'monthly' | 'yearly'
    installments INTEGER NOT NULL,  -- Número de cuotas
    amount REAL NOT NULL,           -- Monto total
    description TEXT,
    status TEXT DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)
```

#### `projects` (extendido)
Proyectos con información de billing.

```sql
-- Campos agregados:
client_id INTEGER,              -- Relación con cliente
start_date DATE,                -- Fecha de inicio de cobros
current_plan_id INTEGER,        -- Plan activo
billing_status TEXT DEFAULT 'active'
```

#### `installments`
Cuotas generadas por plan.

```sql
CREATE TABLE installments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    project_id INTEGER NOT NULL,
    plan_id INTEGER NOT NULL,
    installment_number INTEGER NOT NULL,
    due_date DATE NOT NULL,
    amount REAL NOT NULL,
    status TEXT DEFAULT 'pendiente',  -- 'pendiente' | 'pagada' | 'vencida' | 'cancelada'
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY(plan_id) REFERENCES payment_plans(id)
)
```

#### `payments`
Pagos efectivos realizados.

```sql
CREATE TABLE payments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    installment_id INTEGER NOT NULL,
    amount REAL NOT NULL,
    payment_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    payment_method TEXT,
    reference TEXT,
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(installment_id) REFERENCES installments(id) ON DELETE CASCADE
)
```

#### `project_plan_history`
Auditoría de cambios de plan.

```sql
CREATE TABLE project_plan_history (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    project_id INTEGER NOT NULL,
    old_plan_id INTEGER,
    new_plan_id INTEGER,
    old_start_date DATE,
    new_start_date DATE,
    change_reason TEXT,
    changed_by INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(project_id) REFERENCES projects(id) ON DELETE CASCADE
)
```

#### `notifications_log`
Registro de notificaciones enviadas.

```sql
CREATE TABLE notifications_log (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    installment_id INTEGER NOT NULL,
    notification_type TEXT NOT NULL,  -- 'reminder' | 'overdue'
    recipient TEXT NOT NULL,
    status TEXT DEFAULT 'sent',       -- 'sent' | 'failed'
    sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    error_message TEXT,
    FOREIGN KEY(installment_id) REFERENCES installments(id) ON DELETE CASCADE
)
```

---

## 🌐 API REST

### Clientes

#### `GET /api/billing/clients`
Lista todos los clientes.

**Query Parameters:**
- `status` (opcional): `active` | `inactive` | `all` (default: `active`)

**Respuesta:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Empresa ABC",
      "email": "contacto@abc.com",
      "phone": "+1234567890",
      "status": "active"
    }
  ],
  "count": 1
}
```

#### `POST /api/billing/clients`
Crea un nuevo cliente.

**Body:**
```json
{
  "name": "Empresa XYZ",
  "email": "info@xyz.com",
  "phone": "+0987654321",
  "address": "Calle Principal 123",
  "tax_id": "12345678-9"
}
```

#### `GET /api/billing/clients/{id}`
Obtiene información de un cliente con sus proyectos.

#### `PUT /api/billing/clients/{id}`
Actualiza un cliente.

#### `DELETE /api/billing/clients/{id}`
Desactiva un cliente (soft delete).

---

### Proyectos con Billing

#### `POST /api/billing/projects`
Crea un proyecto con plan de pago.

**Body:**
```json
{
  "name": "Proyecto Web 2024",
  "description": "Desarrollo de sitio web corporativo",
  "client_id": 1,
  "plan_id": 1,
  "start_date": "2024-01-15"
}
```

**Respuesta:**
```json
{
  "success": true,
  "message": "Proyecto creado exitosamente",
  "project_id": 5,
  "installments_created": 12
}
```

#### `PATCH /api/billing/projects/{id}/change-plan`
Cambia el plan de pago de un proyecto.

**Body:**
```json
{
  "new_plan_id": 2,
  "new_start_date": "2024-02-01",
  "reason": "Cliente solicitó cambio a plan anual",
  "user_id": 1
}
```

**Respuesta:**
```json
{
  "success": true,
  "message": "Plan cambiado exitosamente",
  "old_plan_id": 1,
  "new_plan_id": 2,
  "recalculation": {
    "paid_installments_kept": 2,
    "new_installments_created": 1,
    "total_installments": 3
  }
}
```

#### `PATCH /api/billing/projects/{id}/start-date`
Cambia la fecha de inicio de un proyecto.

**Body:**
```json
{
  "new_start_date": "2024-02-01",
  "user_id": 1
}
```

#### `GET /api/billing/projects/{id}/plan-history`
Obtiene el historial de cambios de plan.

---

### Planes de Pago

#### `GET /api/billing/payment-plans`
Lista todos los planes activos.

**Respuesta:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Plan Mensual",
      "frequency": "monthly",
      "installments": 12,
      "amount": 12000,
      "status": "active"
    },
    {
      "id": 2,
      "name": "Plan Anual",
      "frequency": "yearly",
      "installments": 1,
      "amount": 10000,
      "status": "active"
    }
  ],
  "count": 2
}
```

#### `POST /api/billing/payment-plans`
Crea un nuevo plan de pago.

**Body:**
```json
{
  "name": "Plan Trimestral",
  "frequency": "monthly",
  "installments": 4,
  "amount": 4000,
  "description": "Plan de pago trimestral"
}
```

#### `GET /api/billing/payment-plans/{id}`
Obtiene información de un plan con estadísticas de uso.

#### `PUT /api/billing/payment-plans/{id}`
Actualiza un plan de pago.

---

### Cuotas

#### `GET /api/billing/projects/{id}/installments`
Obtiene todas las cuotas de un proyecto.

**Respuesta:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "project_id": 5,
      "plan_id": 1,
      "installment_number": 1,
      "due_date": "2024-01-15",
      "amount": 1000,
      "status": "pagada",
      "plan_name": "Plan Mensual",
      "paid_amount": 1000,
      "payment_count": 1
    },
    {
      "id": 2,
      "installment_number": 2,
      "due_date": "2024-02-15",
      "amount": 1000,
      "status": "pendiente",
      "paid_amount": null,
      "payment_count": 0
    }
  ],
  "count": 12
}
```

#### `GET /api/billing/installments/upcoming`
Obtiene cuotas próximas a vencer.

**Query Parameters:**
- `days` (opcional): Días hacia adelante (default: 30)
- `limit` (opcional): Límite de resultados (default: 50)

#### `GET /api/billing/installments/overdue`
Obtiene cuotas vencidas.

**Query Parameters:**
- `limit` (opcional): Límite de resultados (default: 100)

#### `GET /api/billing/installments/{id}`
Obtiene información detallada de una cuota con sus pagos.

#### `POST /api/billing/installments/{id}/pay`
Registra un pago para una cuota.

**Body:**
```json
{
  "amount": 1000,
  "payment_method": "transferencia",
  "reference": "TRX-123456",
  "notes": "Pago recibido vía transferencia bancaria"
}
```

**Respuesta:**
```json
{
  "success": true,
  "message": "Pago registrado exitosamente",
  "payment_id": 15,
  "installment_id": 2
}
```

---

### Reportes

#### `GET /api/billing/reports/financial-summary`
Resumen financiero general.

**Respuesta:**
```json
{
  "success": true,
  "data": {
    "paid": {
      "installments": 45,
      "amount": 45000
    },
    "pending": {
      "installments": 30,
      "amount": 30000
    },
    "overdue": {
      "installments": 5,
      "amount": 5000
    },
    "active_projects": 8
  }
}
```

#### `GET /api/billing/reports/income-comparison`
Comparación de ingresos reales vs proyectados.

**Query Parameters:**
- `start_date` (opcional): Fecha inicio (default: primer día del mes actual)
- `end_date` (opcional): Fecha fin (default: último día del mes actual)

**Respuesta:**
```json
{
  "success": true,
  "period": {
    "start_date": "2024-01-01",
    "end_date": "2024-01-31"
  },
  "data": {
    "real_income": {
      "payments": 12,
      "amount": 12000
    },
    "projected_income": {
      "installments": 15,
      "amount": 15000
    },
    "collection_rate": 80.00,
    "difference": -3000
  }
}
```

#### `GET /api/billing/reports/upcoming-installments`
Calendario de cobranzas.

**Query Parameters:**
- `days` (opcional): Días hacia adelante (default: 30)
- `group_by` (opcional): `date` | `project` | `client` (default: `date`)

#### `GET /api/billing/reports/client-summary/{id}`
Resumen financiero de un cliente específico.

---

## 🔧 Servicios de Negocio

### InstallmentGenerator

Genera cuotas automáticamente según el plan de pago.

**Métodos principales:**
- `generateInstallments($projectId, $planId, $startDate)`: Genera cuotas iniciales
- `recalculateInstallments($projectId, $newPlanId, $newStartDate)`: Recalcula cuotas preservando las pagadas

### PlanChangeService

Orquesta el cambio de plan de un proyecto.

**Métodos principales:**
- `changePlan($projectId, $newPlanId, $newStartDate, $reason, $userId)`: Cambia el plan completo
- `changeStartDate($projectId, $newStartDate, $userId)`: Solo cambia la fecha de inicio

### ReminderService

Procesa recordatorios de pago automáticos.

**Métodos principales:**
- `processReminders($daysBeforeDue)`: Envía recordatorios para cuotas próximas a vencer

### InstallmentStatusService

Actualiza estados de cuotas automáticamente.

**Métodos principales:**
- `markOverdueInstallments()`: Marca cuotas pendientes como vencidas
- `getOverdueStats()`: Obtiene estadísticas de morosidad

### EmailService

Envía notificaciones por correo electrónico.

**Métodos principales:**
- `sendReminder($data)`: Envía recordatorio de pago
- `sendOverdueNotification($data)`: Envía notificación de vencimiento

---

## ⏰ Cron Jobs

### Recordatorios de Pago

**Script:** `scripts/billing_send_reminders.php`

**Configuración crontab:**
```bash
# Ejecutar diariamente a las 9:00 AM
0 9 * * * /usr/bin/php /opt/homebrew/var/www/data2rest/scripts/billing_send_reminders.php
```

**Funcionalidad:**
- Busca cuotas que vencen en 5 días
- Envía emails de recordatorio
- Evita duplicados (verifica envíos del día)
- Registra resultados en `notifications_log`

### Marcar Cuotas Vencidas

**Script:** `scripts/billing_mark_overdue.php`

**Configuración crontab:**
```bash
# Ejecutar diariamente a las 00:30 AM
30 0 * * * /usr/bin/php /opt/homebrew/var/www/data2rest/scripts/billing_mark_overdue.php
```

**Funcionalidad:**
- Actualiza cuotas pendientes a vencidas
- Envía notificaciones de vencimiento
- Genera estadísticas de morosidad
- Registra en logs del sistema

---

## 🚀 Instalación

### Requisitos

- PHP 8.0+
- SQLite 3
- Data2Rest instalado y funcionando

### Pasos

1. **Las tablas se crean automáticamente** al acceder al sistema (gracias al `Installer.php`)

2. **Verificar planes por defecto:**
```sql
SELECT * FROM payment_plans;
```

Deberías ver:
- Plan Mensual (12 cuotas)
- Plan Anual (1 cuota)

3. **Configurar cron jobs:**
```bash
crontab -e
```

Agregar:
```bash
# Billing Module
0 9 * * * /usr/bin/php /opt/homebrew/var/www/data2rest/scripts/billing_send_reminders.php >> /var/log/billing_reminders.log 2>&1
30 0 * * * /usr/bin/php /opt/homebrew/var/www/data2rest/scripts/billing_mark_overdue.php >> /var/log/billing_overdue.log 2>&1
```

4. **Dar permisos de ejecución:**
```bash
chmod +x /opt/homebrew/var/www/data2rest/scripts/billing_*.php
```

---

## 💡 Ejemplos de Uso

### Crear un cliente y proyecto con plan mensual

```bash
# 1. Crear cliente
curl -X POST http://localhost/data2rest/api/billing/clients \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Empresa ABC",
    "email": "contacto@abc.com",
    "phone": "+1234567890"
  }'

# Respuesta: {"success":true,"client_id":1}

# 2. Crear proyecto con plan mensual
curl -X POST http://localhost/data2rest/api/billing/projects \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Proyecto Web 2024",
    "client_id": 1,
    "plan_id": 1,
    "start_date": "2024-01-15"
  }'

# Respuesta: {"success":true,"project_id":1,"installments_created":12}
```

### Ver cuotas del proyecto

```bash
curl http://localhost/data2rest/api/billing/projects/1/installments
```

### Registrar un pago

```bash
curl -X POST http://localhost/data2rest/api/billing/installments/1/pay \
  -H "Content-Type: application/json" \
  -d '{
    "amount": 1000,
    "payment_method": "transferencia",
    "reference": "TRX-123456"
  }'
```

### Cambiar a plan anual

```bash
curl -X PATCH http://localhost/data2rest/api/billing/projects/1/change-plan \
  -H "Content-Type: application/json" \
  -d '{
    "new_plan_id": 2,
    "reason": "Cliente solicitó cambio a plan anual"
  }'
```

### Ver reportes

```bash
# Resumen financiero
curl http://localhost/data2rest/api/billing/reports/financial-summary

# Ingresos del mes
curl "http://localhost/data2rest/api/billing/reports/income-comparison?start_date=2024-01-01&end_date=2024-01-31"

# Próximos vencimientos
curl "http://localhost/data2rest/api/billing/reports/upcoming-installments?days=30&group_by=date"
```

---

## 🔄 Flujos de Trabajo

### Flujo de Recálculo de Cuotas

```
┌─────────────────────────────────────┐
│ Evento Disparador:                  │
│ - Crear proyecto                    │
│ - Cambiar plan                      │
│ - Cambiar fecha de inicio           │
└──────────────┬──────────────────────┘
               │
               ▼
┌─────────────────────────────────────┐
│ 1. Obtener proyecto y plan          │
└──────────────┬──────────────────────┘
               │
               ▼
┌─────────────────────────────────────┐
│ 2. Consultar cuotas existentes      │
└──────────────┬──────────────────────┘
               │
               ▼
┌─────────────────────────────────────┐
│ 3. Identificar cuotas PAGADAS       │
│    → Se conservan SIN modificar     │
└──────────────┬──────────────────────┘
               │
               ▼
┌─────────────────────────────────────┐
│ 4. Cancelar cuotas futuras          │
│    (status = 'cancelada')           │
└──────────────┬──────────────────────┘
               │
               ▼
┌─────────────────────────────────────┐
│ 5. Calcular fecha base              │
│    (última cuota pagada + 1 mes)    │
└──────────────┬──────────────────────┘
               │
               ▼
┌─────────────────────────────────────┐
│ 6. Generar nuevas cuotas            │
│    según el nuevo plan              │
└──────────────┬──────────────────────┘
               │
               ▼
┌─────────────────────────────────────┐
│ 7. Registrar en historial           │
│    (project_plan_history)           │
└──────────────┬──────────────────────┘
               │
               ▼
┌─────────────────────────────────────┐
│ 8. Log de actividad                 │
└─────────────────────────────────────┘
```

### Reglas de Oro

1. **Nunca eliminar cuotas** - Solo cambiar estado
2. **Nunca modificar cuotas pagadas** - Son inmutables
3. **Un único servicio de recálculo** - `InstallmentGenerator::recalculateInstallments()`
4. **Siempre registrar historial** - Auditoría completa
5. **Transacciones para cambios críticos** - Atomicidad garantizada

---

## 📊 Beneficios del Diseño

### Para el Negocio

- ✅ Control financiero claro y preciso
- ✅ Proyección de ingresos confiable
- ✅ Identificación temprana de morosidad
- ✅ Automatización de cobranza
- ✅ Reportes ejecutivos en tiempo real

### Para el Desarrollo

- ✅ Alta trazabilidad de cambios
- ✅ Código mantenible y escalable
- ✅ Separación clara de responsabilidades
- ✅ Fácil extensión para nuevos planes
- ✅ Testing simplificado

### Para SaaS

- ✅ Multi-proyecto nativo
- ✅ Facturación por cliente
- ✅ Planes flexibles
- ✅ Historial completo
- ✅ API REST lista para integraciones

---

## 🚧 Próximos Pasos Sugeridos

### Funcionalidades Futuras

- [ ] **Prorrateos y créditos**: Ajustes de montos por cambios mid-cycle
- [ ] **Descuentos y promociones**: Cupones y ofertas especiales
- [ ] **Dashboard financiero**: Gráficos interactivos con Chart.js
- [ ] **Webhooks**: Notificaciones a sistemas externos
- [ ] **Pasarelas de pago**: Stripe, PayPal, Mercado Pago
- [ ] **Facturas PDF**: Generación automática
- [ ] **Múltiples monedas**: Soporte internacional
- [ ] **Impuestos**: Cálculo automático de IVA/GST

### Mejoras Técnicas

- [ ] **Tests unitarios**: PHPUnit para servicios críticos
- [ ] **Cache**: Redis para reportes pesados
- [ ] **Queue**: Procesamiento asíncrono de notificaciones
- [ ] **Exportación**: Excel/PDF de reportes
- [ ] **Importación**: Carga masiva de datos

---

## 📞 Soporte

Para preguntas o problemas:

1. Revisa la documentación completa
2. Verifica los logs en `data/logs/`
3. Consulta el historial de cambios en `project_plan_history`
4. Abre un issue en el repositorio

---

**Desarrollado con ❤️ para Data2Rest**

*Versión: 1.0.0*  
*Última actualización: 2024-01-13*
