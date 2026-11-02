<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($judul) ?></title>
    <style>
        body { font-family: system-ui, sans-serif; margin: 0; background: #f5f5f5; color: #222; }
        main { max-width: 40rem; margin: 4rem auto; background: #fff; padding: 2rem; border-radius: .5rem; }
        h1 { margin-top: 0; }
    </style>
</head>
<body>
    <main>
        <h1><?= htmlspecialchars($judul) ?></h1>
        <p>Halo, <?= htmlspecialchars($nama) ?>!</p>
        <p>Halaman ini dirender oleh Selvi Framework.</p>
    </main>
</body>
</html>
