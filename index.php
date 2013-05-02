<?php

define('CACHE_DIR', 'cache');
define('DATA_DIR', 'data');
define('DOMAIN', 'blog');
define('LOCALE', 'locale');
// TODO : remove in version 7
define('MENU_FILE', DATA_DIR.'/menu.php');
define('DATA_FILE', DATA_DIR.'/data.php');
define('CONFIG_FILE', DATA_DIR.'/config.php');

define('STYLE_FILE', 'style.css');
define('BLOG_VERSION', 6);

define('PHPPREFIX', '<?php /* '); // Prefix to encapsulate data in php code.
define('PHPSUFFIX', ' */ ?>'); // Suffix to encapsulate data in php code.


class BlogConf
{
    private $_configFile = '';
    public $login = '';
    public $site = '';
    public $hash = '';
    public $salt = '';

    // Blog title
    public $title = "Kriss blog";

    // Blog description
    public $desc = "Simple and smart (or stupid) blog";

    // Blog locale
    public $locale = "en_GB";
    public $dateformat = "%A %d %B %Y - %H:%M";

    // Number of entries to display per page
    public $bypage = "10";

    // Reversed order ?
    public $reverseorder = true;

    // Allow comments
    public $comments = true;

    // Use cache
    public $cache = false;

    // Blog url (leave empty to autodetect)
    public $url = '';

    // kriss_blog version
    public $version;

    public function __construct($configFile, $version)
    {
        $this->_configFile = $configFile;
        $this->version = $version;

        // Loading user config
        if (file_exists($this->_configFile)) {
            require_once $this->_configFile;
        } else {
            $this->_install();
        }

        // For translating things
        putenv("LANGUAGE=fr_FR.utf-8");
        putenv("LANG=fr_FR.utf-8");
        putenv("LC_ALL=fr_FR.utf-8");
        setlocale(LC_ALL, $this->locale);
        setlocale(LC_TIME, $this->locale);
        setlocale(LC_MESSAGES, $this->locale);
    }

    private function _install()
    {
        if (!empty($_POST['setlogin']) && !empty($_POST['setpassword'])) {
            $this->setSalt(sha1(uniqid('', true).'_'.mt_rand()));
            $this->setLogin($_POST['setlogin']);
            $this->setHash($_POST['setpassword']);

            if (!is_dir(DATA_DIR)) {
                if (!@mkdir(DATA_DIR, 0755)) {
                    echo '
<script>
 alert("Error: can not create '.DATA_DIR.' directory, check permissions");
 document.location=window.location.href;
</script>';
                    exit();
                }
                @chmod(DATA_DIR, 0755);
                if (!is_file(DATA_DIR.'/.htaccess')) {
                    if (
                        !@file_put_contents(
                            DATA_DIR.'/.htaccess',
                            "Allow from none\nDeny from all\n"
                        )) {
                        echo '
<script>
 alert("Can not protect '.DATA_DIR.'");
 document.location=window.location.href;
</script>';
                        exit();
                    }
                }
            }

            if ($this->write($this->_configFile)) {
                echo '
<script language="JavaScript">
 alert("Your simple and smart (or stupid) blog is now configured. Enjoy !");
 document.location=window.location.href;
</script>';
            } else {
                echo '
<script language="JavaScript">
 alert("Error can not write config and data files.");
 document.location=window.location.href;
</script>';
            }
            Session::logout();
        } else {
            BlogPage::init(
                array(
                    'version' => $this->version,
                    'pagetitle' => 'Installation'
                )
            );
            BlogPage::installTpl();
        }
        exit();
    }

    public function hydrate(array $donnees)
    {
        foreach ($donnees as $key => $value) {
            // get setter
            $method = 'set'.ucfirst($key);
            // if setter exists just call it
            if (method_exists($this, $method)) {
                $this->$method($value);
            }
        }
    }

    public function setLogin($login)
    {
        $this->login=$login;
    }

    public function setSite($site)
    {
        $this->site=$site;
    }

    public function setHash($pass)
    {
        $this->hash=sha1($pass.$this->login.$this->salt);
    }

    public function setSalt($salt)
    {
        $this->salt=$salt;
    }

    public function setTitle($title)
    {
        $this->title=$title;
    }

    public function setDesc($desc)
    {
        $this->desc=$desc;
    }

    public function setLocale($locale)
    {
        $this->locale=preg_match('/^[a-z]{2}_[A-Z]{2}$/', $_POST['locale'])
            ? $_POST['locale']
            : $this->locale;
    }

    public function setDateformat($dateformat)
    {
        $this->dateformat=$dateformat;
    }

    public function setBypage($bypage)
    {
        $this->bypage=$bypage;
    }

    public function setComments($comments)
    {
        if ($this->comments!=$comments) {
            $this->comments=$comments;
            MyTool::rrmdir(CACHE_DIR);
        }
    }

    public function setCache($cache)
    {
        $this->cache=$cache;
        if ($this->cache) {
            if (!is_dir(CACHE_DIR)) {
                if (!@mkdir(CACHE_DIR, 0705)) {
                    die("Can't create ".CACHE_DIR);
                }
            }
            @chmod(CACHE_DIR, 0705);
            if (!is_file(CACHE_DIR.'/.htaccess')) {
                if (!@file_put_contents(
                    CACHE_DIR.'/.htaccess',
                    "Allow from none\nDeny from all\n"
                )) {
                    die("Can't protect ".CACHE_DIR);
                }
            }
        } else {
            MyTool::rrmdir(CACHE_DIR);
        }
    }

    public function setReverseorder($reverseorder)
    {
        $this->reverseorder=$reverseorder;
    }

    public function write()
    {
        $data = array('login', 'hash', 'salt', 'title', 'desc', 'dateformat',
                      'locale', 'bypage', 'cache', 'comments', 'reverseorder',
                      'site');
        $out = '<?php';
        $out.= "\n";

        foreach ($data as $key) {
            $value = strtr($this->$key, array('$' => '\\$', '"' => '\\"'));
            $out .= '$this->'.$key.' = "'.$value."\";\n";
        }

        $out.= '?>';

        if (!@file_put_contents($this->_configFile, $out)) {
            return false;
        }

        return true;
    }
}

class BlogPage
{
    public static $var = array();
    private static $_instance;

    public function init($var)
    {
        BlogPage::$var = $var;
    }

    public static function installTpl()
    {
        extract(BlogPage::$var);
?>
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
</html><?php
    }

    public static function includesTpl()
    {
        extract(BlogPage::$var);
?>
<title><?php echo $pagetitle;?></title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">

<link rel="alternate" type="application/rss+xml" href="?rss" title="<?php echo $pagetitle;?>">
<link rel="alternate" type="application/rss+xml" href="?rss=comments" title="<?php echo $pagetitle;?> RSS comments">
<!-- <link href="images/favicon.ico" rel="shortcut icon" type="image/x-icon"> -->
<?php
    if (is_file('inc/style.css')) {
?>
<link type="text/css" rel="stylesheet" href="inc/style.css?version=<?php echo $version;?>" />
<?php
    } else {
?>
<style>
* {
  margin: 0;
  padding: 0;
}

.admin {
  color: red !important;
}

body {
  font-family: Arial, Helvetica, sans-serif;
  background: #eee;
  color: #000;
  width:800px;
  margin:auto;
}

img {
  max-width: 100%;
}

#global {
  border: 2px solid #999;
  border-top: none;
  padding: 1em 1.5em 0;
  background: #fff;
}

#title {
  color: #666;
  border-bottom: 1px dotted #999;
}

#subtitle {
  text-align: right;
  font-style: italic;
  margin-bottom: 1em;
  color: #666;
}

