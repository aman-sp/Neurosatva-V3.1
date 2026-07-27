<section class="panel runtime-shell" data-runtime
    data-mode="test"
    data-module='<?= e(json_encode($payload, JSON_UNESCAPED_SLASHES)) ?>'>
    <div class="panel-head">
        <div>
            <h2>Test <?= e($module['module_name']) ?></h2>
            <p>Testing does not affect tutor assignments or play counts.</p>
        </div>
    </div>
    <div class="runtime-connect">
        <label>ESP32 / WLED IP Address
            <input data-runtime-ip placeholder="192.168.1.25">
        </label>
        <button class="button primary" type="button" data-runtime-start>Start Testing</button>
        <button class="button danger" type="button" data-runtime-stop>Stop Testing</button>
    </div>
    <video class="runtime-video" data-runtime-video controls></video>
    <div class="runtime-status" data-runtime-status></div>
</section>
