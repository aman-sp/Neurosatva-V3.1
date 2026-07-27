<?php if ($message = Session::flash('success')): ?>
    <div class="alert success"><?= e($message) ?></div>
<?php endif; ?>
<?php if ($message = Session::flash('error')): ?>
    <div class="alert danger"><?= e($message) ?></div>
<?php endif; ?>