#nav {
  border: 1px dashed #999;
  font-size: .9em;
  padding: 0.2em;
  color: #666;
  height: 1.3em;
}

.nav {
  list-style-type:none;
}

.nav li {
  float:left;
  margin-right:12px;
}

#status {
  margin: 0;
  font-size: 0.7em;
  text-align: center;
  clear: both;
  background: #fff;
  width: 100%;
}

#extra {
  background: #eee;
  border-radius: 0 0 9px 9px;
  border-style: none solid solid;
  border-width: medium 1px 1px;
  box-shadow: 0 2px 8px 0 rgba(51, 51, 51, 0.5);
  font-size: 10pt;
  font-weight: bold;
  padding: 1px 20px 3px;
  position: absolute;
  left: 75%;
  top: 0;
}

.extratoshow {
  display: none;
}

#extra:hover .extratohide {
  display: none;
}

#extra:hover .extratoshow {
  display: block;
}

.pagination {
  list-style-type: none;
  text-align: center;
  margin: .5em;
}

.pagination li {
  display: inline;
  margin: .3em;
}

.selected {
  font-weight: bold;
  font-size: 1.2em;
}

.article, .comment {
  border: 1px dotted #999;
  padding: .5em;
  margin: 1.5em 0;
  overflow: auto;
}

.subtitle {
  text-align: right;
  font-style: italic;
  color: #666;
  border-bottom: 1px dotted #999;
  margin-bottom: 1em;
}

.content{
  padding:.5em;
}

.link {
  font-size: .9em;
  float: right;
  border: 1px dotted #999;
  padding: .3em;
}

#new_comment button {
  border: 1px solid #000;
  border-radius: 4px;
  margin: 0 .2em;
  background: #fff;
  height:32px;
  width:32px;
}

#new_comment button:hover {
  border: 1px solid #000;
  background: #999;
}


fieldset{
  padding: 1em;
}

legend {
  font-weight: bold;
  margin: 0 .42em;
  padding: 0 .42em;
}

input[type=text], input[type=password], textarea{
  border: 1px solid #000;
  margin: .2em 0;
  padding: .2em;
  font-size: 1em;
  width:100%;
}

a:active, a:visited, a:link {
  text-decoration: underline;
  color: #666;
}

a:hover {
  text-decoration: none;
}

@media (max-width: 750px) {
 body{
  width:100%;
  height:100%;
 }
}
</style>
<?php
    }
?>
<?php
    if (is_file('inc/user.css')) {
?>
<link type="text/css" rel="stylesheet" href="inc/user.css?version=<?php echo $version;?>" />
<?php
    }
