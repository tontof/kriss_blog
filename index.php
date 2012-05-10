<?php
/**
 * kriss_blog simple and smart (or stupid) blogging tool
 * Copyleft (C) 2012 Tontof - http://tontof.net
 * picoBlog useless blogging tool
 * Copyleft (C) 2007-2010 BohwaZ - http://dev.kd2.org
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

define('DATA_FILE','data.php');
define('CONFIG_FILE','config.php');
define('STYLE_FILE','style.css');
define('CACHE_DIR','cache');
define('BLOG_VERSION',2);

define('PHPPREFIX','<?php /* '); // Prefix to encapsulate data in php code.
define('PHPSUFFIX',' */ ?>'); // Suffix to encapsulate data in php code.


class Blog_Conf
{
    private $_file = '';
    public $login = '';
    public $hash = '';
    public $salt = '';

    // Blog title
    public $title = "Kriss blog";

    // Blog description
    public $desc = "Simple and smart (or stupid) blog";

    // Blog locale
    public $locale = "en_GB";
    public $dateformat = "%A %d %B %Y at %H:%M";

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
    public $version = 0;

    public function __construct($config_file,$version)
    {
        $this->_file = $config_file;
        $this->version = $version;

        // Loading user config
        if (file_exists($this->_file)){
            require_once $this->_file;
        }
        else{
            $this->_install();
        }

        // For translating things
	setlocale(LC_TIME, $this->locale);
    }

