<?php
/**
 * ค่าตั้งของเว็บ — ข้อมูลบริษัท/ติดต่อ (หลายภาษา) · ตัวเลขที่ยืนยันแล้ว · โลโก้ลูกค้าที่ได้รับอนุญาต
 * · อีเมลรับแจ้ง lead · รหัสติดตาม (GA4/GTM — ส่งออกเฉพาะ production) · รูป OG เริ่มต้น
 *
 * สิทธิ์: admin เท่านั้น (RoleMiddleware ที่กลุ่ม route)
 */

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Services\MediaService;
use App\Services\SettingService;
use App\Services\SiteService;
use App\Services\TranslationService;

final class SettingController extends AdminController
{
    public function edit(): void
    {
        $company = SettingService::group('company');
        $site    = SiteService::configDefaults();

        // ค่าตั้งต้นจาก config/site.php ช่วยเติมช่องที่ยังว่าง (บันทึกแล้วจึงเป็นค่าของ CMS)
        $address = (array) ($company['address'] ?? []);
        $about   = (array) ($company['description'] ?? []);
        $main = TranslationService::default();
        $address[$main] = ($address[$main] ?? '') !== '' ? $address[$main] : (string) ($site['contact']['address'] ?? '');
        $about[$main]   = ($about[$main] ?? '') !== '' ? $about[$main] : (string) ($site['brand']['about'] ?? '');

        $companyFields = [
            ['name' => 'company[name]', 'label' => __('cms.settings.company_name'), 'value' => $company['name'] ?? '', 'width' => 'medium'],
            ['name' => 'company[legal_name]', 'label' => __('cms.settings.legal_name'),
             'value' => ($company['legal_name'] ?? '') !== '' ? $company['legal_name'] : (string) ($site['brand']['legal_name'] ?? '')],
            ['name' => 'company[founded]', 'label' => __('cms.settings.founded'), 'type' => 'date', 'value' => $company['founded'] ?? '', 'width' => 'medium',
             'hint' => __('cms.settings.founded_hint')],
            ['name' => 'company[phones]', 'label' => __('cms.settings.phones'), 'type' => 'textarea', 'rows' => 2,
             'value' => implode("\n", (array) ($company['phones'] ?? [])), 'hint' => __('cms.settings.lines_hint')],
            ['name' => 'company[emails]', 'label' => __('cms.settings.emails'), 'type' => 'textarea', 'rows' => 3,
             'value' => implode("\n", (array) ($company['emails'] ?? [])), 'hint' => __('cms.settings.emails_hint')],
            ['name' => 'company[line_id]', 'label' => __('cms.settings.line_id'), 'value' => $company['line_id'] ?? '', 'width' => 'medium'],
            ['name' => 'company[line_url]', 'label' => __('cms.settings.line_url'), 'type' => 'url', 'value' => $company['line_url'] ?? ''],
            ['name' => 'company[facebook]', 'label' => __('cms.settings.facebook'), 'type' => 'url', 'value' => $company['facebook'] ?? ''],
        ];

        $localized = [];

        foreach (TranslationService::locales() as $locale) {
            $localized[] = ['name' => 'company[address][' . $locale . ']', 'label' => __('cms.settings.address') . ' · ' . TranslationService::label($locale),
                            'type' => 'textarea', 'rows' => 2, 'value' => $address[$locale] ?? ''];
            $localized[] = ['name' => 'company[description][' . $locale . ']', 'label' => __('cms.settings.description') . ' · ' . TranslationService::label($locale),
                            'type' => 'textarea', 'rows' => 3, 'value' => $about[$locale] ?? ''];
        }

        $tracking      = SettingService::group('tracking');
        $notifications = SettingService::group('notifications');
        $siteSettings  = SettingService::group('site');

        $this->view('admin/settings', [
            'title'            => __('cms.settings.title'),
            'pageLead'         => __('cms.settings.lead'),
            'pageWidth'        => 'form',
            'settingsCompany'  => $companyFields,
            'settingsLocalized' => $localized,
            'settingsStats'    => (array) (SettingService::group('stats')['items'] ?? []),
            'settingsLogos'    => (array) (SettingService::group('trust_logos')['items'] ?? []),
            'settingsOther'    => [
                ['name' => 'notifications[lead_email]', 'label' => __('cms.settings.lead_email'), 'type' => 'email',
                 'value' => $notifications['lead_email'] ?? '', 'hint' => __('cms.settings.lead_email_hint')],
                ['name' => 'tracking[ga4_id]', 'label' => __('cms.settings.ga4'), 'value' => $tracking['ga4_id'] ?? '', 'width' => 'medium',
                 'hint' => __('cms.settings.tracking_hint')],
                ['name' => 'tracking[gtm_id]', 'label' => __('cms.settings.gtm'), 'value' => $tracking['gtm_id'] ?? '', 'width' => 'medium'],
                ['name' => 'site[default_og_image_media_id]', 'label' => __('cms.settings.default_og'), 'type' => 'media',
                 'value' => (string) ($siteSettings['default_og_image_media_id'] ?? '')],
            ],
            'cmsMediaOptions'  => (new MediaService())->pickerOptions(),
            'settingsLocales'  => TranslationService::options(),
        ]);
    }

    public function update(): void
    {
        $service = new SettingService();
        $result  = $service->fromInput($this->request->all());

        if ($result['errors'] !== []) {
            $this->fail($result['errors']);
        }

        $service->saveAll($result['values']);
        $this->flash('success', __('cms.common.saved'));
        $this->redirect('/admin/settings');
    }
}
