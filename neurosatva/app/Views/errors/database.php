<h2>MySQL connection needs configuration</h2>
<p class="muted">
    Neurosatva could not connect to MySQL. Verify the database credentials in your environment variables or <strong>.env</strong> file.
</p>

<div class="callout">
    Database Host: <strong><?= e($dbHost ?? '127.0.0.1') ?></strong><br>
    Database Name: <strong><?= e($dbName ?? 'neurosatva') ?></strong><br>
    Database User: <strong><?= e($dbUser ?? 'root') ?></strong>
    <?php if (!empty($dbError)): ?>
        <br><br><span style="color: #ef4444;">Error details: <?= e($dbError) ?></span>
    <?php endif; ?>
</div>

<p class="muted">
    Ensure your MySQL database server is running and accessible from this host.
</p>
