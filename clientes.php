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
$clientes_selecionados_post = [];
$data_inicio_post = '';
$data_fim_post = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? null;
    $clientes_selecionados = $_POST['clientes'] ?? [];
    $clientes_selecionados_post = $clientes_selecionados;
    $data_inicio_post = $_POST['data_inicio'] ?? '';
    $data_fim_post = $_POST['data_fim'] ?? '';
    $data_inicio = $data_inicio_post ? $data_inicio_post . '-01' : '';
    $data_fim = $data_fim_post ? date('Y-m-t', strtotime($data_fim_post)) : '';
    if ($acao && !empty($clientes_selecionados) && $data_inicio && $data_fim) {
        if ($acao === 'relatorio') {
            $dadosRelatorioClientes = getRelatorioClientes($pdo, $clientes_selecionados, $data_inicio, $data_fim);
            if (!empty($dadosRelatorioClientes)) {
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
    <h1 class="mb-4 border-bottom pb-2">Performance Comercial <small class="text-muted fs-5">- Por Cliente</small></h1>
    <form method="POST" action="clientes.php">
        <div class="row align-items-center">
            <div class="col-md-5">
                <label for="clientes" class="form-label fw-bold">Selecione os Clientes</label>
                <select multiple class="form-select select2-multiple" id="clientes" name="clientes[]" required>
                    <?php foreach ($listaClientes as $cliente): ?>
                        <option value="<?php echo htmlspecialchars($cliente['co_cliente']); ?>" <?php echo in_array($cliente['co_cliente'], $clientes_selecionados_post) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cliente['no_fantasia']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <div class="row">
                    <div class="col-6">
                        <label for="data_inicio" class="form-label fw-bold">De</label>
                        <input type="month" class="form-control" id="data_inicio" name="data_inicio" value="<?php echo htmlspecialchars($data_inicio_post); ?>" required>
                    </div>
                    <div class="col-6">
                        <label for="data_fim" class="form-label fw-bold">Até</label>
                        <input type="month" class="form-control" id="data_fim" name="data_fim" value="<?php echo htmlspecialchars($data_fim_post); ?>" required>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="d-grid gap-2 pt-4">
                    <button type="submit" name="acao" value="relatorio" class="btn btn-primary">Relatório</button>
                    <button type="submit" name="acao" value="grafico" class="btn btn-success">Gráfico</button>
                    <button type="submit" name="acao" value="pizza" class="btn btn-warning">Pizza</button>
                </div>
            </div>
        </div>
    </form>
</div>
<div id="area-resultados-clientes" class="mt-4">
    <?php if ($acao === 'relatorio' && isset($dadosRelatorioClientes)): ?> <div class="card p-4">
            <h2 class="mb-3">Relatório de Performance por Cliente</h2><?php if (empty($dadosRelatorioClientes)): ?><div class="alert alert-info">Nenhum dado encontrado.</div><?php else: ?><table class="table table-bordered table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>Cliente</th>
                            <th>Período</th>
                            <th>Receita Líquida</th>
                        </tr>
                    </thead>
                    <tbody><?php $relatorioAgrupado = [];
                                                                                                                                                                                foreach ($dadosRelatorioClientes as $linha) {
                                                                                                                                                                                    $relatorioAgrupado[$linha['no_fantasia']][] = $linha;
                                                                                                                                                                                } ?><?php foreach ($relatorioAgrupado as $nomeCliente => $periodos): ?><?php $primeiraLinha = true; ?><?php foreach ($periodos as $dadosPeriodo): ?><tr class="<?php echo ($dadosPeriodo['receita_liquida'] == $campeoesPorMesClientes[$dadosPeriodo['periodo']]) ? 'table-primary' : ''; ?>"><?php if ($primeiraLinha): ?><td rowspan="<?php echo count($periodos); ?>" class="align-middle"><strong><?php echo htmlspecialchars($nomeCliente); ?></strong></td><?php $primeiraLinha = false; ?><?php endif; ?><td><?php echo date("M/Y", strtotime($dadosPeriodo['periodo'] . "-01")); ?></td>
                                <td class="<?php echo ($dadosPeriodo['receita_liquida'] == $campeoesPorMesClientes[$dadosPeriodo['periodo']]) ? 'campeao-mes' : ''; ?>">R$ <?php echo number_format($dadosPeriodo['receita_liquida'], 2, ',', '.'); ?></td>
                        </tr><?php endforeach; ?><?php endforeach; ?></tbody>
                </table><?php endif; ?>
        </div><?php endif; ?>
    <?php if (($acao === 'grafico' || $acao === 'pizza') && isset($dadosGraficoClientes)): ?> <div class="card p-4">
            <h2 class="mb-3"><?php echo $acao === 'pizza' ? 'Participação por Cliente (Pizza)' : 'Performance por Cliente'; ?></h2><?php if (empty($dadosGraficoClientes)): ?><div class="alert alert-info">Nenhum dado encontrado.</div><?php else: ?><div class="chart-wrapper <?php echo $acao === 'grafico' ? 'chart-wrapper-bar' : 'chart-wrapper-pizza'; ?>">
                    <div class="chart-container"><canvas id="graficoClientes"></canvas></div>
                </div><?php endif; ?>
        </div>
        <script>
            const ctx = document.getElementById('graficoClientes').getContext('2d');
            const dadosDoPHP = <?php echo json_encode($dadosGraficoClientes); ?>;
            const acao = '<?php echo $acao; ?>';
            let chartConfig;
            const labels = dadosDoPHP.map(item => item.no_fantasia);
            const data = dadosDoPHP.map(item => item.receita_liquida);
            if (acao === 'pizza') {
                chartConfig = {
                    type: 'pie',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Receita Líquida',
                            data: data,
                            backgroundColor: ['rgba(255, 99, 132, 0.7)', 'rgba(54, 162, 235, 0.7)', 'rgba(255, 206, 86, 0.7)', 'rgba(75, 192, 192, 0.7)', 'rgba(153, 102, 255, 0.7)', 'rgba(255, 159, 64, 0.7)']
                        }]
                    },
                    options: {
                        maintainAspectRatio: false,
                        plugins: {
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        const total = context.chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
                                        const value = context.raw;
                                        const percentage = ((value / total) * 100).toFixed(2) + '%';
                                        return `${context.label}: ${percentage}`;
                                    }
                                }
                            }
                        }
                    }
                };
            } else {
                chartConfig = {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Receita Líquida',
                            data: data,
                            backgroundColor: 'rgba(75, 192, 192, 0.7)',
                        }]
                    },
                    options: {
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                };
            }
            new Chart(ctx, chartConfig);
        </script><?php endif; ?>
</div>
<?php require_once 'footer.php'; ?>