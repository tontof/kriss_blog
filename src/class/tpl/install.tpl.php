<!DOCTYPE html>
<html>
<head><?php BlogPage::includesTpl(); ?></head>
<body>
<div id="global">
  <div id="section">
    <h1>Blog installation</h1>
    <form method="post" action="">
      <p><label>Login: <input type="text" name="setlogin" /></label></p>
      <p><label>Password: <input type="password" name="setpassword" /></label></p>
      <p><input type="submit" value="OK" class="submit" /></p>
    </form>
  </div>
  <?php BlogPage::pagefooterTpl(); ?>
</div>
</body>
</html>