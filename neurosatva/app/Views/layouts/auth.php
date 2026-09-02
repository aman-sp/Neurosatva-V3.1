<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? app_config('name')) ?> | <?= e(app_config('name')) ?></title>
    <link rel="stylesheet" href="<?= e(path('/assets/css/app.css?v=' . asset_version('assets/css/app.css'))) ?>">
</head>
<body class="auth-body glass-auth-body">
    <div class="gradient-mesh" aria-hidden="true"></div>
    <div class="auth-blobs" aria-hidden="true">
        <span></span><span></span><span></span>
    </div>
    <div class="aurora-layer"></div>
    <div class="light-wash wash-one"></div>
    <div class="light-wash wash-two"></div>
    <div class="light-wash wash-three"></div>
    <div class="neuro-scene" aria-hidden="true">
        <div class="brain-wireframe">
            <span></span><span></span><span></span><span></span><span></span><span></span>
        </div>
        <div class="neural-network">
            <span class="node n1"></span><span class="node n2"></span><span class="node n3"></span><span class="node n4"></span>
            <span class="node n5"></span><span class="node n6"></span>
            <span class="signal s1"></span><span class="signal s2"></span><span class="signal s3"></span>
        </div>
        <div class="dna-helix">
            <span></span><span></span><span></span><span></span><span></span><span></span>
        </div>
        <div class="ecg-line"></div>
        <div class="energy-wave wave-a"></div>
        <div class="energy-wave wave-b"></div>
        <div class="medical-glyph glyph-a">+</div>
        <div class="medical-glyph glyph-b">x</div>
        <div class="medical-glyph glyph-c">~</div>
    </div>
    <div class="particle-field" aria-hidden="true">
        <span></span><span></span><span></span><span></span><span></span><span></span>
    </div>
    <main class="glass-auth-stage">
        <?php require dirname(__DIR__) . '/partials/flash.php'; ?>
        <?= $content ?>
    </main>
    <script src="<?= e(path('/assets/js/app.js')) ?>"></script>
</body>
</html>
