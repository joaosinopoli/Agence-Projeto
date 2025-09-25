<?php
$paginaAtiva = 'clientes';
require_once 'header.php';
require_once 'conexao.php';
require_once 'consultas.php';

$listaClientes = getClientes($pdo);
$dadosRelatorioClientes = null;
$dadosGraficoClientes = null;
$acao = null;
$campeoesPorMesClientes = [];

// Variáveis para manter os valores selecionados no formulário após o envio
$clientes_selecionados_post = [];
$data_inicio_post = '';
$data_fim_post = '';

// Processa o formulário quando enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? null;
    
    // Pega os dados do formulário e já limpa eles
    $clientes_selecionados = $_POST['clientes'] ?? [];
    $data_inicio_mes = isset($_POST['data_inicio']) ? trim($_POST['data_inicio']) : '';
    $data_fim_mes = isset($_POST['data_fim']) ? trim($_POST['data_fim']) : '';

    // Armazena valores para preencher o formulário novamente
    $clientes_selecionados_post = $clientes_selecionados;
    $data_inicio_post = $data_inicio_mes;
    $data_fim_post = $data_fim_mes;
    
    // Converte as datas limpas para o formato completo
    $data_inicio = $data_inicio_mes ? $data_inicio_mes . '-01' : '';
    $data_fim = $data_fim_mes ? date('Y-m-t', strtotime($data_fim_mes)) : '';

    if ($acao && !empty($clientes_selecionados) && $data_inicio && $data_fim) {
        if ($acao === 'relatorio') {
            $dadosRelatorioClientes = getRelatorioClientes($pdo, $clientes_selecionados, $data_inicio, $data_fim);
            
            if (!empty($dadosRelatorioClientes) && is_array($dadosRelatorioClientes)) {
                foreach ($dadosRelatorioClientes as $linha) {
                    $periodo = $linha['periodo'];
                    $receita = $linha['receita_liquida'];
                    if (!isset($campeoesPorMesClientes[$periodo]) || $receita > $campeoesPorMesClientes[$periodo]) {
                        $campeoesPorMesClientes[$periodo] = $receita;
                    }
                }
            }
        } elseif ($acao === 'grafico' || $acao === 'pizza') {
            $dadosGraficoClientes = getDadosGraficoClientes($pdo, $clientes_selecionados, $data_inicio, $data_fim);
        }
    }
}
?>

<div class="card p-4 shadow-sm">
    <form method="POST" action="clientes.php">
        <div class="row align-items-end">
            <div class="col-md-5">
                <label for="clientes" class="form-label">Selecione os Clientes</label>
                <select multiple class="form-select" id="clientes" name="clientes[]" size="5" required>
                    <?php foreach ($listaClientes as $cliente) : ?>
                        <option value="<?php echo htmlspecialchars($cliente['co_cliente']); ?>" <?php echo in_array($cliente['co_cliente'], $clientes_selecionados_post) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cliente['no_fantasia']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-5">
                <div class="row">
                    <div class="col-6">
                        <label for="data_inicio" class="form-label">Data Início</label>
                        <input type="month" class="form-control" id="data_inicio" name="data_inicio" value="<?php echo htmlspecialchars($data_inicio_post); ?>" required>
                    </div>
                    <div class="col-6">
                        <label for="data_fim" class="form-label">Data Fim</label>
                        <input type="month" class="form-control" id="data_fim" name="data_fim" value="<?php echo htmlspecialchars($data_fim_post); ?>" required>
                    </div>
                </div>
            </div>

            <div class="col-md-2 d-flex justify-content-end">
                <div class="btn-group">
                    <button type="submit" name="acao" value="relatorio" class="btn btn-primary">Relatório</button>
                    <button type="submit" name="acao" value="grafico" class="btn btn-secondary">Gráfico</button>
                    <button type="submit" name="acao" value="pizza" class="btn btn-info">Pizza</button>
                </div>
            </div>
        </div>
    </form>
</div>

