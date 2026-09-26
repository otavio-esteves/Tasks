<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Relatório grande · {{ $team->name }}</title>
    <style>
        body { margin: 0; padding: 24px; background: #f4f4f5; color: #18181b; font: 14px Arial, sans-serif; }
        main { max-width: 540px; margin: 8vh auto; padding: 24px; border: 1px solid #e4e4e7; border-radius: 6px; background: #fff; }
        h1 { margin: 0 0 12px; font-size: 20px; }
        p { line-height: 1.5; }
        a { display: inline-block; margin-top: 12px; padding: 9px 14px; border-radius: 6px; background: #18181b; color: #fff; text-decoration: none; }
        a:focus-visible { outline: 2px solid #2563eb; outline-offset: 2px; }
    </style>
</head>
<body>
    <main>
        <h1>Refine o relatório</h1>
        <p>{{ $message }}</p>
        <a href="{{ route('teams.tasks', $team) }}">Voltar às tarefas</a>
    </main>
</body>
</html>
