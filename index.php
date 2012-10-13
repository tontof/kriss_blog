<?php

define('CACHE_DIR', 'cache');
define('DATA_DIR', 'data');

define('DATA_FILE', DATA_DIR.'/data.php');
define('CONFIG_FILE', DATA_DIR.'/config.php');
define('MENU_FILE', DATA_DIR.'/menu.php');

define('STYLE_FILE', 'style.css');
define('BLOG_VERSION', 5);

define('PHPPREFIX', '<?php /* '); // Prefix to encapsulate data in php code.
define('PHPSUFFIX', ' */ ?>'); // Suffix to encapsulate data in php code.


class Blog_Conf
{
    private $_config_file = '';
    private $_menu_file = '';
    public $login = '';
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

    // Menu
    public $menu = '?rss RSS|?login Login';

    // Extra html in home and articles pages
    public $extra = '<div id="extra">
  <span class="extratohide">extra menu</span>
  <div class="extratoshow">
    <a href= "?">Home</a><br>
    <a href= "?rss">RSS</a><br>
    <a href= "?login">Login</a>
  </div>
</div>';

    // kriss_blog version
    public $version;

    public function __construct($config_file, $menu_file, $version)
    {
        $this->_config_file = $config_file;
        $this->_menu_file = $menu_file;
        $this->version = $version;

        // Loading user config
        if (file_exists($this->_config_file)) {
            require_once $this->_config_file;
        }
        else {
            $this->_install();
        }

        // Loading menu info
        if (file_exists($this->_menu_file)) {
            include_once $this->_menu_file;
        }


        // For translating things
        setlocale(LC_TIME, $this->locale);
    }

    private function _install()
    {
        if (!empty($_POST['setlogin']) && !empty($_POST['setpassword'])) {
            $this->setSalt(sha1(uniqid('',true).'_'.mt_rand()));
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
                    if (!@file_put_contents(
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

            if ($this->write($this->_config_file)) {
                echo '
<script language="JavaScript">
 alert("Your simple and smart (or stupid) blog is now configured. Enjoy !");
 document.location=window.location.href;
</script>';
            }
            else {
                echo '
<script language="JavaScript">
 alert("Error can not write config and data files.");
 document.location=window.location.href;
</script>';
            }
            Session::logout();
        }
        else {
            echo '
<h1>Blog installation</h1>
<form method="post" action="">
  <p><label>Login: <input type="text" name="setlogin" /></label></p>
  <p><label>Password: <input type="password" name="setpassword" /></label></p>
  <p><input type="submit" value="OK" class="submit" /></p>
</form>';
        }
        exit();
    }

    public function hydrate(array $donnees)
    {
        foreach ($donnees as $key => $value)
        {
            // get setter
            $method = 'set'.ucfirst($key);
            // if setter exists just call it
            if (method_exists($this, $method))
            {
                $this->$method($value);
            }
        }
    }
    public function setLogin($login)
    {
        $this->login=$login;
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
		if (!@mkdir(CACHE_DIR,0705)) {
		    die("Can't create ".CACHE_DIR);
		}
	    }
	    @chmod(CACHE_DIR,0705);
	    if (!is_file(CACHE_DIR.'/.htaccess')) {
		if (!@file_put_contents(
			CACHE_DIR.'/.htaccess',
			"Allow from none\nDeny from all\n")) {
		    die("Can't protect ".CACHE_DIR);
		}
	    }
	}
	else {
	    MyTool::rrmdir(CACHE_DIR);
	}
    }

    public function setReverseorder($reverseorder)
    {
        $this->reverseorder=$reverseorder;
    }

    public function setMenu($menu)
    {
        $this->menu = $menu;
    }

    public function setExtra($extra)
    {
        $this->extra = $extra;
    }

    public function write($file)
    {
        if ($file == $this->_config_file) {
            $data = array('login', 'hash', 'salt', 'title', 'desc', 'dateformat',
                          'locale', 'bypage', 'cache', 'comments', 'reverseorder');
        } elseif ($file == $this->_menu_file) {
            $data = array('menu', 'extra');
        } else {
            die("Unknown file");
        }

        $out = '<?php';
        $out.= "\n";

        foreach ($data as $key)
        {
            $value = strtr($this->$key, array('$' => '\\$', '"' => '\\"'));
            $out .= '$this->'.$key.' = "'.$value."\";\n";
        }

        $out.= '?>';

        if (!@file_put_contents($file, $out))
            return false;

        return true;
    }
}

class Blog_Page
{
  // Default stylesheet
  private $css = '<style>
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
  padding: .5em;
  font-size: .9em;
  color: #666;
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
';

  public function __construct($css_file) {
      // We allow the user to have its own stylesheet
      if (file_exists($css_file)) {
          $this->css = '<link rel="stylesheet" href="'.$css_file.'">';
      }
  }
    public function status()
    {
        return '<div id="status"><a href="http://github.com/tontof/kriss_blog">KrISS blog'
            . ' ' . BLOG_VERSION . '</a><span class="nomobile">'
            . ' - A simple and smart (or stupid) blog'
            . '</span>. By <a href="http://tontof.net">Tontof</a></div>';
    }

    public function loginPage()
    {
        $ref = '';
        if (isset($_SERVER['HTTP_REFERER'])) {
            $ref = $_SERVER['HTTP_REFERER'];
        }
        $token = Session::getToken();
        return <<<HTML
<div id="global">
<form method="post" action="?login" name="loginform">
  <fieldset>
  <legend>Welcome to KrISS blog</legend>
  <input type="hidden" name="returnurl" value="$ref">
  <input type="hidden" name="token" value="$token">
  <label for="login">Login: <input type="text" id="login" name="login" tabindex="1"/></label>
  <label for="password">Password: <input type="password" id="password" name="password" tabindex="2"/></label>
  <input type="checkbox" name="longlastingsession" id="longlastingsession" tabindex="3">
  <label for="longlastingsession">&nbsp;Stay signed in (Do not check on public computers)</label>
  <input type="submit" value="OK" class="submit" tabindex="4">
  </fieldset>
</form>
<div>
<script>
document.loginform.login.focus();
</script>
HTML;
    }

