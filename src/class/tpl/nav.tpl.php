  <div id="nav">
    <ul class="nav">
    <?php
        if ($menu['private'] !== 1) {
            echo $menu['text'];
        }
    ?>

    <?php
        if (Session::isLogged()) {
    ?>
      <li><a href="?edit" class="admin"><b>New entry</b></a></li>
      <li><a href="?edit=page" class="admin"><b>New page</b></a></li>
      <li><a href="?page" class="admin">All pages</a></li>
      <li><a href="?config" class="admin">Config</a></li>
      <li><a href="?private" class="admin"><?php echo (empty($_SESSION['privateonly'])?'Private only':'All entries')?></a></li>
      <li><a href="?logout" class="admin"><?php echo _("Logout"); ?></a></li>
    <?php
        }
    ?>
    </ul>
  </div>

  <div id="extra">
    <?php
        if ($extra['private'] !== 1) {
            echo $extra['text'];
        }
    ?>
  </div>
