<div class="main-container tao-scope">
    <h1><?= __('Revisions of %s',get_data('resourceLabel')) ?></h1>
    <?php foreach (get_data('revisions') as $revision) : ?>
        <?= $revision->getMessage() ?><br />
    <?php endforeach;?>
</div>