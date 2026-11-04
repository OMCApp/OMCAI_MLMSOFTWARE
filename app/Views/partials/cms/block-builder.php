<?php
/**
 * ตัวสร้างหน้าแบบบล็อก (block builder) ของ CMS
 *
 * ‼️ ช่องของแต่ละชนิดมาจาก App\Services\BlockFormService::schema() ที่เดียว — คีย์ตรงกับ partial ของบล็อก 1:1
 * ‼️ ใช้ได้โดยไม่มี JS: เพิ่มบล็อก = เลือกชนิดแล้วกด "บันทึกและเพิ่มบล็อก" · เรียง = ช่องลำดับ · ลบ = ติ๊ก "ลบบล็อกนี้"
 *    รายการซ้ำได้ (ปุ่ม/การ์ด/ขั้นตอน) มีแถวว่างเผื่อไว้หนึ่งแถวทุกครั้ง — แถวว่างถูกตัดตอนบันทึก
 *    cms.js เพิ่ม: ปุ่มขึ้น/ลง · ลบทันที · เพิ่มบล็อก/แถวโดยไม่ต้องบันทึก (จาก <template>)
 *
 * ตัวแปรที่รับ:
 *   $cmsBlocks        list ของ ['type','data'] (จากฐานข้อมูล หรือจากค่าที่กรอกไว้เมื่อ validate ไม่ผ่าน)
 *   $cmsBlockSchema   BlockFormService::schema()
 *   $cmsMediaOptions  id => ป้าย ของคลังสื่อ
 *   $cmsMediaIdOf     callable(array|null $image): int — หา media id ของรูปที่เลือกไว้
 */

declare(strict_types=1);

$cmsBlocks      = array_values((array) ($cmsBlocks ?? []));
$cmsBlockSchema = (array) ($cmsBlockSchema ?? []);
$cmsMediaPick   = ['' => __('cms.media.none')] + (array) ($cmsMediaOptions ?? []);

$cmsOptionLabel = static fn (string $value): string => is_numeric($value) ? $value : __('cms.block_option.' . str_replace('-', '_', $value));

/**
 * วาดช่องหนึ่งช่อง — เรียกซ้ำได้สำหรับรายการซ้ำ (list)
 * ‼️ ใช้ partials/field.php ของกลางทุกช่อง · ชื่อช่องประกอบจาก $prefix (blocks[i][data])
 */
$cmsRenderField = static function (array $spec, string $prefix, mixed $value) use (&$cmsRenderField, $cmsMediaPick, $cmsOptionLabel, $cmsMediaIdOf): void {
    $key   = (string) $spec['key'];
    $name  = $prefix . '[' . $key . ']';
    $label = __('cms.block_field.' . $key);

    $fieldName     = $name;
    $fieldLabel    = $label;
    $fieldRequired = false;
    $fieldHint     = '';
    $fieldWidth    = 'full';
    $fieldOptions  = [];
    $fieldRows     = 3;

    switch ($spec['type']) {
        case 'from_settings':
            echo '<p class="hint">' . e(__('cms.block_note.' . $key)) . '</p>';

            return;

        case 'list':
            $rows = array_values(array_filter(is_array($value) ? $value : [], 'is_array'));
            $rows[] = []; // แถวว่างเผื่อเพิ่ม (ไม่มี JS ก็เพิ่มได้ทีละแถว)
            echo '<fieldset class="group cms-rows" data-rows><legend>' . e($label) . '</legend>';

            foreach ($rows as $index => $row) {
                echo '<div class="cms-row" data-row>';
                foreach ($spec['fields'] as $sub) {
                    $cmsRenderField($sub, $name . '[' . $index . ']', $row[$sub['key']] ?? null);
                }
                echo '</div>';
            }

            // แม่แบบแถวใหม่ของ cms.js — __ROW__ ถูกแทนด้วยเลขไม่ซ้ำ
            echo '<template data-row-template><div class="cms-row" data-row>';
            foreach ($spec['fields'] as $sub) {
                $cmsRenderField($sub, $name . '[__ROW__]', null);
            }
            echo '</div></template></fieldset>';

            return;

        case 'link':
            echo '<fieldset class="group"><legend>' . e($label) . '</legend><div class="field-grid">';

            $fieldName = $name . '[label]'; $fieldLabel = __('cms.block_field.label'); $fieldType = 'text'; $fieldValue = (string) (((array) $value)['label'] ?? '');
            include __DIR__ . '/../field.php';
            $fieldName = $name . '[url]'; $fieldLabel = __('cms.block_field.url'); $fieldType = 'text'; $fieldValue = (string) (((array) $value)['url'] ?? '');
            include __DIR__ . '/../field.php';

            if (isset($spec['styles'])) {
                $fieldName = $name . '[style]'; $fieldLabel = __('cms.block_field.style'); $fieldType = 'select';
                $fieldValue = (string) (((array) $value)['style'] ?? '');
                $fieldOptions = ['' => '—'];
                foreach ($spec['styles'] as $style) {
                    $fieldOptions[$style] = $cmsOptionLabel($style);
                }
                include __DIR__ . '/../field.php';
            }

            echo '</div></fieldset>';

            return;

        case 'image':
            $fieldName    = $name . '[media_id]';
            $fieldType    = 'select';
            $fieldOptions = $cmsMediaPick;
            $fieldValue   = (string) ($cmsMediaIdOf(is_array($value) ? $value : null) ?: '');
            $fieldHint    = __('cms.block_hint.image');
            include __DIR__ . '/../field.php';

            return;

        case 'select':
            $fieldType = 'select';
            foreach ($spec['options'] as $option) {
                $fieldOptions[(string) $option] = $cmsOptionLabel((string) $option);
            }
            $fieldValue = (string) ($value ?? '');
            include __DIR__ . '/../field.php';

            return;

        case 'checkbox':
            $fieldType  = 'checkbox';
            $fieldValue = !empty($value);
            include __DIR__ . '/../field.php';

            return;

        case 'number':
            $fieldType  = 'number';
            $fieldWidth = 'short';
            $fieldValue = $value === null ? '' : (string) $value;
            include __DIR__ . '/../field.php';

            return;

        case 'lines':
        case 'paragraphs':
            $fieldType  = 'textarea';
            $fieldRows  = 4;
            $fieldHint  = __('cms.block_hint.' . $spec['type']);
            $fieldValue = implode($spec['type'] === 'lines' ? "\n" : "\n\n", array_map('strval', is_array($value) ? $value : []));
            include __DIR__ . '/../field.php';

            return;

        case 'cells':
            $fieldType  = 'text';
            $fieldHint  = __('cms.block_hint.cells');
            $fieldValue = implode(' | ', array_map(
                static fn ($cell): string => $cell === true ? '+' : ($cell === false ? '-' : (string) $cell),
                is_array($value) ? $value : []
            ));
            include __DIR__ . '/../field.php';

            return;

        case 'html':
            $fieldType  = 'textarea';
            $fieldRows  = 8;
            $fieldHint  = __('cms.block_hint.html');
            $fieldValue = (string) ($value ?? '');
            include __DIR__ . '/../field.php';

            return;

        default:
            $fieldType     = $spec['type'] === 'textarea' ? 'textarea' : 'text';
            $fieldRequired = (bool) ($spec['required'] ?? false);
            $fieldValue    = (string) ($value ?? '');
            include __DIR__ . '/../field.php';
            $fieldRequired = false;
    }
};