    public function rssCommentsPage($pb)
    {
        $list = $pb->getComments();
        $last_update = array_keys($list);
        $last_update = date(DATE_W3C, $last_update[0]);
        $str='<?xml version="1.0" encoding="UTF-8" ?>
<rdf:RDF
  xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
  xmlns:dc="http://purl.org/dc/elements/1.1/"
  xmlns:sy="http://purl.org/rss/1.0/modules/syndication/"
  xmlns:content="http://purl.org/rss/1.0/modules/content/"
  xmlns="http://purl.org/rss/1.0/">

  <channel rdf:about="'.MyTool::getUrl().'">
    <title>'.strip_tags(MyTool::formatText($pb->pc->title)).'</title>
    <link>'.MyTool::getUrl().'</link>
    <description>'.strip_tags(MyTool::formatText($pb->pc->desc)).'</description>
    <dc:language>'.substr($pb->pc->locale, 0, 2).'</dc:language>
    <dc:rights></dc:rights>
    <dc:creator>'.$pb->pc->login.'</dc:creator>
    <dc:date>'.$last_update.'</dc:date>
    <dc:source>kriss blog</dc:source>

    <sy:updatePeriod>daily</sy:updatePeriod>
    <sy:updateFrequency>1</sy:updateFrequency>
    <sy:updateBase>'.$last_update.'</sy:updateBase>

    <items>
      <rdf:Seq>
    ';
        $today=time();
        foreach ($list as $id=>$comment)
        {
            $str .= '    <rdf:li rdf:resource="'.$comment[3].'" />
    ';
        }
        $str .= '  </rdf:Seq>
    </items>
  </channel>
';
        foreach ($list as $id=>$comment)
        {
            $str .= 	 '
  <item rdf:about="'.$comment[3].'">
      <title>'.strip_tags($comment[0]).'</title>
      <link>'.$comment[3].'</link>
      <description><![CDATA['.MyTool::formatText($comment[2]).']]></description>
      <dc:date>'.date(DATE_W3C, $id).'</dc:date>
      <dc:language>'.substr($pb->pc->locale, 0, 2).'</dc:language>
      <dc:creator>'.$pb->pc->login.'</dc:creator>
      <dc:subject>Simple and smart (or stupid) blog</dc:subject>
      <content:encoded>
          <![CDATA['.MyTool::formatText($comment[2]).']]>
      </content:encoded>
  </item>';
        }

        $str .= '
</rdf:RDF>';

        return $str;
    }

    public function rssPage($pb)
    {
        $pb->pc->reverseorder = true;
        $pb->sortData();

        $list = $pb->getList();
        $last_update = array_keys($list);
        $last_update = date(DATE_W3C, $last_update[0]);

        $str='<?xml version="1.0" encoding="UTF-8" ?>
<rdf:RDF
  xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
  xmlns:dc="http://purl.org/dc/elements/1.1/"
  xmlns:sy="http://purl.org/rss/1.0/modules/syndication/"
  xmlns:content="http://purl.org/rss/1.0/modules/content/"
  xmlns="http://purl.org/rss/1.0/">

  <channel rdf:about="'.MyTool::getUrl().'">
    <title>'.strip_tags(MyTool::formatText($pb->pc->title)).'</title>
    <link>'.MyTool::getUrl().'</link>
    <description>'.strip_tags(MyTool::formatText($pb->pc->desc)).'</description>
    <dc:language>'.substr($pb->pc->locale, 0, 2).'</dc:language>
    <dc:rights></dc:rights>
    <dc:creator>'.$pb->pc->login.'</dc:creator>
    <dc:date>'.$last_update.'</dc:date>
    <dc:source>kriss blog</dc:source>

    <sy:updatePeriod>daily</sy:updatePeriod>
    <sy:updateFrequency>1</sy:updateFrequency>
    <sy:updateBase>'.$last_update.'</sy:updateBase>

    <items>
      <rdf:Seq>
    ';
        $today=time();
        foreach ($list as $id=>$content)
        {
            if (!((!empty($content['private']) and $content['private']!=0) or $id > $today)) {
                $str .= '    <rdf:li rdf:resource="'.MyTool::getUrl().'?'.$id.'" />
    ';
            }
        }
        $str .= '  </rdf:Seq>
    </items>
  </channel>
';
        foreach ($list as $id=>$content)
        {
            if (!((!empty($content['private']) and $content['private']!=0) or $id > $today)) {
                $str .= 	 '
  <item rdf:about="'.MyTool::getUrl().'?'.$id.'">
      <title>'.strip_tags(MyTool::formatText($content['title'])).'</title>
      <link>'.MyTool::getUrl().'?'.$id.'</link>
      <description><![CDATA['.MyTool::formatText($content['text']).']]></description>
      <dc:date>'.date(DATE_W3C, $id).'</dc:date>
      <dc:language>'.substr($pb->pc->locale, 0, 2).'</dc:language>
      <dc:creator>'.$pb->pc->login.'</dc:creator>
      <dc:subject>Simple and smart (or stupid) blog</dc:subject>
      <content:encoded>
          <![CDATA['.MyTool::formatText($content['text']).']]>
      </content:encoded>
  </item>';
            }
        }

        $str .= '
</rdf:RDF>';

        return $str;
    }
    public function htmlPage($title,$body)
    {
        return '<!DOCTYPE html>
<html>
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, user-scalable=yes" />
    <title>'.$title.'</title>
    '.$this->css.'
    <link rel="alternate" type="application/rss+xml" title="'.$title.' RSS" href="?rss">
    <link rel="alternate" type="application/rss+xml" title="'.$title.' RSS comments" href="?rss=comments">
  </head>
  <body>'.$body.'
  </body>
</html>';
    }

    public function editMenuPage($pc)
    {
        $ref = '';
        if (isset($_SERVER['HTTP_REFERER'])) {
            $ref = $_SERVER['HTTP_REFERER'];
        }
        $token = Session::getToken();
        $menuArray = explode('|', $pc->menu);

        $text = '';
        foreach ($menuArray as $item) {
            $text .= $item."\n";
        }

        $extra = $pc->extra;

        $str = '
    <div id="global">
      <div id="header">
        <h1 id="title">Edit menu</h1>
      </div>
      <div id="section">
        <form method="post" action="">
          <input type="hidden" name="returnurl" value="'.$ref.'">
          <input type="hidden" name="token" value="'.$token.'">
          <fieldset>
            <legend>Main menu</legend>
             - [URL] [text of the link]:<br>
          &nbsp;&nbsp; - Example of absolute URL: http://tontof.net Tontof website > <a href="http://tontof.net">Tontof website</a><br>
          &nbsp;&nbsp; - Example of relative URL: ?rss Link to the RSS > <a href="?rss">Link to the RSS</a><br>
            <textarea name="menu" cols="70" rows="20">'.htmlspecialchars($text).'</textarea><br>
          </fieldset>
          <fieldset>
            <legend>Extra menu</legend>
             - html added in home and articles pages:<br>
             <pre>
&lt;div id=&quot;extra&quot;&gt;
  &lt;span class=&quot;extratohide&quot;&gt;extra menu&lt;/span&gt;
  &lt;div class=&quot;extratoshow&quot;&gt;
    &lt;a href= &quot;?&quot;&gt;Home&lt;/a&gt;&lt;br&gt;
    &lt;a href= &quot;?rss&quot;&gt;RSS&lt;/a&gt;&lt;br&gt;
    &lt;a href= &quot;?login&quot;&gt;Login&lt;/a&gt;
  &lt;/div&gt;
&lt;/div&gt;
</pre>
            <textarea name="extra" cols="70" rows="20">'.htmlspecialchars($extra).'</textarea><br>
          </fieldset>
          <input type="submit" value="Cancel" name="cancel">
          <input type="submit" value="Save" name="save">
        </form><br>
      </div>
    </div>
';
        return $str;
    }

