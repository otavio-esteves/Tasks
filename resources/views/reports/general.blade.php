<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Relatório geral · {{ $team->name }}</title>
    <style>
        :root { color-scheme: light; font-family: Arial, sans-serif; color: #18181b; background: #f4f4f5; }
        * { box-sizing: border-box; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        body { margin: 0; padding: 28px; }
        .page { width: min(1120px, 100%); margin: 0 auto; padding: 28px; border: 1px solid #e4e4e7; border-radius: 8px; background: #fff; }
        .toolbar { display: flex; justify-content: flex-end; width: min(1120px, 100%); margin: 0 auto 12px; }
        .button { border: 0; border-radius: 6px; padding: 9px 14px; color: #fff; background: #18181b; font-size: 13px; font-weight: 600; cursor: pointer; }
        header { display: flex; align-items: flex-start; justify-content: space-between; gap: 24px; padding-bottom: 20px; border-bottom: 1px solid #e4e4e7; }
        h1 { margin: 0; font-size: 22px; } h2 { margin: 0; font-size: 14px; } p { margin: 5px 0 0; }
        .muted { color: #71717a; font-size: 12px; }
        .filters { margin-top: 16px; padding: 12px; border: 1px solid #e4e4e7; border-radius: 6px; background: #fafafa; font-size: 12px; }
        .summary { display: grid; grid-template-columns: repeat(6, 1fr); gap: 8px; margin-top: 16px; }
        .metric { padding: 12px; border: 1px solid #e4e4e7; border-radius: 6px; }
        .metric span { display: block; color: #71717a; font-size: 10px; text-transform: uppercase; }
        .metric strong { display: block; margin-top: 7px; font-size: 20px; }
        .chart { display: grid; gap: 8px; margin-top: 16px; padding: 16px; border: 1px solid #e4e4e7; border-radius: 6px; }
        .bar-row { display: grid; grid-template-columns: 90px 1fr 32px; align-items: center; gap: 10px; font-size: 11px; }
        .bar-track { height: 7px; overflow: hidden; border-radius: 2px; background: #e4e4e7; }
        .bar { height: 100%; border-radius: 2px; }
        .columns { display: grid; grid-template-columns: repeat(5, 1fr); align-items: end; gap: 14px; min-height: 150px; }
        .column { display: flex; min-width: 0; flex-direction: column; align-items: center; justify-content: flex-end; gap: 6px; height: 130px; font-size: 10px; text-align: center; }
        .column-bar { width: 34px; min-height: 2px; border-radius: 3px 3px 0 0; }
        .pie-layout { display: flex; align-items: center; justify-content: center; gap: 28px; min-height: 160px; }
        .pie { position: relative; width: 142px; height: 142px; flex: none; border-radius: 50%; }
        .pie::after { position: absolute; inset: 34px; content: ''; border-radius: 50%; background: #fff; }
        .legend { display: grid; gap: 8px; font-size: 11px; }
        .legend-row { display: flex; align-items: center; gap: 8px; }
        .legend-color { width: 10px; height: 10px; border-radius: 2px; }
        .table-wrap { margin-top: 18px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 11px; }
        th { padding: 9px 8px; border-bottom: 1px solid #d4d4d8; color: #52525b; background: #fafafa; text-align: left; }
        td { padding: 9px 8px; border-bottom: 1px solid #e4e4e7; vertical-align: top; }
        .status { white-space: nowrap; }
        footer { margin-top: 18px; color: #71717a; font-size: 10px; text-align: right; }
        @media screen and (max-width: 720px) {
            body { padding: 10px; }
            .page { padding: 16px; }
            .toolbar { justify-content: stretch; }
            .button { width: 100%; }
            header { flex-direction: column; gap: 8px; }
            .summary { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .chart { padding: 12px; }
            .bar-row { grid-template-columns: 72px minmax(0, 1fr) 26px; gap: 7px; }
            .columns { grid-template-columns: repeat(5, minmax(56px, 1fr)); overflow-x: auto; }
            .pie-layout { flex-direction: column; gap: 16px; }
            table { min-width: 720px; }
        }
        @page { size: A4 landscape; margin: 12mm; }
        @media print { body { padding: 0; background: #fff; } .toolbar { display: none; } .page { width: 100%; padding: 0; border: 0; } .table-wrap { overflow: visible; } tr { break-inside: avoid; } }
    </style>
</head>
<body>
    <div class="toolbar"><button class="button" type="button" onclick="window.print()">Imprimir ou salvar PDF</button></div>
    <main class="page">
        <header>
            <div><h1>Relatório geral de tarefas</h1><p class="muted">{{ $team->name }} · {{ config('app.name') }}</p></div>
            <div class="muted">Emitido em {{ now()->format('d/m/Y H:i') }}</div>
        </header>

        <div class="filters">
            <strong>Filtros:</strong>
            Responsável: {{ $assignee?->name ?? 'Todos' }} ·
            Período: {{ $dueFrom ? \Carbon\Carbon::parse($dueFrom)->format('d/m/Y') : 'início' }} até {{ $dueTo ? \Carbon\Carbon::parse($dueTo)->format('d/m/Y') : 'hoje e próximas' }}
        </div>

        @php
            $metrics = [
                ['Total', $report->summary['total'], '#52525b'],
                ['Pendentes', $report->summary['pending'], '#f59e0b'],
                ['Em andamento', $report->summary['in_progress'], '#3b82f6'],
                ['Concluídas', $report->summary['completed'], '#10b981'],
                ['Urgentes', $report->summary['urgent'], '#ef4444'],
                ['Vencidas', $report->summary['overdue'], '#d97706'],
            ];
            $maximum = max(1, ...array_column($metrics, 1));
            $pendingEnd = ($report->summary['pending'] / max(1, $report->summary['total'])) * 100;
            $progressEnd = $pendingEnd + (($report->summary['in_progress'] / max(1, $report->summary['total'])) * 100);
            $statusPie = $report->summary['total'] > 0
                ? "conic-gradient(#f59e0b 0 {$pendingEnd}%, #3b82f6 {$pendingEnd}% {$progressEnd}%, #10b981 {$progressEnd}% 100%)"
                : '#e4e4e7';
        @endphp

        <section class="summary" aria-label="Indicadores do relatório">
            @foreach ($metrics as [$label, $value, $color])
                <div class="metric" style="border-top: 3px solid {{ $color }}"><span>{{ $label }}</span><strong>{{ $value }}</strong></div>
            @endforeach
        </section>

        <section class="chart" aria-label="Comparação dos indicadores no estilo {{ $chartStyle }}">
            <h2>Distribuição geral</h2>
            @if ($chartStyle === 'columns')
                <div class="columns">
                    @foreach (array_slice($metrics, 1) as [$label, $value, $color])
                        <div class="column"><strong>{{ $value }}</strong><div class="column-bar" style="height: {{ ($value / $maximum) * 100 }}px; background-color: {{ $color }}"></div><span>{{ $label }}</span></div>
                    @endforeach
                </div>
            @elseif ($chartStyle === 'pie')
                <div class="pie-layout">
                    <div class="pie" style="background: {{ $statusPie }}"></div>
                    <div class="legend">
                        @foreach ([['Pendentes', $report->summary['pending'], '#f59e0b'], ['Em andamento', $report->summary['in_progress'], '#3b82f6'], ['Concluídas', $report->summary['completed'], '#10b981']] as [$label, $value, $color])
                            <div class="legend-row"><span class="legend-color" style="background-color: {{ $color }}"></span><span>{{ $label }}: <strong>{{ $value }}</strong></span></div>
                        @endforeach
                        <div class="legend-row"><span class="legend-color" style="background-color: #ef4444"></span><span>Urgentes: <strong>{{ $report->summary['urgent'] }}</strong></span></div>
                    </div>
                </div>
            @else
                @foreach (array_slice($metrics, 1) as [$label, $value, $color])
                    <div class="bar-row"><span>{{ $label }}</span><div class="bar-track"><div class="bar" style="width: {{ ($value / $maximum) * 100 }}%; background-color: {{ $color }}"></div></div><strong>{{ $value }}</strong></div>
                @endforeach
            @endif
        </section>

        <div class="table-wrap">
            <table>
                <thead><tr><th>Código</th><th>Tarefa</th><th>Categoria</th><th>Responsáveis</th><th>Status</th><th>Prioridade</th><th>Prazo</th></tr></thead>
                <tbody>
                    @forelse ($report->tasks as $task)
                        <tr><td>{{ $task->code }}</td><td><strong>{{ $task->title }}</strong><br><span class="muted">{{ $task->location ?: 'Sem localização' }}</span></td><td>{{ $task->category?->name ?? '—' }}</td><td>{{ $task->assignees->pluck('name')->join(', ') ?: 'Sem responsável' }}</td><td class="status">{{ $task->status->label() }}</td><td>{{ $task->is_urgent ? 'Urgente' : 'Normal' }}</td><td>{{ $task->due_date?->format('d/m/Y') ?? 'Sem prazo' }}</td></tr>
                    @empty
                        <tr><td colspan="7" style="padding: 28px; text-align: center; color: #71717a;">Nenhuma tarefa encontrada para os filtros informados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <footer>{{ $report->summary['total'] }} tarefa(s) neste relatório.</footer>
    </main>
    <script>
        window.addEventListener('load', () => window.setTimeout(() => window.print(), 250));
    </script>
</body>
</html>
