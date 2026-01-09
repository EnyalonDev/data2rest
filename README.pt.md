# 🚀 Data2Rest - Sistema de Gestão de Bases de Dados e APIs REST

![Version](https://img.shields.io/badge/version-1.0.0-blue.svg)
![PHP](https://img.shields.io/badge/PHP-8.0%2B-777BB4.svg)
![SQLite](https://img.shields.io/badge/SQLite-3-003B57.svg)
![License](https://img.shields.io/badge/license-MIT-green.svg)

**Data2Rest** nasceu de uma necessidade real: acelerar o desenvolvimento de backends para aplicações web e móveis. Meu objetivo era que, no momento de desenhar a base de dados, os endpoints necessários estivessem prontos imediatamente. Dessa visão surgiu este sistema simples e prático, ideal para desenvolvedores independentes, estudantes e pequenas equipes que precisam lançar MVPs ou protótipos produtivos em questão de minutos.

Com o Data2Rest, o backend adapta-se à sua ideia e não o contrário. Esqueça a busca por exemplos genéricos de APIs que não se ajustam ao seu projeto; aqui você desenha sua estrutura de dados e o sistema gera automaticamente os endpoints REST prontos para consumo de qualquer cliente.

### 🎯 Objetivo do Projeto
Fornecer uma ferramenta de código aberto que elimine a fricção inicial ao criar backends, reduzindo erros repetitivos e permitindo que os desenvolvedores se concentrem no que realmente importa: o design de seus dados e a lógica de seu negócio.

**Ideal para:**
*   👨‍💻 **Desenvolvedores Independentes**: Crie protótipos e apps completos sem escrever boilerplate.
*   🎓 **Estudantes**: Aprenda sobre estruturas de dados e APIs vendo resultados imediatos.
*   🚀 **MVPs e Startups**: Valide suas ideias de negócio com um backend funcional em tempo recorde.
*   👥 **Pequenas Equipes**: Melhore a produtividade compartilhando um núcleo de dados unificado.

---

## 📋 Tabela de Conteúdos

- [Características Principais](#-características-principais)
- [Requisitos do Sistema](#-requisitos-del-sistema)
- [Instalação](#-instalação)
- [Arquitetura do Sistema](#-arquitetura-do-sistema)
- [Módulos](#-módulos)
- [Configuração](#-configuração)
- [Uso Básico](#-uso-básico)
- [Segurança](#-seguridad)
- [Contribuir](#-contribuir)
- [Licença](#-licencia)
- [Créditos](#-créditos)

---

## ✨ Características Principales

### 🗄️ Gestão de Bases de Dados
- **Criação dinâmica** de bases de dados SQLite
- **Gestão visual** de tabelas e campos
- **CRUD completo** com interface intuitiva
- **Configuração de campos** com tipos de dados personalizados
- **Gestão de arquivos** e média integrada

### 🔌 API REST Automática
- **Geração automática** de endpoints REST para cada tabela
- **Documentação interativa** estilo Swagger
- **Autenticação por API Keys**
- **Suporte completo** para GET, POST, PUT, PATCH, DELETE
- **Filtragem e paginação** de resultados

### 🔐 Sistema de Autenticação e Autorização
- **Login seguro** com sessões PHP
- **Controle de acesso baseado em papéis** (RBAC) com herança
- **Isolamento de Equipes**: Visibilidade estrita de usuários por Grupo
- **Arquiteto de Políticas**: Definição visual de permissões (`delete_users`, `crud_create`, etc.)
- **Sistema de mensagens flash** com modais elegantes

### 🎨 Interface Moderna
- **Design dark mode** com efeitos glassmorphism
- **Design responsivo** otimizado para dispositivos móveis
- **Animações fluidas** e micro-interações
- **Tailwind CSS** para estilos consistentes
- **Tipografia premium** com Google Fonts (Outfit)

---

## 💻 Requisitos do Sistema

- **PHP**: 8.0 ou superior
- **SQLite**: 3.x
- **Apache**: 2.4+ com mod_rewrite habilitado
- **Extensões PHP requeridas**:
  - `pdo_sqlite`
  - `session`
  - `json`

---

## 🚀 Instalação

### Instalação Automática (Recomendada)

1. **Clone ou descarregue** o projeto no seu servidor web:
   ```bash
   cd /path/to/webserver/
   git clone <repository-url> data2rest
   ```

2. **Configure o Apache** para permitir o `.htaccess`:
   ```apache
   <Directory "/path/to/webserver/data2rest">
       AllowOverride All
       Require all granted
   </Directory>
   ```

3. **Reinicie o Apache**:
   ```bash
   brew services restart httpd
   ```

4. **Aceda à aplicação** no seu navegador:
   ```
   http://localhost/data2rest/
   ```

5. **Instalação automática**: O sistema detetará que é a primeira vez e criará automaticamente:
   - Base de dados do sistema (`data/system.sqlite`)
   - Usuário administrador por defeito
   - Estrutura de tabelas necessárias

### Credenciais por Defeito

Ao finalizar a instalação automática, poderá aceder com as seguintes credenciais:

```
Usuário: admin
Senha: admin123
```

⚠️ **AVISO DE SEGURANÇA**: Embora pareça um passo óbvio, **recomenda-se vivamente mudar a senha** imediatamente após o seu primeiro acesso para proteger a integridade do seu sistema e dos seus dados.

---

## 🏗️ Arquitetura do Sistema

```
data2rest/
├── public/                 # Ponto de entrada público
│   ├── index.php          # Router principal
│   └── uploads/           # Arquivos subidos
├── src/
│   ├── Core/              # Núcleo do sistema
│   │   ├── Auth.php       # Autenticação e autorização
│   │   ├── Config.php     # Configuração global
│   │   ├── Database.php   # Conexão a BD
│   │   ├── Installer.php  # Instalador automático
│   │   └── Router.php     # Sistema de rotas
│   ├── Modules/           # Módulos funcionais
│   │   ├── Api/           # → Ver docs/API.md
│   │   ├── Auth/          # → Ver docs/AUTH.md
│   │   └── Database/      # → Ver docs/DATABASE.md
│   └── Views/             # Vistas e templates
│       ├── admin/         # Painel de administração
│       ├── auth/          # Vistas de autenticação
│       └── partials/      # Componentes reutilizáveis
├── data/                  # Bases de dados do sistema
│   └── system.sqlite      # BD principal
└── docs/                  # Documentação detalhada
```

---

## 📦 Módulos

O sistema está organizado em módulos independentes e bem documentados:

### 1. [Módulo de API REST](docs/API.md)
Geração automática de endpoints REST com documentação interativa e exemplos multiplataforma.
- Controladores REST (GET, POST, PUT, DELETE)
- Gestão de API Keys com validação de segurança
- Documentação dinâmica com exemplos práticos
- **Exemplos incluídos**: cURL, JavaScript, Python

### 2. [Módulo de Autenticação](docs/AUTH.md)
Sistema completo de login, usuários, papéis e permissões granulares.
- Gestão de perfis de usuário
- Arquiteto de Políticas (Permissões por tabela e ação)
- Grupos de trabalho e hierarquias
- **Casos de uso**: Criação de papéis restritos, gestão de equipes

### 3. [Módulo de Bases de Dados](docs/DATABASE.md)
Gestão visual integral de bases de dados SQLite e fluxos de dados.
- Design de esquemas (Tabelas e Colunas)
- Tipos de dados avançados e interfaces de carregamento
- CRUD dinâmico com validações
- **Tutoriais**: Configuração de relações, gestão de arquivos multimédia

---

## ⚙️ Configuração

### Arquivo de Configuração

O arquivo `src/Core/Config.php` contém a configuração principal:

```php
private static $config = [
    'db_path' => __DIR__ . '/../../data/system.sqlite',
    'app_name' => 'Data2Rest',
    'base_url' => '',
    'upload_dir' => __DIR__ . '/../../public/uploads/',
    'allowed_roles' => ['admin', 'user'],
];
```

### Variáveis Configuráveis

- **db_path**: Caminho para a base de dados do sistema
- **app_name**: Nome da aplicação
- **upload_dir**: Diretório para arquivos subidos
- **allowed_roles**: Papéis permitidos no sistema

---

## 📖 Uso Básico

### 1. Criar uma Base de Dados

1. Aceda a **Databases** no menu principal
2. Preencha o formulário "Initialize New Node"
3. Insira nome e descrição
4. Clique em "Create Database"

### 2. Criar Tabelas

1. Selecione uma base de dados
2. Clique em "View Tables"
3. Insira o nome da tabela
4. Clique em "Create Table"

### 3. Configurar Campos

1. Clique no ícone de configuração (⚙️) da tabela
2. Adicione campos com seus tipos de dados
3. Configure opções especiais (upload de arquivos, textarea, etc.)

### 4. Gestão de Dados (CRUD)

1. Clique em "Enter Segment" numa tabela
2. Use o botão "New Entry" para criar registros
3. Edite ou elimine registros existentes

### 5. Gerar API REST

As APIs são geradas automaticamente para cada tabela:

```
GET    /api/v1/{database}/{table}        # Listar todos
GET    /api/v1/{database}/{table}/{id}   # Obter um
POST   /api/v1/{database}/{table}        # Criar
PUT    /api/v1/{database}/{table}/{id}   # Atualizar completo
PATCH  /api/v1/{database}/{table}/{id}   # Atualizar parcial
DELETE /api/v1/{database}/{table}/{id}   # Eliminar
```

### 6. Ver Documentação da API

1. Aceda a **API Docs** no menu
2. Selecione uma base de dados
3. Consulte endpoints e exemplos de uso

---

## 🔒 Segurança

### Melhores Práticas Implementadas

✅ **Autenticação de sessões** com PHP nativo
✅ **Preparação de consultas SQL** (Prepared Statements)
✅ **Escape de HTML** em todas as saídas
✅ **Validação de permissões** em cada ação
✅ **API Keys** para acesso a endpoints REST
✅ **Controle de acesso baseado em papéis** (RBAC)

---

## 🤝 Contribuir

As contribuições são bem-vindas. Por favor:

1. Fork o projeto
2. Crie uma branch para a sua feature (`git checkout -b feature/AmazingFeature`)
3. Commit suas mudanças (`git commit -m 'Add some AmazingFeature'`)
4. Push para a branch (`git push origin feature/AmazingFeature`)
5. Abra um Pull Request

---

## 📄 Licença

Este projeto está sob a Licença MIT. Veja o arquivo `LICENSE` para mais detalhes.

---

## 👨‍💻 Créditos

**Desenvolvido por:** **EnyalonDev - Néstor Ovallos Cañas**

- 🌐 Website: [nestorovallos.com](https://nestorovallos.com)
- 📧 Email: contacto@nestorovallos.com
- 💼 LinkedIn: [Néstor Ovallos](https://linkedin.com/in/nestorovallos)

---

## 🆘 Suporte

Se encontrar algum problema ou tiver perguntas:

1. Reveja a [documentação de módulos](docs/)
2. Abra um [Issue](https://github.com/teu-usuario/data2rest/issues)
3. Contacte o desenvolvedor

---

**Obrigado por usar o Data2Rest!** 🚀

---

## 🚧 TODOs e Melhorias Propostas

### 🎯 Prioridade Alta

- [ ] **Suporte Multi-Motor de Base de Dados**
  - Implementação de drivers para **MySQL, PostgreSQL e MariaDB**
  - Migração transparente entre motores
  - Suporte para bases de datos remotas
  - Painel de configuração de conexões externas

- [ ] **Sistema de Backup Automático**
  - Implementar backups programados de bases de dados
  - Exportação para SQL/JSON
  - Restauração a partir de backups
  - Armazenamento na nuvem (S3, Google Cloud)

---

## 💬 Contribuições

Tem ideias para melhorar o projeto?

1. Reveja a lista de TODOs
2. Abra um Issue para discutir a melhoria
3. Crie um Pull Request com a sua implementação

---
