# 🔐 Módulo de Autenticação

[← Voltar ao README Principal](../../README.pt.md)

## 📋 Descrição

O **Módulo de Autenticação** fornece um sistema completo de login, gestão de usuários, papéis e permissões baseado em RBAC (Role-Based Access Control).

---

## 📁 Estrutura do Módulo

```
src/Modules/Auth/
├── LoginController.php     # Gestão de login/logout
├── UserController.php      # CRUD de usuários
└── RoleController.php      # Gestão de papéis e permissões
```

---

## ✨ Características

### 🔑 Sistema de Login
- Autenticação segura com sessões PHP
- Validação de credenciais
- Mensagens flash para feedback

### 👥 Gestão de Usuários
- Criar, editar e eliminar usuários
- Atribuição de papéis
- Permissões granulares por base de dados
- Listagem e busca de usuários

### 🛡️ Controle de Acesso (RBAC) - Policy Architect
- Papéis personalizáveis (admin, user, etc.)
- **Arquiteto de Políticas**: Interface visual para definir permissões granulares.
- **Permissões de Gestão de Usuários**:
    - `invite_users`: Permitir convidar/criar novos usuários.
    - `edit_users`: Permitir editar perfis existentes.
    - `delete_users`: Permitir eliminar usuários (botão de exclusão oculto se não possuído).
- **Isolamento de Equipes**:
    - **Admins**: Veem todos os usuários e podem filtrar por grupo.
    - **Usuários**: Apenas podem ver membros do seu próprio grupo de trabalho.
- Validação em cada ação.

---

## 🚀 Uso

### 1. Login
Aceda a `/login` e insira suas credenciais:
```
Usuário: admin
Senha: admin123
```

### 2. Exemplos de Implementação

#### Verificação de Permissões em PHP
```php
use App\Core\Auth;

// Requerer que o usuário esteja logado
Auth::requireLogin();

// Requerer permissão específica para uma base de dados
Auth::requireDatabaseAccess($db_id);

// Verificar se tem permissão de gerenciamento num módulo
if (Auth::hasPermission("module:api", "manage")) {
    // Realizar ação administrativa
}
```

#### Estrutura de uma Política JSON (Arquiteto de Políticas)
```json
{
  "all": false,
  "modules": {
    "databases": ["view", "manage"],
    "api": ["view"]
  },
  "databases": {
    "1": ["read", "insert", "update"],
    "2": ["view"]
  }
}
```

---

## 🔒 Segurança

### Sessões
As sessões são gerenciadas com PHP nativo e armazenadas de forma segura.

### Permissões
O sistema verifica permissões em cada ação:
```php
Auth::requirePermission("db:1", "write");
```

### Hashing de Senhas
As senhas são hasheadas com `password_hash()` do PHP.

---

[← Voltar ao README Principal](../../README.pt.md)
