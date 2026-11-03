<?php
declare(strict_types=1);
?>
<div class="stack">
    <div class="card stack">
        <h2><?= e((string) config('name')) ?></h2>
        <p class="muted"><?= e(__('state.empty_hint')) ?></p>
        <div class="row">
            <a class="btn btn-primary" href="<?= e(url('/login')) ?>"><?= e(__('auth.login')) ?></a>
            <a class="btn btn-secondary" href="<?= e(url('/manual')) ?>"><?= e(__('ui.manual')) ?></a>
        </div>
    </div>
</div>
