<?php
declare(strict_types=1);

$manual_title   = 'Managing leads';
$manual_summary = 'See what visitors sent through the website forms, track status, assign, keep notes and export CSV';
?>
<h2>Who can use it</h2>
<p>Administrators and sales</p>

<h2>Steps</h2>
<ol>
  <li>Open <strong>Leads</strong> and filter by status, form, dates, language or source (UTM, landing page, referrer)</li>
  <li>Click the view icon to open every field the visitor filled in</li>
  <li>Change the status (New → Contacted → Qualified → Won/Lost), choose the owner and save</li>
  <li>Add a note every time you contact the client</li>
  <li>Click <strong>Export</strong> for a CSV of the current filter — Excel shows Thai correctly</li>
</ol>

<figure>
  <img src="<?= e(url('/assets/manuals/cms/leads.webp')) ?>" alt="Lead details on a phone" width="500" height="1300" loading="lazy">
  <figcaption>① What the visitor sent ② Status and owner ③ Internal notes</figcaption>
</figure>

<h2>Common mistakes</h2>
<ul>
  <li>Notes cannot be edited or deleted — check before adding</li>
  <li>Only active administrators and sales users can be owners</li>
  <li>The CSV contains personal data — keep it safe</li>
</ul>
