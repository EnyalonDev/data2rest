# 🚀 Instalación Rápida - Módulo de Billing

## ✅ Verificación de Instalación

El módulo de Billing se instala **automáticamente** al acceder a Data2Rest. Para verificar que todo está correcto:

```bash
php scripts/verify_billing_module.php
```

Deberías ver:
```
✅ Verificaciones exitosas: 25
⚠️  Advertencias: 0
❌ Errores: 0

🎉 ¡El módulo de Billing está correctamente instalado!
```

---

## 🎬 Cargar Datos de Demo

Para probar el módulo con datos de ejemplo:

```bash
php scripts/billing_demo.php
```

Esto creará:
- 3 clientes de ejemplo
- 3 proyectos con diferentes planes
- 25 cuotas automáticas
- 3 pagos simulados
- 1 cuota vencida

---

## ⏰ Configurar Cron Jobs

### 1. Editar crontab

```bash
crontab -e
```

### 2. Agregar las siguientes líneas

**IMPORTANTE:** Ajusta la ruta de PHP según tu sistema:

```bash
# Recordatorios de pago (9:00 AM diario)
0 9 * * * /opt/homebrew/bin/php /opt/homebrew/var/www/data2rest/scripts/billing_send_reminders.php >> /var/log/billing_reminders.log 2>&1

# Marcar cuotas vencidas (00:30 AM diario)
30 0 * * * /opt/homebrew/bin/php /opt/homebrew/var/www/data2rest/scripts/billing_mark_overdue.php >> /var/log/billing_overdue.log 2>&1
```

### 3. Verificar ruta de PHP

```bash
which php
```

Usa la ruta que te devuelva este comando en el crontab.

### 4. Verificar que los cron jobs están activos

```bash
crontab -l
```

---

## 🧪 Probar los Endpoints

### 1. Listar clientes

```bash
curl http://localhost/data2rest/api/billing/clients
```

### 2. Ver resumen financiero

```bash
curl http://localhost/data2rest/api/billing/reports/financial-summary
```

### 3. Ver cuotas de un proyecto

```bash
curl http://localhost/data2rest/api/billing/projects/1/installments
```

### 4. Ver cuotas vencidas

```bash
curl http://localhost/data2rest/api/billing/installments/overdue
```

---

## 📊 Estructura de Tablas Creadas

El módulo crea automáticamente 6 tablas nuevas:

1. **clients** - Información de clientes
2. **payment_plans** - Planes de pago (Mensual, Anual)
3. **installments** - Cuotas generadas
4. **payments** - Pagos efectivos realizados
5. **project_plan_history** - Historial de cambios de plan
6. **notifications_log** - Registro de notificaciones enviadas

Además, agrega 4 campos a la tabla **projects**:
- `client_id`
- `start_date`
- `current_plan_id`
- `billing_status`

---

## 🔍 Verificar Tablas en la Base de Datos

```bash
sqlite3 data/system.sqlite "SELECT name FROM sqlite_master WHERE type='table' AND name LIKE '%payment%' OR name LIKE '%installment%' OR name LIKE '%client%'"
```

---

## 📚 Documentación Completa

- **Documentación exhaustiva**: `docs/BILLING.md`
- **README del módulo**: `src/Modules/Billing/README.md`
- **README principal**: `README.md` (sección Módulos)

---

## 🆘 Solución de Problemas

### Los planes de pago no se crearon

```bash
php -r "
require_once 'src/autoload.php';
\$db = App\Core\Database::getInstance()->getConnection();
\$db->exec(\"INSERT INTO payment_plans (name, frequency, installments, amount, description) VALUES ('Plan Mensual', 'monthly', 12, 0, 'Plan de pago mensual con 12 cuotas')\");
\$db->exec(\"INSERT INTO payment_plans (name, frequency, installments, amount, description) VALUES ('Plan Anual', 'yearly', 1, 0, 'Plan de pago anual con 1 cuota')\");
echo 'Planes creados\n';
"
```

### Los scripts de cron no son ejecutables

```bash
chmod +x scripts/billing_*.php
```

### Error "Class not found"

Verifica que el autoloader esté funcionando:

```bash
php -r "require_once 'src/autoload.php'; echo 'Autoloader OK\n';"
```

---

## ✨ Próximos Pasos

1. ✅ Verificar instalación
2. ✅ Cargar datos de demo
3. ✅ Configurar cron jobs
4. ✅ Probar endpoints REST
5. 📖 Leer documentación completa
6. 🎨 Crear interfaz administrativa (opcional)
7. 🔗 Integrar con pasarelas de pago (futuro)

---

**¡Listo para usar!** 🎉

El módulo de Billing está completamente funcional y listo para gestionar pagos por proyecto.
