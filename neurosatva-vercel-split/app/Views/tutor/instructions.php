<section class="panel narrow">
    <div class="panel-head">
        <div>
            <h2>Submit Video Link</h2>
            <p>Send your class video folder link to the admin for verification.</p>
        </div>
    </div>
    <form class="grid-form one-col" method="post" action="<?= e(path('/tutor/instructions')) ?>">
        <?= csrf_field() ?>
        <label>Video Title
            <input name="title" required maxlength="180" placeholder="Enter video title">
        </label>
        <label>Description
            <textarea name="description" required rows="4" placeholder="Enter a short description"></textarea>
        </label>
        <label>Folder Link
            <input type="url" name="folder_link" required placeholder="https://drive.google.com/...">
        </label>
        <button class="button primary" type="submit">Send for Verification</button>
    </form>
</section>
