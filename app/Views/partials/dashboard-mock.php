<?php
/**
 * mockup หน้าจอระบบ MLM ที่สร้างด้วย HTML/CSS — ใช้ในบล็อก hero และ dashboard_showcase
 *
 * ‼️ ทำไมไม่ใช้ภาพหน้าจอ
 *    ยังไม่มีภาพหน้าจอระบบจริงของ OMC (QUESTIONS.md) และ brief ห้ามภาพสต็อกแอบอ้าง
 *    HTML/CSS คมทุกความละเอียด ไม่มีไฟล์ให้โหลด และขยับเบาๆ ได้ตามที่ brief ขอ
 *
 * ‼️ เป็นของตกแต่ง — aria-hidden ทั้งก้อน ความหมายอยู่ในข้อความรอบๆ และ caption ที่บอกว่าเป็นข้อมูลสมมติ
 *    ตัวเลขในนี้เป็นตัวอย่างหน้าจอ ไม่ใช่ผลงานของบริษัท (brief: ห้ามตัวเลขมั่ว — จึงต้องมี caption เสมอ)
 *
 * ตัวแปรที่รับ:
 *   $dashboardMockView  'overview' (ค่าเริ่มต้น) | 'team' | 'commission' — หน้าจอที่แสดง
 *   $dashboardMockFrame 'laptop' (แล็ปท็อป + มือถือ สำหรับ hero) | 'panel' (แผงเดี่ยว สำหรับแท็บ)
 */

declare(strict_types=1);

