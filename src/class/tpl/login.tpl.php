<!DOCTYPE html>
<html>
  <head>
  <?php BlogPage::includesTpl(); ?>
  </head>
  <body onload="document.loginform.login.focus();">
    <div id="global">
      <?php BlogPage::pageheaderTpl(); ?>
      <form method="post" action="?login" name="loginform">
        <fieldset>
          <legend>Welcome to KrISS blog</legend>
          <label for="login">Login: <input type="text" id="login" name="login" tabindex="1"/></label>
          <label for="password">Password: <input type="password" id="password" name="password" tabindex="2"/></label>
          <input type="checkbox" name="longlastingsession" id="longlastingsession" tabindex="3">
          <label for="longlastingsession">&nbsp;Stay signed in (Do not check on public computers)</label>
          <input type="hidden" name="returnurl" value="<?php echo htmlspecialchars($referer);?>">
          <input type="hidden" name="token" value="<?php echo Session::getToken(); ?>">
          <input type="submit" value="OK" class="submit" tabindex="4">
        </fieldset>
      </form>
      <?php BlogPage::pagefooterTpl(); ?>
    </div>
    <script>
      document.loginform.login.focus();
    </script>                                              
  </body>
</html>