<?php $_SESSION['explorer_csrf'] ??= bin2hex(random_bytes(32)); ?>
<?php $explorerCssVersion = filemtime(__DIR__ . '/../../assets/css/explorer.css'); ?>
<link rel="stylesheet" href="<?= e(SITE_URL) ?>/assets/css/explorer.css?v=<?= e($explorerCssVersion) ?>">
<section id="project-drive" data-endpoint="<?= e(SITE_URL) ?>/ajax/drive.php" data-csrf="<?= e($_SESSION['explorer_csrf']) ?>">
 <header class="pd-heading"><h1>Drive</h1><span id="pd-count"></span></header>
 <div class="pd-toolbar">
  <div class="pd-new-wrap"><button id="pd-new" class="btn-primary"><i class="fas fa-plus"></i> New</button><div id="pd-new-menu" class="pd-menu" hidden></div></div>
  <button id="pd-upload" class="btn-ghost" hidden><i class="fas fa-upload"></i> Upload</button>
  <label class="pd-search"><i class="fas fa-search"></i><input id="pd-search" type="search" placeholder="Search in Project Drive" aria-label="Search in Project Drive"></label>
  <div class="pd-views" role="group" aria-label="View"><button id="pd-grid" title="Grid view" aria-label="Grid view"><i class="fas fa-border-all"></i></button><button id="pd-list" title="List view" aria-label="List view"><i class="fas fa-list"></i></button></div>
 </div>
 <div id="pd-busy" class="pd-busy" hidden><span></span></div>
 <div id="pd-limit" class="pd-limit" hidden></div>
 <div class="pd-navigation"><button id="pd-back" title="Back" aria-label="Back"><i class="fas fa-arrow-left"></i></button><button id="pd-forward" title="Forward" aria-label="Forward"><i class="fas fa-arrow-right"></i></button><nav id="pd-breadcrumbs" aria-label="Folder path"></nav></div>
 <div id="pd-status" role="status" aria-live="polite"></div><div id="pd-items" aria-label="Folder contents"></div>
 <input id="pd-files" type="file" multiple hidden><input id="pd-folder-files" type="file" webkitdirectory multiple hidden><div id="pd-context" class="pd-menu" hidden></div>
 <section id="pd-queue" class="pd-queue" hidden aria-label="Upload progress">
  <header><strong id="pd-queue-title">Uploads</strong><button id="pd-queue-min" type="button" title="Minimize uploads" aria-label="Minimize uploads"><i class="fas fa-minus"></i></button></header>
  <div id="pd-queue-list"></div>
 </section>
 <dialog id="pd-dialog"><form id="pd-form"><header><h2 id="pd-dialog-title"></h2><button type="button" id="pd-close" title="Close" aria-label="Close"><i class="fas fa-xmark"></i></button></header><div id="pd-dialog-body"></div><p id="pd-dialog-error" role="alert"></p><footer id="pd-dialog-footer"><button type="button" id="pd-cancel" class="btn-ghost">Cancel</button><button type="submit" id="pd-submit" class="btn-primary">Save</button></footer></form></dialog>
</section>
