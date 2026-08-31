<section class="panel narrow">
    <div class="panel-head">
        <div>
            <h2>Complete Your Neurosatva Account Setup</h2>
            <p>Before accessing your Tutor Dashboard, you must create your official Neurosatva Gmail account.</p>
        </div>
        <?= status_badge($gmailStatus) ?>
    </div>

    <div class="callout">
        Create a Gmail account in the following format:<br>
        <strong>anyname_neuro@gmail.com</strong><br>
        Example: <strong>rahul_neuro@gmail.com</strong>
    </div>

    <p class="muted">
        This email will replace your registered email and will be used for login, official communication, and submitting recorded class videos to the Administrator.
    </p>

    <?php if (!empty($tutor['official_gmail']) && (int) ($tutor['gmail_verified'] ?? 0) !== 1): ?>
        <div class="alert success">We have sent an OTP to your official Gmail.</div>
        <p class="muted">Enter the 6-digit OTP below. The OTP expires in 10 minutes.</p>
    <?php endif; ?>

    <form class="grid-form one-col" method="post" action="<?= e(path('/tutor/official-gmail')) ?>">
        <?= csrf_field() ?>
        <label>Official Gmail Address
            <input type="email" name="official_gmail" required placeholder="john_neuro@gmail.com" value="<?= e($tutor['official_gmail'] ?? '') ?>">
        </label>
        <label>Confirm Official Gmail Address
            <input type="email" name="confirm_official_gmail" required placeholder="john_neuro@gmail.com" value="<?= e($tutor['official_gmail'] ?? '') ?>">
        </label>
        <label class="check-row">
            <input type="checkbox" name="gmail_confirmed" value="1" required>
            <span>I confirm that I have created and verified this Gmail account.</span>
        </label>
        <button class="button primary" type="submit"><?= !empty($tutor['official_gmail']) ? 'Resend OTP' : 'Send OTP' ?></button>
    </form>

    <?php if (!empty($tutor['official_gmail']) && (int) ($tutor['gmail_verified'] ?? 0) !== 1): ?>
        <form class="grid-form one-col top-space" method="post" action="<?= e(path('/tutor/official-gmail/verify-otp')) ?>">
            <?= csrf_field() ?>
            <label>Enter OTP
                <input name="otp" required pattern="\d{6}" maxlength="6" inputmode="numeric" placeholder="6-digit OTP">
            </label>
            <button class="button primary" type="submit">Verify OTP & Continue</button>
        </form>
    <?php endif; ?>
</section>