    private function _install()
    {
        if (!empty($_POST['setlogin']) && !empty($_POST['setpassword'])){
            $this->setSalt(sha1(uniqid('',true).'_'.mt_rand()));
            $this->setLogin($_POST['setlogin']);
            $this->setHash($_POST['setpassword']);
            if ($this->write()){
                echo '
<script language="JavaScript">
 alert("Your simple and smart (or stupid) blog is now configured. Enjoy !");
 document.location=window.location.href;
</script>';
            }
            else{
                echo '
<script language="JavaScript">
 alert("Error can not write config and data files.");
 document.location=window.location.href;
</script>';
            }    
            Session::logout(); 
        }
        else{
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
	if ($this->comments!=$comments){
	    $this->comments=$comments;
	    MyTool::rrmdir(CACHE_DIR);
	}
    }

    public function setCache($cache)
    {
	$this->cache=$cache;
	if ($this->cache){
	    if (!is_dir(CACHE_DIR)) {
		if (!@mkdir(CACHE_DIR,0705)){
		    die("Can't create ".CACHE_DIR);
		}
	    }
	    @chmod(CACHE_DIR,0705);
	    if (!is_file(CACHE_DIR.'/.htaccess')) {
		if (!@file_put_contents(
			CACHE_DIR.'/.htaccess',
			"Allow from none\nDeny from all\n")){
		    die("Can't protect ".CACHE_DIR);
		}
	    } 
	}
	else{
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
		      'locale', 'bypage', 'cache', 'comments', 'reverseorder');
        $out = '<?php';
        $out.= "\n";

        foreach ($data as $key)
        {
            $value = strtr($this->$key, array('$' => '\\$', '"' => '\\"'));
            $out .= '$this->'.$key.' = "'.$value."\";\n";
        }

        $out.= '?>';

        if (!@file_put_contents($this->_file, $out))
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
  font: Arial, Helvetica, sans-serif;
  background: #eee;
  color: #000;
  width:800px;
  margin:auto;
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

input[type=text], textarea{
  border: 1px solid #000;
  margin: .2em 0;
  padding: .2em;
  font-family: Arial, Helvetica, sans-serif;
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

    public function __construct($css_file){
        // We allow the user to have its own stylesheet
	if (file_exists($css_file))
	    $this->css = '<link rel="stylesheet" href="'.$css_file.'">';
    }

    public function rssPage($pb){
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

	foreach ($list as $id=>$content)
	{
	    $str .= '    <rdf:li rdf:resource="'.MyTool::getUrl().'?'.$id.'" />
    ';
	}
	$str .= '  </rdf:Seq>
    </items>
  </channel>
';
	foreach ($list as $id=>$content)
	{
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
  </head>
  <body>'.$body.'
  </body>
</html>';
    }

    public function configPage($pc){
	return '
    <div id="global">
      <div id="header">
        <h1 id="title">Configuration (version '.$pc->version.')</h1>
        <h2 id="subtitle">Why don\'t you <a href="http://github.com/tontof/kriss_blog/">check for a new version</a> ?</h2>
      </div>
      <div id="section">
        <form method="post" action="">
          <fieldset>
            <legend>Blog informations</legend>
            <label>- Blog title</label><br>
            <input type="text" name="title" value="'.$pc->title.'"><br>
            <label>- Blog description (HTML allowed)</label><br>
            <input type="text" name="desc" value="'.$pc->desc.'"><br>
          </fieldset>
          <fieldset>
            <legend>Language informations</legend>
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
            <input type="radio" id="reverse" name="reverseorder" value="1" '.($pc->reverseorder ? 'checked="checked"' : '').' /><label for="reverseorder"><strong>Reverse order:</strong> from the newest to the latest</label><br>
            <input type="submit" name="cancel" value="Cancel"/>
            <input type="submit" name="save" value="Save" />
          </fieldset>
        </form><br>
      </div>
    </div>';
    }
    public function editPage($pb){
	$str ='
    <div id="global">
      <div id="header">
        <h1 id="title">'.MyTool::formatText($pb->pc->title).'</h1>
        <h2 id="subtitle">'.MyTool::formatText($pb->pc->desc).'</h2>
      </div>
      <div id="nav">
        <p>
          <a href="'.MyTool::getUrl().'">Home</a>';
	if (!empty($_SERVER['HTTP_REFERER'])){
            $str .= ' | <a href="'.$_SERVER['HTTP_REFERER'].'">Back</a>';
	}
	$str .= '
        </p>
      </div>
      <div class="article">';
	$title ='';
	$text = '';
	if (empty($_GET['edit'])){
	    $str .= '
        <h3>New entry</h3>';
	    if ($pb->getArticleNumber() < 2){
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
	    $date = date('Y-m-d H:i:s', (int)$_GET['edit']);
	}

	$str .= '
        <form id="edit_form" method="post" class="edit" action="?edit='.(int)$_GET['edit'].'">
          <fieldset>
            <label for="f_title">Title</label><br>
            <input type="text" id="f_title" name="title" value="'.$title.'"><br>
            <textarea name="text" cols="70" rows="20">'.$text.'</textarea><br>
            <label for="f_date">Entry date</label><br>
            <input type="text" id="f_date" name="date" value="'.$date.'"><br>
            <label for="with_comm">Comments</label><br>
            <input type="radio" id="with_comm" name="comments" value="1" '.($pb->pc->comments ? 'checked="checked"' : '').'><label for="with_comm">Allow comments</label><br>
            <input type="radio" id="without_comm" name="comments" value="0" '.(!$pb->pc->comments ? 'checked="checked"' : '').'><label for="without_comm">Disable comments</label><br>
            <input type="submit" name="save" value="Post article">
          </fieldset>
        </form>
        <br>
      </div>
    </div>';
	return $str;
    }
    public function indexPage($pb,$page){
	$begin = ($page - 1) * $pb->pc->bypage;
	$list = $pb->getList($begin);

	$str = '
    <div id="global">
      <div id="header">
        <h1 id="title">'.MyTool::formatText($pb->pc->title).'</h1>
        <h2 id="subtitle">'.MyTool::formatText($pb->pc->desc).'</h2>
      </div>
      <div id="nav">
        <p>
          <a href="?rss">RSS</a>';
	if (Session::isLogged()){
	    $str .= ' | <a href="?edit" class="admin">New entry</a> | <a href="?config" class="admin">Configuration</a> | <a href="?logout" class="admin">Logout</a>';
	}
	else{
	    $str .= ' | <a href="?login">login</a>';
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
        <div class="article">
          <h3 class="title"><a href="?'.$id.'">'.$content['title'].'</a></h3>
          <h4 class="subtitle">'.strftime($pb->pc->dateformat, $id).'</h4>
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
	if (!empty($pages)){
	    $str .= '
        <ul class="pagination">
          ';

	    for ($p = 1; $p <= $pages; $p++){
		$str .= '<li'.($page == $p ? ' class="selected"' : '').'><a href="?page='.$p.'">'.$p.'</a></li>';
	    }

	    $str .= '
        </ul>';
	}
	$str .= '
      </div>
    </div>';
	return $str;
    }
    public function entryPage($pb,$id,$cache=0,$input_pseudo='',$input_site='',$input_comment=''){
	$str = '
    <div id="global">
      <div id="header">
        <h1 id="title">'.MyTool::formatText($pb->pc->title).'</h1>
        <h2 id="subtitle">'.MyTool::formatText($pb->pc->desc).'</h2>
      </div>
      <div id="nav">
        <p><a href="'.MyTool::getUrl().'">Home</a>';
	if (!empty($_SERVER['HTTP_REFERER'])){
            $str .= ' |
           <a href="'.$_SERVER['HTTP_REFERER'].'">Back</a>';
	}
	$str .= '
        </p>
      </div>';
	$entry = $pb->getEntry($id);
	if (!$entry){
	    $str .= "  <p>Can't find this entry.</p>";
	}
	else
	{
	    $str .= '
      <div class="article">
        <h3 class="title">'.$entry['title'].'</h3>
        <h4 class="subtitle">'.strftime($pb->pc->dateformat, $id).'</h4>
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
	    foreach ($entry['comments'] as $key=>$comment){
		$str .= '
        <div class="comment">';
		$str .= '
          <h4 id="'.$i.'">'.$i.' - <a href="#new_comment" onclick="reply('."'[b]@[".strip_tags($comment[0])."|#".$i."][/b]'".')">@</a> - ';
		if (MyTool::isUrl($comment[1])){
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
		if ($i==$numComm and isset($_POST['preview'])){
		    $str .= '
            <strong>Preview</strong>';
		}
		else{
		    $str .= '
          '.strftime($pb->pc->dateformat, $key);
		    if (Session::isLogged()){
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
function reply(com){
  var c=document.getElementById("comment");
  if (c.value){
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
		if (!$cache){
		    $captcha = new Captcha();
		    $str .= '
            <label for="captcha">Captcha</label><br>';
		    $_SESSION['captcha']=$captcha->generateString();
		    $str .= $captcha->convertString($_SESSION['captcha']).'<br>';
		    $str .= '
            <input type="text" placeholder="Captcha" id="captcha" name="captcha"';
		    $str .= (isset($_POST['captcha']) and !isset($_POST['preview']))?' style="border-color:red">':'>'.'<br>';
		}
		if (strpos($_SERVER['QUERY_STRING'],'_') === false){
		    $str .= '
            <input type="submit" value="Preview" name="preview">';
		    if (!$cache){
			$str.='
            <input type="submit" value="Send" name="send">';
		    }
		}
		else{
		    $str.='
            <input type="submit" value="Edit" name="edit">';
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
	$str .= '
    </div>';
	return $str;
    }
    public function loadCachePage($file){
	if(file_exists($file)){
	    readfile($file);
	    return true;
	}
	return false;
    }
    public function writeCachePage($file, $str){
	ob_start();
	echo $str;
	$page = ob_get_contents();
	ob_end_clean();
	if (!@file_put_contents($file, $page)){
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

    public function loadData()
    {
        if (file_exists($this->file)){
            $this->_data = unserialize(
                gzinflate(
                    base64_decode(
                        substr(
                            file_get_contents($this->file),
                            strlen(PHPPREFIX),
                            -strlen(PHPSUFFIX)))));
            $this->sortData();
        }
	else{
	    $this->editEntry(
		time(),
		'Your simple and smart (or stupid) blog',
		'Welcome to your <a href="http://github.com/tontof/kriss_blog">blog</a>'.
		' (want to learn more about wp:Blog ?)'."\n\n".
		'<a href="'.MyTool::getUrl().'?login">Login</a> and edit this entry to see a bit how this thing works.',
		$this->pc->comments);
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

    public function editEntry($id, $title, $text, $comment)
    {
        $comments=array();
        if (!empty($this->_data[(int)$id]["comments"])){
            $comments=$this->_data[(int)$id]["comments"];
        }
        $this->_data[(int)$id] = array(
            "title" => $title,
            "text" => $text,
            "comments" => $comments,
	    "comment" => $comment);
    }

    public function addComment($id, $pseudo, $site, $comment)
    {
        $entry = $this->getEntry($id);
        if (!$entry)
            die("Can't find this entry.");
        else
        {
	    if (isset($entry["comment"]) && $entry["comment"]){
		$comments=$this->_data[(int)$id]["comments"];
		$comments[time()]=array($pseudo,$site,$comment);
		$this->_data[(int)$id]=array(
		    "title" => $entry['title'],
		    "text" => $entry['text'],
		    "comments" => $comments,
		    "comment" => $entry['comment']);
	    }
	    else{
		die("Comments not allowed for this entry.");
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


/**
 * Captcha management class
 * 
 * Features:
 * - Use an ASCII font by default but can be customized
 *   (letter width should be the same)
 * - generate random strings with 5 letters
 * - convert string into captcha using the ASCII font
 */

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
                                $row_font=3){
        $this->alphabet_font = $alpha_font;

        $keys = array_keys($this->alphabet_font);

        foreach ($keys as $k){
            $this->alphabet .= $k;
        }

        if ($keys[0]){
            $this->row_font = $row_font;
            $this->col_font =
                (int)strlen($this->alphabet_font[$keys[0]])/$this->row_font;
        }
    }

    public function generateString($len=5){
        $i=0;
        $str='';
        while ($i<$len){
            $str.=$this->alphabet[mt_rand(0,strlen($this->alphabet)-1)];
            $i++;
        }
        return $str;
    }

    public function convertString($str_in){
        $str_out="\n";
        $str_out.='<pre>';
        $str_out.="\n";
        $i=0;
        while($i<$this->row_font){
            $j=0;
            while($j<strlen($str_in)){
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
        if (phpversion() < 5){
            die("Argh you don't have PHP 5 ! Please install it right now !");
        }

        error_reporting(E_ALL);
    
        if (get_magic_quotes_gpc()){
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
        $pattern= "/^(https?:\/\/)(w{0}|w{3})\.?[A-Z0-9._-]+\.[A-Z]{2,3}\$/i";
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
	    '/\[([^ ]*?)\|(.*?)\]/is'
	    => '<a href="$2">$1</a>',
	    '/\[url\](.+?)\[\/url]/is'
            => '<a href="$1">$1</a>',
	    '/\[url=(\w+:\/\/[^\]]+)\](.+?)\[\/url]/is'
            => '<a href="$1">$2</a>',
            '/\[quote\](.+?)\[\/quote\]/is'
            => '<blockquote>$1</blockquote>',
            '/\[code\](.+?)\[\/code\]/is'
            => '<code>$1</code>'
            );
        $text = preg_replace(array_keys($replace),array_values($replace),$text);
        return $text;
    }

    public static function formatText($text){
        $text = preg_replace_callback(
            '/<php>(.*?)<\/php>/is',
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
		if( $file == '.' || $file == '..' ){
		    continue;
		}
		else{
		    unlink($dir.'/'.$file);
		}
	    }
        }
    }
}


/**
 * Session management class
 * http://www.developpez.net/forums/d51943/php/langage/sessions/
 * http://sebsauvage.net/wiki/doku.php?id=php:session
 * http://sebsauvage.net/wiki/doku.php?id=php:shaarli
 *
 * Features:
 * - Everything is stored on server-side (we do not trust client-side data,
 *   such as cookie expiration)
 * - IP addresses + user agent are checked on each access to prevent session
 *   cookie hijacking (such as Firesheep)
 * - Session expires on user inactivity (Session expiration date is
 *   automatically updated everytime the user accesses a page.)
 * - A unique secret key is generated on server-side for this session
 *   (and never sent over the wire) which can be used
 *   to sign forms (HMAC) (See $_SESSION['uid'] )
 * - Token management to prevent XSRF attacks.
 * 
 * TODO:
 * - log login fail
 * - prevent brute force (ban IP)
 *
 * HOWTOUSE:
 * - Just call Session::initSession(); to initialize session and
 *   check if connected with Session::isLogged()
 */

class Session
{  
    // If the user does not access any page within this time,
    // his/her session is considered expired (in seconds).
    public static $inactivity_timeout = 3600;
    private static $_instance;
 
    // constructor
    private function __construct()
    {
        // Use cookies to store session.
        ini_set('session.use_cookies', 1);
        // Force cookies for session  (phpsessionID forbidden in URL)
        ini_set('session.use_only_cookies', 1);
        if (!session_id()){
            // Prevent php to use sessionID in URL if cookies are disabled.
            ini_set('session.use_trans_sid', false);
            session_start(); 
        }
    } 

    // initialize session
    public static function init()
    {
        if (!isset(self::$_instance)) {
            self::$_instance = new Session();
        }
    }

    // Returns the IP address, user agent and language of the client
    // (Used to prevent session cookie hijacking.)
    private static function _allInfos()
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
 
    // Check that user/password is correct and init some SESSION variables.
    public static function login($login,$password,$login_test,$password_test,
                                 $pValues = array())
    {
        foreach ($pValues as $key => $value) { 
            $_SESSION[$key] = $value; 
        } 
        if ($login==$login_test && $password==$password_test){
            // generate unique random number to sign forms (HMAC)
            $_SESSION['uid'] = sha1(uniqid('',true).'_'.mt_rand());
            $_SESSION['info']=Session::_allInfos(); 
            $_SESSION['username']=$login;
            // Set session expiration.
            $_SESSION['expires_on']=time()+Session::$inactivity_timeout;
            return true;
        }
        return false;
    }
 
    // Force logout
    public static function logout()
    {  
        session_unset(); 
        session_destroy();
    } 

    // Make sure user is logged in.
    public static function isLogged()
    {
        if (!isset ($_SESSION['uid'])
            || $_SESSION['info']!=Session::_allInfos()
            || time()>=$_SESSION['expires_on']){
            return false;
        }
        // User accessed a page : Update his/her session expiration date.
        $_SESSION['expires_on']=time()+Session::$inactivity_timeout;  
        return true;
    }

    // Returns a token.
    public static function getToken()
    {
        if (!isset($_SESSION['tokens'])){
            $_SESSION['tokens']=array();
        }
        // We generate a random string and store it on the server side.
        $rnd = sha1(uniqid('',true).'_'.mt_rand());
        $_SESSION['tokens'][$rnd]=1;  
        return $rnd;
    }

    // Tells if a token is ok. Using this function will destroy the token.
    // return true if token is ok.
    public static function isToken($token)
    {
        if (isset($_SESSION['tokens'][$token]))
        {
            unset($_SESSION['tokens'][$token]); // Token is used: destroy it.
            return true; // Token is ok.
        }
        return false; // Wrong token, or already used.
    }
}


MyTool::initPHP();
Session::init();
$pc = new Blog_Conf(CONFIG_FILE, BLOG_VERSION);
$pb = new Blog(DATA_FILE, $pc);
$pp = new Blog_Page(STYLE_FILE);

///////////////////////////////////// PAGES ////////////////////////////////////
// (only if i'm in blog)

if (!defined('FROM_EXTERNAL') || !FROM_EXTERNAL){
    if (isset($_GET['login'])){
// Login
	if (!empty($_POST['login'])
	    && !empty($_POST['password'])){
	    if (Session::login(
		    $pc->login,
		    $pc->hash,
		    $_POST['login'],
		    sha1($_POST['password'].$_POST['login'].$pc->salt))){
		header('Location: '.MyTool::getUrl());
		exit();
	    }
	    die("Login failed !");
	}
	else {
	    echo '
<h1>Login</h1>
<form method="post" action="?login">
  <p><label>Login: <input type="text" name="login" /></label></p>
  <p><label>Password: <input type="password" name="password" /></label></p>
  <p><input type="submit" value="OK" class="submit" /></p>
</form>';
	}
    } elseif (isset($_GET['logout'])){
// Logout
	Session::logout();
	header('Location: '.MyTool::getUrl());
	exit(); 
    } elseif (isset($_GET['config']) && Session::isLogged()) {
// Config
	if (isset($_POST['save'])){
	    $pc->hydrate($_POST);
		
	    if (!$pc->write())
		die("Can't write to ".CONFIG_FILE);
		
	    header('Location: '.MyTool::getUrl());
	    exit();
	} elseif (isset($_POST['cancel'])) {
	    header('Location: '.MyTool::getUrl());
	    exit();
	} else {
	    echo $pp->htmlPage('Configuration',$pp->configPage($pc));
	    exit();
	}
    } elseif (isset($_GET['edit']) && Session::isLogged()){
// Edit an entry
	$pb->loadData();
	if (isset($_POST['save'])){
	    $id = (int) $_GET['edit'];

	    if (empty($_GET['edit']))
		$id = time();
		
	    if (!empty($_POST['date'])){
		$new_date = strtotime($_POST['date']);
	    }
	    else{
		$new_date = time();
	    }
	    if ((int)$new_date != (int)$_GET['edit'] && !empty($new_date)){
		$pb->deleteEntry($_GET['edit']);
		$id = $new_date;
	    }

	    $pb->editEntry(
		$id,
		$_POST['title'],
		$_POST['text'],
		$_POST['comments']);
	    if (!$pb->writeData())
		die("Can't write to ".$pb->file);

	    if ($pc->cache){
		if(file_exists(CACHE_DIR.'/rss.xml')){
		    unlink(CACHE_DIR.'/rss.xml');
		}
		if(file_exists(CACHE_DIR.'/index.html')){
		    unlink(CACHE_DIR.'/index.html');
		}
		if(file_exists(CACHE_DIR.'/'.$id.'.html')){
		    unlink(CACHE_DIR.'/'.$id.'.html');
		}
	    }

	    header('Location: '.MyTool::getUrl().'?'.$id);
	    exit();
	}
	echo $pp->htmlPage('Edit entry',$pp->editPage($pb));
	exit();
    } elseif (isset($_GET['delete']) && Session::isLogged()) {
// Delete an entry
	$pb->loadData();
	$pb->deleteEntry($_GET['delete']);
	if (!$pb->writeData())
	    die("Can't write to ".$pb->file);
        
	if ($pc->cache){
	    if(file_exists(CACHE_DIR.'/rss.xml')){
		unlink(CACHE_DIR.'/rss.xml');
	    }
	    if(file_exists(CACHE_DIR.'/index.html')){
		unlink(CACHE_DIR.'/index.html');
	    }
	    if(file_exists(CACHE_DIR.'/'.$_GET['delete'].'.html')){
		unlink(CACHE_DIR.'/'.$_GET['delete'].'.html');
	    }
	}
	header('Location: '.MyTool::getUrl());
	exit();
    } elseif (isset($_GET['page'])) {
// Entries by page
	$pb->loadData();
	$page = (int)$_GET['page'];
	echo $pp->htmlPage(strip_tags(MyTool::formatText($pb->pc->title)),$pp->indexPage($pb,$page));
	exit();
    } elseif (isset($_GET['rss'])){
// RSS in cache
	if ($pc->cache && $pp->loadCachePage(CACHE_DIR.'/rss.xml')){
	    exit();
	}
	else{
	    $pb->loadData();
	    $page = $pp->rssPage($pb);
	    if ($pc->cache){
		$pp->writeCachePage(CACHE_DIR.'/rss.xml', $page);
	    }
	    echo $page;
	    exit();
	}
    } elseif (empty($_SERVER['QUERY_STRING'])){
// Index page
	if (Session::isLogged()){
	    $pb->loadData();
	    echo $pp->htmlPage(strip_tags(MyTool::formatText($pb->pc->title)),$pp->indexPage($pb,1));
	    exit();
	}
	else {
	    if ($pc->cache && $pp->loadCachePage(CACHE_DIR.'/index.html')){
		exit();
	    }
	    else{
		$pb->loadData();
		$page= $pp->htmlPage(strip_tags(MyTool::formatText($pb->pc->title)),$pp->indexPage($pb,1));
		if ($pc->cache){
		    $pp->writeCachePage(CACHE_DIR.'/index.html', $page);
		}
		echo $page;
		exit();
	    }
	}
    } else {
// Permalink to an entry
	$id = (int)$_SERVER['QUERY_STRING'];

	if (isset($_POST['send']) || isset($_POST['preview'])){
	    $pb->loadData();
	    $input_pseudo=htmlspecialchars($_POST['pseudo']);
	    $input_comment=htmlspecialchars($_POST['comment']);
	    $input_site=htmlspecialchars($_POST['site']);
	    if (empty($input_pseudo)){
		$input_pseudo="<em>Anonymous</em>";
	    }
	    if (isset($_POST['send'])){
		$input_captcha=strtoupper(htmlspecialchars($_POST['captcha']));
	    }	    
		
	    if (!empty($input_comment)
		&& $_SESSION['captcha']==$input_captcha
		&& (empty($input_site)
		    || (!empty($input_site)
			&& MyTool::isUrl($input_site)))){
		    
		$pb->addComment($id,$input_pseudo,$input_site,$input_comment);
		if (!$pb->writeData())
		    die("Can't write to ".$pb->file);

		if ($pc->cache){
		    if(file_exists(CACHE_DIR.'/index.html')){
			unlink(CACHE_DIR.'/index.html');
		    }
		    if(file_exists(CACHE_DIR.'/'.$id.'.html')){
			unlink(CACHE_DIR.'/'.$id.'.html');
		    }
		}
		header('Location: '.MyTool::getUrl().'?'.$id);
		exit();
	    }
	    else{
		if (isset($_POST["preview"])){
		    $pb->addComment($id,$input_pseudo,$input_site,$input_comment);
		}
		echo $pp->htmlPage(strip_tags(MyTool::formatText($pb->pc->title)),$pp->entryPage($pb,$id,0,htmlspecialchars($_POST['pseudo']),$input_site,$input_comment));
		exit();     
	    }
	} 
	else {
	    if (Session::isLogged()){
		$pb->loadData();
		if (strpos($_SERVER['QUERY_STRING'],'_') === false){
		    echo $pp->htmlPage(strip_tags(MyTool::formatText($pb->pc->title)),$pp->entryPage($pb,$id));
		}
		else{
		    $ids=explode("_",$_SERVER['QUERY_STRING']);
		    $entry = $pb->getEntry($id);;
		    if (!empty($entry['comments'][$ids[1]])){
			echo $pp->htmlPage(strip_tags(MyTool::formatText($pb->pc->title)),$pp->entryPage($pb,$id,0,$entry['comments'][$ids[1]][0],$entry['comments'][$ids[1]][1],$entry['comments'][$ids[1]][2]));
		    }
		    else{
			echo $pp->htmlPage(strip_tags(MyTool::formatText($pb->pc->title)),$pp->entryPage($pb,$id));
		    }
		}
		exit();
	    }
	    else {
		if ($pc->cache && $pp->loadCachePage(CACHE_DIR.'/'.$id.'.html')){
		    exit();
		} else {
		    $pb->loadData();
		    if ($pc->cache && $pb->getEntry($id)){
			$page = $pp->htmlPage(strip_tags(MyTool::formatText($pb->pc->title)),$pp->entryPage($pb,$id,1));
			$pp->writeCachePage(CACHE_DIR.'/'.$id.'.html', $page);
			echo $page;
			exit();
		    }
		    else{
			echo $pp->htmlPage(strip_tags(MyTool::formatText($pb->pc->title)),$pp->entryPage($pb,$id));
			exit();
		    }
		}   
	    } 
	}
    }
}

?>
