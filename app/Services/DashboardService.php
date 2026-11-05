<?php
/**
 * ตัวเลขของหน้าแรกหลังบ้าน (/admin) — แยกตามบทบาท: sales เห็นแค่ lead
 */

declare(strict_types=1);

namespace App\Services;

use App\Models\NotFoundLog;
use Core\Auth;

final class DashboardService
{
    /** @return array<string, mixed> */
    public function summary(): array
    {
        $content = Auth::can('admin', 'editor');
        $leads   = Auth::can('admin', 'sales');
        $out     = ['content' => $content, 'leads' => $leads];

        if ($leads) {
            $out['new_leads']       = (new LeadService())->countNew();
            // ‼️ อีเมลแจ้งทีมขายที่ส่งไม่ถึง — lead ไม่หาย แต่ทีมขายอาจไม่รู้ว่ามี lead ใหม่ จึงต้องเตือนบนหน้าแรก
            $out['notify_backlog']  = (new LeadNotifier())->backlogCount();
            $out['mail_configured'] = MailService::configured();
        }

        if ($content) {
            $pages = (new PageService())->countByStatus();
            $posts = (new PostService())->countByStatus();

            $out['pages_draft']     = $pages['draft'] ?? 0;
            $out['pages_published'] = $pages['published'] ?? 0;
            $out['posts_draft']     = $posts['draft'] ?? 0;
            $out['posts_published'] = $posts['published'] ?? 0;
            $out['not_found_7d']    = (new NotFoundLog())->countSince(7);
            $out['redirect_hits']   = (new RedirectService())->totalHits();
            $out['media_no_alt']    = (new MediaService())->missingAltCount();
        }

        return $out;
    }
}
