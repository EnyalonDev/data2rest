# 💳 Módulo de Billing - Guía Rápida

## 🚀 Inicio Rápido

### 1. Crear un Cliente

```bash
curl -X POST http://localhost/data2rest/api/billing/clients \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Mi Empresa",
    "email": "contacto@miempresa.com"
  }'
```

### 2. Crear Proyecto con Plan Mensual

```bash
curl -X POST http://localhost/data2rest/api/billing/projects \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Proyecto 2024",
    "client_id": 1,
    "plan_id": 1,
    "start_date": "2024-01-15"
  }'
```

Esto generará automáticamente 12 cuotas mensuales.

### 3. Ver Cuotas del Proyecto

```bash
curl http://localhost/data2rest/api/billing/projects/1/installments
```

### 4. Registrar un Pago

```bash
curl -X POST http://localhost/data2rest/api/billing/installments/1/pay \
  -H "Content-Type: application/json" \
  -d '{
    "amount": 1000,
    "payment_method": "transferencia",
    "reference": "TRX-123"
  }'
```

### 5. Ver Resumen Financiero

```bash
curl http://localhost/data2rest/api/billing/reports/financial-summary
```

---

## 📊 Endpoints Principales

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| `GET` | `/api/billing/clients` | Listar clientes |
| `POST` | `/api/billing/clients` | Crear cliente |
| `POST` | `/api/billing/projects` | Crear proyecto con plan |
| `GET` | `/api/billing/projects/{id}/installments` | Ver cuotas |
| `POST` | `/api/billing/installments/{id}/pay` | Registrar pago |
| `GET` | `/api/billing/reports/financial-summary` | Resumen financiero |
| `PATCH` | `/api/billing/projects/{id}/change-plan` | Cambiar plan |

---

## ⏰ Configurar Cron Jobs

Editar crontab:
```bash
crontab -e
```

Agregar:
```bash
# Recordatorios de pago (9:00 AM diario)
0 9 * * * /usr/bin/php /opt/homebrew/var/www/data2rest/scripts/billing_send_reminders.php

# Marcar vencidas (00:30 AM diario)
30 0 * * * /usr/bin/php /opt/homebrew/var/www/data2rest/scripts/billing_mark_overdue.php
```

---

## 📖 Documentación Completa

Ver [BILLING.md](../docs/BILLING.md) para documentación exhaustiva.

---

## 🔧 Estructura del Módulo

```
src/Modules/Billing/
├── Controllers/          # Endpoints REST
├── Services/            # Lógica de negocio
└── Repositories/        # Acceso a datos

scripts/
├── billing_send_reminders.php
└── billing_mark_overdue.php
```

---

## 💡 Casos de Uso Comunes

### Cambiar de Plan Mensual a Anual

```bash
curl -X PATCH http://localhost/data2rest/api/billing/projects/1/change-plan \
  -H "Content-Type: application/json" \
  -d '{
    "new_plan_id": 2,
    "reason": "Cliente solicitó cambio"
  }'
```

### Ver Cuotas Vencidas

```bash
curl http://localhost/data2rest/api/billing/installments/overdue
```

### Ver Próximos Vencimientos (30 días)

```bash
curl "http://localhost/data2rest/api/billing/installments/upcoming?days=30"
```

### Comparar Ingresos del Mes

```bash
curl "http://localhost/data2rest/api/billing/reports/income-comparison?start_date=2024-01-01&end_date=2024-01-31"
```

---

## 🎯 Características Destacadas

✅ **Cuotas Automáticas**: Se generan al crear el proyecto  
✅ **Recálculo Inteligente**: Preserva cuotas pagadas al cambiar plan  
✅ **Recordatorios Automáticos**: Emails 5 días antes del vencimiento  
✅ **Auditoría Completa**: Historial de todos los cambios  
✅ **Reportes en Tiempo Real**: Estadísticas financieras actualizadas  

---

**Desarrollado para Data2Rest** 🚀
