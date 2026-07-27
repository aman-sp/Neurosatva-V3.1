<section class="panel runtime-shell" data-runtime
    data-mode="tutor"
    data-assignment-id="<?= e($assignment['id']) ?>"
    data-device-ip="<?= e($assignment['esp32_ip']) ?>"
    data-module='<?= e(json_encode($payload, JSON_UNESCAPED_SLASHES)) ?>'>
    <div class="panel-head">
        <div>
            <h2><?= e($assignment['module_name']) ?></h2>
            <p>Remaining plays: <?= e($assignment['remaining_plays']) ?>. Assigned device: <?= e($assignment['esp32_ip']) ?></p>
        </div>
        <button class="button primary" type="button" data-runtime-start>Play Module</button>
    </div>
    <video class="runtime-video" data-runtime-video controls></video>
    <div class="runtime-status" data-runtime-status></div>
</section>
