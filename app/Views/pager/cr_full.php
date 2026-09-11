<?php
/**
 * Pager template CyberRun — Bootstrap 5 + fleches Unicode.
 * Variables fournies par CI4 : $pager (CodeIgniter\Pager\PagerRenderer).
 */
$pager->setSurroundCount(2);
?>
<nav aria-label="<?= lang('Pager.pageNavigation') ?>">
    <ul class="pagination pagination-sm mb-0 justify-content-center">

        <?php if ($pager->hasPreviousPage()): ?>
            <li class="page-item">
                <a class="page-link" href="<?= $pager->getFirst() ?>" aria-label="<?= lang('Pager.first') ?>" title="<?= lang('Pager.first') ?>">
                    <span aria-hidden="true">&laquo;</span>
                </a>
            </li>
            <li class="page-item">
                <a class="page-link" href="<?= $pager->getPreviousPage() ?>" aria-label="<?= lang('Pager.previous') ?>" title="<?= lang('Pager.previous') ?>">
                    <span aria-hidden="true">&lsaquo;</span>
                </a>
            </li>
        <?php else: ?>
            <li class="page-item disabled"><span class="page-link" aria-hidden="true">&laquo;</span></li>
            <li class="page-item disabled"><span class="page-link" aria-hidden="true">&lsaquo;</span></li>
        <?php endif ?>

        <?php foreach ($pager->links() as $link): ?>
            <li class="page-item <?= $link['active'] ? 'active' : '' ?>">
                <?php if ($link['active']): ?>
                    <span class="page-link"><?= $link['title'] ?></span>
                <?php else: ?>
                    <a class="page-link" href="<?= $link['uri'] ?>"><?= $link['title'] ?></a>
                <?php endif ?>
            </li>
        <?php endforeach ?>

        <?php if ($pager->hasNextPage()): ?>
            <li class="page-item">
                <a class="page-link" href="<?= $pager->getNextPage() ?>" aria-label="<?= lang('Pager.next') ?>" title="<?= lang('Pager.next') ?>">
                    <span aria-hidden="true">&rsaquo;</span>
                </a>
            </li>
            <li class="page-item">
                <a class="page-link" href="<?= $pager->getLast() ?>" aria-label="<?= lang('Pager.last') ?>" title="<?= lang('Pager.last') ?>">
                    <span aria-hidden="true">&raquo;</span>
                </a>
            </li>
        <?php else: ?>
            <li class="page-item disabled"><span class="page-link" aria-hidden="true">&rsaquo;</span></li>
            <li class="page-item disabled"><span class="page-link" aria-hidden="true">&raquo;</span></li>
        <?php endif ?>

    </ul>
</nav>