    public function configPage($pc) {
	return '
    <div id="global">
      <div id="header">
        <h1 id="title">Configuration (version '.$pc->version.')</h1>
        <h2 id="subtitle">Why don\'t you <a href="http://github.com/tontof/kriss_blog/">check for a new version</a> ?</h2>
      </div>
      <div id="section">
        <form method="post" action="">
          <fieldset>
            <legend>Blog information</legend>
            <label>- Blog title</label><br>
            <input type="text" name="title" value="'.htmlspecialchars($pc->title).'"><br>
            <label>- Blog description (HTML allowed)</label><br>
            <input type="text" name="desc" value="'.htmlspecialchars($pc->desc).'"><br>
          </fieldset>
          <fieldset>
            <legend>Language information</legend>
            <label>- Locale (eg. en_GB or fr_FR)</label><br>
            <input type="text" maxlength="5" name="locale" value="'.$pc->locale.'" /><br>
            <label>- Date format (<a href="http://php.net/strftime">strftime</a> format)</label><br>
            <input type="text" name="dateformat" value="'.$pc->dateformat.'"><br>
          </fieldset>
          <fieldset>
            <legend>Blog preferences</legend>
            <label>- Number of entries by page</label><br>
            <input type="text" maxlength="3" name="bypage" value="'.(int) $pc->bypage.'"><br>
            <label for="with_comm">- Comments</label><br>
            <input type="radio" id="with_comm" name="comments" value="1" '.($pc->comments ? 'checked="checked"' : '').' /><label for="with_comm"> Allow comments</label><br>
            <input type="radio" id="without_comm" name="comments" value="0" '.(!$pc->comments ? 'checked="checked"' : '').' /><label for="without_comm"> Disable comments</label><br>
            <label for="with_cache">- Cache</label><br>
            <input type="radio" id="with_cache" name="cache" value="1" '.($pc->cache ? 'checked="checked"' : '').' /><label for="with_cache"> Cache pages</label><br>
            <input type="radio" id="without_cache" name="cache" value="0" '.(!$pc->cache ? 'checked="checked"' : '').' /><label for="without_cache"> No cache</label><br>
            <label for="reverse">- Order of entries</label><br>
            <input type="radio" id="normalorder" name="reverseorder" value="0" '.(!$pc->reverseorder ? 'checked="checked"' : '').' /> <label for="normalorder">From the latest to the newest</label><br>
            <input type="radio" id="reverse" name="reverseorder" value="1" '.($pc->reverseorder ? 'checked="checked"' : '').' /><label for="reverseorder"> <strong>Reverse order:</strong> from the newest to the latest</label><br>
            <input type="submit" name="cancel" value="Cancel"/>
            <input type="submit" name="save" value="Save" />
          </fieldset>
        </form><br>
      </div>
    </div>';
    }
    public function editPage($pb) {
	$str ='
    <div id="global">
      <div id="header">
        <h1 id="title">'.MyTool::formatText($pb->pc->title).'</h1>
        <h2 id="subtitle">'.MyTool::formatText($pb->pc->desc).'</h2>
      </div>
      <div id="nav">
        <p>
          <a href="'.MyTool::getUrl().'">Home</a>';
	if (!empty($_SERVER['HTTP_REFERER'])) {
            $str .= ' | <a href="'.$_SERVER['HTTP_REFERER'].'">Back</a>';
	}
	$str .= '
        </p>
      </div>
      <div class="article">';
	$title ='';
	$text = '';
	$tags='';
    $comment = $pb->pc->comments;
	$private = 0;
	if (empty($_GET['edit'])) {
	    $str .= '
        <h3>New entry</h3>';
	    if ($pb->getArticleNumber() < 2) {
		$title = "New title";
		$text = "Describe your entry here. HTML is <strong>allowed</strong>. URLs are automatically converted.\n\n"
		    .   "You can use wp:Article to link to a wikipedia article. Or maybe, for an article in a specific language, "
		    .   "try wp:lang:Article (eg. wp:nl:Homomonument).\n\nTry it !";
	    }
	    $date = date('Y-m-d H:i:s');
	} else {
	    $str .=  '
        <h3>Edit '.(int)$_GET['edit'].'</h3>';
	    $entry = $pb->getEntry($_GET['edit']);
	    $title = $entry['title'];
	    $text = $entry['text'];
	    $tags = $entry['tags'];
        $comment = $entry['comment'];
	    $private = $entry['private'];
	    $date = date('Y-m-d H:i:s', (int)$_GET['edit']);
	}

    if (isset($_SESSION['autosave'])) {
        $title = $_SESSION['autosave']['title'];
        $text = $_SESSION['autosave']['text'];
        $tags = $_SESSION['autosave']['tags'];
        $date = $_SESSION['autosave']['date'];
        $comment = $_SESSION['autosave']['comment'];
        $private = $_SESSION['autosave']['private'];
        unset($_SESSION['autosave']);
    }

	$str .= '
        <form id="edit_form" method="post" class="edit" action="?edit='.(int)$_GET['edit'].'">
          <fieldset>
            <label for="f_title">Title</label><br>
            <input type="text" id="f_title" name="title" value="'.htmlspecialchars($title).'"><br>
            <textarea name="text" cols="70" rows="20">'.htmlspecialchars($text).'</textarea><br>
            <label for="f_tags">Tags</label><br>
            <input type="text" id="f_tags" name="tags" value="'.htmlspecialchars($tags).'"><br>
            <label for="f_date">Entry date</label><br>
            <input type="text" id="f_date" name="date" value="'.htmlspecialchars($date).'"><br>
            <label for="with_comm">Comments</label><br>
            <input type="radio" id="with_comm" name="comments" value="1" '.($comment ? 'checked="checked"' : '').'><label for="with_comm">Allow comments</label><br>
            <input type="radio" id="without_comm" name="comments" value="0" '.(!$comment ? 'checked="checked"' : '').'><label for="without_comm">Disable comments</label><br>
            <input type="checkbox" id="f_private" name="private" '.(($private==1)?'checked="checked"':'').' value="1">
            <label for="f_private">Private</label><br>
            <input type="submit" name="save" value="Post article">
          </fieldset>
        </form>
        <br>
      </div>
    </div>';
	return $str;
    }

