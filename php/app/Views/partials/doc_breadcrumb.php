<?php
/**
 * Document Management breadcrumb, for the page header: Documents › School
 * Forms › SF3 Term 1. Expects $path (DocumentFolderModel::pathTo(), may be
 * empty). Crumbs other than the current folder are drop targets for moving
 * folders up the tree (data-drop-target; 0 = top level).
 */
?>
<nav class="doc-breadcrumb mb-2" aria-label="Folder path">
  <a href="<?= base_url('documents') ?>" class="doc-crumb" data-drop-target="0" data-drop-name="Documents (top level)">
    <i class="bi bi-house-door me-1"></i>Documents
  </a>
  <?php foreach ($path as $i => $crumb): $isLast = $i === count($path) - 1; ?>
  <i class="bi bi-chevron-right doc-crumb-sep"></i>
  <?php if ($isLast): ?>
  <span class="doc-crumb doc-crumb-current"><?= e($crumb['name']) ?></span>
  <?php else: ?>
  <a href="<?= base_url('documents?folder=' . $crumb['id']) ?>" class="doc-crumb"
     data-drop-target="<?= (int) $crumb['id'] ?>" data-drop-name="<?= e($crumb['name']) ?>"><?= e($crumb['name']) ?></a>
  <?php endif; ?>
  <?php endforeach; ?>
</nav>
