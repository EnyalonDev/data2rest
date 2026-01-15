# 🔗 Ejemplos de Integración - Módulo de Billing

## 📋 Índice

- [Python](#python)
- [JavaScript (Node.js)](#javascript-nodejs)
- [PHP](#php)
- [cURL](#curl)
- [Postman Collection](#postman-collection)

---

## Python

### Instalación de dependencias

```bash
pip install requests
```

### Ejemplo completo

```python
import requests
import json
from datetime import datetime

BASE_URL = "http://localhost/data2rest"

class BillingClient:
    def __init__(self, base_url):
        self.base_url = base_url
        self.headers = {"Content-Type": "application/json"}
    
    # CLIENTES
    def create_client(self, name, email, phone=None):
        """Crea un nuevo cliente"""
        data = {"name": name, "email": email, "phone": phone}
        response = requests.post(
            f"{self.base_url}/api/billing/clients",
            headers=self.headers,
            json=data
        )
        return response.json()
    
    def list_clients(self, status="active"):
        """Lista clientes"""
        response = requests.get(
            f"{self.base_url}/api/billing/clients?status={status}"
        )
        return response.json()
    
    # PROYECTOS
    def create_project(self, name, client_id, plan_id, start_date):
        """Crea un proyecto con plan de pago"""
        data = {
            "name": name,
            "client_id": client_id,
            "plan_id": plan_id,
            "start_date": start_date
        }
        response = requests.post(
            f"{self.base_url}/api/billing/projects",
            headers=self.headers,
            json=data
        )
        return response.json()
    
    def change_plan(self, project_id, new_plan_id, reason=None):
        """Cambia el plan de un proyecto"""
        data = {"new_plan_id": new_plan_id, "reason": reason}
        response = requests.patch(
            f"{self.base_url}/api/billing/projects/{project_id}/change-plan",
            headers=self.headers,
            json=data
        )
        return response.json()
    
    # CUOTAS
    def get_installments(self, project_id):
        """Obtiene cuotas de un proyecto"""
        response = requests.get(
            f"{self.base_url}/api/billing/projects/{project_id}/installments"
        )
        return response.json()
    
    def pay_installment(self, installment_id, amount, payment_method, reference=None):
        """Registra un pago"""
        data = {
            "amount": amount,
            "payment_method": payment_method,
            "reference": reference
        }
        response = requests.post(
            f"{self.base_url}/api/billing/installments/{installment_id}/pay",
            headers=self.headers,
            json=data
        )
        return response.json()
    
    # REPORTES
    def financial_summary(self):
        """Obtiene resumen financiero"""
        response = requests.get(
            f"{self.base_url}/api/billing/reports/financial-summary"
        )
        return response.json()
    
    def income_comparison(self, start_date, end_date):
        """Compara ingresos reales vs proyectados"""
        response = requests.get(
            f"{self.base_url}/api/billing/reports/income-comparison",
            params={"start_date": start_date, "end_date": end_date}
        )
        return response.json()

# USO
if __name__ == "__main__":
    client = BillingClient(BASE_URL)
    
    # Crear cliente
    new_client = client.create_client(
        name="Mi Empresa S.A.",
        email="contacto@miempresa.com",
        phone="+1234567890"
    )
    print(f"Cliente creado: {new_client}")
    
    # Crear proyecto con plan mensual
    project = client.create_project(
        name="Proyecto Web 2024",
        client_id=new_client['client_id'],
        plan_id=1,  # Plan Mensual
        start_date=datetime.now().strftime("%Y-%m-%d")
    )
    print(f"Proyecto creado: {project}")
    
    # Ver cuotas
    installments = client.get_installments(project['project_id'])
    print(f"Cuotas generadas: {installments['count']}")
    
    # Registrar pago de primera cuota
    if installments['data']:
        first_installment = installments['data'][0]
        payment = client.pay_installment(
            installment_id=first_installment['id'],
            amount=first_installment['amount'],
            payment_method="transferencia",
            reference="TRX-001"
        )
        print(f"Pago registrado: {payment}")
    
    # Ver resumen financiero
    summary = client.financial_summary()
    print(f"Resumen financiero: {summary}")
```

---

## JavaScript (Node.js)

### Instalación de dependencias

```bash
npm install axios
```

### Ejemplo completo

```javascript
const axios = require('axios');

const BASE_URL = 'http://localhost/data2rest';

class BillingClient {
  constructor(baseUrl) {
    this.baseUrl = baseUrl;
    this.headers = { 'Content-Type': 'application/json' };
  }

  // CLIENTES
  async createClient(name, email, phone = null) {
    const response = await axios.post(
      `${this.baseUrl}/api/billing/clients`,
      { name, email, phone },
      { headers: this.headers }
    );
    return response.data;
  }

  async listClients(status = 'active') {
    const response = await axios.get(
      `${this.baseUrl}/api/billing/clients?status=${status}`
    );
    return response.data;
  }

  // PROYECTOS
  async createProject(name, clientId, planId, startDate) {
    const response = await axios.post(
      `${this.baseUrl}/api/billing/projects`,
      {
        name,
        client_id: clientId,
        plan_id: planId,
        start_date: startDate
      },
      { headers: this.headers }
    );
    return response.data;
  }

  async changePlan(projectId, newPlanId, reason = null) {
    const response = await axios.patch(
      `${this.baseUrl}/api/billing/projects/${projectId}/change-plan`,
      { new_plan_id: newPlanId, reason },
      { headers: this.headers }
    );
    return response.data;
  }

  // CUOTAS
  async getInstallments(projectId) {
    const response = await axios.get(
      `${this.baseUrl}/api/billing/projects/${projectId}/installments`
    );
    return response.data;
  }

  async payInstallment(installmentId, amount, paymentMethod, reference = null) {
    const response = await axios.post(
      `${this.baseUrl}/api/billing/installments/${installmentId}/pay`,
      { amount, payment_method: paymentMethod, reference },
      { headers: this.headers }
    );
    return response.data;
  }

  // REPORTES
  async financialSummary() {
    const response = await axios.get(
      `${this.baseUrl}/api/billing/reports/financial-summary`
    );
    return response.data;
  }

  async incomeComparison(startDate, endDate) {
    const response = await axios.get(
      `${this.baseUrl}/api/billing/reports/income-comparison`,
      { params: { start_date: startDate, end_date: endDate } }
    );
    return response.data;
  }

  async upcomingInstallments(days = 30, groupBy = 'date') {
    const response = await axios.get(
      `${this.baseUrl}/api/billing/reports/upcoming-installments`,
      { params: { days, group_by: groupBy } }
    );
    return response.data;
  }
}

// USO
(async () => {
  const client = new BillingClient(BASE_URL);

  try {
    // Crear cliente
    const newClient = await client.createClient(
      'Mi Empresa S.A.',
      'contacto@miempresa.com',
      '+1234567890'
    );
    console.log('Cliente creado:', newClient);

    // Crear proyecto
    const project = await client.createProject(
      'Proyecto Web 2024',
      newClient.client_id,
      1, // Plan Mensual
      new Date().toISOString().split('T')[0]
    );
    console.log('Proyecto creado:', project);

    // Ver cuotas
    const installments = await client.getInstallments(project.project_id);
    console.log(`Cuotas generadas: ${installments.count}`);

    // Registrar pago
    if (installments.data.length > 0) {
      const firstInstallment = installments.data[0];
      const payment = await client.payInstallment(
        firstInstallment.id,
        firstInstallment.amount,
        'transferencia',
        'TRX-001'
      );
      console.log('Pago registrado:', payment);
    }

    // Ver resumen financiero
    const summary = await client.financialSummary();
    console.log('Resumen financiero:', summary);

  } catch (error) {
    console.error('Error:', error.response?.data || error.message);
  }
})();
```

---

## PHP

### Ejemplo completo

```php
<?php

class BillingClient
{
    private $baseUrl;

    public function __construct($baseUrl)
    {
        $this->baseUrl = $baseUrl;
    }

    private function request($method, $endpoint, $data = null)
    {
        $url = $this->baseUrl . $endpoint;
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } elseif ($method === 'PATCH') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } elseif ($method === 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }

    // CLIENTES
    public function createClient($name, $email, $phone = null)
    {
        return $this->request('POST', '/api/billing/clients', [
            'name' => $name,
            'email' => $email,
            'phone' => $phone
        ]);
    }

    public function listClients($status = 'active')
    {
        return $this->request('GET', "/api/billing/clients?status=$status");
    }

    // PROYECTOS
    public function createProject($name, $clientId, $planId, $startDate)
    {
        return $this->request('POST', '/api/billing/projects', [
            'name' => $name,
            'client_id' => $clientId,
            'plan_id' => $planId,
            'start_date' => $startDate
        ]);
    }

    public function changePlan($projectId, $newPlanId, $reason = null)
    {
        return $this->request('PATCH', "/api/billing/projects/$projectId/change-plan", [
            'new_plan_id' => $newPlanId,
            'reason' => $reason
        ]);
    }

    // CUOTAS
    public function getInstallments($projectId)
    {
        return $this->request('GET', "/api/billing/projects/$projectId/installments");
    }

    public function payInstallment($installmentId, $amount, $paymentMethod, $reference = null)
    {
        return $this->request('POST', "/api/billing/installments/$installmentId/pay", [
            'amount' => $amount,
            'payment_method' => $paymentMethod,
            'reference' => $reference
        ]);
    }

    // REPORTES
    public function financialSummary()
    {
        return $this->request('GET', '/api/billing/reports/financial-summary');
    }

    public function incomeComparison($startDate, $endDate)
    {
        return $this->request('GET', "/api/billing/reports/income-comparison?start_date=$startDate&end_date=$endDate");
    }
}

// USO
$client = new BillingClient('http://localhost/data2rest');

// Crear cliente
$newClient = $client->createClient('Mi Empresa S.A.', 'contacto@miempresa.com', '+1234567890');
echo "Cliente creado: " . json_encode($newClient) . "\n";

// Crear proyecto
$project = $client->createProject(
    'Proyecto Web 2024',
    $newClient['client_id'],
    1, // Plan Mensual
    date('Y-m-d')
);
echo "Proyecto creado: " . json_encode($project) . "\n";

// Ver cuotas
$installments = $client->getInstallments($project['project_id']);
echo "Cuotas generadas: {$installments['count']}\n";

// Registrar pago
if (!empty($installments['data'])) {
    $firstInstallment = $installments['data'][0];
    $payment = $client->payInstallment(
        $firstInstallment['id'],
        $firstInstallment['amount'],
        'transferencia',
        'TRX-001'
    );
    echo "Pago registrado: " . json_encode($payment) . "\n";
}

// Ver resumen financiero
$summary = $client->financialSummary();
echo "Resumen financiero: " . json_encode($summary) . "\n";
```

---

## cURL

### Crear cliente

```bash
curl -X POST http://localhost/data2rest/api/billing/clients \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Mi Empresa S.A.",
    "email": "contacto@miempresa.com",
    "phone": "+1234567890"
  }'
```

### Crear proyecto con plan

```bash
curl -X POST http://localhost/data2rest/api/billing/projects \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Proyecto Web 2024",
    "client_id": 1,
    "plan_id": 1,
    "start_date": "2024-01-15"
  }'
```

### Ver cuotas de un proyecto

```bash
curl http://localhost/data2rest/api/billing/projects/1/installments
```

### Registrar pago

```bash
curl -X POST http://localhost/data2rest/api/billing/installments/1/pay \
  -H "Content-Type: application/json" \
  -d '{
    "amount": 1000,
    "payment_method": "transferencia",
    "reference": "TRX-001"
  }'
```

### Cambiar plan

```bash
curl -X PATCH http://localhost/data2rest/api/billing/projects/1/change-plan \
  -H "Content-Type: application/json" \
  -d '{
    "new_plan_id": 2,
    "reason": "Cliente solicitó cambio a plan anual"
  }'
```

### Ver resumen financiero

```bash
curl http://localhost/data2rest/api/billing/reports/financial-summary
```

### Ver ingresos del mes

```bash
curl "http://localhost/data2rest/api/billing/reports/income-comparison?start_date=2024-01-01&end_date=2024-01-31"
```

### Ver próximos vencimientos

```bash
curl "http://localhost/data2rest/api/billing/reports/upcoming-installments?days=30&group_by=date"
```

---

## Postman Collection

### Importar en Postman

Crea un archivo `billing_api.postman_collection.json`:

```json
{
  "info": {
    "name": "Data2Rest - Billing API",
    "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
  },
  "variable": [
    {
      "key": "base_url",
      "value": "http://localhost/data2rest"
    }
  ],
  "item": [
    {
      "name": "Clients",
      "item": [
        {
          "name": "List Clients",
          "request": {
            "method": "GET",
            "url": "{{base_url}}/api/billing/clients"
          }
        },
        {
          "name": "Create Client",
          "request": {
            "method": "POST",
            "url": "{{base_url}}/api/billing/clients",
            "header": [{"key": "Content-Type", "value": "application/json"}],
            "body": {
              "mode": "raw",
              "raw": "{\n  \"name\": \"Mi Empresa\",\n  \"email\": \"contacto@empresa.com\"\n}"
            }
          }
        }
      ]
    },
    {
      "name": "Projects",
      "item": [
        {
          "name": "Create Project",
          "request": {
            "method": "POST",
            "url": "{{base_url}}/api/billing/projects",
            "header": [{"key": "Content-Type", "value": "application/json"}],
            "body": {
              "mode": "raw",
              "raw": "{\n  \"name\": \"Proyecto 2024\",\n  \"client_id\": 1,\n  \"plan_id\": 1,\n  \"start_date\": \"2024-01-15\"\n}"
            }
          }
        }
      ]
    },
    {
      "name": "Reports",
      "item": [
        {
          "name": "Financial Summary",
          "request": {
            "method": "GET",
            "url": "{{base_url}}/api/billing/reports/financial-summary"
          }
        }
      ]
    }
  ]
}
```

---

**¡Listo para integrar!** 🚀

Elige el lenguaje de tu preferencia y comienza a usar el módulo de Billing.