    public function indexPage($pb,$page) {
	$begin = ($page - 1) * $pb->pc->bypage;
	$list = $pb->getList($begin);

    $menuArray = explode('|', $pb->pc->menu);
    $menu = '';

    if (count($menuArray) > 0 && !empty($menuArray[0])) {
        $itemArray = explode(' ', $menuArray[0], 2);
        $menu .= '<a href="'.$itemArray[0].'">'.$itemArray[1].'</a>';
        for($i = 1; $i < count($menuArray); $i++) {
            $itemArray = explode(' ', $menuArray[$i], 2);
            $menu .= ' | <a href="'.$itemArray[0].'">'.$itemArray[1].'</a>';
        }
    }

    $extra = $pb->pc->extra;

	$str = '
    <div id="global">
      '.$extra.'
      <div id="header">
        <h1 id="title">'.MyTool::formatText($pb->pc->title).'</h1>
        <h2 id="subtitle">'.MyTool::formatText($pb->pc->desc).'</h2>
      </div>
      <div id="nav">
        <p>
          '.$menu;
	if (Session::isLogged()) {
	    $str .= ' | <a href="?edit" class="admin">New entry</a> | <a href="?editmenu" class="admin">Edit menu</a> | <a href="?private" class="admin">Show Private</a> | <a href="?config" class="admin">Configuration</a> | <a href="?logout" class="admin">Logout</a>';
	}
	$str .= '
        </p>
      </div>
      <div id="section">';

	$pages = $pb->getPagination();
	if (!empty($pages))
	{
	    $str .= '
        <ul class="pagination">
        ';

	    for ($p = 1; $p <= $pages; $p++)
	    {
		$str .= '<li'.($page == $p ? ' class="selected"' : '').'><a href="?page='.$p.'">'.$p.'</a></li>';
	    }

	    $str .= '
        </ul>';
	}

	if (empty($list))
	    $str .= '<p>No item.</p>';
	else
	{
	    $today = time();
	    foreach ($list as $id=>$content)
	    {
		$str .= '
        <div class="article"'.((Session::isLogged() and ($content['private'] or $id>time()))?' style="border-color:red;"':'').'>
          <h3 class="title"><a href="?'.$id.'">'.$content['title'].'</a></h3>
          <h4 class="subtitle">'.((Session::isLogged() and $content['private'])?'(<em>private</em>)':'').' '.utf8_encode(strftime($pb->pc->dateformat, $id)).'</h4>
          <div class="content">
            '.MyTool::formatText($content['text']).'
          </div>
          <p class="link">
            <a href="?'.$id.'#comments">'.count($content['comments']).' comment(s)</a>';

		if (Session::isLogged())
		{
		    $str .= ' | <a href="?edit='.$id.'" class="admin">Edit</a> | <a href="?delete='.$id.'" class="admin" onclick="if (confirm(\'Sure?\') != true) return false;">Delete</a>';
		}
		$str .= '
          </p>
        </div>';
		}
	}

	$pages = $pb->getPagination();
	if (!empty($pages)) {
	    $str .= '
        <ul class="pagination">
          ';

	    for ($p = 1; $p <= $pages; $p++) {
		$str .= '<li'.($page == $p ? ' class="selected"' : '').'><a href="?page='.$p.'">'.$p.'</a></li>';
	    }

	    $str .= '
        </ul>';
	}
	$str .= '
      </div>
'.$this->status().'
    </div>';
	return $str;
    }
    public function entryPage($pb,$id,$cache=0,$input_pseudo='',$input_site='',$input_comment='') {
        $extra = $pb->pc->extra;

        $str = '
    <div id="global">
      '.$extra.'
      <div id="header">
        <h1 id="title">'.MyTool::formatText($pb->pc->title).'</h1>
        <h2 id="subtitle">'.MyTool::formatText($pb->pc->desc).'</h2>
      </div>
      <div id="nav">
        <p><a href="'.MyTool::getUrl().'">Home</a>';
	if (!empty($_SERVER['HTTP_REFERER'])) {
            $str .= ' |
           <a href="'.$_SERVER['HTTP_REFERER'].'">Back</a>';
	}
	$str .= '
        </p>
      </div>';
	$entry = $pb->getEntry($id);
	if (!$entry) {
	    $str .= "  <p>Can't find this entry.</p>";
	}
	else
	{
	    $str .= '
      <div class="article"'.((Session::isLogged() and ($entry['private'] or $id > time()))?' style="border-color:red;"':'').'>
        <h3 class="title">'.$entry['title'].'</h3>
        <h4 class="subtitle">'.utf8_encode(strftime($pb->pc->dateformat, $id)).'</h4>
        <div class="content">'.MyTool::formatText($entry['text']);

	    $str .= '
          <p class="link">
            <a href="?'.$id.'">Permalink</a>';

	    if (Session::isLogged())
	    {
		$str .= ' | <a href="?edit='.$id.'" class="admin">Edit</a> | <a href="?delete='.$id.'" class="admin" onclick="if (confirm(\'Sure?\') != true) return false;">Delete</a>';
	    }
	    $str .= '
          </p>
        </div>
      </div>';
	    $str .= '
      <div id="comments">
        <h3>Comments</h3>';
    	    $numComm = count($entry['comments']);
	    $i = 1;
	    foreach ($entry['comments'] as $key=>$comment) {
		$str .= '
        <div class="comment">';
		$str .= '
          <h4 id="'.$i.'">'.$i.' - <a href="#new_comment" onclick="reply('."'[b]@[".strip_tags($comment[0])."|#".$i."][/b]'".')">@</a> - ';
		if (MyTool::isUrl($comment[1])) {
		    $str .= '<a href="'.$comment[1].'">'.$comment[0].'</a>';
		}
		else {
		    $str .= $comment[0];
		}
		$str .= '
          </h4>
          <div class="content">
            '.MyTool::formatText($comment[2]).'
          </div>
          <p class="link">';
		if ($i==$numComm and isset($_POST['preview'])) {
		    $str .= '
            <strong>Preview</strong>';
		}
		else {
		    $str .= '
          '.utf8_encode(strftime($pb->pc->dateformat, $key));
		    if (Session::isLogged()) {
			$str .= ' | <a href="?'.$id.'_'.$key.'#new_comment" class="admin">Edit</a>';
		    }
		}
                $str .= '
          </p>
        </div>';
		$i++;
	    }

	    if (isset($entry['comment']) && $entry['comment']) {
		$str .= '
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
</script>';
		$str .= '
        <form id="new_comment" action="#new_comment" method="post">
          <fieldset>
            <legend>New comment</legend>
            <label for="pseudo">Pseudo</label><br>
            <input type="text" placeholder="pseudo (facultatif)" id="pseudo" name="pseudo" value="'.$input_pseudo.'"><br>
            <label for="site">Site</label><br>
            <input type="text" placeholder="site (facultatif)" id="site" name="site" value="'.$input_site.'" '.((!empty($input_site) and !MyTool::isUrl($input_site))?'style="border-color:red">':'>').'<br>
            <label for="comment">Comment</label><br>
            <textarea id="comment" name="comment" rows="10"'.((empty($input_comment) and isset($_POST['comment']))?' style="border-color:red">':'>').$input_comment.'</textarea>
            <p>
              <button onclick="insertTag(\'[b]\',\'[/b]\',\'comment\');" title="bold" type="button"><strong>b</strong></button><button onclick="insertTag(\'[i]\',\'[/i]\',\'comment\');" title="italic" type="button"><em>i</em></button><button onclick="insertTag(\'[u]\',\'[/u]\',\'comment\');" title="underline" type="button"><span style="text-decoration:underline;">u</span></button><button onclick="insertTag(\'[s]\',\'[/s]\',\'comment\');" title="strike through" type="button"><del>s</del></button><button onclick="insertTag(\'[\',\'|http://]\',\'comment\');" title="link" type="button">url</button><button onclick="insertTag(\'[quote]\',\'[/quote]\',\'comment\');" title="quote" type="button">&#171;&nbsp;&#187;</button><button onclick="insertTag(\'[code]\',\'[/code]\',\'comment\');" title="code" type="button">&#60;&#62;</button>
            </p><br>';
		if (!$cache
            && !Session::isLogged()
            && (!isset($_SESSION['captcha'])
                || $_SESSION['captcha']!='human')) {
		    $captcha = new Captcha();
		    $str .= '
            <label for="captcha">Captcha</label><br>';
		    $_SESSION['captcha']=$captcha->generateString();
		    $str .= $captcha->convertString($_SESSION['captcha']).'<br>';
		    $str .= '
            <input type="text" placeholder="Captcha" id="captcha" name="captcha"';
		    $str .= (isset($_POST['captcha']) && !isset($_POST['preview']))?' style="border-color:red">':'>'.'<br>';
		}
		if (strpos($_SERVER['QUERY_STRING'],'_') === false) {
		    $str .= '
            <input type="submit" value="Preview" name="preview">';
                if (!$cache) {
                    $str.='
            <input type="submit" value="Send" name="send">';
                }
        }
		else {
            if (Session::isLogged()) {
                $str.='
            <input type="submit" value="Edit" name="edit">';
		    }
		}

		$str .= '
          </fieldset>
        </form><br>';
	    } else {
		$str .= '
        <p>Comments are disabled for this entry.</p>';
	    }
	    $str .= '
      </div>';
	}
	$str .= $this->status().'
    </div>
';
	return $str;
    }
    public function loadCachePage($file) {
        if (file_exists($file)) {
            readfile($file);
            return true;
        }
        return false;
    }
    public function writeCachePage($file, $str) {
        ob_start();
        echo $str;
        $page = ob_get_contents();
        ob_end_clean();
        if (!@file_put_contents($file, $page)) {
            die("Can't write ".$file);
        }
    }
}

