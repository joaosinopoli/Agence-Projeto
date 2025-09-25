<?php
require_once 'conexao.php';

// --- Funções para a Aba CONSULTORES ---
function getConsultores(PDO $pdo): array
{
    $sql = "SELECT u.co_usuario, u.no_usuario FROM cao_usuario AS u JOIN permissao_sistema AS ps ON u.co_usuario = ps.co_usuario WHERE ps.co_sistema = 1 AND ps.in_ativo = 'S' AND ps.co_tipo_usuario IN (0, 1, 2) ORDER BY u.no_usuario;";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
function getRelatorioConsultores(PDO $pdo, array $c, string $i, string $f): array
{
    if (empty($c)) return [];
    $p = implode(',', array_fill(0, count($c), '?'));
    $sql = "SELECT u.no_usuario, DATE_FORMAT(f.data_emissao, '%Y-%m') AS periodo, SUM(f.valor - (f.valor * (f.total_imp_inc / 100))) AS receita_liquida, s.brut_salario AS custo_fixo FROM cao_usuario AS u JOIN cao_os AS o ON u.co_usuario = o.co_usuario JOIN cao_fatura AS f ON o.co_os = f.co_os LEFT JOIN cao_salario AS s ON u.co_usuario = s.co_usuario WHERE f.data_emissao BETWEEN ? AND ? AND u.co_usuario IN ($p) GROUP BY u.co_usuario, u.no_usuario, s.brut_salario, periodo ORDER BY u.no_usuario, periodo;";
    $stmt = $pdo->prepare($sql);
    $params = array_merge([$i, $f], $c);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
function getDadosGraficoConsultores(PDO $pdo, array $c, string $i, string $f): array
{
    if (empty($c)) return [];
    $p = implode(',', array_fill(0, count($c), '?'));
    $sql = "SELECT u.no_usuario, SUM(f.valor - (f.valor * (f.total_imp_inc / 100))) AS receita_liquida FROM cao_usuario AS u LEFT JOIN cao_os AS o ON u.co_usuario = o.co_usuario LEFT JOIN cao_fatura AS f ON o.co_os = f.co_os AND f.data_emissao BETWEEN ? AND ? WHERE u.co_usuario IN ($p) GROUP BY u.co_usuario, u.no_usuario ORDER BY u.no_usuario;";
    $params = array_merge([$i, $f], $c);
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
function getCustosFixosConsultores(PDO $pdo, array $consultores): array
{
    if (empty($consultores)) return [];
    $placeholders = implode(',', array_fill(0, count($consultores), '?'));
    $sql = "SELECT brut_salario FROM cao_salario WHERE co_usuario IN ($placeholders)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($consultores);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// --- Funções para a Aba CLIENTES ---
function getClientes(PDO $pdo): array
{
    $sql = "SELECT co_cliente, no_fantasia FROM cao_cliente ORDER BY no_fantasia;";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
function getRelatorioClientes(PDO $pdo, array $cli, string $i, string $f): array
{
    if (empty($cli)) return [];
    $p = implode(',', array_fill(0, count($cli), '?'));
    $sql = "SELECT c.no_fantasia, DATE_FORMAT(f.data_emissao, '%Y-%m') AS periodo, SUM(f.valor - (f.valor * (f.total_imp_inc / 100))) AS receita_liquida FROM cao_cliente AS c JOIN cao_fatura AS f ON c.co_cliente = f.co_cliente JOIN cao_os AS o ON f.co_os = o.co_os JOIN cao_usuario AS u ON o.co_usuario = u.co_usuario JOIN permissao_sistema AS ps ON u.co_usuario = ps.co_usuario WHERE f.data_emissao BETWEEN ? AND ? AND c.co_cliente IN ($p) AND ps.co_sistema = 1 AND ps.in_ativo = 'S' AND ps.co_tipo_usuario IN (0, 1, 2) GROUP BY c.co_cliente, c.no_fantasia, periodo ORDER BY c.no_fantasia, periodo;";
    $stmt = $pdo->prepare($sql);
    $params = array_merge([$i, $f], $cli);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
function getDadosGraficoClientes(PDO $pdo, array $cli, string $i, string $f): array
{
    if (empty($cli)) return [];
    $p = implode(',', array_fill(0, count($cli), '?'));
    $sql = "SELECT c.no_fantasia, SUM(f.valor - (f.valor * (f.total_imp_inc / 100))) AS receita_liquida FROM cao_cliente AS c JOIN cao_fatura AS f ON c.co_cliente = f.co_cliente JOIN cao_os AS o ON f.co_os = o.co_os JOIN cao_usuario AS u ON o.co_usuario = u.co_usuario JOIN permissao_sistema AS ps ON u.co_usuario = ps.co_usuario WHERE f.data_emissao BETWEEN ? AND ? AND c.co_cliente IN ($p) AND ps.co_sistema = 1 AND ps.in_ativo = 'S' AND ps.co_tipo_usuario IN (0, 1, 2) GROUP BY c.co_cliente, c.no_fantasia ORDER BY receita_liquida DESC;";
    $stmt = $pdo->prepare($sql);
    $params = array_merge([$i, $f], $cli);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