$dashboardMockView  = (string) ($dashboardMockView ?? 'overview');
$dashboardMockFrame = (string) ($dashboardMockFrame ?? 'panel');
$dashboardMockMark  = asset('/assets/images/brand/omc-mark.svg');
?>
<div class="mock-frame" data-frame="<?= e($dashboardMockFrame) ?>" aria-hidden="true">
    <div class="<?= e($dashboardMockFrame === 'laptop' ? 'device-laptop' : 'device-panel') ?>">
        <div class="mock">
            <div class="mock-side">
                <img class="mock-logo" src="<?= e($dashboardMockMark) ?>" alt="" width="300" height="100">
                <span class="mock-side-item is-on"><?= icon('dashboard', 'icon-sm') ?></span>
                <span class="mock-side-item"><?= icon('network', 'icon-sm') ?></span>
                <span class="mock-side-item"><?= icon('cart', 'icon-sm') ?></span>
                <span class="mock-side-item"><?= icon('wallet', 'icon-sm') ?></span>
                <span class="mock-side-item"><?= icon('chart', 'icon-sm') ?></span>
            </div>

            <div class="mock-main">
                <div class="mock-top">
                    <span class="mock-title"><?= e(__('showcase.app_title')) ?></span>
                    <span class="mock-avatar"></span>
                </div>

                <?php if ($dashboardMockView === 'team'): ?>
                    <div class="mock-card mock-tree-card">
                        <span class="mock-card-label"><?= e(__('showcase.team_tree')) ?></span>
                        <div class="mock-tree is-large">
                            <span class="mock-node is-root"></span>
                            <span class="mock-branch"></span>
                            <div class="mock-tree-row">
                                <span class="mock-node"></span><span class="mock-node"></span><span class="mock-node"></span>
                            </div>
                            <span class="mock-branch is-wide"></span>
                            <div class="mock-tree-row">
                                <span class="mock-node is-sm"></span><span class="mock-node is-sm"></span><span class="mock-node is-sm"></span>
                                <span class="mock-node is-sm"></span><span class="mock-node is-sm"></span><span class="mock-node is-sm"></span>
                            </div>
                        </div>
                    </div>
                <?php elseif ($dashboardMockView === 'commission'): ?>
                    <div class="mock-card">
                        <span class="mock-card-label"><?= e(__('showcase.commission_due')) ?></span>
                        <ul class="mock-rows">
                            <?php foreach ([['A-1024', '12,450', 'paid'], ['B-2048', '8,920', 'pending'], ['C-0512', '5,310', 'paid'], ['D-4096', '3,780', 'pending']] as [$dashboardMockMember, $dashboardMockAmount, $dashboardMockStatus]): ?>
                                <li class="mock-row">
                                    <span class="mock-avatar is-sm"></span>
                                    <span class="mock-row-id"><?= e($dashboardMockMember) ?></span>
                                    <span class="mock-row-amount">&#x0E3F;<?= e($dashboardMockAmount) ?></span>
                                    <span class="mock-chip" data-status="<?= e($dashboardMockStatus) ?>">
                                        <?= e(__('showcase.status_' . $dashboardMockStatus)) ?>
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php else: ?>
                    <div class="mock-kpis">
                        <div class="mock-kpi">
                            <span class="mock-kpi-label"><?= e(__('showcase.sales_today')) ?></span>
                            <span class="mock-kpi-value">&#x0E3F;<span data-count="3256000">3,256,000</span></span>
                            <span class="mock-kpi-delta">+18.2%</span>
                        </div>
                        <div class="mock-kpi">
                            <span class="mock-kpi-label"><?= e(__('showcase.members_total')) ?></span>
                            <span class="mock-kpi-value"><span data-count="128560">128,560</span></span>
                            <span class="mock-kpi-delta">+12.5%</span>
                        </div>
                        <div class="mock-kpi is-optional">
                            <span class="mock-kpi-label"><?= e(__('showcase.new_members')) ?></span>
                            <span class="mock-kpi-value"><span data-count="842">842</span></span>
                            <span class="mock-kpi-delta">+6.4%</span>
                        </div>
                    </div>

                    <div class="mock-panels">
                        <div class="mock-card mock-chart">
                            <span class="mock-card-label"><?= e(__('showcase.sales_trend')) ?></span>
                            <svg class="mock-chart-svg" viewBox="0 0 240 96" preserveAspectRatio="none">
                                <defs>
                                    <linearGradient id="mock-area-<?= e($dashboardMockFrame) ?>" x1="0" y1="0" x2="0" y2="1">
                                        <stop offset="0" class="mock-stop-top"/>
                                        <stop offset="1" class="mock-stop-bottom"/>
                                    </linearGradient>
                                </defs>
                                <path class="mock-chart-grid" d="M0 24h240M0 48h240M0 72h240"/>
                                <path class="mock-chart-area" fill="url(#mock-area-<?= e($dashboardMockFrame) ?>)"
                                      d="M0 80 L30 70 L60 74 L90 56 L120 60 L150 42 L180 46 L210 26 L240 14 V96 H0 Z"/>
                                <path class="mock-chart-line" pathLength="1"
                                      d="M0 80 L30 70 L60 74 L90 56 L120 60 L150 42 L180 46 L210 26 L240 14"/>
                                <circle class="mock-chart-dot" cx="240" cy="14" r="4"/>
                            </svg>
                        </div>
                        <div class="mock-card mock-tree-card is-optional">
                            <span class="mock-card-label"><?= e(__('showcase.team_tree')) ?></span>
                            <div class="mock-tree">
                                <span class="mock-node is-root"></span>
                                <span class="mock-branch"></span>
                                <div class="mock-tree-row">
                                    <span class="mock-node"></span><span class="mock-node"></span><span class="mock-node"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if ($dashboardMockFrame === 'laptop'): ?>
        <div class="device-phone">
            <div class="mock-phone">
                <div class="mock-phone-head">
                    <img class="mock-logo" src="<?= e($dashboardMockMark) ?>" alt="" width="300" height="100">
                    <span class="mock-avatar is-sm"></span>
                </div>
                <div class="mock-phone-card">
                    <span class="mock-kpi-label"><?= e(__('showcase.bonus')) ?></span>
                    <span class="mock-kpi-value">&#x0E3F;24,860</span>
                </div>
                <div class="mock-phone-grid">
                    <span class="mock-phone-tile"><?= icon('cart', 'icon-sm') ?><span><?= e(__('showcase.menu_orders')) ?></span></span>
                    <span class="mock-phone-tile"><?= icon('network', 'icon-sm') ?><span><?= e(__('showcase.menu_members')) ?></span></span>
                    <span class="mock-phone-tile"><?= icon('chart', 'icon-sm') ?><span><?= e(__('showcase.menu_reports')) ?></span></span>
                    <span class="mock-phone-tile"><?= icon('wallet', 'icon-sm') ?><span><?= e(__('showcase.menu_wallet')) ?></span></span>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
<?php
$dashboardMockView  = 'overview';
$dashboardMockFrame = 'panel';