class Blog
{
    // The file containing the data
    public $file = 'data.php';

    // blog_conf object
    public $pc;

    private $_data = array();

    public function __construct($data_file, $pc)
    {
        $this->pc = $pc;
        $this->file = $data_file;
    }

    public function getArticleNumber()
    {
        return count($this->_data);
    }

    public function getTitle($id)
    {
        if (!isset($this->_data[(int)$id]))
            return "";

        return $this->_data[(int)$id]['title'];
    }

    public function keepPrivate()
    {
        foreach($this->_data as $id => $entry) {
            if ((empty($entry['private']) || $entry['private']!=1)) {
                $this->deleteEntry($id);
            }
        }
    }

    public function loadData($force = false)
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
            if (!Session::isLogged() && !$force) {
                $today=time();
                foreach($this->_data as $id => $entry) {
                    if ((!empty($entry['private']) and $entry['private']!=0) or $id > $today) {
                        $this->deleteEntry($id);
                    }
                }
            }
        }
        else {
            $this->editEntry(
                time(),
                'Your simple and smart (or stupid) blog',
                'Welcome to your <a href="http://github.com/tontof/kriss_blog">blog</a>'.
                ' (want to learn more about wp:Blog ?)'."\n\n".
                '<a href="'.MyTool::getUrl().'?login">Login</a> and edit this entry to see a bit how this thing works.',
                $this->pc->comments,0,'public');
            $this->editEntry(
                time()+1,
                'Private : Your simple and smart (or stupid) blog',
                'This is a private article'.
                ' (want to learn more about wp:Blog ?)'."\n\n",
                $this->pc->comments,1,'private');
            if (!$this->writeData())
                die("Can't write to ".$pb->file);

            header('Location: '.MyTool::getUrl());
            exit();
        }
    }

    public function sortData()
    {
        if ($this->pc->reverseorder)
            krsort($this->_data);
        else
            ksort($this->_data);
    }

    public function writeData()
    {
        $out = PHPPREFIX.
            base64_encode(gzdeflate(serialize($this->_data))).
            PHPSUFFIX;

        if (!@file_put_contents($this->file, $out))
            return false;

        return true;
    }

    public function getEntry($id)
    {
        if (!isset($this->_data[(int)$id]))
            return false;

        return $this->_data[(int)$id];
    }

    public function getList($begin=0)
    {
        $list = array_slice($this->_data, $begin, $this->pc->bypage, true);
        return $list;
    }

    public function getComments()
    {
        $comments=array();
        foreach($this->_data as $id => $entry) {
            $ecomments = $entry["comments"];
            $i = 1;
            foreach(array_keys($ecomments) as $time) {
                $ecomments[$time][] = MyTool::getUrl().'?'.$id.'#'.$i;
                $i++;
            }
            $comments = $comments + $ecomments;
        }
        krsort($comments);
        return array_slice($comments, 0, $this->pc->bypage, true);
    }

    public function editEntry($id, $title, $text, $comment, $private, $tags)
    {
        $comments=array();
        if (!empty($this->_data[(int)$id]["comments"])) {
            $comments=$this->_data[(int)$id]["comments"];
        }
        $this->_data[(int)$id] = array(
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
        if (!$entry)
            die("Can't find this entry. " . $entry);
        else
        {
            if (isset($entry["comment"]) && $entry["comment"]) {
                $comments=$this->_data[(int)$id]["comments"];
                $comments[time()]=array($pseudo,$site,$comment);
                $this->_data[(int)$id]=array(
                    "title" => $entry['title'],
                    "text" => $entry['text'],
                    "comments" => $comments,
                    "comment" => $entry['comment'],
                    "private" => $entry['private'],
                    "tags" => $entry['tags']);
            }
            else {
                die("Comments not allowed for this entry.");
            }
        }
    }
    public function editComment($idE, $idC, $pseudo, $site, $comment)
    {
        $entry = $this->getEntry($idE);
        if (!$entry)
            die("Can't find this entry.");
        else
        {
            if (Session::isLogged() and !empty($entry["comments"][$idC])) {
                $comments=$this->_data[(int)$idE]["comments"];
                $comments[$idC]=array($pseudo,$site,$comment);
                $this->_data[(int)$idE]=array(
                    "title" => $entry['title'],
                    "text" => $entry['text'],
                    "comments" => $comments,
                    "comment" => $entry['comment'],
                    "private" => $entry['private'],
                    "tags" => $entry['tags']);
            }
            else {
                die("Can not edit this comment.");
            }
        }
    }

    public function deleteEntry($id)
    {
        unset($this->_data[(int)$id]);
    }

    public function getPagination()
    {
        if (count($this->_data) <= $this->pc->bypage)
            return false;

        $pages = ceil(count($this->_data) / $this->pc->bypage);
        return $pages;
    }
}