?>
<meta name="format-detection" content="telephone=no">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<?php
    }

    public static function pageheaderTpl()
    {
        extract(BlogPage::$var);
?>
<div id="header">
  <h1 id="title"><?php echo $blogtitle ?></h1>
  <h2 id="subtitle"><?php echo $blogdesc ?></h2>
</div>
<?php
    }

    public static function navTpl()
    {
        extract(BlogPage::$var);
?>
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
<?php
    }

    public static function pagefooterTpl()
    {
        extract(BlogPage::$var);
?>
<div id="footer">
  <div id="status">
    <strong><a href="http://github.com/tontof/kriss_blog">KrISS blog <?php echo htmlspecialchars($version); ?></a></strong>
    <span class="nomobile"> - A simple and smart (or stupid) blog</span>. By <a href="http://tontof.net">Tontof</a>
  </div>
</div>



<?php
    }

    public function configTpl()
    {
        extract(BlogPage::$var);
?>
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
<?php
    }

    public static function loginTpl()
    {
        extract(BlogPage::$var);
?>
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
</html><?php
    }

    public function rssTpl()
    {
        extract(BlogPage::$var);
?>
<?php
$today = time();
$lastUpdate = date(DATE_W3C, $today);
if (count($entries) > 0) {
    $lastUpdate = array_keys($entries);
    $lastUpdate = date(DATE_W3C, $lastUpdate[0]);
}
echo '<?xml version="1.0" encoding="UTF-8" ?>';
?>
<rdf:RDF
  xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
  xmlns:dc="http://purl.org/dc/elements/1.1/"
  xmlns:sy="http://purl.org/rss/1.0/modules/syndication/"
  xmlns:content="http://purl.org/rss/1.0/modules/content/"
  xmlns="http://purl.org/rss/1.0/">

  <channel rdf:about="<?php echo $blogurl; ?>">
    <title><?php echo strip_tags(MyTool::formatText($blogtitle)); ?></title>
    <description><?php echo strip_tags(MyTool::formatText($blogdesc)); ?></description>
    <link><?php echo $blogurl; ?></link>
    <dc:language><?php echo $bloglanguage; ?></dc:language>
    <dc:rights></dc:rights>
    <dc:creator><?php echo $bloglogin; ?></dc:creator>
    <dc:date><?php echo $lastUpdate; ?></dc:date>
    <dc:source>kriss blog</dc:source>

    <sy:updatePeriod>daily</sy:updatePeriod>
    <sy:updateFrequency>1</sy:updateFrequency>
    <sy:updateBase><?php echo $lastUpdate; ?></sy:updateBase>

    <items>
      <rdf:Seq>
    <?php
        foreach ($entries as $id => $entry) {
    ?>
        <rdf:li rdf:resource="<?php echo $entry['link']; ?>" />
    <?php
        }
    ?>
      </rdf:Seq>
    </items>
  </channel>
    <?php
        foreach ($entries as $id => $entry) {
    ?>
    <item rdf:about="<?php echo $entry['link']; ?>">
      <title><?php echo $entry['title']; ?></title>
      <link><?php echo $entry['link']; ?></link>
      <description><![CDATA[<?php echo MyTool::formatText($entry['content']); ?>]]></description>
      <dc:date><?php echo date(DATE_W3C, $id); ?></dc:date>
      <dc:language><?php echo $bloglanguage; ?></dc:language>
      <dc:creator><?php echo $bloglogin; ?></dc:creator>
      <dc:subject><?php echo strip_tags(MyTool::formatText($blogdesc)); ?></dc:subject>
      <content:encoded>
          <![CDATA[<?php echo MyTool::formatText($entry['content']); ?>]]>
      </content:encoded>
    </item>
    <?php
        }
    ?>
</rdf:RDF>
<?php 
    }

    public function editTpl()
    {
        extract(BlogPage::$var);
?>
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
<?php
    }

    public function indexTpl()
    {
        extract(BlogPage::$var);
?>
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
<?php
    }

    public function entryTpl()
    {
        extract(BlogPage::$var);
?>
<!DOCTYPE html>
<html>
<head><?php BlogPage::includesTpl(); ?></head>
<body>
<div id="global">
  <?php BlogPage::pageheaderTpl(); ?>
  <?php BlogPage::navTpl(); ?>

  <div id="section">
  <?php
      if (empty($entry)) {
  ?>
    <div class="article">
      <div class="content">
      No entry
      <?php 
          if (Session::isLogged()) {
      ?>
      <p class="link">
        <a href="?edit=<?php echo $id; ?>" class="admin">Create</a>
      </p>
      <?php
          }
      ?>
      </div>
    </div>
  <?php
      } else {
  ?>
    <div class="article"<?php echo ((Session::isLogged() and ($entry['private'] or $id > time()))?' style="border-color:red;"':''); ?>>
      <h3 class="title"><?php echo $entry['title'] ?></h3>
      <?php
          if (is_numeric($id)) {
      ?>
      <h4 class="subtitle"><?php echo utf8_encode(strftime($dateformat, $id)); ?></h4>
      <?php
          }
      ?>
      <div class="content">
        <?php echo MyTool::formatText($entry['text']); ?>
        <p class="link">
         <?php
             if (is_numeric($id)) {
         ?>
         <a href="?<?php echo MyTool::urlize($id, $entry['title']); ?>">Permalink</a>
         <?php
             } else {
         ?>
         <a href="?<?php echo $id; ?>">Permalink</a>
         <?php
             }
         ?>

         <?php
             if (Session::isLogged()) {
         ?>
           | <a href="?edit=<?php echo $id; ?>" class="admin">Edit</a> | <a href="?delete=<?php echo $id; ?>" class="admin" onclick="if (confirm('Sure?') != true) return false;">Delete</a>
         <?php
             }
         ?>
        </p>
      </div>
    </div>

    <div id="comments">
      <?php $numComm = count($entry['comments']);
         if ($numComm > 0) { ?>
            <h3>Comments</h3>
      <?php
         }
      $i = 1;
      foreach ($entry['comments'] as $key => $comment) {
      ?>
        <div class="comment">
          <h4 id="<?php echo $i; ?>"><a href='#<?php echo $i; ?>'><?php echo $i; ?></a> - <a href="#new_comment" onclick="reply('[b]@[<?php echo strip_tags($comment[0]); ?>|#<?php echo $i; ?>][/b]')">@</a> - 
          <?php
              if (MyTool::isUrl($comment[1])) {
          ?>
            <a href="<?php echo $comment[1]; ?>"><?php echo $comment[0]; ?></a>
          <?php
              } else {
                  echo $comment[0];
              }
          ?>
          </h4>
          <div class="content">
            <?php echo MyTool::formatText($comment[2]); ?>
          </div>
          <p class="link">
          <?php
              if ($i==$numComm and isset($_POST['preview'])) {
          ?>
            <strong>Preview</strong>
          <?php
              } else {
                  echo utf8_encode(strftime($dateformat, $key));
                  if (Session::isLogged()) {
          ?>
            | <a href="?<?php echo $id; ?>_<?php echo $key; ?>#new_comment" class="admin">Edit</a>
          <?php
                  }
              }
          ?>
          </p>
        </div>
      <?php
          $i++;
      }
      ?>
    </div>


    <?php
        if (isset($entry['comment']) && $entry['comment']) {
    ?>
        <form id="new_comment" action="#new_comment" method="post">
          <fieldset>
            <legend>New comment</legend>
            <label for="pseudo">Pseudo</label><br>
            <input type="text" placeholder="pseudo (facultatif)" id="pseudo" name="pseudo" value="<?php echo (isset($inputpseudo)?$inputpseudo:''); ?>"><br>
            <label for="site">Site</label><br>
            <input type="text" placeholder="site (facultatif)" id="site" name="site" value="<?php echo (isset($inputsite)?$inputsite:''); ?>" <?php echo ((!empty($inputsite) and !MyTool::isUrl($inputsite))?'style="border-color:red">':'>'); ?>
            <br>
            <div style="display:none;">
            <label for="message">Leave empty</label><br>
            <textarea id="message" name="message" rows="3"></textarea>
            </div>
            <label for="comment">Comment</label><br>
            <textarea id="comment" name="comment" rows="10"<?php echo ((empty($inputcomment) and isset($_POST['comment']))?' style="border-color:red">':'>'); echo (isset($inputcomment)?$inputcomment:''); ?></textarea>
            <p>
              <button onclick="insertTag('[b]','[/b]','comment');" title="bold" type="button"><strong>b</strong></button><button onclick="insertTag('[i]','[/i]','comment');" title="italic" type="button"><em>i</em></button><button onclick="insertTag('[u]','[/u]','comment');" title="underline" type="button"><span style="text-decoration:underline;">u</span></button><button onclick="insertTag('[s]','[/s]','comment');" title="strike through" type="button"><del>s</del></button><button onclick="insertTag('[','|http://]','comment');" title="link" type="button">url</button><button onclick="insertTag('[quote]','[/quote]','comment');" title="quote" type="button">&#171;&nbsp;&#187;</button><button onclick="insertTag('[code]','[/code]','comment');" title="code" type="button">&#60;&#62;</button>
            </p><br>

        <?php
            if (isset($captcha)) {
        ?>
          <label for="captcha">Captcha</label><br>
          <pre><?php echo (isset($captcha)?$captcha:''); ?></pre><br>
          <input type="text" placeholder="Captcha" id="captcha" name="captcha"<?php echo ((isset($_POST['captcha']) && !isset($_POST['preview']))?' style="border-color:red"':''); ?>> <br>
        <?php
            }
        ?>
        <br>
        <?php
            if (strpos($_SERVER['QUERY_STRING'], '_') === false) {
        ?>
          <input type="submit" value="Preview" name="preview">
          <input type="submit" value="Send" name="send">
        <?php
            } else {
                if (Session::isLogged()) {
        ?>
          <input type="submit" value="Edit" name="edit">
        <?php
                }
            }
        ?>
          </fieldset>
        </form><br>
    <?php
        }
    ?>
<script>
// script from http://lehollandaisvolant.net
function reply(com) {
  var c=document.getElementById("comment");
  if (c.value) {
    c.value += "\n\n";
  }
  c.value += com;
  c.focus();
}

function insertTag(startTag, endTag, tag) {
  var field = document.getElementById(tag);
  var startSelection   = field.value.substring(0, field.selectionStart);
  var currentSelection = field.value.substring(field.selectionStart, field.selectionEnd);
  var endSelection     = field.value.substring(field.selectionEnd);
  if (currentSelection == "") {
    currentSelection = "TEXT";
  }
  field.value = startSelection + startTag + currentSelection + endTag + endSelection;
  field.setSelectionRange(startSelection.length + startTag.length, startSelection.length + startTag.length + currentSelection.length);
  field.focus();
}
</script>


  <?php
      }
  ?>
  </div>
  <?php BlogPage::pagefooterTpl(); ?>
</div>
</body>
</html>
<?php
    }
}

class Blog
{
    // The file containing the data
    public $file = 'data.php';
    // TODO : remove in version 7
    public $menuFile = 'menu.php';
    public $menu = '';
    public $extra = '';

    // blog_conf object
    public $bc;

    private $_filtered = false;

    private $_data = array();

    public function __construct($dataFile, $menuFile, $bc)
    {
        $this->bc = $bc;
        $this->file = $dataFile;
        $this->menuFile = $menuFile;
    }

    public function loadData()
    {
        if (file_exists($this->file)) {
            $this->_data = unserialize(
                gzinflate(
                    base64_decode(
                        substr(
                            file_get_contents($this->file),
                            strlen(PHPPREFIX),
                            -strlen(PHPSUFFIX)))));
            $this->sortData();
        } else {
            $this->editEntry(
                'menu',
                'Menu',
                '<li><a href="?">Home</a></li>
<li><a href="?rss">RSS</a></li>
<li><a href="?login">Login</a></li>',
                0, 0, 'public');
            $this->editEntry(
                'extra',
                'Extra',
                '<span class="extratohide">extra menu</span>
<div class="extratoshow">
  <a href= "?">Home</a><br>
  <a href= "?rss">RSS</a><br>
  <a href= "?login">Login</a>
</div>',
                0, 0, 'public');
            $this->editEntry(
                time(),
                'Your simple and smart (or stupid) blog',
                'Welcome to your <a href="http://github.com/tontof/kriss_blog">blog</a>'.
                ' (want to learn more about wp:Blog ?)'."\n\n".
                '<a href="'.MyTool::getUrl().'?login">Login</a> and edit this entry to see a bit how this thing works.',
                $this->bc->comments, 0, 'public');
            $this->editEntry(
                time()+1,
                'Private : Your simple and smart (or stupid) blog',
                'This is a private article'
                . ' (want to learn more about wp:Blog ?)' . "\n\n"
                . 'Describe your entry here. HTML is <strong>allowed</strong>. URLs are automatically converted.'."\n\n"
                . 'You can use wp:Article to link to a wikipedia article. Or maybe, for an article in a specific language, '
                . 'try wp:lang:Article (eg. wp:nl:Homomonument).'."\n\n".'Try it !',
                $this->bc->comments, 1, 'private');

            if (!$this->writeData()) {
                die("Can't write to ".$this->file);
            }
        }
    }

