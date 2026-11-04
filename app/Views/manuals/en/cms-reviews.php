<?php
declare(strict_types=1);

$manual_title   = 'Managing FAQ, testimonials and case studies';
$manual_summary = 'FAQ, client testimonials and case studies — only real, permitted ones are published';
?>
<h2>Who can use it</h2>
<p>Administrators and content editors</p>

<h2>Steps</h2>
<ol>
  <li>FAQ: <strong>FAQ</strong> → Add question; enter the question, answer, group (e.g. general) and language</li>
  <li>A FAQ block on a page uses the same group name to pull the published questions</li>
  <li>Testimonials/case studies: add an item, tick <strong>The client has given permission to publish</strong> and note the proof</li>
  <li>Set the status to Published and save</li>
</ol>

<figure>
  <img src="<?= e(url('/assets/manuals/cms/pages.webp')) ?>" alt="Editor" width="960" height="1300" loading="lazy">
  <figcaption>Testimonial and case study forms have a publishing section with the permission checkbox</figcaption>
</figure>

<h2>Common mistakes</h2>
<ul>
  <li>Publishing is refused until permission is ticked — the system records who confirmed it and when</li>
  <li>Never add made-up testimonials or case studies — showing nothing is better than showing something fake</li>
</ul>