class Captcha
{
    public $alphabet="";
    public $alphabet_font;
    public $col_font = 0;
    public $row_font = 0;

    public function __construct($alpha_font=array(
                                    'A'=>" _ |_|| |",
                                    'B'=>" _ |_)|_)",
                                    'C'=>" __|  |__",
                                    'D'=>" _ | \\|_/",
                                    'E'=>" __|__|__",
                                    'F'=>" __|_ |  ",
                                    'G'=>" __/ _\\_/",
                                    'H'=>"   |_|| |",
                                    'I'=>"___ | _|_",
                                    'J'=>"___ | _| ",
                                    'K'=>"   |_/| \\",
                                    'L'=>"   |  |__",
                                    'M'=>"_ _|||| |",
                                    'N'=>"__ | || |",
                                    'O'=>" _ / \\\\_/",
                                    'P'=>" _ |_||  ",
                                    'Q'=>" _ | ||_\\",
                                    'R'=>" _ |_|| \\",
                                    'S'=>" _ (_  _)",
                                    'T'=>"___ |  | ",
                                    'U'=>"   | ||_|",
                                    'V'=>"   \\ / v ",
                                    'W'=>"   \\ / w ",
                                    'X'=>"   \\_// \\",
                                    'Y'=>"   |_| _|",
                                    'Z'=>"___ / /__",
                                    '0'=>" _ |/||_|",
                                    '1'=>"    /|  |",
                                    '2'=>" _  _||_ ",
                                    '3'=>" _  _| _|",
                                    '4'=>"   |_|  |",
                                    '5'=>" _ |_  _|",
                                    '6'=>" _ |_ |_|",
                                    '7'=>" __  | / ",
                                    '8'=>" _ |_||_|",
                                    '9'=>" _ |_| _|"),
                                $row_font=3) {
        $this->alphabet_font = $alpha_font;

        $keys = array_keys($this->alphabet_font);

        foreach ($keys as $k) {
            $this->alphabet .= $k;
        }

        if ($keys[0]) {
            $this->row_font = $row_font;
            $this->col_font =
                (int)strlen($this->alphabet_font[$keys[0]])/$this->row_font;
        }
    }

    public function generateString($len=5) {
        $i=0;
        $str='';
        while ($i<$len) {
            $str.=$this->alphabet[mt_rand(0,strlen($this->alphabet)-1)];
            $i++;
        }
        return $str;
    }

    public function convertString($str_in) {
        $str_out="\n";
        $str_out.='<pre>';
        $str_out.="\n";
        $i=0;
        while($i<$this->row_font) {
            $j=0;
            while($j<strlen($str_in)) {
                $str_out.= substr($this->alphabet_font[$str_in[$j]],
                                  $i*$this->col_font,
                                  $this->col_font)." ";
                $j++;
            }
            $str_out.= "\n";
            $i++;
        }
        $str_out.='</pre>';
        return $str_out;
    }
}

class MyTool
{
    public static function initPHP()
    {
        if (phpversion() < 5) {
            die("Argh you don't have PHP 5 ! Please install it right now !");
        }

        error_reporting(E_ALL);

        if (get_magic_quotes_gpc()) {
            function stripslashes_deep($value)
            {
                return is_array($value)
                    ? array_map('stripslashes_deep', $value)
                    : stripslashes($value);
            }
            $_POST = array_map('stripslashes_deep', $_POST);
            $_GET = array_map('stripslashes_deep', $_GET);
            $_COOKIE = array_map('stripslashes_deep', $_COOKIE);
        }

        /* ob_start('ob_gzhandler');
         * register_shutdown_function('ob_end_flush'); */
    }
    public static function isUrl($url)
    {
        $pattern='|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i';
        return preg_match($pattern, $url);
    }

    public static function isEmail($mail)
    {
        $pattern = "/^[A-Z0-9._%-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$/i";
        return (preg_match($pattern, $mail));
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
	    '/\[url\](.+?)\[\/url]/is'
            => '<a href="$1">$1</a>',
	    '/\[url=(\w+:\/\/[^\]]+)\](.+?)\[\/url]/is'
            => '<a href="$1">$2</a>',
            '/\[quote\](.+?)\[\/quote\]/is'
            => '<blockquote>$1</blockquote>',
            '/\[code\](.+?)\[\/code\]/is'
            => '<code>$1</code>',
	    '/\[([^[]+)\|([^[]+)\]/is'
	    => '<a href="$2">$1</a>'
            );
        $text = preg_replace(array_keys($replace),array_values($replace),$text);
        return $text;
    }

    public static function formatText($text) {
        $text = preg_replace_callback(
            '/<code_html>(.*?)<\/code_html>/is',
            create_function(
                '$matches',
                'return htmlspecialchars($matches[1]);'),
            $text);
        $text = preg_replace_callback(
            '/<code_php>(.*?)<\/code_php>/is',
            create_function(
                '$matches',
                'return highlight_string("<?php $matches[1] ?>",true);'),
            $text);
        $text = preg_replace('/<br \/>/is','',$text);

        $text = preg_replace(
            '#(^|\s)([a-z]+://([^\s\w/]?[\w/])*)(\s|$)#im',
            '\\1<a href="\\2">\\2</a>\\4',
            $text);
        $text = preg_replace(
            '#(^|\s)wp:?([a-z]{2}|):([\w]+)#im',
            '\\1<a href="http://\\2.wikipedia.org/wiki/\\3">\\3</a>',
            $text);
        $text = str_replace(
            'http://.wikipedia.org/wiki/',
            'http://www.wikipedia.org/wiki/',
            $text);
        $text = str_replace('\wp:', 'wp:', $text);
        $text = str_replace('\http:', 'http:', $text);
        $text = MyTool::formatBBCode($text);
        $text = nl2br($text);
        return $text;
    }

    public static function getUrl()
    {
        $url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        $url = preg_replace('/([?&].*)$/', '', $url);
        return $url;
    }

    public static function rrmdir($dir) {
        if (is_dir($dir) && ($d = @opendir($dir))) {
	    while (($file = @readdir($d)) !== false) {
		if ( $file == '.' || $file == '..' ) {
		    continue;
		}
		else {
		    unlink($dir.'/'.$file);
		}
	    }
        }
    }

    //http://www.php.net/manual/fr/function.disk-free-space.php#103382
    public static function humanBytes($bytes) {
	$si_prefix = array( 'bytes', 'KB', 'MB', 'GB', 'TB', 'EB', 'ZB', 'YB' );
	$base = 1024;
	$class = min((int)log($bytes , $base) , count($si_prefix) - 1);
	return sprintf('%1.2f' , $bytes / pow($base,$class)) . ' ' . $si_prefix[$class];
    }

    // Convert post_max_size/upload_max_filesize (eg.'16M') parameters to bytes.
    public static function return_bytes($val)
    {
	$val = trim($val); $last=strtolower($val[strlen($val)-1]);
	switch($last)
	{
        case 'g': $val *= 1024;
        case 'm': $val *= 1024;
        case 'k': $val *= 1024;
	}
	return $val;
    }

    // http://sebsauvage.net/wiki/doku.php?id=php:shaarli
    // Try to determine max file size for uploads (POST).
    // Returns an integer (in bytes)
    public static function getMaxFileSize()
    {
	$size1 = MyTool::return_bytes(ini_get('post_max_size'));
	$size2 = MyTool::return_bytes(ini_get('upload_max_filesize'));
	// Return the smaller of two:
	return min($size1,$size2);
    }

    // http://sebsauvage.net/wiki/doku.php?id=php:shaarli
    /* Returns the small hash of a string
       eg. smallHash('20111006_131924') --> yZH23w
       Small hashes:
       - are unique (well, as unique as crc32, at last)
       - are always 6 characters long.
       - only use the following characters: a-z A-Z 0-9 - _ @
       - are NOT cryptographically secure (they CAN be forged)
    */
    function smallHash($text)
    {
	$t = rtrim(base64_encode(hash('crc32',$text,true)),'=');
	$t = str_replace('+','-',$t); // Get rid of characters which need encoding in URLs.
	$t = str_replace('/','_',$t);
	$t = str_replace('=','@',$t);
	return $t;
    }

}

