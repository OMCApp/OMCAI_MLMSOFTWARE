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

<h2>E-mail alerts to sales</h2>
<p>Every lead is saved first; then an e-mail goes to the <strong>lead alert address</strong> (Site settings). The <strong>E-mail alert</strong> column shows the result.</p>
<ul>
  <li><strong>Sent</strong> — sales has the e-mail</li>
  <li><strong>Failed</strong> — the lead is safe, but sales was not e-mailed · the Overview page warns until it is sent · press <strong>Resend pending e-mails</strong> (the system also retries when the next lead is sent successfully) · open the lead to see why</li>
  <li><strong>Not sent (e-mail off)</strong> — no mail server is set up yet; check this page regularly</li>
</ul>
<p>Admins can test it in <strong>Site settings → Lead e-mail alerts → Send test e-mail</strong> (do this after every mail change).</p>

<h2>Common mistakes</h2>
<ul>
  <li>Alerts land in spam — ask the domain owner to set SPF/DKIM for the sender address</li>
  <li>Notes cannot be edited or deleted — check before adding</li>
  <li>Only active administrators and sales users can be owners</li>
  <li>The CSV contains personal data — keep it safe</li>
</ul>
