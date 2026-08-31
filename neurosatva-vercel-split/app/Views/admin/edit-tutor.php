<section class="panel narrow">
    <div class="panel-head">
        <div>
            <h2>Edit Tutor</h2>
            <p>Update access status or reset credentials.</p>
        </div>
    </div>
    <form class="grid-form" method="post" action="<?= e(path('/admin/tutors/edit')) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= e($tutor['id']) ?>">
        <label>Tutor Name<input name="name" required value="<?= e($tutor['name']) ?>"></label>
        <label>Tutor Email<input type="email" name="email" required value="<?= e($tutor['email']) ?>"></label>
        <label>Phone Number<input type="tel" name="phone" maxlength="20" value="<?= e($tutor['phone'] ?? '') ?>"></label>
        <label>Status
            <select name="status">
                <option value="active" <?= $tutor['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                <option value="deactivated" <?= $tutor['status'] === 'deactivated' ? 'selected' : '' ?>>Deactivated</option>
            </select>
        </label>
        <label>New Password <span class="hint">optional</span><input type="password" name="password" minlength="8"></label>
        <div class="form-actions">
            <button class="button primary" type="submit">Save Changes</button>
            <a class="button ghost" href="<?= e(path('/admin/tutors')) ?>">Back</a>
        </div>
    </form>
</section>
