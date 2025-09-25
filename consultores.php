<?php
$paginaAtiva = 'consultores';
require_once 'header.php';
require_once 'conexao.php';
require_once 'consultas.php';

$listaConsultores = getConsultores($pdo);
$dadosRelatorio = null;
$dadosGrafico = null;
$acao = null;
$campeoesPorMes = [];
$custoFixoMedio = 0;

// Variáveis para manter os valores no formulário
$consultores_selecionados_post = [];
$data_inicio_post = '';
$data_fim_post = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? null;
    $consultores_selecionados = $_POST['consultores'] ?? [];

    // Armazena os valores para preencher o formulário novamente
    $consultores_selecionados_post = $consultores_selecionados;
    $data_inicio_post = $_POST['data_inicio'] ?? '';
    $data_fim_post = $_POST['data_fim'] ?? '';

    
    // Conversão de data
    $data_inicio_mes = $_POST['data_inicio'] ?? '';
    $data_fim_mes = $_POST['data_fim'] ?? '';
    
    // Converte 'YYYY-MM' para 'YYYY-MM-DD'
    $data_inicio = $data_inicio_mes ? $data_inicio_mes . '-01' : '';
    $data_fim = $data_fim_mes ? date('Y-m-t', strtotime($data_fim_mes)) : '';

    if ($acao && !empty($consultores_selecionados) && $data_inicio && $data_fim) {
        if ($acao === 'relatorio') {
            $dadosRelatorio = getRelatorioConsultores($pdo, $consultores_selecionados, $data_inicio, $data_fim);
            if (!empty($dadosRelatorio)) {
                foreach ($dadosRelatorio as $linha) {
                    $periodo = $linha['periodo'];
                    $receita = $linha['receita_liquida'];
                    if (!isset($campeoesPorMes[$periodo]) || $receita > $campeoesPorMes[$periodo]) {
                        $campeoesPorMes[$periodo] = $receita;
                    }
                }
            }
        } elseif ($acao === 'grafico' || $acao === 'pizza') {
            $dadosGrafico = getDadosGraficoConsultores($pdo, $consultores_selecionados, $data_inicio, $data_fim);
            $relatorioParaCusto = getRelatorioConsultores($pdo, $consultores_selecionados, $data_inicio, $data_fim);
            if (!empty($relatorioParaCusto)) {
                $somaCustos = 0;
                $custosUnicos = [];
                foreach ($relatorioParaCusto as $linha) {
                    $custosUnicos[$linha['no_usuario']] = $linha['custo_fixo'];
                }
                foreach ($custosUnicos as $custo) {
                    $somaCustos += $custo;
                }
                $custoFixoMedio = $somaCustos;
            }
        }
    }
}
?>

