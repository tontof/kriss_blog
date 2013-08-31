<!DOCTYPE html>
<html>
<head><?php BlogPage::includesTpl(); ?></head>
<body>
<div id="global">
  <?php BlogPage::pageheaderTpl(); ?>
  <?php BlogPage::navTpl(); ?>

  <div id="section">
  <?php
      if (!empty($pages)) {
  ?>
    <ul class="pagination">
    <?php
        for ($p = 1; $p <= $pages; $p++) {
    ?>
      <li<?php echo ($page == $p ? ' class="selected"' : '' ) ?>><a href="?page=<?php echo $p; ?>"><?php echo $p; ?></a></li>
    <?php
        }
    ?>
    </ul>
  <?php
      }
  ?>
  <?php
      if (empty($entries)) {
  ?>
    <p>No item.</p>
  <?php
      } else {
          $today = time();
          foreach ($entries as $id => $content) {
  ?>
      <div class="article"<?php echo ((Session::isLogged() and ($content['private'] or $id>time()))?' style="border-color:red;"':'') ?>>
        <h3 class="title">
          <?php
              if (is_numeric($id)) {
          ?>
          <a href="?<?php echo MyTool::urlize($id, $content['title']); ?>"><?php echo $content['title']; ?></a>
          <?php
              } else {
          ?>
          <a href="?<?php echo $id; ?>"><?php echo $content['title']; ?></a>
          <?php
              }
          ?>
        </h3>
          <?php
              if (is_numeric($id)) {
          ?>
        <h4 class="subtitle"><?php echo ((Session::isLogged() and $content['private'])?'(<em>private</em>)':'')?> <?php echo utf8_encode(strftime($dateformat, $id)) ?></h4>
          <?php
              }
          ?>
          <?php
              if (!isset($list) || !$list) {
          ?>
          <div class="content">
            <?php echo MyTool::formatText($content['text']); ?>
          </div>
          <?php
              }
          ?>
          <p class="link">
          <?php
              if (is_numeric($id)) {
          ?>
          <a href="?<?php echo MyTool::urlize($id, $content['title']); ?>#comments"><?php echo count($content['comments']); ?> comment(s)</a>
          <?php
              } else {
          ?>
          <a href="?<?php echo $id; ?>#comments"><?php echo count($content['comments']); ?> comment(s)</a>
          <?php
              }
          ?>
          <?php
              if (Session::isLogged()) {
          ?>
              | <a href="?edit=<?php echo $id; ?>" class="admin">Edit</a> | <a href="?delete=<?php echo $id ?>" class="admin" onclick="if (confirm('Sure?') != true) return false;">Delete</a>
          <?php
              }
          ?>
          </p>
        </div>
      <?php
          }
      ?>
  <?php
      }
  ?>

  <?php
      if (!empty($pages)) {
  ?>
    <ul class="pagination">
      <?php
          for ($p = 1; $p <= $pages; $p++) {
      ?>
      <li<?php echo ($page == $p ? ' class="selected"' : '' ) ?>><a href="?page=<?php echo $p; ?>"><?php echo $p; ?></a></li>
      <?php
          }
      ?>
    </ul>
  <?php
      }
  ?>
  </div>
  <?php BlogPage::pagefooterTpl(); ?>
</div>
</body>
</html>
