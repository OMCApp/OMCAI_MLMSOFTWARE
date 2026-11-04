<?php
declare(strict_types=1);

$manual_title   = 'Editing website pages';
$manual_summary = 'Build pages from blocks, set SEO, preview, publish and restore earlier versions';
?>
<h2>Who can use it</h2>
<p>Administrators and content editors</p>

<h2>Steps</h2>
<ol>
  <li>Open <strong>Pages</strong> and click a page, or click <strong>Add page</strong></li>
  <li>Enter the title and URL (starting and ending with /)</li>
  <li>Under <strong>Page blocks</strong> choose a block type and add it, fill in the text, pick images from the media library, use the arrows to reorder</li>
  <li>Fill in the SEO title and meta description and watch the counters and the search preview</li>
  <li>Click <strong>Save</strong>, then <strong>Preview</strong> to check the page before publishing</li>
  <li>Set the status to <strong>Published</strong> and save again</li>
  <li>For another language, click <strong>Create translation</strong> in the Other languages panel — a draft is created under /en/ or /zh/</li>
</ol>

<figure>
  <img src="<?= e(url('/assets/manuals/cms/pages.webp')) ?>" alt="Page editor" width="960" height="1300" loading="lazy">
  <figcaption>① Title and URL ② Publishing ③ Page blocks — arrows reorder, × removes a block</figcaption>
</figure>

<h2>Common mistakes</h2>
<ul>
  <li>Changing the URL of a live page — always keep "create a 301 redirect from the old URL" ticked, or old links and Google rankings are lost</li>
  <li>Figures and client logos are not typed into blocks — add them in <strong>Site settings</strong> and tick "Verified" first</li>
  <li>To bring back old content open <strong>Revision history</strong>, choose a version and restore it (URL and status stay the same)</li>
</ul>
