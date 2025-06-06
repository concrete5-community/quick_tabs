<?php
/**
 * @var Concrete\Package\QuickTabs\Block\QuickTabs\Controller $controller
 * @var Concrete\Core\Form\Service\Form $form
 *
 * @var array $opencloseOptions
 * @var array $semanticOptions
 *
 * @var string $openclose
 * @var string $tabTitle
 * @var string $semantic
 * @var string $tabHandle
 */

defined('C5_EXECUTE') or die('Access Denied.');

?>
<div class="form-group">
    <?= $form->label('openclose', t('Is this the Opening or Closing Block?')) ?>
    <?= $form->select('openclose', $opencloseOptions, $openclose, ['required' => 'required']) ?>
</div>

<div class="form-group<?= $openclose === $controller::OPENCLOSE_OPEN ? '' : ' hide d-none' ?>">
    <?= $form->label('tabTitle', t('Tab Title')) ?>
    <?= $form->text('tabTitle', $tabTitle) ?>
</div>

<div class="form-group<?= $openclose === $controller::OPENCLOSE_OPEN ? '' : ' hide d-none' ?>">
    <?= $form->label('semantic', t('Semantic Tag for the Tab Title')) ?>
    <?= $form->select('semantic', $semanticOptions, $semantic) ?>
</div>

<div class="form-group<?= $openclose === $controller::OPENCLOSE_OPEN ? '' : ' hide d-none' ?>">
    <?= $form->label('tabHandle', t('Tab Handle')) ?>
    <?= $form->text('tabHandle', $tabHandle, ['maxlength' => 255]) ?>
</div>

<script>
$(document).ready(function() {
    $('#openclose')
        .on('change', function() {
            var $groups = $('#tabTitle,#semantic,#tabHandle').closest('.form-group');
            $groups.toggleClass('hide', this.value !== <?= json_encode($controller::OPENCLOSE_OPEN) ?>);
            $groups.toggleClass('d-none', this.value !== <?= json_encode($controller::OPENCLOSE_OPEN) ?>);
        })
        .trigger('change')
    ;
});
</script>
