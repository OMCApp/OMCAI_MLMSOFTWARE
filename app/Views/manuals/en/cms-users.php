<?php
declare(strict_types=1);

$manual_title   = 'Managing admin users';
$manual_summary = 'Add users, set roles, deactivate accounts, reset passwords and change your own password';
?>
<h2>Who can use it</h2>
<p>Administrators (changing your own password: everyone)</p>

<h2>Steps</h2>
<ol>
  <li>Open <strong>Users</strong> → <strong>Add user</strong>; enter the username, display name, role and an initial password</li>
  <li>Roles: Administrator = everything · Content editor = content, SEO, media, menus, FAQ · Sales = leads only</li>
  <li>New users must change their password at the first login</li>
  <li>To deactivate, untick "Active" and save — it takes effect immediately</li>
  <li>Your own password: Overview → <strong>Change password</strong></li>
</ol>

<figure>
  <img src="<?= e(url('/assets/manuals/cms/settings.webp')) ?>" alt="Form" width="960" height="1300" loading="lazy">
  <figcaption>All forms work the same way — fields with an asterisk are required</figcaption>
</figure>

<h2>Common mistakes</h2>
<ul>
  <li>Passwords need at least 10 characters</li>
  <li>You cannot change the role of, deactivate or delete your own account, and at least one administrator must remain</li>
  <li>Lost device: click <strong>Forget all devices</strong> on that user</li>
</ul>