    public function sortData()
    {
        if ($this->bc->reverseorder) {
            krsort($this->_data);
        } else {
            ksort($this->_data);
        }
    }

    public function writeData()
    {
        if (!$this->_filtered) {
            $out = PHPPREFIX.
                base64_encode(gzdeflate(serialize($this->_data))).
                PHPSUFFIX;

            if (!@file_put_contents($this->file, $out)) {
                return false;
            }

            return true;
        }

        return false;
    }

    // $which = menu or extra
    // TODO : remove in version 7
    public function getMenu($which)
    {
        switch ($which) {
        case 'menu':
            // Loading menu info
            if (file_exists($this->menuFile)) {
                include_once $this->menuFile;
            }
            $menuArray = explode('|', $this->menu);
            $menu = '';

            if (count($menuArray) > 0 && !empty($menuArray[0])) {
                for ($i = 0; $i < count($menuArray); $i++) {
                    $itemArray = explode(' ', $menuArray[$i], 2);
                    $menu .= '<li><a href="'.$itemArray[0].'">'.$itemArray[1].'</a></li>';
                }
            }

            $this->editEntry('menu', 'Menu', $menu, 0, 0, '');

            if (!$this->writeData()) {
                die("Can't write to ".$this->file);
            }

            return array('text' => $menu, 'private' => 0);
        case 'extra':
            // Loading menu info
            if (file_exists($this->menuFile)) {
                include_once $this->menuFile;
            }

            $extra = $this->extra;
            $extra = preg_replace('/<div id="extra">/', '', $extra, 1);
            $extra = preg_replace('/<\/div>/', '', $extra, 1);

            $this->editEntry('extra', 'Extra', $extra, 0, 0, '');

            if (!$this->writeData()) {
                die("Can't write to ".$this->file);
            }

            return array('text' => $extra, 'private' => 0);
        default:
            break;
        }

        die('error with menu');
    }

    public function getEntry($id)
    {
        if (!isset($this->_data[$id])) {
            if ($id === 'menu' || $id === 'extra') {
                return $this->getMenu($id);
            }

            return false;
        }

        if (Session::isLogged()) {
            return $this->_data[$id];
        } else {
            if ($this->_data[$id]['private']) {
                return false;
            } else {
                return $this->_data[$id];
            }
        }
    }

    public function filter($page = false)
    {
        $list = array();
        if (Session::isLogged()) {
            if (!empty($_SESSION['privateonly'])) {
                foreach ($this->_data as $id => $entry) {
                    if (is_numeric($id) != $page && (!empty($entry['private']) && $entry['private'] === 1)) {
                        $list[$id] = $entry;
                    }
                }
            } else {
                foreach ($this->_data as $id => $entry) {
                    if (is_numeric($id) != $page) {
                        $list[$id] = $entry;
                    }
                }
            }
        } else {
            $today = time();
            foreach ($this->_data as $id => $entry) {
                if (is_numeric($id) && !$page && ((empty($entry['private']) || $entry['private'] === 0) && $id <= $today)) {
                    $list[$id] = $entry;
                }
            }
        }
        $this->_filtered = true;
        $this->_data = $list;

        return $list;
    }

    public function getList($begin=0)
    {
        if (!$this->_filtered) {
            $this->filter();
        }

        return array_slice($this->_data, $begin, $this->bc->bypage, true);
    }

    public function getComments()
    {
        $comments = array();
        foreach ($this->_data as $id => $entry) {
            $i = 1;
            foreach (array_keys($entry["comments"]) as $time) {
                $link = MyTool::getUrl().'?'.MyTool::urlize($id, $entry['title']);
                $ecomments = array();
                $ecomments[$time]['author'] = $entry['comments'][$time][0];
                $ecomments[$time]['website'] = $entry['comments'][$time][1];
                $content = $entry['comments'][$time][2];
                // convert relative url in comments
                $ecomments[$time]['content'] = preg_replace('/@\[([^[]+)\|([^[]+)\]/is', '@[$1|'.$link.'$2]', $content);
                $ecomments[$time]['link'] = $link.'#'.$i;
                $comments += $ecomments;
                $i++;
            }
        }
        krsort($comments);

        return array_slice($comments, 0, $this->bc->bypage, true);
    }

    public function editEntry($id, $title, $text, $comment, $private, $tags)
    {
        $comments=array();
        if (!empty($this->_data[$id]["comments"])) {
            $comments=$this->_data[$id]["comments"];
        }
        $this->_data[$id] = array(
            "title" => $title,
            "text" => $text,
            "comments" => $comments,
            "comment" => $comment,
            "private" => $private,
            "tags" => $tags);
    }

    public function addComment($id, $pseudo, $site, $comment)
    {
        $entry = $this->getEntry($id);
        if (!$entry) {
            die("Can't find this entry. " . $entry);
        } else {
            if (isset($entry["comment"]) && $entry["comment"]) {
                $comments=$this->_data[$id]["comments"];
                $comments[time()]=array($pseudo,$site,$comment);
                $this->_data[$id]=array(
                    "title" => $entry['title'],
                    "text" => $entry['text'],
                    "comments" => $comments,
                    "comment" => $entry['comment'],
                    "private" => $entry['private'],
                    "tags" => $entry['tags']);
            } else {
                die("Comments not allowed for this entry.");
            }
        }
    }
    public function editComment($idE, $idC, $pseudo, $site, $comment)
    {
        $entry = $this->getEntry($idE);
        if (!$entry) {
            die("Can't find this entry.");
        } else {
            if (Session::isLogged() and !empty($entry["comments"][$idC])) {
                $comments=$this->_data[$idE]["comments"];
                $comments[$idC]=array($pseudo,$site,$comment);
                $this->_data[$idE]=array(
                    "title" => $entry['title'],
                    "text" => $entry['text'],
                    "comments" => $comments,
                    "comment" => $entry['comment'],
                    "private" => $entry['private'],
                    "tags" => $entry['tags']);
            } else {
                die("Can not edit this comment.");
            }
        }
    }

    public function deleteEntry($id)
    {
        unset($this->_data[$id]);
    }

    public function getPagination()
    {
        if (count($this->_data) <= $this->bc->bypage) {
            return false;
        }

        $pages = ceil(count($this->_data) / $this->bc->bypage);

        return $pages;
    }
}

class Captcha
{
    public $alphabet="";
    public $alphabetFont;
    public $colFont = 0;
    public $rowFont = 0;

