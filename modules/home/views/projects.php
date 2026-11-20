<?php
declare(strict_types=1);

/** @var array $projects modules with 'project' => true */
?>
<section>
    <p class="eyebrow">Code</p>
    <h1>Projects</h1>
    <p class="lede">Here are some fun things I made that are also maybe useful.</p>

    <?php if ($projects === []): ?>
        <p class="lede">No projects listed yet.</p>
    <?php else: ?>
        <div class="card-grid">
            <?php foreach ($projects as $project): ?>
                <div class="card">
                    <h3><?= e($project['title']) ?></h3>
                    <p><?= e($project['description']) ?></p>
                    <a href="<?= e($project['url']) ?>" class="card-link">Open project &rarr;</a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
