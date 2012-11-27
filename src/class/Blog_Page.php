<?php
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
    /**
     * Status information (update, footer)
     *
     * @return string HTML corresponding to default status
     */
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