<div class="card p-4 mt-4 shadow-sm">
    <?php if ($acao === 'relatorio') : ?>
        <h2 class="mb-3">Relatório de Performance</h2>
        <?php if (!empty($dadosRelatorioClientes) && is_array($dadosRelatorioClientes)) : ?>
            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>Período</th>
                        <th>Cliente</th>
                        <th>Receita Líquida</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $totaisPorCliente = [];
                    foreach ($dadosRelatorioClientes as $linha) {
                        if (!isset($totaisPorCliente[$linha['no_fantasia']])) {
                            $totaisPorCliente[$linha['no_fantasia']] = 0;
                        }
                        $totaisPorCliente[$linha['no_fantasia']] += $linha['receita_liquida'];
                    ?>
                        <tr <?php echo ($linha['receita_liquida'] == ($campeoesPorMesClientes[$linha['periodo']] ?? 0)) ? 'class="table-success"' : ''; ?>>
                            <td><?php echo htmlspecialchars($linha['periodo']); ?></td>
                            <td><?php echo htmlspecialchars($linha['no_fantasia']); ?></td>
                            <td>R$ <?php echo number_format($linha['receita_liquida'], 2, ',', '.'); ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
                <tfoot class="table-group-divider">
                    <?php foreach ($totaisPorCliente as $nome => $total) : ?>
                        <tr>
                            <td colspan="2" class="text-end"><strong>Total <?php echo htmlspecialchars($nome); ?></strong></td>
                            <td><strong>R$ <?php echo number_format($total, 2, ',', '.'); ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                </tfoot>
            </table>
        <?php else : ?>
            <div class="alert alert-warning">Nenhum resultado encontrado.</div>
        <?php endif; ?>
    <?php endif; ?>

    <?php if (($acao === 'grafico' || $acao === 'pizza') && !empty($dadosGraficoClientes) && is_array($dadosGraficoClientes)) : ?>
        <h2 class="mb-3"><?php echo $acao === 'grafico' ? 'Gráfico de Performance' : 'Gráfico de Pizza'; ?></h2>
        <div style="height: 400px;">
            <canvas id="performanceChart"></canvas>
        </div>
        <script>
            const ctx = document.getElementById('performanceChart').getContext('2d');
            const labels = <?php echo json_encode(array_column($dadosGraficoClientes, 'no_fantasia')); ?>;
            const data = <?php echo json_encode(array_column($dadosGraficoClientes, 'receita_liquida')); ?>;
            let chartConfig;
            if ('<?php echo $acao; ?>' === 'pizza') {
                chartConfig = { type: 'pie', data: { labels: labels, datasets: [{ label: 'Participação na Receita Líquida', data: data, backgroundColor: ['rgba(255, 99, 132, 0.7)','rgba(54, 162, 235, 0.7)','rgba(255, 206, 86, 0.7)','rgba(75, 192, 192, 0.7)','rgba(153, 102, 255, 0.7)','rgba(255, 159, 64, 0.7)'], }] }, options: { maintainAspectRatio: false, plugins: { tooltip: { callbacks: { label: function(context) { const total = context.chart.data.datasets[0].data.reduce((a, b) => a + b, 0); const value = context.raw; const percentage = ((value / total) * 100).toFixed(2) + '%'; return `${context.label}: ${percentage}`; } } } } } };
            } else {
                chartConfig = { type: 'bar', data: { labels: labels, datasets: [{ label: 'Receita Líquida', data: data, backgroundColor: 'rgba(75, 192, 192, 0.7)', }] }, options: { maintainAspectRatio: false, scales: { y: { beginAtZero: true } } } };
            }
            new Chart(ctx, chartConfig);
        </script>
    <?php elseif (($acao === 'grafico' || $acao === 'pizza') && (empty($dadosGraficoClientes) || !is_array($dadosGraficoClientes))) : ?>
        <div class="alert alert-warning">Nenhum resultado encontrado para gerar o gráfico.</div>
    <?php endif; ?>
</div>

</main>
</body>
</html>