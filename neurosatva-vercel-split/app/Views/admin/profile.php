<section class="panel narrow">
    <div class="panel-head">
        <div>
            <h2>Settings/Profile</h2>
            <p>Update admin identity and credentials.</p>
        </div>
    </div>
    <form class="grid-form" method="post" action="<?= e(path('/admin/profile')) ?>">
        <?= csrf_field() ?>
        <label>Name<input name="name" required value="<?= e(Auth::user()['name']) ?>"></label>
        <label>Email<input type="email" name="email" required value="<?= e(Auth::user()['email']) ?>"></label>
        <label>New Password <span class="hint">optional</span><input type="password" name="password" minlength="8"></label>
        <button class="button primary" type="submit">Save Profile</button>
    </form>
</section>