    public function __construct(
        $alphaFont = array(
            'A' => " __ /  \\|__||  ||  |",
            'B' => " __ |  \\|__/|  \\|__/",
            'C' => " __ /  \\|   |   \\__/",
            'D' => " _  | \\ |  ||  ||_/ ",
            'E' => " ___|   |__ |   |___",
            'F' => " ___|   |__ |   |   ",
            'G' => " __ /  \\| _ |  |\\__/",
            'H' => "    |  ||__||  ||  |",
            'I' => " ___  |   |   |  _|_",
            'J' => " ___  |   |   | \\_/ ",
            'K' => "    |  /|_/ | \\ |  \\",
            'L' => "    |   |   |   |___",
            'M' => "    |  ||\\/||  ||  |",
            'N' => "    |  ||\\ || \\||  |",
            'O' => " __ /  \\|  ||  |\\__/",
            'P' => " __ |  \\|__/|   |   ",
            'Q' => " __ /  \\|  || \\|\\__\\",
            'R' => " __ |  \\|__/| \\ |  \\",
            'S' => " ___/   \\__    \\___/",
            'T' => " ___  |   |   |   | ",
            'U' => "    |  ||  ||  |\\__/",
            'V' => "    |  ||  |\\  / \\/ ",
            'W' => "    |  ||  ||/\\||  |",
            'X' => "    \\  / \\/  /\\ /  \\",
            'Y' => "    |  |\\__/   |\\__/",
            'Z' => "____   /  /  /  /___",
            '0' => " __ /  \\| /||/ |\\__/",
            '1' => "     /| / |   |  _|_",
            '2' => " __ /  \\  _/ /  /___",
            '3' => "____   / _/    \\___/",
            '4' => "      /  /  /_|_  | ",
            '5' => "____|   |__    \\___/",
            '6' => "      /  /_ /  \\\\__/",
            '7' => "____   / _/_ /  /   ",
            '8' => " __ /  \\\\__//  \\\\__/",
            '9' => " __ /  \\\\__/  /  /  ",
        ),
        $rowFont = 5
    )
    {
        $this->alphabetFont = $alphaFont;

        $keys = array_keys($this->alphabetFont);

        foreach ($keys as $k) {
            $this->alphabet .= $k;
        }

        if ($keys[0]) {
            $this->rowFont = $rowFont;
            $this->colFont = (int) strlen($this->alphabetFont[$keys[0]])/$this->rowFont;
        }
    }

    public function generateString($len = 7)
    {
        $i = 0;
        $str = '';
        while ($i < $len) {
            $str .= $this->alphabet[mt_rand(0, strlen($this->alphabet) - 1)];
            $i++;
        }

        return $str;
    }

    public function convertString($strIn)
    {
        $strOut="\n";
        $strOut.='<pre>';
        $strOut.="\n";
        $i=0;
        while ($i<$this->rowFont) {
            $j=0;
            while ($j<strlen($strIn)) {
                $strOut.= substr(
                    $this->alphabetFont[$strIn[$j]],
                    $i*$this->colFont,
                    $this->colFont)." ";
                $j++;
            }
            $strOut.= "\n";
            $i++;
        }
        $strOut.='</pre>';

        return $strOut;
    }
}

class MyTool
{
    private static $_instance;

    public static function stripslashesDeep($value)
    {
        return is_array($value)
            ? array_map(array(self::$_instance, 'stripslashesDeep'), $value)
            : stripslashes($value);
    }

    public static function initPhp()
    {
        if (!isset(self::$_instance)) {
            self::$_instance = new MyTool();
        }

        if (phpversion() < 5) {
            die("Argh you don't have PHP 5 !");
        }

        error_reporting(E_ALL);

        if (get_magic_quotes_gpc()) {
            $_POST = array_map(array(self::$_instance, 'stripslashesDeep'), $_POST);
            $_GET = array_map(array(self::$_instance, 'stripslashesDeep'), $_GET);
            $_COOKIE = array_map(array(self::$_instance, 'stripslashesDeep'), $_COOKIE);
        }

        ob_start('ob_gzhandler');
        register_shutdown_function('ob_end_flush');
    }

    public static function isUrl($url)
    {
        // http://neo22s.com/check-if-url-exists-and-is-online-php/
        $pattern='|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i';

        return preg_match($pattern, $url);
    }

    public static function isEmail($email)
    {
        $pattern = "/^[A-Z0-9._%-]+@[A-Z0-9.-]+\.[A-Z]{2, 4}$/i";

        return (preg_match($pattern, $email));
    }

    public static function formatBBCode($text)
    {
        $replace = array(
            '/\[m\](.+?)\[\/m\]/is'
            => '/* moderate */',
            '/\[b\](.+?)\[\/b\]/is'
            => '<strong>$1</strong>',
            '/\[i\](.+?)\[\/i\]/is'
            => '<em>$1</em>',
            '/\[s\](.+?)\[\/s\]/is'
            => '<del>$1</del>',
            '/\[u\](.+?)\[\/u\]/is'
            => '<span style="text-decoration: underline;">$1</span>',
            '/\[quote\](.+?)\[\/quote\]/is'
            => '<blockquote>$1</blockquote>',
            '/\[code\](.+?)\[\/code\]/is'
            => '<code>$1</code>',
            );
        $text = preg_replace(
            array_keys($replace),
            array_values($replace),
            $text
        );
        $text = preg_replace_callback(
            '/\[url\](.+?)\[\/url]/is',
            create_function(
                '$matches',
                'return MyTool::formatUrl($matches[1],$matches[1]);'
            ),
            $text
        );
        $text = preg_replace_callback(
            '/\[url=(\w+:\/\/[^\]]+)\](.+?)\[\/url]/is',
            create_function(
                '$matches',
                'return MyTool::formatUrl($matches[1],$matches[2]);'
            ),
            $text
        );
        $text = preg_replace_callback(
            '/\[([^[]+)\|([^[]+)\]/is',
            create_function(
                '$matches',
                'return MyTool::formatUrl($matches[2],$matches[1]);'
            ),
            $text
        );

        return $text;
    }

    public static function formatText($text)
    {
        $text = preg_replace_callback(
            '/<code_html>(.*?)<\/code_html>/is',
            create_function(
                '$matches',
                'return htmlspecialchars($matches[1]);'
            ),
            $text
        );
        $text = preg_replace_callback(
            '/<code_php>(.*?)<\/code_php>/is',
            create_function(
                '$matches',
                'return highlight_string("<?php $matches[1] ?>", true);'
            ),
            $text
        );
        $text = preg_replace_callback(
            '#(^|\s)([a-z]+://([^\s])*)(\s|$)#im',
            create_function(
                '$matches',
                'return "$matches[1]".MyTool::formatUrl($matches[2],$matches[2])."$matches[4]";'
            ),
            $text
        );


        $text = preg_replace('/<br \/>/is', '', $text);

        $text = preg_replace(
            '#(^|\s)wp:?([a-z]{2}|):([\w]+)#im',
            '\\1<a href="http://\\2.wikipedia.org/wiki/\\3">\\3</a>',
            $text
        );
        $text = str_replace(
            'http://.wikipedia.org/wiki/',
            'http://www.wikipedia.org/wiki/',
            $text
        );
        $text = str_replace('\wp:', 'wp:', $text);
        $text = str_replace('\http:', 'http:', $text);
        $text = MyTool::formatBBCode($text);
        $text = nl2br($text);

        return $text;
    }

    public static function formatUrl($link, $text)
    {
        return '<a href="'.htmlspecialchars($link).'">'.htmlspecialchars($text).'</a>';
    }

