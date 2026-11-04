<?php
declare(strict_types=1);

$manual_title   = 'Site settings';
$manual_summary = 'Company and contact details (per language), verified figures, client logos, lead email and tracking codes';
?>
<h2>Who can use it</h2>
<p>Administrators</p>

<h2>Steps</h2>
<ol>
  <li>Open <strong>Site settings</strong></li>
  <li>Company details: one phone or email per line; address and introduction per language</li>
  <li>Company figures: enter the number and label, then tick <strong>Verified</strong> only for figures you can prove</li>
  <li>Client logos: pick the image from the media library and tick that permission was given</li>
  <li>Enter the lead alert email and the GA4/GTM IDs, then save</li>
</ol>

<figure>
  <img src="<?= e(url('/assets/manuals/cms/settings.webp')) ?>" alt="Site settings" width="960" height="1300" loading="lazy">
  <figcaption>① Company details ② Address/introduction per language ③ Company figures with the verified tick</figcaption>
</figure>

<h2>Common mistakes</h2>
<ul>
  <li>Unverified figures and logos never appear on the site</li>
  <li>Tracking codes only run on the live site, never on staging</li>
  <li>LINE/Facebook links must start with https://</li>
</ul>
