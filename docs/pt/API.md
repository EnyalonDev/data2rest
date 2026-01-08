# 🔌 Módulo de API REST

[← Voltar ao README Principal](../../README.pt.md)

## 📋 Descrição

O **Módulo de API REST** fornece geração automática de endpoints RESTful para todas as tabelas das bases de dados geridas pelo sistema. Inclui autenticação por API Keys, documentação interativa e suporte completo para operações CRUD.

---

## 📁 Estrutura do Módulo

```
src/Modules/Api/
├── RestController.php      # Controlador principal de API REST
└── ApiDocsController.php   # Gerador de documentação
```

---

## ✨ Características

### 🔄 Endpoints Automáticos
- **GET** `/api/v1/{database}/{table}` - Listar todos os registros
- **GET** `/api/v1/{database}/{table}/{id}` - Obter um registro específico
- **POST** `/api/v1/{database}/{table}` - Criar novo registro
- **PUT** `/api/v1/{database}/{table}/{id}` - Atualizar registro completo
- **PATCH** `/api/v1/{database}/{table}/{id}` - Atualizar registro parcial
- **DELETE** `/api/v1/{database}/{table}/{id}` - Eliminar registro

### 🔐 Autenticação
- API Keys armazenadas na base de dados do sistema
- Validação em cada pedido
- Gestão de keys a partir do painel de administração

### 📖 Documentação Automática
- Geração dinâmica de documentação estilo Swagger
- Exemplos de uso com cURL
- Listagem de todos os endpoints disponíveis

---

## 🚀 Uso

### 1. Gerar API Key

1. Aceda ao painel de administração
2. Vá a **API Management**
3. Clique em "Generate New Key"
4. Copie e guarde a API Key gerada

### 2. Realizar Pedidos

Todos os pedidos devem incluir o header `X-API-Key`:

```bash
curl -H "X-API-Key: sua-api-key-aqui" \
     http://localhost/data2rest/api/v1/minhabd/usuarios
```

### 3. Exemplos de Uso

#### Listar Todos os Registros

```bash
GET /api/v1/minhabd/usuarios

curl -H "X-API-Key: abc123..." \
     http://localhost/data2rest/api/v1/minhabd/usuarios
```

**Resposta:**
```json
[
  {
    "id": 1,
    "nome": "João Silva",
    "email": "joao@example.com"
  }
]
```

#### JavaScript (Fetch API)
```javascript
const response = await fetch('http://localhost/data2rest/api/v1/minhabd/usuarios', {
    method: 'POST',
    headers: {
        'X-API-Key': 'sua-api-key-aqui',
        'Content-Type': 'application/json'
    },
    body: JSON.stringify({
        nome: 'Pedro Lopes',
        email: 'pedro@example.com'
    })
});
const data = await response.json();
console.log(data);
```

#### Python (Requests)
```python
import requests

url = "http://localhost/data2rest/api/v1/minhabd/usuarios"
headers = {
    "X-API-Key": "sua-api-key-aqui",
    "Content-Type": "application/json"
}
data = {
    "nome": "Pedro Lopes",
    "email": "pedro@example.com"
}

response = requests.post(url, json=data, headers=headers)
print(response.json())
```

---

## 🔒 Segurança

### API Keys
As API Keys são armazenadas na tabela `api_keys` da base de dados do sistema.

### Validação
Cada pedido passa por:
1. **Validação de API Key** - Verifica que existe e está ativa
2. **Validação de Base de Dados** - Verifica que a BD existe
3. **Validação de Tabela** - Verifica que a tabela existe
4. **Validação de Dados** - Sanitiza inputs antes de executar queries

---

## 📊 Respostas de Erro

### 401 Unauthorized
```json
{
  "error": "Invalid or missing API key"
}
```

### 404 Not Found
```json
{
  "error": "Record not found"
}
```

---

[← Voltar ao README Principal](../../README.pt.md)
