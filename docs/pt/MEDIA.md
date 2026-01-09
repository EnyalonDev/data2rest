# 🖼️ Módulo de Biblioteca de Mídia

[← Voltar ao README Principal](../../README.pt.md)

## 📋 Descrição

O **Módulo de Biblioteca de Mídia** é uma solução integral para a gestão de ativos digitais dentro do Data2Rest. Permite não apenas organizar e visualizar arquivos, mas também realizar edições avançadas de imagens, gerenciar a lixeira e rastrear o uso de arquivos em todas as bases de dados do sistema.

---

## ✨ Características Principais

### 📁 Organização e Visualização
- **Navegação por Pastas**: Estrutura organizada por datas e tabelas.
- **Vistas Duplas**: Alterne entre vista de **Mosaico (Grid)** e **Lista (List)** para maior conveniência.
- **Breadcrumbs Dinâmicos**: Navegação rápida entre diretórios com uma barra de caminho compacta.
- **Busca em Tempo Real**: Filtre seus arquivos instantaneamente por nome.

### 🎨 Editor de Imagens Profissional
Integração nativa poderosa para manipulação de imagens sem sair do painel:
- **Recorte (Crop)**: Ajuste de dimensões com pré-visualização em tempo real.
- **Redimensionamento**: Ajuste de largura e altura mantendo a proporção.
- **Filtros Artísticos**: Cinza, Sépia, Inverter, Vintage, Dramático, Desfoque e Nitidez.
- **Otimização**: Controle de qualidade (JPEG/WebP) para equilibrar peso e nitidez.
- **Segurança**: Opção de **"Salvar como cópia"** ativa por padrão para proteger originais.

### 🗑️ Lixeira e Retenção
- **Exclusão Segura**: Os arquivos excluídos são movidos para uma lixeira `.trash`.
- **Restauração em um Clique**: Recupere arquivos excluídos acidentalmente para sua localização original.
- **Expurgo Automático**: Configure quantos dias os arquivos devem permanecer na lixeira antes de serem excluídos definitivamente.

### 📊 Rastreador de Uso (Usage Tracker)
- **Detecção de Órfãos**: Identificação de arquivos que não estão sendo usados em nenhuma tabela.
- **Mapa de Referências**: Visualize exatamente em qual base de dados e tabela cada arquivo é referenciado antes de excluí-lo.

### 🛠️ Ferramentas de Desenvolvimento e Manutenção
- **Super Refresh**: Botão para forçar o recarregamento da interface ignorando o cache do navegador.
- **Limpeza de Cache**: Ferramenta para purgar arquivos temporários e otimizar o servidor.

---

## 🚀 Uso do Editor de Imagens

1. Selecione uma **imagem** na galeria.
2. No painel direito (Inspetor), clique no botão **Edit (Lápis)**.
3. O modal do editor abrirá com as seguintes opções:
   - **Transformar**: Use o mouse para selecionar a área de recorte.
   - **Filtros**: Escolha entre mais de 8 efeitos artísticos.
   - **Dimensões**: Mude o tamanho manualmente.
   - **Qualidade**: Ajuste o controle deslizante de otimização.
4. Clique em **Salvar Mudanças**. Se "Salvar como cópia" estiver marcado, um novo arquivo com o sufixo `-edited` será criado.

---

## 🔧 Detalhes Técnicos

### Localização de Arquivos
```
public/uploads/
├── YYYY-MM-DD/     # Organização por data
├── .trash/         # Lixeira
└── [tabelas]/      # Arquivos específicos de módulos
```

### Controlador Principal
`src/Modules/Media/MediaController.php`

**Métodos Chave:**
- `list()`: Escaneamento e listagem de arquivos com metadados.
- `edit()`: Processamento de imagens usando a biblioteca **GD** do PHP.
- `usage()`: Algoritmo de busca cruzada em múltiplas bases de dados SQLite.
- `bulkDelete()`, `restore()`, `purge()`: Gestão do ciclo de vida de arquivos.

---

## 🔒 Segurança e Integridade

### 🔗 Integração Robusta
- **Suporte de URLs Externas**: Detecção inteligente de imagens em links assinados ou com parâmetros de consulta (e.g., `image.jpg?token=123`).
- **Validação de Caminhos**: Sistema de segurança que impede o acesso a arquivos fora do escopo do projeto atual (`../ traversal attack prevention`).
- **Permissões Granulares**: Requer permissões específicas (`module:media.view_files`) para acesso.

---

[← Voltar ao README Principal](../../README.pt.md)
