<?php
// Inclui o arquivo de conexão do banco, que deve existir na mesma pasta ou em um caminho acessível
include 'conexao.php'; 

// Inicia a sessão para garantir que o ID do terreiro está disponível
session_start();

// Supondo que o ID do terreiro é obtido da sessão após o login
$id_terreiro = $_SESSION['id_terreiro'] ?? 1; 

// Obtém a ação desejada da URL, com um valor padrão para exibir a lista
$action = $_GET['action'] ?? 'list';

// Lógica de roteamento simples
switch ($action) {
    case 'list':
        // Inclui o arquivo que lista as movimentações
        include '<Financeiro>financas_list.php';
        break;
    case 'add_form':
        // Inclui o arquivo com o formulário de adição
        include 'Financeiro/financas_add_form.php';
        break;
    case 'add_action':
        // Inclui o arquivo que processa a adição de uma nova movimentação
        include 'Financeiro/financas_add.php';
        break;
    case 'resumo':
        // Inclui o arquivo com o resumo financeiro
        include 'Financeiro/financas_resumo.php';
        break;
    case 'dashboard':
        // Inclui o arquivo do dashboard interativo
        include 'Financeiro/financas_dashboard.php';
        break;
    default:
        echo "Página não encontrada.";
        break;
}

?>