/** วาดบล็อกหนึ่งบล็อก — $index เป็นตัวเลขหรือ __BLOCK__ (แม่แบบของ cms.js) */
$cmsRenderBlock = static function (string $index, string $type, array $data, int $position) use ($cmsRenderField, $cmsBlockSchema): void {
    $prefix = 'blocks[' . $index . ']';
    ?>
    <fieldset class="card cms-block stack" data-block>
        <legend class="cms-block-head">
            <span class="badge badge-type"><?= e(__('cms.block_type.' . $type)) ?></span>
        </legend>
        <input type="hidden" name="<?= e($prefix) ?>[type]" value="<?= e($type) ?>">
        <div class="row cms-block-tools" data-block-tools>
            <?php
            $fieldName  = $prefix . '[sort]';
            $fieldLabel = __('cms.blocks.order');
            $fieldType  = 'number';
            $fieldWidth = 'short';
            $fieldValue = (string) (($position + 1) * 10);
            include __DIR__ . '/../field.php';

            $fieldName    = $prefix . '[remove]';
            $fieldLabel   = __('cms.blocks.remove');
            $fieldType    = 'checkbox';
            $fieldValue   = false;
            include __DIR__ . '/../field.php';
            ?>
        </div>
        <?php foreach ($cmsBlockSchema[$type] ?? [] as $spec): ?>
            <?php $cmsRenderField($spec, $prefix . '[data]', $data[$spec['key']] ?? null); ?>
        <?php endforeach; ?>
    </fieldset>
    <?php
};
?>
<section class="stack" aria-labelledby="cms-blocks-title" data-block-builder>
    <h2 id="cms-blocks-title"><?= e(__('cms.blocks.title')) ?></h2>
    <p class="hint"><?= e(__('cms.blocks.hint')) ?></p>

    <div class="stack" data-block-list>
        <?php foreach ($cmsBlocks as $cmsBlockIndex => $cmsBlock): ?>
            <?php
            $cmsBlockType = (string) ($cmsBlock['type'] ?? '');
            if (!isset($cmsBlockSchema[$cmsBlockType])) {
                continue;
            }
            $cmsRenderBlock((string) $cmsBlockIndex, $cmsBlockType, (array) ($cmsBlock['data'] ?? []), $cmsBlockIndex);
            ?>
        <?php endforeach; ?>
    </div>

    <?php if ($cmsBlocks === []): ?>
        <p class="muted" data-block-empty><?= e(__('cms.blocks.empty')) ?></p>
    <?php endif; ?>

    <div class="card row cms-block-add">
        <?php
        $fieldName    = 'add_block';
        $fieldLabel   = __('cms.blocks.add_type');
        $fieldType    = 'select';
        $fieldValue   = '';
        $fieldWidth   = 'medium';
        $fieldOptions = ['' => '—'];
        foreach (array_keys($cmsBlockSchema) as $cmsSchemaType) {
            $fieldOptions[$cmsSchemaType] = __('cms.block_type.' . $cmsSchemaType);
        }
        include __DIR__ . '/../field.php';
        ?>
        <button type="submit" class="btn btn-secondary" name="_add" value="1" data-block-add>
            <?= icon('plus') ?><?= e(__('cms.blocks.add')) ?>
        </button>
    </div>

    <?php /* แม่แบบของ cms.js — เพิ่มบล็อกโดยไม่ต้องบันทึก · ไม่มี JS ไม่ถูกใช้ */ ?>
    <?php foreach (array_keys($cmsBlockSchema) as $cmsSchemaType): ?>
        <template data-block-template="<?= e($cmsSchemaType) ?>">
            <?php $cmsRenderBlock('__BLOCK__', $cmsSchemaType, [], 999); ?>
        </template>
    <?php endforeach; ?>
</section>
