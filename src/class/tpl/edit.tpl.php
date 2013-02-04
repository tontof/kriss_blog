<!DOCTYPE html>
<html>
<head><?php BlogPage::includesTpl(); ?></head>
<body>
<div id="global">
  <?php BlogPage::pageheaderTpl(); ?>
  <div id="section">
    <div class="article">
      <form id="edit_form" method="post" class="edit" action="?edit<?php echo (empty($id)?'':'='.$id); ?>">
        <fieldset>
          <label for="f_title">Title</label><br>
          <input type="text" id="f_title" name="title" value="<?php echo $entrytitle; ?>"><br>
          <textarea name="text" cols="70" rows="20"><?php echo $entrytext; ?></textarea><br>
<?php
          if (empty($_GET['edit']) || is_numeric($_GET['edit'])) {
?>
          <label for="f_tags">Tags</label><br>
          <input type="text" id="f_tags" name="tags" value="<?php echo $entrytags; ?>"><br>
          <label for="f_date">Entry date</label><br>
          <input type="text" id="f_date" name="date" value="<?php echo $entrydate; ?>"><br>
<?php
          }
?>
          <label for="with_comm">Comments</label><br>
      <input type="radio" id="with_comm" name="comments" value="1" <?php echo ($entrycomments?'checked="checked"':''); ?>><label for="with_comm">Allow comments</label><br>
      <input type="radio" id="without_comm" name="comments" value="0" <?php echo (!$entrycomments?'checked="checked"':''); ?>><label for="without_comm">Disable comments</label><br>
      <input type="checkbox" id="f_private" name="private" value="1" <?php echo ($entryprivate?'checked="checked"':''); ?>>
          <label for="f_private">Private</label><br>
          <input type="hidden" name="returnurl" value="<?php echo htmlspecialchars($referer);?>">
          <input type="hidden" name="token" value="<?php echo Session::getToken(); ?>">
          <input type="submit" name="save" value="Post article">
          <input type="submit" name="cancel" value="Cancel">
        </fieldset>
      </form>
    </div>
  </div>
  <?php BlogPage::pagefooterTpl(); ?>
</div>
</body>
</html>
