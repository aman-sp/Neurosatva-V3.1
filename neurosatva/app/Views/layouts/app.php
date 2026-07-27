<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'Dashboard') ?> | <?= e(app_config('name')) ?></title>
    <link rel="stylesheet" href="<?= e(path('/assets/css/app.css')) ?>">
</head>
<body class="dashboard-body" data-base-path="<?= e(rtrim(parse_url(app_config('url'), PHP_URL_PATH) ?: '', '/')) ?>">
    <div class="app-shell">
        <?php require dirname(__DIR__) . '/partials/sidebar.php'; ?>
        <div class="main-panel">
            <header class="topbar">
                <button class="icon-button menu-toggle" data-menu-toggle aria-label="Toggle navigation">☰</button>
                <div>
                    <p class="eyebrow"><?= e(Auth::role() === 'admin' ? 'Admin Portal' : 'Tutor Portal') ?></p>
                    <h1><?= e($title ?? 'Dashboard') ?></h1>
                </div>
                <form method="post" action="<?= e(path('/logout')) ?>">
                    <?= csrf_field() ?>
                    <button class="button ghost" type="submit">Logout</button>
                </form>
            </header>
            <main class="content">
                <?php require dirname(__DIR__) . '/partials/flash.php'; ?>
                <?= $content ?>
            </main>
        </div>
    </div>
    <script src="<?= e(path('/assets/js/app.js')) ?>"></script>
</body>
</html>
