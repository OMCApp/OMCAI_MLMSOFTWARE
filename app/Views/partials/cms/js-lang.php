<?php
/**
 * ข้อความของ cms.js — JS อ่านจาก data-* ของ element นี้ (ไม่มีข้อความตายตัวใน JS)
 */

declare(strict_types=1);
?>
<span id="cms-lang" hidden
      data-chars="<?= e(__('cms.seo.chars', ['n' => ':n', 'max' => ':max'])) ?>"
      data-move-up="<?= e(__('cms.blocks.move_up')) ?>"
      data-move-down="<?= e(__('cms.blocks.move_down')) ?>"
      data-remove-block="<?= e(__('cms.blocks.remove')) ?>"
      data-add-row="<?= e(__('cms.blocks.add_row')) ?>"
      data-remove-row="<?= e(__('cms.blocks.remove_row')) ?>"
      data-h2="<?= e(__('cms.js.h2')) ?>"
      data-h3="<?= e(__('cms.js.h3')) ?>"
      data-p="<?= e(__('cms.js.p')) ?>"
      data-ul="<?= e(__('cms.js.ul')) ?>"
      data-ol="<?= e(__('cms.js.ol')) ?>"
      data-strong="<?= e(__('cms.js.strong')) ?>"
      data-em="<?= e(__('cms.js.em')) ?>"
      data-link="<?= e(__('cms.js.link')) ?>"
      data-link-prompt="<?= e(__('cms.js.link_prompt')) ?>"
      data-table="<?= e(__('cms.js.table')) ?>"
      data-image="<?= e(__('cms.js.image')) ?>"
      data-image-none="<?= e(__('cms.js.image_none')) ?>"></span>