    public static function getUrl()
    {
        $https = (!empty($_SERVER['HTTPS'])
                  && (strtolower($_SERVER['HTTPS']) == 'on'))
            || $_SERVER["SERVER_PORT"] == '443'; // HTTPS detection.
        $serverport = ($_SERVER["SERVER_PORT"] == '80'
                       || ($https && $_SERVER["SERVER_PORT"] == '443')
                       ? ''
                       : ':' . $_SERVER["SERVER_PORT"]);

        return 'http' . ($https ? 's' : '') . '://'
            . $_SERVER["SERVER_NAME"] . $serverport . $_SERVER['SCRIPT_NAME'];

    }

    public static function rrmdir($dir)
    {
        if (is_dir($dir) && ($d = @opendir($dir))) {
            while (($file = @readdir($d)) !== false) {
                if ( $file == '.' || $file == '..' ) {
                    continue;
                } else {
                    unlink($dir . '/' . $file);
                }
            }
        }
    }

    public static function humanBytes($bytes)
    {
        $siPrefix = array( 'bytes', 'KB', 'MB', 'GB', 'TB', 'EB', 'ZB', 'YB' );
        $base = 1024;
        $class = min((int) log($bytes, $base), count($siPrefix) - 1);
        $val = sprintf('%1.2f', $bytes / pow($base, $class));

        return $val . ' ' . $siPrefix[$class];
    }

    public static function returnBytes($val)
    {
        $val  = trim($val);
        $last = strtolower($val[strlen($val)-1]);
        switch($last)
        {
        case 'g': $val *= 1024;
        case 'm': $val *= 1024;
        case 'k': $val *= 1024;
        }

        return $val;
    }

    public static function getMaxFileSize()
    {
        $sizePostMax   = MyTool::returnBytes(ini_get('post_max_size'));
        $sizeUploadMax = MyTool::returnBytes(ini_get('upload_max_filesize'));

        // Return the smaller of two:
        return min($sizePostMax, $sizeUploadMax);
    }

    public static function smallHash($text)
    {
        $t = rtrim(base64_encode(hash('crc32', $text, true)), '=');
        // Get rid of characters which need encoding in URLs.
        $t = str_replace('+', '-', $t);
        $t = str_replace('/', '_', $t);
        $t = str_replace('=', '@', $t);

        return $t;
    }

    public static function redirect($rurl = '')
    {
        if (empty($rurl)) {
            $rurl = (empty($_SERVER['HTTP_REFERER'])
                     ? MyTool::getUrl()
                     : $_SERVER['HTTP_REFERER']);

            if (!empty($_POST) && isset($_POST['returnurl'])) {
                $rurl = $_POST['returnurl'];
            }
            // prevent loop on login page
            if ($rurl === '' || strpos($rurl, '?login') !== false) {
                $rurl = MyTool::getUrl();
            }
        }

        header('Location: '.$rurl);
        exit();
    }

    public static function slugify($string)
    {
        // http://www.php.net/manual/fr/function.preg-replace.php#107112
        // Strip off multiple spaces
        $slug = preg_replace('/\s[\s]+/', '-', $string);
        // http://css-tricks.com/snippets/php/convert-accented-characters/#comment-164967
        $slug = htmlentities($slug, ENT_QUOTES, 'UTF-8');
        $slug = preg_replace('/&([a-zA-Z])(uml|acute|grave|circ|tilde|cedil|elig|ring|th|slash|zlig|horn);/', '$1', $slug);
        $slug = html_entity_decode($slug, ENT_QUOTES, 'UTF-8');
        // Strip off spaces and non-alpha-numeric
        $slug = preg_replace('/[\s\W]+/', '-', $slug);
        // Strip off the starting hyphens
        $slug = preg_replace('/^[\-]+/', '', $slug);
        // Strip off the ending hyphens
        $slug = preg_replace('/[\-]+$/', '', $slug);
        $slug = strtolower($slug);

        return $slug;
    }

    public static function urlize($time, $title)
    {
        return strftime('%Y/%m/%d/%H/%M/%S-', $time).MyTool::slugify($title);
    }

}

class PageBuilder
{
    private $tpl; // For lazy initialization

    private $pageClass;

    public $var = array();

    public function __construct($pageClass)
    {
        $this->tpl = false;
        $this->pageClass = $pageClass;
    }

    private function initialize()
    {
        $this->tpl = true;
        $ref = empty($_SERVER['HTTP_REFERER']) ? '' : $_SERVER['HTTP_REFERER'];
        $this->assign('referer', $ref);
    }

    // 
    public function assign($variable, $value = null)
    {
        if ($this->tpl === false) {
            $this->initialize(); // Lazy initialization
        }
        if (is_array($variable)) {
            $this->var += $variable;
        } else {
            $this->var[$variable] = $value;
        }
    }

    public function renderPage($page)
    {
        if ($this->tpl===false) {
            $this->initialize(); // Lazy initialization
        }
        $method = $page.'Tpl';
        if (method_exists($this->pageClass, $method)) {
            $classPage = new $this->pageClass;
            $classPage->init($this->var);
            ob_start();
            $classPage->$method();
            ob_end_flush();
        } else {
            die("renderPage does not exist: ".$page);
        }
    }
}

class Session
{
    public static $inactivityTimeout = 3600;

    private static $_instance;

    private function __construct()
    {
        // Force cookie path (but do not change lifetime)
        $cookie=session_get_cookie_params();
        // Default cookie expiration and path.
        session_set_cookie_params($cookie['lifetime'], dirname($_SERVER["SCRIPT_NAME"]).(dirname($_SERVER["SCRIPT_NAME"]) == '/' ? '' : '/'));
        // Use cookies to store session.
        ini_set('session.use_cookies', 1);
        // Force cookies for session  (phpsessionID forbidden in URL)
        ini_set('session.use_only_cookies', 1);
        if (!session_id()) {
            // Prevent php to use sessionID in URL if cookies are disabled.
            ini_set('session.use_trans_sid', false);
            session_name('kriss');
            session_start();
        }
    }

    public static function init()
    {
        if (!isset(self::$_instance)) {
            self::$_instance = new Session();
        }
    }

    private static function _allInfo()
    {
        $infos = $_SERVER["REMOTE_ADDR"];
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $infos.=$_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        if (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $infos.='_'.$_SERVER['HTTP_CLIENT_IP'];
        }
        $infos.='_'.$_SERVER['HTTP_USER_AGENT'];
        $infos.='_'.$_SERVER['HTTP_ACCEPT_LANGUAGE'];

        return sha1($infos);
    }

    public static function login (
        $login,
        $password,
        $loginTest,
        $passwordTest,
        $pValues = array())
    {
        if ($login == $loginTest && $password==$passwordTest) {
            // Generate unique random number to sign forms (HMAC)
            $_SESSION['uid'] = sha1(uniqid('', true).'_'.mt_rand());
            $_SESSION['info']=Session::_allInfo();
            $_SESSION['username']=$login;
            // Set session expiration.
            $_SESSION['expires_on']=time()+Session::$inactivityTimeout;

            foreach ($pValues as $key => $value) {
                $_SESSION[$key] = $value;
            }

            return true;
        }
        Session::logout();

        return false;
    }

    public static function logout()
    {
        unset($_SESSION['uid'], $_SESSION['info'], $_SESSION['expires_on']);
    }

    public static function isLogged()
    {
        if (!isset ($_SESSION['uid'])
            || $_SESSION['info']!=Session::_allInfo()
            || time()>=$_SESSION['expires_on']) {
            Session::logout();

            return false;
        }
        // User accessed a page : Update his/her session expiration date.
        if (time()+Session::$inactivityTimeout > $_SESSION['expires_on']) {
            $_SESSION['expires_on'] = time()+Session::$inactivityTimeout;
        }

        return true;
    }

