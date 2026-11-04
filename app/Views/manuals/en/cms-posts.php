<?php
declare(strict_types=1);

$manual_title   = 'Writing blog posts';
$manual_summary = 'Write posts, format them, insert images, set categories and manage categories';
?>
<h2>Who can use it</h2>
<p>Administrators and content editors</p>

<h2>Steps</h2>
<ol>
  <li>Open <strong>Posts</strong> and click <strong>Write a post</strong></li>
  <li>Enter the title and the slug (last part of the URL, Thai is allowed)</li>
  <li>Write the content using the toolbar above it for headings, lists, bold, links, tables and images from the library</li>
  <li>Choose the category, featured image, publish date and fill in the SEO</li>
  <li>Save, preview, then set the status to Published</li>
  <li>Categories are managed under <strong>Categories</strong> (only empty categories can be deleted)</li>
</ol>

<figure>
  <img src="<?= e(url('/assets/manuals/cms/pages.webp')) ?>" alt="Editor" width="960" height="1300" loading="lazy">
  <figcaption>Posts use the same SEO fields and search preview as pages</figcaption>
</figure>

<h2>Common mistakes</h2>
<ul>
  <li>A post URL comes from its publish date and slug — when you change either, keep the redirect from the old URL ticked</li>
  <li>There is no H1 inside the content — the post title is the H1</li>
  <li>Unsafe code (script, onclick) is removed automatically when saving</li>
</ul>
