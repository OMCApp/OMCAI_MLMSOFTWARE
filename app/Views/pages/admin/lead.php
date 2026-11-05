<?php
/**
 * รายละเอียด lead — ทุกช่องที่ลูกค้ากรอก + ที่มา (URL, UTM) · เปลี่ยนสถานะ/ผู้รับผิดชอบ · โน้ตภายใน (เพิ่มอย่างเดียว)
 *
 * ‼️ ทุกค่ามาจากคนนอก (ฟอร์มสาธารณะ) — ผ่าน e() ทุกตัว · ลิงก์ source_url แสดงเป็นข้อความ ไม่ใช่ลิงก์ที่กดได้
 *    (กันการหลอกให้แอดมินกดลิงก์ที่คนนอกใส่มา)
 */

declare(strict_types=1);

$leadFields = ['name', 'company', 'phone', 'email', 'line_id', 'business_type', 'has_system', 'need', 'member_estimate', 'message', 'locale', 'source_url', 'landing_page', 'referrer', 'ip'];
$leadLabelOf = [
    'business_type' => 'lead.business_types.', 'has_system' => 'lead.has_system_options.', 'need' => 'lead.needs.', 'member_estimate' => 'lead.member_ranges.',
];
?>
<div class="stack">
    <section class="card stack" aria-labelledby="lead-info-title">
        <div class="row">
            <h2 id="lead-info-title"><?= e((string) $lead['name']) ?></h2>
            <span class="badge badge-type"><?= e(__('cms.leads.form_' . $lead['form_key'])) ?></span>
            <span class="badge"><?= e(__('cms.leads.status_' . $lead['status'])) ?></span>
            <span class="muted small spacer"><?= e(format_date((string) $lead['created_at'], true)) ?></span>
        </div>
        <dl class="cms-dl">
            <?php foreach ($leadFields as $leadField): ?>
                <?php $leadValue = (string) ($lead[$leadField] ?? ''); ?>
                <?php if ($leadValue === '') { continue; } ?>
                <dt><?= e(__('cms.leads.' . $leadField)) ?></dt>
                <dd><?= e($leadField === 'locale' ? \App\Services\TranslationService::label($leadValue) : (isset($leadLabelOf[$leadField]) ? __($leadLabelOf[$leadField] . $leadValue) : $leadValue)) ?></dd>
            <?php endforeach; ?>
            <?php foreach ((array) $leadUtm as $leadUtmKey => $leadUtmValue): ?>
                <dt><?= e((string) $leadUtmKey) ?></dt>
                <dd><?= e(is_scalar($leadUtmValue) ? (string) $leadUtmValue : (string) json_encode($leadUtmValue, JSON_UNESCAPED_UNICODE)) ?></dd>
            <?php endforeach; ?>
            <?php $leadNotify = (string) ($lead['notify_status'] ?? ''); ?>
            <?php if ($leadNotify !== ''): ?>
                <dt><?= e(__('cms.leads.notify')) ?></dt>
                <dd>
                    <span class="badge"<?php if ($leadNotify === 'failed'): ?> data-type="warning"<?php endif; ?>><?= e(__('cms.leads.notify_' . $leadNotify)) ?></span>
                    <?php if ((string) ($lead['notified_at'] ?? '') !== ''): ?> <span class="muted small"><?= e(format_date((string) $lead['notified_at'], true)) ?></span><?php endif; ?>
                    <?php if ((string) ($lead['notify_error'] ?? '') !== ''): ?><br><span class="muted small"><?= e((string) $lead['notify_error']) ?></span><?php endif; ?>
                </dd>
            <?php endif; ?>
        </dl>
    </section>

    <form class="card stack" method="POST" action="<?= e(url('/admin/leads/' . (int) $lead['id'])) ?>">
        <?= csrf_field() ?>
        <?= method_field('PATCH') ?>
        <h2><?= e(__('cms.leads.manage')) ?></h2>
        <div class="field-grid">
            <?php
            $fieldName    = 'status';
            $fieldLabel   = __('cms.field.status');
            $fieldType    = 'select';
            $fieldValue   = (string) $lead['status'];
            $fieldOptions = (array) $leadStatuses;
            include __DIR__ . '/../../partials/field.php';

            $fieldName    = 'assigned_to';
            $fieldLabel   = __('cms.leads.assignee');
            $fieldType    = 'select';
            $fieldValue   = (string) ($lead['assigned_to'] ?? '');
            $fieldOptions = (array) $leadAssignees;
            include __DIR__ . '/../../partials/field.php';
            ?>
        </div>
        <div class="row">
            <button type="submit" class="btn btn-primary" data-submit-once><?= icon('check') ?><?= e(__('common.save')) ?></button>
        </div>
    </form>

    <section class="card stack" aria-labelledby="lead-notes-title">
        <h2 id="lead-notes-title"><?= e(__('cms.leads.notes')) ?></h2>
        <form class="stack" method="POST" action="<?= e(url('/admin/leads/' . (int) $lead['id'] . '/notes')) ?>">
            <?= csrf_field() ?>
            <?php
            $fieldName  = 'body';
            $fieldLabel = __('cms.leads.add_note');
            $fieldType  = 'textarea';
            $fieldValue = '';
            $fieldRows  = 3;
            $fieldHint  = __('cms.leads.notes_hint');
            include __DIR__ . '/../../partials/field.php';
            ?>
            <div class="row">
                <button type="submit" class="btn btn-secondary" data-submit-once><?= icon('plus') ?><?= e(__('cms.leads.add_note')) ?></button>
            </div>
        </form>

        <?php if ($leadNotes === []): ?>
            <p class="muted"><?= e(__('cms.leads.no_notes')) ?></p>
        <?php else: ?>
            <ul class="cms-list">
                <?php foreach ($leadNotes as $leadNote): ?>
                    <li class="stack">
                        <span class="muted small"><?= e(format_date((string) $leadNote['created_at'], true)) ?> · <?= e((string) ($leadNote['author'] ?? '—')) ?></span>
                        <p class="cms-note"><?= e((string) $leadNote['body']) ?></p>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</div>
