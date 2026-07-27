<section class="panel">
    <div class="panel-head">
        <div>
            <h2>Assign Module</h2>
            <p>Select a tutor, module, ESP32 IP, play limit, and optional expiry date.</p>
        </div>
    </div>
    <form class="grid-form" method="post" action="<?= e(path('/admin/assign-module')) ?>">
        <?= csrf_field() ?>
        <label>Select Tutor
            <select name="tutor_id" required>
                <option value="">Choose tutor</option>
                <?php foreach ($tutors as $tutor): ?><option value="<?= e($tutor['id']) ?>"><?= e($tutor['name'] . ' - ' . $tutor['email']) ?></option><?php endforeach; ?>
            </select>
        </label>
        <label>Select Module
            <select name="module_id" required>
                <option value="">Choose module</option>
                <?php foreach ($modules as $module): ?><option value="<?= e($module['id']) ?>"><?= e($module['module_name']) ?></option><?php endforeach; ?>
            </select>
        </label>
        <label>ESP32 IP Address
            <input name="esp32_ip" placeholder="192.168.1.25" required>
        </label>
        <label>Allowed Plays
            <input type="number" min="1" name="remaining_plays" value="1" required>
        </label>
        <label>Assignment Expiry Date
            <input type="date" name="expiry_date">
        </label>
        <div class="form-actions"><button class="button primary" type="submit">Assign</button></div>
    </form>
</section>

<section class="panel">
    <div class="panel-head"><h2>Current Assignments</h2></div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Tutor</th><th>Module</th><th>IP</th><th>Plays</th><th>Expiry</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($assignments as $assignment): ?>
                    <tr>
                        <td><?= e($assignment['tutor_name']) ?><span class="table-sub"><?= e($assignment['tutor_email']) ?></span></td>
                        <td><?= e($assignment['module_name']) ?></td>
                        <td><?= e($assignment['esp32_ip']) ?></td>
                        <td><?= e($assignment['remaining_plays']) ?></td>
                        <td><?= e($assignment['expiry_date'] ?: 'No expiry') ?></td>
                        <td><?= status_badge($assignment['status']) ?></td>
                        <td>
                            <form method="post" action="<?= e(path('/admin/assignments/delete')) ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= e($assignment['id']) ?>">
                                <button class="button small danger" type="submit">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
