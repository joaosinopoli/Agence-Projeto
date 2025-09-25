<?php
require_once 'conexao.php';

// --- Funções para a Aba CONSULTORES 
function getConsultores(PDO $pdo): array {
    $sql = "SELECT u.co_usuario, u.no_usuario FROM cao_usuario AS u JOIN permissao_sistema AS ps ON u.co_usuario = ps.co_usuario WHERE ps.co_sistema = 1 AND ps.in_ativo = 'S' AND ps.co_tipo_usuario IN (0, 1, 2) ORDER BY u.no_usuario;";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
function getRelatorioConsultores(PDO $pdo, array $consultores, string $inicio, string $fim): array {
    if (empty($consultores)) return [];
    $placeholders = implode(',', array_fill(0, count($consultores), '?'));
    $sql = "SELECT u.no_usuario, DATE_FORMAT(f.data_emissao, '%Y-%m') AS periodo, SUM(f.valor - (f.valor * (f.total_imp_inc / 100))) AS receita_liquida, s.brut_salario AS custo_fixo FROM cao_usuario AS u JOIN cao_os AS o ON u.co_usuario = o.co_usuario JOIN cao_fatura AS f ON o.co_os = f.co_os LEFT JOIN cao_salario AS s ON u.co_usuario = s.co_usuario WHERE f.data_emissao BETWEEN ? AND ? AND u.co_usuario IN ($placeholders) GROUP BY u.co_usuario, u.no_usuario, s.brut_salario, periodo ORDER BY u.no_usuario, periodo;";
    $stmt = $pdo->prepare($sql);
    $params = array_merge([$inicio, $fim], $consultores);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
function getDadosGraficoConsultores(PDO $pdo, array $consultores, string $inicio, string $fim): array {
    if (empty($consultores)) return [];
    $placeholders = implode(',', array_fill(0, count($consultores), '?'));
    $sql = "SELECT u.no_usuario, SUM(f.valor - (f.valor * (f.total_imp_inc / 100))) AS receita_liquida FROM cao_usuario AS u JOIN cao_os AS o ON u.co_usuario = o.co_usuario JOIN cao_fatura AS f ON o.co_os = f.co_os WHERE f.data_emissao BETWEEN ? AND ? AND u.co_usuario IN ($placeholders) GROUP BY u.co_usuario, u.no_usuario ORDER BY u.no_usuario;";
    $stmt = $pdo->prepare($sql);
    $params = array_merge([$inicio, $fim], $consultores);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// --- Funções para a Aba CLIENTES
function getClientes(PDO $pdo): array {
    $sql = "SELECT co_cliente, no_fantasia FROM cao_cliente ORDER BY no_fantasia;";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
function getRelatorioClientes(PDO $pdo, array $clientes, string $inicio, string $fim) {
    if (empty($clientes)) return [];
    try {
        $placeholders = implode(',', array_fill(0, count($clientes), '?'));
        $sql = "SELECT c.no_fantasia, DATE_FORMAT(f.data_emissao, '%Y-%m') AS periodo, SUM(f.valor - (f.valor * (f.total_imp_inc / 100))) AS receita_liquida FROM cao_cliente AS c JOIN cao_fatura AS f ON c.co_cliente = f.co_cliente WHERE f.data_emissao BETWEEN ? AND ? AND c.co_cliente IN ($placeholders) GROUP BY c.co_cliente, c.no_fantasia, periodo ORDER BY c.no_fantasia, periodo;";
        
        $stmt = $pdo->prepare($sql);
        

        $stmt->bindValue(1, $inicio, PDO::PARAM_STR);
        $stmt->bindValue(2, $fim, PDO::PARAM_STR);
        
        
        $i = 3;
        foreach ($clientes as $id) {
            $stmt->bindValue($i, (int)$id, PDO::PARAM_INT);
            $i++;
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) { return "ERRO NA CONSULTA: " . $e->getMessage(); }
}
function getDadosGraficoClientes(PDO $pdo, array $clientes, string $inicio, string $fim) {
    if (empty($clientes)) return [];
    try {
        $placeholders = implode(',', array_fill(0, count($clientes), '?'));
        $sql = "SELECT c.no_fantasia, SUM(f.valor - (f.valor * (f.total_imp_inc / 100))) AS receita_liquida FROM cao_cliente AS c JOIN cao_fatura AS f ON c.co_cliente = f.co_cliente WHERE f.data_emissao BETWEEN ? AND ? AND c.co_cliente IN ($placeholders) GROUP BY c.co_cliente, c.no_fantasia ORDER BY c.no_fantasia;";
        
        $stmt = $pdo->prepare($sql);
        
        $stmt->bindValue(1, $inicio, PDO::PARAM_STR);
        $stmt->bindValue(2, $fim, PDO::PARAM_STR);
        
        $i = 3;
        foreach ($clientes as $id) {
            $stmt->bindValue($i, (int)$id, PDO::PARAM_INT);
            $i++;
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) { return "ERRO NA CONSULTA: " . $e->getMessage(); }
}
?>