    public static function getToken()
    {
        if (!isset($_SESSION['tokens'])) {
            $_SESSION['tokens']=array();
        }
        // We generate a random string and store it on the server side.
        $rnd = sha1(uniqid('', true).'_'.mt_rand());
        $_SESSION['tokens'][$rnd]=1;

        return $rnd;
    }

    public static function isToken($token)
    {
        if (isset($_SESSION['tokens'][$token])) {
            unset($_SESSION['tokens'][$token]); // Token is used: destroy it.

            return true; // Token is ok.
        }

        return false; // Wrong token, or already used.
    }
}//end class

// Check if php version is correct
MyTool::initPHP();
// Initialize Session
Session::init();
// Create Page Builder
$pb = new PageBuilder('BlogPage');
$pb->assign('version', BLOG_VERSION);
$pb->assign('blogurl', MyTool::getUrl());

$kbc = new BlogConf(CONFIG_FILE, BLOG_VERSION);
$kb = new Blog(DATA_FILE, MENU_FILE, $kbc);

bindtextdomain("blog.fr_FR", './'.DATA_DIR.'/'.LOCALE);
textdomain(DOMAIN.".".$kbc->locale);

$pb->assign('blogtitle', $kbc->title);
$pb->assign('blogdesc', $kbc->desc);
$pb->assign('bloglogin', $kbc->login);
$pb->assign('bloglanguage', substr($kbc->locale, 0, 2));