<div class="card p-4 shadow-sm">
    <form method="POST" action="consultores.php">
        <div class="row align-items-end">
            <div class="col-md-5">
                <label for="consultores" class="form-label">Selecione os Consultores</label>
                <select multiple class="form-select" id="consultores" name="consultores[]" size="5" required>
                    <?php foreach ($listaConsultores as $consultor) : ?>
                        <option value="<?php echo htmlspecialchars($consultor['co_usuario']); ?>" <?php echo in_array($consultor['co_usuario'], $consultores_selecionados_post) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($consultor['no_usuario']); ?>
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
    <?php if ($acao === 'relatorio' && isset($dadosRelatorio)) : ?>
        <h2 class="mb-3">Relatório de Performance</h2>
        <?php if (!empty($dadosRelatorio)) : ?>
            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>Período</th>
                        <th>Consultor</th>
                        <th>Receita Líquida</th>
                        <th>Custo Fixo</th>
                        <th>Comissão</th>
                        <th>Lucro</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $totais = [];
                    foreach ($dadosRelatorio as $linha) {
                        if (!isset($totais[$linha['no_usuario']])) {
                            $totais[$linha['no_usuario']] = ['receita' => 0, 'lucro' => 0];
                        }
                        $totais[$linha['no_usuario']]['receita'] += $linha['receita_liquida'];
                    ?>
                        <tr <?php echo ($linha['receita_liquida'] == ($campeoesPorMes[$linha['periodo']] ?? 0)) ? 'class="table-success"' : ''; ?>>
                            <td><?php echo htmlspecialchars($linha['periodo']); ?></td>
                            <td><?php echo htmlspecialchars($linha['no_usuario']); ?></td>
                            <td>R$ <?php echo number_format($linha['receita_liquida'], 2, ',', '.'); ?></td>
                            <td>R$ <?php echo number_format($linha['custo_fixo'], 2, ',', '.'); ?></td>
                            <td>R$ 0,00</td>
                            <td>R$ <?php echo number_format($linha['receita_liquida'] - $linha['custo_fixo'], 2, ',', '.'); ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
                <tfoot class="table-group-divider">
                    <?php foreach ($totais as $nome => $total) : ?>
                        <tr>
                            <td colspan="5" class="text-end"><strong>Total <?php echo htmlspecialchars($nome); ?></strong></td>
                            <td><strong>R$ <?php echo number_format($total['receita'] - ($custosUnicos[$nome] ?? 0), 2, ',', '.'); ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                </tfoot>
            </table>
        <?php else : ?>
            <div class="alert alert-warning">Nenhum resultado encontrado.</div>
        <?php endif; ?>
    <?php endif; ?>

    <?php if (($acao === 'grafico' || $acao === 'pizza') && isset($dadosGrafico) && !empty($dadosGrafico)) : ?>
        <h2 class="mb-3"><?php echo $acao === 'grafico' ? 'Gráfico de Performance' : 'Gráfico de Pizza'; ?></h2>
        <div style="height: 400px;">
            <canvas id="performanceChart"></canvas>
        </div>
        <script>
            const ctx = document.getElementById('performanceChart').getContext('2d');
            const labels = <?php echo json_encode(array_column($dadosGrafico, 'no_usuario')); ?>;
            const data = <?php echo json_encode(array_column($dadosGrafico, 'receita_liquida')); ?>;
            const custoFixoMedio = <?php echo $custoFixoMedio; ?>;
            let chartConfig;

            if ('<?php echo $acao; ?>' === 'pizza') {
                chartConfig = { type: 'pie', data: { labels: labels, datasets: [{ label: 'Participação na Receita Líquida', data: data, backgroundColor: ['rgba(255, 99, 132, 0.7)','rgba(54, 162, 235, 0.7)','rgba(255, 206, 86, 0.7)','rgba(75, 192, 192, 0.7)','rgba(153, 102, 255, 0.7)','rgba(255, 159, 64, 0.7)'], }] }, options: { maintainAspectRatio: false, plugins: { tooltip: { callbacks: { label: function(context) { const total = context.chart.data.datasets[0].data.reduce((a, b) => a + b, 0); const value = context.raw; const percentage = ((value / total) * 100).toFixed(2) + '%'; return `${context.label}: ${percentage}`; } } } } } };
            } else {
                chartConfig = { type: 'bar', data: { labels: labels, datasets: [{ label: 'Receita Líquida', data: data, backgroundColor: 'rgba(54, 162, 235, 0.7)', type: 'bar', order: 2 }, { label: 'Custo Fixo Médio', data: Array(labels.length).fill(custoFixoMedio), borderColor: 'rgba(255, 99, 132, 1)', backgroundColor: 'rgba(255, 99, 132, 1)', pointRadius: 0, type: 'line', order: 1 }] }, options: { maintainAspectRatio: false, scales: { y: { beginAtZero: true } } } };
            }
            new Chart(ctx, chartConfig);
        </script>
    <?php elseif (($acao === 'grafico' || $acao === 'pizza') && (empty($dadosGrafico) || !isset($dadosGrafico))) : ?>
        <div class="alert alert-warning">Nenhum resultado encontrado para gerar o gráfico.</div>
    <?php endif; ?>
</div>

</main>
</body>
</html>