class Session
{
    public static $inactivityTimeout = 3600;

    private static $_instance;

    private function __construct()
    {
        // Use cookies to store session.
        ini_set('session.use_cookies', 1);
        // Force cookies for session  (phpsessionID forbidden in URL)
        ini_set('session.use_only_cookies', 1);
        if (!session_id()) {
            // Prevent php to use sessionID in URL if cookies are disabled.
            ini_set('session.use_trans_sid', false);
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

MyTool::initPHP();
Session::init();

$pc = new Blog_Conf(CONFIG_FILE, MENU_FILE, BLOG_VERSION);
$pb = new Blog(DATA_FILE, $pc);
$pp = new Blog_Page(STYLE_FILE);

if (isset($_GET['login'])) {
// Login
    if (!empty($_POST['login'])
        && !empty($_POST['password'])
    ) {
        if (!Session::isToken($_POST['token'])) {
            die('Wrong token.');
        }
        if (Session::login(
            $pc->login,
            $pc->hash,
            $_POST['login'],
            sha1($_POST['password'].$_POST['login'].$pc->salt)
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

            $rurl = $_POST['returnurl'];
            if (empty($rurl) || strpos($rurl, '?login') !== false) {
                $rurl = MyTool::getUrl();
            }
            header('Location: '.$rurl);
            exit();
        }
        die("Login failed !");
    } else {
        echo $pp->htmlPage('Login', $pp->loginPage());
    }
} elseif (isset($_GET['logout'])) {
// Logout
    Session::logout();
    header('Location: '.MyTool::getUrl());
    exit();
} elseif (isset($_GET['config']) && Session::isLogged()) {
// Config
    if (isset($_POST['save'])) {
        $pc->hydrate($_POST);

        if (!$pc->write(CONFIG_FILE)) {
            die("Can't write to ".CONFIG_FILE);
        }

        header('Location: '.MyTool::getUrl());
        exit();
    } elseif (isset($_POST['cancel'])) {
        header('Location: '.MyTool::getUrl());
        exit();
    } else {
        echo $pp->htmlPage('Configuration', $pp->configPage($pc));
        exit();
    }
} elseif (isset($_GET['edit'])) {
// Edit an entry
    if (Session::isLogged()) {
        $pb->loadData();
        if (isset($_POST['save'])) {
            $id = (int) $_GET['edit'];

            if (empty($_GET['edit'])) {
                $id = time();
            }
            if (!empty($_POST['date'])) {
                $newDate = strtotime($_POST['date']);
            } else {
                $newDate = time();
            }
            if ((int) $newDate != (int) $_GET['edit'] && !empty($newDate)) {
                $pb->deleteEntry($_GET['edit']);
                $id = $newDate;
            }

            $pb->editEntry(
                $id,
                $_POST['title'],
                $_POST['text'],
                $_POST['comments'],
                (isset($_POST['private']) ? 1 : 0),
                $_POST['tags']
            );

            if (!$pb->writeData()) {
                die("Can't write to ".$pb->file);
            }

            if ($pc->cache) {
                if (file_exists(CACHE_DIR.'/rss.xml')) {
                    unlink(CACHE_DIR.'/rss.xml');
                }
                if (file_exists(CACHE_DIR.'/index.html')) {
                    unlink(CACHE_DIR.'/index.html');
                }
                if (file_exists(CACHE_DIR.'/'.$id.'.html')) {
                    unlink(CACHE_DIR.'/'.$id.'.html');
                }
            }
            header('Location: '.MyTool::getUrl().'?'.$id);
            exit();
        }
        echo $pp->htmlPage('Edit entry', $pp->editPage($pb));
        exit();
    } else {
        if (isset($_POST['save'])) {
            $_SESSION['autosave'] = array();
            $_SESSION['autosave']['title'] = htmlspecialchars($_POST['title']);
            $_SESSION['autosave']['text'] = htmlspecialchars($_POST['text']);
            $_SESSION['autosave']['tags'] = htmlspecialchars($_POST['tags']);
            $_SESSION['autosave']['date'] = htmlspecialchars($_POST['date']);
            $_SESSION['autosave']['comment'] =
                htmlspecialchars($_POST['comment']);
            $_SESSION['autosave']['private'] =
                htmlspecialchars($_POST['private']);
        }
        header('Location: '.MyTool::getUrl().'?login');
        exit();
    }
} elseif (isset($_GET['delete']) && Session::isLogged()) {
// Delete an entry
    $pb->loadData();
    $pb->deleteEntry($_GET['delete']);
    if (!$pb->writeData()) {
        die("Can't write to ".$pb->file);
    }

    if ($pc->cache) {
        if (file_exists(CACHE_DIR.'/rss.xml')) {
            unlink(CACHE_DIR.'/rss.xml');
        }
        if (file_exists(CACHE_DIR.'/index.html')) {
            unlink(CACHE_DIR.'/index.html');
        }
        if (file_exists(CACHE_DIR.'/'.$_GET['delete'].'.html')) {
            unlink(CACHE_DIR.'/'.$_GET['delete'].'.html');
        }
    }
    header('Location: '.MyTool::getUrl());
    exit();
} elseif (isset($_GET['private']) && Session::isLogged()) {
    $pb->loadData();
    $pb->keepPrivate();
    echo $pp->htmlPage(
        strip_tags(MyTool::formatText($pb->pc->title)),
        $pp->indexPage($pb, 1)
    );
} elseif (isset($_GET['editmenu']) && Session::isLogged()) {
    if (isset($_POST['save'])) {
        if (!Session::isToken($_POST['token'])) {
            die('Wrong token.');
        }

        $menu = rtrim(str_replace("\r\n", "|", $_POST['menu']), '|');

        $pb->pc->setExtra($_POST['extra']);
        $pb->pc->setMenu($menu);
        if (!$pb->pc->write(MENU_FILE)) {
            die("Can't write to ".MENU_FILE);
        }

        $rurl = $_POST['returnurl'];
        if (empty($rurl)) {
            $rurl = MyTool::getUrl();
        }

        header('Location: '.$rurl);
        exit;
    } elseif (isset($_POST['cancel'])) {
        $rurl = $_POST['returnurl'];
        if (empty($rurl)) {
            $rurl = MyTool::getUrl();
        }

        header('Location: '.$rurl);
        exit;
    } else {
        echo $pp->htmlPage(
            strip_tags(MyTool::formatText($pb->pc->title)),
            $pp->editMenuPage($pc)
        );
        exit();
    }
} elseif (isset($_GET['page'])) {
// Entries by page
    $pb->loadData();
    $page = (int) $_GET['page'];
    echo $pp->htmlPage(
        strip_tags(MyTool::formatText($pb->pc->title)),
        $pp->indexPage($pb, $page)
    );
    exit();
} elseif (isset($_GET['rss'])) {
// RSS articles or comments
    if (empty($_GET['rss'])) {
        // articles
        if ($pc->cache && $pp->loadCachePage(CACHE_DIR.'/rss.xml')) {
            exit();
        } else {
            $pb->loadData();
            $page = $pp->rssPage($pb);
            if ($pc->cache) {
                $pp->writeCachePage(CACHE_DIR.'/rss.xml', $page);
            }
            echo $page;
            exit();
        }
    } elseif ($_GET['rss'] == 'comments') {
        // comments
        if ($pc->cache && $pp->loadCachePage(CACHE_DIR.'/comments.xml')) {
            exit();
        } else {
            $pb->loadData();
            $page = $pp->rssCommentsPage($pb);
            if ($pc->cache) {
                $pp->writeCachePage(CACHE_DIR.'/comments.xml', $page);
            }
            echo $page;
            exit();
        }
    } else {
        header('Location: '.MyTool::getUrl());
        exit();
    }
} elseif (empty($_SERVER['QUERY_STRING'])) {
// Index page
    if (Session::isLogged()) {
        $pb->loadData();
        echo $pp->htmlPage(
            strip_tags(MyTool::formatText($pb->pc->title)),
            $pp->indexPage($pb, 1)
        );
        exit();
    } else {
        if ($pc->cache && $pp->loadCachePage(CACHE_DIR.'/index.html')) {
            exit();
        } else {
            $pb->loadData();
            $page= $pp->htmlPage(
                strip_tags(MyTool::formatText($pb->pc->title)),
                $pp->indexPage($pb, 1)
            );
            if ($pc->cache) {
                $pp->writeCachePage(CACHE_DIR.'/index.html', $page);
            }
            echo $page;
            exit();
        }
    }
} else {
// Permalink to an entry
    $id = (int) $_SERVER['QUERY_STRING'];

    if (isset($_POST['send']) || isset($_POST['preview'])) {
        $pb->loadData(true);
        $inputPseudo=htmlspecialchars($_POST['pseudo']);
        $inputComment=htmlspecialchars($_POST['comment']);
        $inputSite=htmlspecialchars($_POST['site']);
        if (empty($inputPseudo)) {
            $inputPseudo="<em>Anonymous</em>";
        }
        if (isset($_POST['captcha'])) {
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
            $pb->addComment($id, $inputPseudo, $inputSite, $inputComment);
            if (!$pb->writeData()) {
                die("Can't write to ".$pb->file);
            }

            if ($pc->cache) {
                if (file_exists(CACHE_DIR.'/index.html')) {
                    unlink(CACHE_DIR.'/index.html');
                }
                if (file_exists(CACHE_DIR.'/'.$id.'.html')) {
                    unlink(CACHE_DIR.'/'.$id.'.html');
                }
            }
            header('Location: '.MyTool::getUrl().'?'.$id);
            exit();
        } else {
            if (isset($_POST["preview"])) {
                $pb->addComment($id, $inputPseudo, $inputSite, $inputComment);
            }
            echo $pp->htmlPage(
                $pb->getTitle($id)
                . " | " . strip_tags(MyTool::formatText($pb->pc->title)),
                $pp->entryPage(
                    $pb,
                    $id,
                    0,
                    htmlspecialchars($_POST['pseudo']),
                    $inputSite,
                    $inputComment
                )
            );
            exit();
        }
    } elseif (isset($_POST['edit']) and Session::isLogged()) {
        $pb->loadData();
        $inputPseudo=$_POST['pseudo'];
        $inputComment=$_POST['comment'];
        $inputSite=$_POST['site'];
        $ids=explode("_", $_SERVER['QUERY_STRING']);
        $pb->editComment($id, $ids[1], $inputPseudo, $inputSite, $inputComment);
        if (!$pb->writeData()) {
            die("Can't write to ".$pb->file);
        }

        header('Location: '.MyTool::getUrl().'?'.$id);
        exit();
    } else {
        if (Session::isLogged()) {
            $pb->loadData();
            if (strpos($_SERVER['QUERY_STRING'], '_') === false) {
                echo $pp->htmlPage(
                    $pb->getTitle($id)
                    . " | " . strip_tags(MyTool::formatText($pb->pc->title)),
                    $pp->entryPage($pb, $id)
                );
            } else {
                $ids=explode("_", $_SERVER['QUERY_STRING']);
                $entry = $pb->getEntry($id);;
                if (!empty($entry['comments'][$ids[1]])) {
                    echo $pp->htmlPage(
                        $pb->getTitle($id)
                        . " | " . strip_tags(MyTool::formatText($pb->pc->title)),
                        $pp->entryPage(
                            $pb,
                            $id,
                            0,
                            $entry['comments'][$ids[1]][0],
                            $entry['comments'][$ids[1]][1],
                            $entry['comments'][$ids[1]][2]
                        )
                    );
                } else {
                    echo $pp->htmlPage(
                        $pb->getTitle($id)
                        . " | " . strip_tags(MyTool::formatText($pb->pc->title)),
                        $pp->entryPage($pb, $id)
                    );
                }
            }
            exit();
        } else {
            if ($pc->cache && $pp->loadCachePage(CACHE_DIR.'/'.$id.'.html')) {
                exit();
            } else {
                $pb->loadData();
                if ($pc->cache && $pb->getEntry($id)) {
                    $page = $pp->htmlPage(
                        strip_tags(MyTool::formatText($pb->pc->title)),
                        $pp->entryPage($pb, $id, 1)
                    );
                    $pp->writeCachePage(CACHE_DIR.'/'.$id.'.html', $page);
                    echo $page;
                    exit();
                } else {
                    echo $pp->htmlPage(
                        $pb->getTitle($id)
                        . " | " . strip_tags(MyTool::formatText($pb->pc->title)),
                        $pp->entryPage($pb, $id)
                    );
                    exit();
                }
            }
        }
    }
}
