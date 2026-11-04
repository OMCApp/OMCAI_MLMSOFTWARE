<?php
declare(strict_types=1);

$manual_title   = 'Managing redirects and broken links';
$manual_summary = 'Send old URLs to new ones and fix broken links (404) from one screen';
?>
<h2>Who can use it</h2>
<p>Administrators and content editors</p>

<h2>Steps</h2>
<ol>
  <li>Open <strong>Broken links (404)</strong> to see URLs visitors could not find, most visited first</li>
  <li>Click the link icon on a row to create a redirect — the source is filled in for you</li>
  <li>Enter the destination, choose 301 (permanent) and save</li>
  <li>Hide URLs that do not matter</li>
  <li>All redirects are listed and editable under <strong>Redirects</strong></li>
</ol>

<figure>
  <img src="<?= e(url('/assets/manuals/cms/redirects.webp')) ?>" alt="Redirects screen" width="960" height="1300" loading="lazy">
  <figcaption>The table shows source, destination, type and how many times each redirect was used</figcaption>
</figure>

<h2>Common mistakes</h2>
<ul>
  <li>Redirect loops are refused</li>
  <li>If the destination redirects again, you are shown the final destination — tick "Point to the final destination instead" and save again</li>
  <li>Use 410 for pages removed for good with no replacement</li>
</ul>
