<?php use oat\tao\helpers\Template;?>
<link rel="stylesheet" href="<?= Template::css('revision.css') ?>" />
<div class="revision-container flex-container-full">
    <h1><?= __('Revisions of %s',get_data('resourceLabel')) ?></h1>

    <form action="<?=_url('commitResource')?>" method="POST" class="grid-container">
        <input type="hidden" name="id" id="resource_id" value="<?= get_data('id')?>">
        <div class="grid-row commit">
            <div class="col-10">
                <?= __('Message')?> :
                <input type="text" name="message" id="message">
            </div>
            <div class="col-2  txt-rgt">
                <button type="submit" class="btn-info small"><?= __('Commit')?></button>
            </div>
        </div>
        <table class="revision" id="revisions-table">
            <tbody>
            <?php foreach (get_data('revisions') as $revision):?>
            <tr>
                <td>
                    <div>
                        <div class="col-2"><?= $revision['id']?></div>
                        <div class="col-8"><?= $revision['message']?></div>
                    </div>
                    <div class="secondary">
                        <div class="col-6"><?= $revision['modified']?></div>
                        <div class="col-6"><?=__('by')?> <?= $revision['author']?></div>
                    </div>
                </td>
                <td class="button">
                    <button type="button" class="small restore_revision tooltip btn-link" data-revision="<?=$revision['id']?>">
                        <span class="icon-restore"></span><?= __('Restore')?>
                    </button>
                </td>
            </tr>
            <?php endforeach;?>
            </tbody>
        </table>
    </form>
</div>
