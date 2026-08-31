<p class="eyebrow">Tutor Registration</p>
<h2>Request tutor access</h2>
<p class="muted">Your account will be created only after Admin approval.</p>
<form class="stack" method="post" action="<?= e(path('/tutor/register')) ?>">
    <?= csrf_field() ?>
    <label>Full Name<input name="full_name" required maxlength="120" autocomplete="name"></label>
    <label>Personal Email Address<input type="email" name="email" required maxlength="190" autocomplete="email"></label>
    <label>Phone Number<input type="tel" name="phone" required maxlength="20" autocomplete="tel"></label>
    <label>School Name (Optional)<input name="school_name" maxlength="160"></label>
    <label>Gender (Optional)
        <select name="gender">
            <option value="">Select gender</option>
            <option>Female</option>
            <option>Male</option>
            <option>Non-binary</option>
            <option>Prefer not to say</option>
        </select>
    </label>
    <button class="button primary" type="submit">Register</button>
</form>
<p class="switch-link">Already approved? <a href="<?= e(path('/tutor/login')) ?>">Open tutor login</a></p>
