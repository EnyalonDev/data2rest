<?php

// Translations Map
$translations = [
    'en' => [
        'system_database' => [
            'title' => 'System Database',
            'dashboard' => 'Dashboard',
            'tables' => 'System Tables',
            'backups' => 'Backups',
            'logs' => 'System Logs',
            'query_executor' => 'SQL Executor',
            'optimize' => 'Optimize Database',
            'create_backup' => 'Create Backup',
            'restore_backup' => 'Restore Backup',
            'download_backup' => 'Download Backup',
            'delete_backup' => 'Delete Backup',
            'backup_created' => 'Backup created successfully',
            'backup_restored' => 'Database restored successfully',
            'database_optimized' => 'Database optimized',
            'query_executed' => 'Query executed successfully',
            'dangerous_query' => 'This query might be dangerous. Confirm execution?',
            'no_backups' => 'No backups available',
            'backup_size' => 'Backup Size',
            'backup_date' => 'Backup Date',
            'backup_type' => 'Type',
            'manual' => 'Manual',
            'automatic' => 'Automatic',
            'total_tables' => 'Total Tables',
            'total_records' => 'Total Records',
            'database_size' => 'Database Size',
            'last_backup' => 'Last Backup',
            'disk_space' => 'Disk Space',
            'execute_query' => 'Execute Query',
            'query_results' => 'Query Results',
            'export_results' => 'Export Results',
            'clean_old_data' => 'Clean Old Data',
            'data_cleaned' => 'Old data cleaned successfully'
        ],
        'dashboard' => [
            'activity' => [
                'summary' => 'System statistics and detailed API usage logs.',
                'event' => 'Event',
                'user' => 'User',
                'details' => 'Details',
                'time' => 'Time',
                'api_usage' => 'API Usage',
                'data_mutations' => 'Data Changes',
                'active_endpoints' => 'Active Endpoints',
                'refresh' => 'Refresh',
                'no_activity' => 'No activity recorded yet.'
            ]
        ],
        'command_palette' => [
            'no_results' => 'No results found for ":q"'
        ]
    ],
    'pt' => [
        'system_database' => [
            'title' => 'Banco de Dados do Sistema',
            'dashboard' => 'Painel',
            'tables' => 'Tabelas do Sistema',
            'backups' => 'Backups',
            'logs' => 'Logs do Sistema',
            'query_executor' => 'Executor SQL',
            'optimize' => 'Otimizar Banco de Dados',
            'create_backup' => 'Criar Backup',
            'restore_backup' => 'Restaurar Backup',
            'download_backup' => 'Baixar Backup',
            'delete_backup' => 'Excluir Backup',
            'backup_created' => 'Backup criado com sucesso',
            'backup_restored' => 'Banco de dados restaurado com sucesso',
            'database_optimized' => 'Banco de dados otimizado',
            'query_executed' => 'Consulta executada com sucesso',
            'dangerous_query' => 'Esta consulta pode ser perigosa. Confirmar execução?',
            'no_backups' => 'Nenhum backup disponível',
            'backup_size' => 'Tamanho do Backup',
            'backup_date' => 'Data do Backup',
            'backup_type' => 'Tipo',
            'manual' => 'Manual',
            'automatic' => 'Automático',
            'total_tables' => 'Total de Tabelas',
            'total_records' => 'Total de Registros',
            'database_size' => 'Tamanho do Banco de Dados',
            'last_backup' => 'Último Backup',
            'disk_space' => 'Espaço em Disco',
            'execute_query' => 'Executar Consulta',
            'query_results' => 'Resultados da Consulta',
            'export_results' => 'Exportar Resultados',
            'clean_old_data' => 'Limpar Dados Antigos',
            'data_cleaned' => 'Dados antigos limpos com sucesso'
        ],
        'dashboard' => [
            'activity' => [
                'summary' => 'Estatísticas e registros detalhados do sistema e uso de API.',
                'event' => 'Evento',
                'user' => 'Usuário',
                'details' => 'Detalhes',
                'time' => 'Tempo',
                'api_usage' => 'Uso da API',
                'data_mutations' => 'Alterações de Dados',
                'active_endpoints' => 'Endpoints Ativos',
                'refresh' => 'Atualizar',
                'no_activity' => 'Nenhuma atividade registrada ainda.'
            ]
        ],
        'command_palette' => [
            'no_results' => 'Nenhum resultado encontrado para ":q"'
        ]
    ]
];

function updateLangFile($path, $newStrings)
{
    if (!file_exists($path))
        return;
    $current = include $path;

    // Merge recursively
    $merged = array_replace_recursive($current, $newStrings);

    // Export back to file
    $content = "<?php\nreturn " . var_export($merged, true) . ";\n";
    file_put_contents($path, $content);
    echo "Updated $path\n";
}

updateLangFile(__DIR__ . '/../src/I18n/en.php', $translations['en']);
updateLangFile(__DIR__ . '/../src/I18n/pt.php', $translations['pt']);

echo "Language files updated successfully.\n";