if (isset($_GET['login'])) {
// Login
    if (!empty($_POST['login'])
        && !empty($_POST['password'])
    ) {
        if (!Session::isToken($_POST['token'])) {
            die('Wrong token.');
        }
        if (Session::login(
            $kbc->login,
            $kbc->hash,
            $_POST['login'],
            sha1($_POST['password'].$_POST['login'].$kbc->salt)
        )) {
            if (!empty($_POST['longlastingsession'])) {
                // (31536000 seconds = 1 year)
                $_SESSION['longlastingsession'] = 31536000;
                $_SESSION['expires_on'] =
                    time() + $_SESSION['longlastingsession'];
                session_set_cookie_params($_SESSION['longlastingsession']);
            } else {
                session_set_cookie_params(0); // when browser closes
            }
            session_regenerate_id(true);

            MyTool::redirect();
        }
        die("Login failed !");
    } else {
        $pb->assign('pagetitle', 'Login - '.strip_tags($kbc->title));
        $pb->renderPage('login');
    }
} elseif (isset($_GET['logout'])) {
// Logout
    Session::logout();
    MyTool::redirect();
} elseif (isset($_GET['config']) && Session::isLogged()) {
// Config
    if (isset($_POST['save'])) {
        if (!Session::isToken($_POST['token'])) {
            die('Wrong token.');
        }
        $kbc->hydrate($_POST);

        if (!$kbc->write()) {
            die("Can't write to ".CONFIG_FILE);
        }
        MyTool::redirect();
    } elseif (isset($_POST['cancel'])) {
        MyTool::redirect();
    } else {
        $pb->assign('pagetitle', 'Config - '.strip_tags($kbc->title));
        $pb->assign('kbcsite', htmlspecialchars($kbc->site));
        $pb->assign('kbctitle', htmlspecialchars($kbc->title));
        $pb->assign('kbcdesc', htmlspecialchars($kbc->desc));
        $pb->assign('kbclocale', htmlspecialchars($kbc->locale));
        $pb->assign('kbcdateformat', htmlspecialchars($kbc->dateformat));
        $pb->assign('kbcbypage', (int) $kbc->bypage);
        $pb->assign('kbccomments', (int) $kbc->comments);
        $pb->assign('kbccache', (int) $kbc->cache);
        $pb->assign('kbcreverseorder', (int) $kbc->reverseorder);
        $pb->renderPage('config');
    }
} elseif (isset($_GET['edit'])) {
// Edit an entry
    if (Session::isLogged()) {
        $kb->loadData();
        if (isset($_POST['save'])) {
            if (!Session::isToken($_POST['token'])) {
                die('Wrong token.');
            }
            $id = $_GET['edit'];
            if (empty($id)) {
                $id = time();
            }
            if (is_numeric($id)) {
                if (!empty($_POST['date'])) {
                    $newDate = strtotime($_POST['date']);
                } else {
                    $newDate = time();
                }
                if ($newDate != $id && !empty($newDate)) {
                    $kb->deleteEntry($_GET['edit']);
                    $id = $newDate;
                }
            } else {
                $slug = MyTool::slugify($_POST['title']);
                if ($id != 'page') {
                    if ($id != $slug) {
                        $kb->deleteEntry($id);
                    }
                }
                $id = $slug;
            }

            $kb->editEntry(
                $id,
                $_POST['title'],
                $_POST['text'],
                $_POST['comments'],
                (isset($_POST['private']) ? 1 : 0),
                $_POST['tags']
            );

            if (!$kb->writeData()) {
                die("Can't write to ".$kb->file);
            }
            MyTool::redirect(MyTool::getUrl().'?'.$id);
        } elseif (isset($_POST['cancel'])) {
            MyTool::redirect();
        }

        $title ='';
        $text = '';
        $tags='';
        $comments = $kbc->comments;
        $private = 0;
        $date = '';
        if (!empty($_GET['edit'])) {
            $entry = $kb->getEntry($_GET['edit']);
            $title = $entry['title'];
            $text = $entry['text'];
            $tags = $entry['tags'];
            $comments = $entry['comment'];
            $private = $entry['private'];
            $date = date('Y-m-d H:i:s', (int) $_GET['edit']);
        }
        if (isset($_SESSION['autosave'])) {
            $title = $_SESSION['autosave']['title'];
            $text = $_SESSION['autosave']['text'];
            $tags = $_SESSION['autosave']['tags'];
            $date = $_SESSION['autosave']['date'];
            $comments = $_SESSION['autosave']['comments'];
            $private = $_SESSION['autosave']['private'];
            unset($_SESSION['autosave']);
        }

        $pb->assign('id', htmlspecialchars($_GET['edit']));
        $pb->assign('pagetitle', 'Edit - '.strip_tags($kbc->title));
        $pb->assign('entrytitle', htmlspecialchars($title));
        $pb->assign('entrytext', htmlspecialchars($text));
        $pb->assign('entrytags', htmlspecialchars($tags));
        $pb->assign('entrydate', htmlspecialchars($date));
        $pb->assign('entrycomments', (int) $comments);
        $pb->assign('entryprivate', (int) $private);
        $pb->renderPage('edit');
    } else {
        if (isset($_POST['save'])) {
            $_SESSION['autosave'] = array();
            $_SESSION['autosave']['title'] = $_POST['title'];
            $_SESSION['autosave']['text'] = $_POST['text'];
            $_SESSION['autosave']['tags'] = $_POST['tags'];
            $_SESSION['autosave']['date'] = $_POST['date'];
            $_SESSION['autosave']['comments'] = (int) $_POST['comments'];
            $_SESSION['autosave']['private'] = isset($_POST['private']) ? 1 : 0;
        }
        $pb->assign('pagetitle', 'Login - '.strip_tags($kbc->title));
        $pb->renderPage('login');
    }
} elseif (isset($_GET['delete']) && Session::isLogged()) {
// Delete an entry
    $kb->loadData();
    $kb->deleteEntry($_GET['delete']);
    if (!$kb->writeData()) {
        die("Can't write to ".$kb->file);
    }
    MyTool::redirect();
} elseif (isset($_GET['private']) && Session::isLogged()) {
// private only / all entries
    if (empty($_SESSION['privateonly'])) {
        $_SESSION['privateonly']=1; // See only private links
    } else {
        unset($_SESSION['privateonly']); // See all links
    }
    MyTool::redirect();
} elseif (isset($_GET['page'])) {
// Entries by page
    $kb->loadData();
    $pb->assign('menu', $kb->getEntry('menu'));
    $pb->assign('extra', $kb->getEntry('extra'));
    if (empty($_GET['page'])) {
        $pb->assign('entries', $kb->filter(true));
        $pb->assign('page', '');
        $pb->assign('pages', '');
        $pb->assign('list', true);
    } else {
        $page = (int) $_GET['page'];
        $begin = ($page - 1) * $kbc->bypage;
        $pb->assign('entries', $kb->getList($begin));
        $pb->assign('page', $page);
        $pb->assign('pages', $kb->getPagination());
    }
    $pb->assign('pagetitle', strip_tags($kbc->title));
    $pb->assign('dateformat', $kbc->dateformat);
    $pb->renderPage('index');
} elseif (isset($_GET['rss'])) {
// RSS articles or comments
    $kb->loadData();

    if (empty($_GET['rss'])) {
        // articles
        $kbc->reverseorder = true;
        $kb->sortData();
        $entries = $kb->getList();
        foreach ($entries as $id => $entry) {
            $entries[$id]['link']=MyTool::getUrl().'?'.MyTool::urlize($id, $entry['title']);
            $entries[$id]['content']=$entries[$id]['text'];
        }
        $pb->assign('entries', $entries);
        $pb->renderPage('rss');
    } elseif ($_GET['rss'] == 'comments') {
        // comments
        $entries = $kb->getComments();
        foreach ($entries as $id => $entry) {
            $entries[$id]['title']=$entries[$id]['author'];
        }
        $pb->assign('blogtitle', $kbc->title.' comments');
        $pb->assign('entries', $entries);
        $pb->renderPage('rss');
    } else {
        MyTool::redirect(MyTool::getUrl());
    }
} elseif (empty($_SERVER['QUERY_STRING'])) {
// Index page
    $kb->loadData();
    $page = 1;
    $begin = ($page - 1) * $kbc->bypage;
    $pb->assign('menu', $kb->getEntry('menu'));
    $pb->assign('extra', $kb->getEntry('extra'));
    $pb->assign('pagetitle', strip_tags($kbc->title));
    $pb->assign('entries', $kb->getList($begin));
    $pb->assign('page', $page);
    $pb->assign('pages', $kb->getPagination());
    $pb->assign('dateformat', $kbc->dateformat);
    $pb->renderPage('index');
} else {
// Permalink to an entry
    $kb->loadData();

    if (preg_match("/^(\d{4})\/(\d{2})\/(\d{2})\/(\d{2})\/(\d{2})\/(\d{2})-(.*)$/", $_SERVER['QUERY_STRING'], $matches) === 1) {
        $id=strtotime($matches[1].'/'.$matches[2].'/'.$matches[3].' '.$matches[4].':'.$matches[5].':'.$matches[6]);
    } else {
        $id = (int) $_SERVER['QUERY_STRING'];
    }

    $entry = $kb->getEntry($id);
    if (empty($entry)) {
        $id = preg_replace("[^A-Za-z0-9\-]", "", $_SERVER['QUERY_STRING']);
        if (Session::isLogged() || ($id != 'menu' && $id != 'extra')) {
            $entry = $kb->getEntry($id);
        }
    }

    if (isset($_POST['send']) || isset($_POST['preview'])) {
        $inputPseudo=htmlspecialchars($_POST['pseudo']);
        $inputComment=htmlspecialchars($_POST['comment']);
        $inputSite=htmlspecialchars($_POST['site']);
        if (empty($inputPseudo)) {
            $inputPseudo="<em>Anonymous</em>";
        }
        if (isset($_POST['captcha']) && empty($_POST['message'])) {
            $inputCaptcha=strtoupper(htmlspecialchars($_POST['captcha']));
            if ($_SESSION['captcha']==$inputCaptcha) {
                $_SESSION['captcha'] = 'human';
            }
        }

        if (!empty($inputComment)
            && isset($_POST['send'])
            && (Session::isLogged()
                || $_SESSION['captcha']=='human')
            && (empty($inputSite)
                || (!empty($inputSite)
                    && MyTool::isUrl($inputSite)))) {
            unset($_SESSION['captcha']);
            $_SESSION['pseudo'] = $inputPseudo;
            $_SESSION['site'] = $inputSite;
            $kb->addComment($id, $inputPseudo, $inputSite, $inputComment);
            if (!$kb->writeData()) {
                die("Can't write to ".$kb->file);
            }
            MyTool::redirect(MyTool::getUrl().'?'.$id);
        } else {
            if (isset($_POST["preview"])) {
                $kb->addComment($id, $inputPseudo, $inputSite, $inputComment);
            }
            $entry = $kb->getEntry($id); // update entry
            $pb->assign('pagetitle', $entry['title'] . " - " . strip_tags($kbc->title));
            $pb->assign('inputpseudo', empty($_POST['pseudo'])?'':$inputPseudo);
            $pb->assign('inputsite', $inputSite);
            $pb->assign('inputcomment', $inputComment);
        }
    } elseif (isset($_POST['edit']) && Session::isLogged()) {
        $kb->loadData();
        $inputPseudo=$_POST['pseudo'];
        $inputComment=$_POST['comment'];
        $inputSite=$_POST['site'];
        $ids=explode("_", $_SERVER['QUERY_STRING']);
        $kb->editComment($id, $ids[1], $inputPseudo, $inputSite, $inputComment);
        if (!$kb->writeData()) {
            die("Can't write to ".$kb->file);
        }
        MyTool::redirect(MyTool::getUrl().'?'.$id);
    } else {
        if (Session::isLogged()) {
            if (strpos($_SERVER['QUERY_STRING'], '_') !== false) {
                $ids=explode("_", $_SERVER['QUERY_STRING']);
                if (!empty($entry['comments'][$ids[1]])) {
                    $pb->assign('inputpseudo', $entry['comments'][$ids[1]][0]);
                    $pb->assign('inputsite', $entry['comments'][$ids[1]][1]);
                    $pb->assign('inputcomment', $entry['comments'][$ids[1]][2]);
                }
            } else {
                $pb->assign('inputpseudo', $kbc->login);
                $pb->assign('inputsite', $kbc->site);
            }
        } else {
            $pb->assign('inputpseudo', isset($_SESSION['pseudo']) ? $_SESSION['pseudo'] : '');
            $pb->assign('inputsite', isset($_SESSION['site']) ? $_SESSION['site'] : '');
        }
    }
    if (empty($_SESSION['captcha']) || isset($_SESSION['captcha']) && $_SESSION['captcha']!='human') {
        if (Session::isLogged()) {
            $_SESSION['captcha'] = 'human';
        } else {
            $captcha = new Captcha();
            $_SESSION['captcha'] = $captcha->generateString();
            $pb->assign('captcha', $captcha->convertString($_SESSION['captcha']));
        }
    }

    $pb->assign('pagetitle', $entry['title'].' - '.strip_tags($kbc->title));
    $pb->assign('menu', $kb->getEntry('menu'));
    $pb->assign('extra', $kb->getEntry('extra'));
    $pb->assign('dateformat', $kbc->dateformat);
    $pb->assign('id', $id);
    $pb->assign('entry', $entry);
    $pb->renderPage('entry');
}
