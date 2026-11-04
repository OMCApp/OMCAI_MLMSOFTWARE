<?php
/**
 * บล็อก lead_form — ฟอร์มขอ Demo / ใบเสนอราคา / ปรึกษา / ติดต่อ / ย้ายระบบ
 *
 * $block = [
 *   'form_key' => 'demo' | 'quote' | 'consult' | 'contact' | 'migration'   ส่งไปกับฟอร์ม (leads.form_key)
 *   'eyebrow', 'title' (h2), 'lead'
 *   'submit'   => string   ข้อความปุ่ม — ไม่บังคับ (ค่าเริ่มต้น lead.submit)
 *   'options'  => array    ตัวเลือกของ select/radio — BlockService::prepare() ใส่ให้ ห้ามส่งเอง
 *   'privacy_url' => string ลิงก์นโยบายความเป็นส่วนตัว — BlockService::prepare() ใส่ให้จาก config/site.php
 * ]
 *
 * ‼️ ส่งไปที่ POST /lead/ — ตัวรับ (Phase 4) ต้อง: ตรวจ CSRF (hash_equals) · ช่อง website ต้องว่าง (honeypot)
 *    · rate limit · validate ค่าของ select/radio กับ BlockService::LEAD_OPTIONS · ต้องมีเบอร์หรืออีเมลอย่างน้อยหนึ่ง
 * ‼️ ฟอร์มเกิน 4 ช่องต้องแบ่งกลุ่ม (design.md 5.7) — ติดต่อ · ธุรกิจ
 * ‼️ ใช้ partials/field.php ทุกช่อง — label ผูก id · error ใต้ช่อง · คืนค่าที่กรอกไว้เมื่อ validate ไม่ผ่าน
 */

declare(strict_types=1);

$leadBlock   = (array) ($block ?? []);
$leadOptions = (array) ($leadBlock['options'] ?? []);
$leadUid     = (string) ($leadBlock['_uid'] ?? 'lead');
$leadKey     = in_array($leadBlock['form_key'] ?? '', ['demo', 'quote', 'consult', 'contact', 'migration'], true)
    ? (string) $leadBlock['form_key'] : 'contact';
$leadChoose  = ['' => __('lead.choose')];
?>
<?php /* id="lead-form" คงที่ — ปุ่ม "ขอใบเสนอราคา" ในหน้าเดียวกันลิงก์มาที่ #lead-form (หนึ่งฟอร์มต่อหน้า) */ ?>
<section class="section" data-tone="tint" aria-labelledby="<?= e($leadUid) ?>-title" id="lead-form">
    <div class="container lead-form-wrap">
        <?php
        $sectionHeadEyebrow = (string) ($leadBlock['eyebrow'] ?? '');
        $sectionHeadTitle   = (string) ($leadBlock['title'] ?? '');
        $sectionHeadLead    = (string) ($leadBlock['lead'] ?? '');
        $sectionHeadId      = $leadUid . '-title';
        include __DIR__ . '/../section-head.php';
        ?>

        <form class="lead-form reveal" method="POST" action="<?= e(url('/lead/')) ?>" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="form_key" value="<?= e($leadKey) ?>">
            <input type="hidden" name="source_path" value="<?= e(current_path()) ?>">
            <?php /* แหล่งที่มาของการเข้าชมหน้านี้ (UTM ใน URL · referrer ภายนอก) — BlockService::prepare() ใส่ให้ */ ?>
            <?php foreach ((array) ($leadBlock['tracking'] ?? []) as $leadTrackName => $leadTrackValue): ?>
                <input type="hidden" name="<?= e((string) $leadTrackName) ?>" value="<?= e((string) $leadTrackValue) ?>">
            <?php endforeach; ?>

            <?php /* ‼️ honeypot — คนไม่เห็นและ Tab ไม่ผ่าน · bot ที่กรอกทุกช่องจะกรอกช่องนี้ด้วย */ ?>
            <div class="hp-field" aria-hidden="true">
                <label for="<?= e($leadUid) ?>-website"><?= e(__('lead.honeypot')) ?></label>
                <input id="<?= e($leadUid) ?>-website" name="website" type="text" tabindex="-1" autocomplete="off" value="">
            </div>

            <fieldset class="group">
                <legend><?= e(__('lead.group_contact')) ?></legend>
                <div class="field-grid">
                    <?php
                    $fieldType = 'text'; $fieldRequired = true; $fieldWidth = 'full';
                    $fieldName = 'name'; $fieldLabel = __('lead.name');
                    include __DIR__ . '/../field.php';

                    $fieldRequired = false;
                    $fieldName = 'company'; $fieldLabel = __('lead.company');
                    include __DIR__ . '/../field.php';

                    $fieldType = 'tel';
                    $fieldName = 'phone'; $fieldLabel = __('lead.phone'); $fieldHint = __('lead.contact_hint');
                    include __DIR__ . '/../field.php';

                    $fieldType = 'email';
                    $fieldName = 'email'; $fieldLabel = __('lead.email');
                    include __DIR__ . '/../field.php';

                    $fieldType = 'text';
                    $fieldName = 'line_id'; $fieldLabel = __('lead.line_id');
                    include __DIR__ . '/../field.php';
                    ?>
                </div>
            </fieldset>

            <fieldset class="group">
                <legend><?= e(__('lead.group_business')) ?></legend>
                <div class="field-grid">
                    <?php
                    $fieldType = 'select'; $fieldRequired = false;
                    $fieldName = 'business_type'; $fieldLabel = __('lead.business_type');
                    $fieldOptions = $leadChoose + (array) ($leadOptions['business_type'] ?? []);
                    include __DIR__ . '/../field.php';

                    $fieldType = 'select';
                    $fieldName = 'need'; $fieldLabel = __('lead.need');
                    $fieldOptions = $leadChoose + (array) ($leadOptions['need'] ?? []);
                    include __DIR__ . '/../field.php';

                    $fieldType = 'select';
                    $fieldName = 'member_estimate'; $fieldLabel = __('lead.member_estimate');
                    $fieldOptions = $leadChoose + (array) ($leadOptions['member_estimate'] ?? []);
                    include __DIR__ . '/../field.php';
                    ?>
                </div>
                <?php
                $fieldType = 'radio';
                $fieldName = 'has_system'; $fieldLabel = __('lead.has_system');
                $fieldOptions = (array) ($leadOptions['has_system'] ?? []);
                include __DIR__ . '/../field.php';

                $fieldType = 'textarea'; $fieldRows = 4;
                $fieldName = 'message'; $fieldLabel = __('lead.message'); $fieldHint = __('lead.message_hint');
                include __DIR__ . '/../field.php';
                ?>
            </fieldset>

            <div class="lead-form-foot">
                <p class="lead-form-note">
                    <?= e(__('lead.privacy_note')) ?>
                    <?php if ((string) ($leadBlock['privacy_url'] ?? '') !== ''): ?>
                        <a href="<?= e(url((string) $leadBlock['privacy_url'])) ?>"><?= e(__('lead.privacy_link')) ?></a>
                    <?php endif; ?>
                </p>
                <button type="submit" class="btn btn-pill btn-primary btn-lg">
                    <span><?= e((string) ($leadBlock['submit'] ?? __('lead.submit'))) ?></span>
                    <?= icon('arrow-right', 'btn-arrow') ?>
                </button>
            </div>
        </form>
    </div>
</section>
