<?php
declare(strict_types=1);

$manual_title   = 'Using the media library';
$manual_summary = 'Upload images, write descriptions (alt), search and delete images';
?>
<h2>Who can use it</h2>
<p>Administrators and content editors</p>

<h2>Steps</h2>
<ol>
  <li>Open <strong>Media library</strong></li>
  <li>Choose a JPG, PNG, GIF or WebP file and write the <strong>image description (alt)</strong>, which is required</li>
  <li>Click <strong>Upload image</strong> — WebP and smaller sizes are created automatically</li>
  <li>Click an image in the grid to edit its alt or see where it is used</li>
  <li>For images of the old site click <strong>Register images of the old site</strong>, then add alt to images marked "Missing alt"</li>
</ol>

<figure>
  <img src="<?= e(url('/assets/manuals/cms/media.webp')) ?>" alt="Media library" width="960" height="1300" loading="lazy">
  <figcaption>① File picker ② Image description (required) ③ Image grid, click to edit</figcaption>
</figure>

<h2>Common mistakes</h2>
<ul>
  <li>SVG files are not accepted for security</li>
  <li>An image in use cannot be deleted — its page lists where it is used; remove it there first</li>
  <li>A good alt says what the image shows, not the file name</li>
</ul>
