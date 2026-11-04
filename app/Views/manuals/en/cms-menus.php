<?php
declare(strict_types=1);

$manual_title   = 'Editing the site menus';
$manual_summary = 'Edit the header and footer menus per language';
?>
<h2>Who can use it</h2>
<p>Administrators and content editors</p>

<h2>Steps</h2>
<ol>
  <li>Open <strong>Menus</strong>, choose the location (header/footer) and language, then click View</li>
  <li>The first time, click <strong>Copy the default menu to edit</strong></li>
  <li>Add an item: type the text, choose a page (or a URL) and the heading it belongs under</li>
  <li>Use the up/down arrows to reorder, the pencil to edit and the bin to delete</li>
</ol>

<figure>
  <img src="<?= e(url('/assets/manuals/cms/menus.webp')) ?>" alt="Menus screen" width="960" height="1300" loading="lazy">
  <figcaption>① Location and language ② Menu items ③ Add item form</figcaption>
</figure>

<h2>Common mistakes</h2>
<ul>
  <li>Items linking to draft pages are hidden on the site until the page is published</li>
  <li>Footer: a heading with items = a column · a top-level item without children = a policy link at the bottom</li>
  <li>Two levels only</li>
</ul>
