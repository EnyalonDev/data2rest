# 🗄️ Módulo de Bases de Dados

[← Voltar ao README Principal](../../README.pt.md)

## 📋 Descrição

O **Módulo de Bases de Dados** permite criar e gerir bases de dados SQLite de forma visual, com suporte completo para operações CRUD, configuração de campos e gestão de tabelas.

---

## 📁 Estrutura do Módulo

```
src/Modules/Database/
├── DatabaseController.php  # Gestão de bases de dados e tabelas
└── CrudController.php      # Operações CRUD em registros
```

---

## ✨ Características

### 📦 Gestão de Bases de Dados
- Criar novas bases de dados SQLite
- Listar bases de dados existentes
- Eliminar bases de dados
- Ver informações detalhadas

### 📋 Gestão de Tabelas
- Criar tabelas dinamicamente
- Configurar campos com tipos de dados
- Eliminar tabelas
- Ver estrutura de tabelas

### ✏️ Operações CRUD
- Criar registros
- Ler/Listar registros
- Atualizar registros
- Eliminar registros
- Busca e filtragem

### 🎨 Configuração de Campos
- Tipos de dados: TEXT, INTEGER, REAL, BLOB
- Campos especiais: file, textarea, checkbox, date, time
- Validações personalizadas
- Valores por defeito

---

## 🚀 Uso

### 1. Criar uma Base de Dados
1. Vá a **Databases**
2. Preencha o formulário "Initialize New Node"
3. Insira nome e descrição
4. Clique em "Create Database"

### 2. Criar Tabelas
1. Selecione uma base de dados
2. Clique em "View Tables"
3. Insira o nome da tabela
4. Clique em "Create Table"

### 3. Configurar Campos
1. Clique no ícone ⚙️ da tabela
2. Adicione campos:
   - **Field Name**: nome do campo
   - **Type**: tipo de dado (TEXT, INTEGER, etc.)
   - **Special**: opções especiais (arquivo, textarea)
3. Guarde a configuração

### 4. Gerir Dados
1. Clique em "Enter Segment"
2. Use "New Entry" para criar registros
3. Edite com o botão "Edit"
4. Elimine com o botão "Kill"

---

## 🔧 Controladores

### DatabaseController.php
**Métodos principais:**
- `index()` - Lista todas as bases de dados
- `create()` - Cria nova base de dados
- `delete()` - Elimina base de dados
- `viewTables()` - Mostra tabelas de uma BD
- `createTable()` - Cria nova tabela
- `deleteTable()` - Elimina tabela
- `fields()` - Gere campos de tabela

### CrudController.php
**Métodos principais:**
- `list()` - Lista registros de uma tabela
- `form()` - Formulário criar/editar
- `save()` - Guarda registro
- `delete()` - Elimina registro
- `mediaList()` - Gere arquivos subidos

---

## 📚 Exemplos

### Configuração de Relações (Foreign Keys)
O sistema permite vincular tabelas para criar estruturas relacionais complexas:

1. **Tabela Destino**: `categorias` (id, nome)
2. **Tabela Origem**: `productos`
3. **Configuração de Campo em `productos`**:
   - **Nome**: `categoria_id`
   - **Tipo**: `INTEGER`
   - **Relação**: Selecionar tabela `categorias`
   - **Display Field**: Selecionar `nombre`

Isto permitirá que, ao inserir um produto, o sistema mostre um seletor com os nomes das categorias.

---

## 🔒 Segurança

### Validação de Permissões (Granular)
Cada operação valida permissões específicas definidas no Arquiteto de Políticas:

- **`databases.crud_read`**: Ver registros.
- **`databases.crud_create`**: Inserir novos registros.
- **`databases.crud_update`**: Modificar registros existentes.
- **`databases.crud_delete`**: Eliminar registros.
- **`databases.create_db`**, **`databases.delete_db`**: Gestão estrutural.

```php
// Exemplo interno
Auth::requirePermission("module:databases.crud_read");
```

### Prepared Statements
Todas as consultas utilizam prepared statements.

### Sanitização
Os dados são sanitizados antes de serem exibidos usando `htmlspecialchars()`.

---

[← Voltar ao README Principal](../../README.pt.md)
