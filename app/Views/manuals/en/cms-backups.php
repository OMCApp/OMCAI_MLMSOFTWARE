<?php
declare(strict_types=1);

$manual_title   = 'Backup and restore';
$manual_summary = 'Create database backups, download them, and how to restore';
?>
<h2>Who can use it</h2>
<p>Administrators</p>

<h2>Steps</h2>
<ol>
  <li>Open <strong>Backups</strong> and click <strong>Back up now</strong></li>
  <li>Click the download icon to keep a copy off the server (at least weekly)</li>
  <li>Restoring is done by the server administrator only: maintenance on → unzip the .gz → import with mysql --default-character-set=utf8mb4 → check the site → maintenance off (full steps in project/DEPLOY.md)</li>
</ol>

<figure>
  <img src="<?= e(url('/assets/manuals/cms/settings.webp')) ?>" alt="Form" width="960" height="1300" loading="lazy">
  <figcaption>The backups screen lists the files with date and size</figcaption>
</figure>

<h2>Common mistakes</h2>
<ul>
  <li>There is deliberately no restore button — a restore overwrites everything, users included</li>
  <li>Backup files contain all data including client details — keep them safe</li>
</ul>
