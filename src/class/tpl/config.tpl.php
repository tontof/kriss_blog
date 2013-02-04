<!DOCTYPE html>
<html>
<head><?php BlogPage::includesTpl(); ?></head>
<body>
<div id="global">
  <?php BlogPage::pageheaderTpl(); ?>
  <div id="section">
    <form method="post" action="">
      <fieldset>
        <legend>Blog information</legend>
        <label>- Blog title (HTML allowed)</label><br>
        <input type="text" name="title" value="<?php echo $kbctitle; ?>"><br>
        <label>- Blog description (HTML allowed)</label><br>
        <input type="text" name="desc" value="<?php echo $kbcdesc; ?>"><br>
        <label>- Blog site (appear in comments)</label><br>
        <input type="text" name="site" value="<?php echo $kbcsite; ?>"><br>
      </fieldset>
      <fieldset>
        <legend>Language information</legend>
        <label>- Locale (eg. en_GB or fr_FR)</label><br>
        <input type="text" maxlength="5" name="locale" value="<?php echo $kbclocale; ?>" /><br>
        <label>- Date format (<a href="http://php.net/strftime">strftime</a> format)</label><br>
        <input type="text" name="dateformat" value="<?php echo $kbcdateformat; ?>"><br>
      </fieldset>
      <fieldset>
        <legend>Blog preferences</legend>
        <label>- Number of entries by page</label><br>
        <input type="text" maxlength="3" name="bypage" value="<?php echo $kbcbypage; ?>"><br>
        <label for="with_comm">- Comments</label><br>
        <input type="radio" id="with_comm" name="comments" value="1" <?php echo ($kbccomments ? 'checked="checked"' : '') ?> /><label for="with_comm"> Allow comments</label><br>
        <input type="radio" id="without_comm" name="comments" value="0" <?php echo (!$kbccomments ? 'checked="checked"' : '') ?> /><label for="without_comm"> Disable comments</label><br>
        <label for="with_cache">- Cache</label><br>
        <input type="radio" id="with_cache" name="cache" value="1" <?php echo ($kbccache ? 'checked="checked"' : '') ?> /><label for="with_cache"> Cache pages</label><br>
        <input type="radio" id="without_cache" name="cache" value="0" <?php echo (!$kbccache ? 'checked="checked"' : '') ?> /><label for="without_cache"> No cache</label><br>
        <label for="reverse">- Order of entries</label><br>
        <input type="radio" id="reverse" name="reverseorder" value="1" <?php echo ($kbcreverseorder ? 'checked="checked"' : '') ?> /><label for="reverseorder"> <strong>Reverse order:</strong> from the newest to the latest</label><br>
        <input type="radio" id="normalorder" name="reverseorder" value="0" <?php echo (!$kbcreverseorder ? 'checked="checked"' : '') ?> /> <label for="normalorder">From the latest to the newest</label><br>
        <input type="hidden" name="returnurl" value="<?php echo htmlspecialchars($referer); ?>">
        <input type="hidden" name="token" value="<?php echo Session::getToken(); ?>">
        <input type="submit" name="cancel" value="Cancel"/>
        <input type="submit" name="save" value="Save" />
      </fieldset>
    </form>
  </div>
  <?php BlogPage::pagefooterTpl(); ?>
</div>
</body>
</html>
