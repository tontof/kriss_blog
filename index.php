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

class MyTool
{
    public static function initPHP()
    {
        if (phpversion() < 5){
            die("Argh you don't have PHP 5 ! Please install it right now !");
        }
        
        ob_start('ob_gzhandler');
        register_shutdown_function('ob_end_flush');

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
    public function formatBBCode($text)
    {
        $replace = array(
            '/\[b\](.*?)\[\/b\]/is'
            => '<strong>$1</strong>',
            '/\[i\](.*?)\[\/i\]/is'
            => '<em>$1</em>',
            '/\[s\](.*?)\[\/s\]/is'
            => '<span style="text-decoration: line-through;">$1</span>',
            '/\[u\](.*?)\[\/u\]/is'
            => '<span style="text-decoration: underline;">$1</span>',
            '/\[url\=(.*)\](.*)\[\/url\]/is'
            => '<a href="$1">$2</a>',
            '/\[url\](.*)\[\/url\]/is'
            => '<a href="$1">$1</a>',
            '/\[quote\](.+?)\[\/quote\]/is'
            => '<blockquote>$1</blockquote>',
            '/\[code\](.+?)\[\/code\]/is'
            => '<code>$1</code>'
            );
        $text = preg_replace(array_keys($replace),array_values($replace),$text);
        return $text;
    }
}

MyTool::initPHP();

define('DATA_FILE','data.php');
define('CONFIG_FILE','config.php');
define('STYLE_FILE','style.css');
define('BLOG_VERSION',1);

define('PHPPREFIX','<?php /* '); // Prefix to encapsulate data in php code.
define('PHPSUFFIX',' */ ?>'); // Suffix to encapsulate data in php code.

// Default stylesheet
$default_css = '
* {
    margin: 0;
    padding: 0;
}

body {
    font-family: Arial, Helvetica, sans-serif;
    font-size: 12px;
    background: #eee;
    color: #000;
}

#global {
    border: 2px solid #999;
    border-top: none;
    width: 750px;
    padding: 1.5em;
    padding-top: 1em;
    background: #fff;
    margin-left: auto;
    margin-right: auto;
}

h1 {
    color: #666;
    border-bottom: 1px dotted #999;
}
h2 {
    text-align: right;
    font-size: 1.3em;
    font-style: italic;
    margin-bottom: 1em;
    color: #666;
    letter-spacing: 0.2em;
}
h4 {
    text-align: right;
    font-size: 1.1em;
    font-style: italic;
    margin-bottom: 1em;
    color: #666;
    letter-spacing: 0.2em;
}

#footer {
    border-top: 1px dashed #999;
    padding-top: 0.5em;
    padding-bottom: 0.5em;
    font-size: 0.9em;
    color: #666;
}

#footer a {
    color: #666;
}

#footer form {
    float: right;
}

#footer form p {
    display: inline;
    margin-left: 1em;
}

#footer label input {
    border: 1px solid #999;
    padding: 0.1em;
    width: 10em;
}

#content p, #content div, #content ul, #content h3 {
    margin-bottom: 1em;
}

#content fieldset {
    border: 2px solid #ccc;
    margin-bottom: 1em;
    width: 90%;
}

#content textarea {
    height: 20em;
    width: 95%;
    border: 1px solid #000;
    padding: 0.3em;
    font-family: Arial, Helvetica, sans-serif;
    font-size: 1em;
}

#content fieldset dd {
    margin: 1em;
}

#content fieldset dt {
    font-weight: bold;
    margin: 0.5em;
}

#content input[type=text] {
    border: 1px solid #000;
    padding: 0.3em;
    font-family: Arial, Helvetica, sans-serif;
    font-size: 1em;
    width: 95%;
}

.item {
    border: 1px dotted #999;
    padding: 0.5em;
    margin-bottom: 2em !important;
}

.item .link {
    margin-bottom: 0 !important;
    margin-top: -0.5em;
    margin-right: 2em;
    font-size: 0.9em;
    float: right;
    background: #fff;
    border: 1px dotted #999;
    padding: 0.3em;
}

.item .link a {
    color: darkblue;
}

.item .link a:hover {
    color: red;
}

.admin {
    color: red !important;
    background: yellow;
    padding: 0.2em;
}

#config fieldset {
    padding: 0.5em;
}

#config fieldset legend {
    font-weight: bold;
    padding-left: 1em;
    padding-right: 1em;
}

#config fieldset dd {
    margin: 0.5em;
}

#config input[type=text] {
    width: 95%;
    padding: 0.2em;
}

#comments fieldset {
    padding: 0.5em;
}

#comments fieldset legend {
    font-weight: bold;
    padding-left: 1em;
    padding-right: 1em;
}

#comments fieldset dd {
    margin: 0.5em;
}

#comments input[type=text] {
    width: 95%;
    padding: 0.2em;
}

.pagination {
    list-style-type: none;
    text-align: center;
}

.pagination li {
    display: inline;
    margin: 0.5em;
}

.pagination .selected {
    font-weight: bold;
    font-size: 1.2em;
}

.pagination a {
    color: #999;
}

dl.tips dt { font-weight: bold; }
dl.tips dd { margin: 0.5em; margin-bottom: 1em; }
';

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

    public function setReverseorder($reverseorder)
    {
        $this->reverseorder=$reverseorder;
    }

    public function write()
    {
        $data = array('login', 'hash', 'salt', 'title', 'desc', 'locale',
                      'dateformat', 'bypage', 'reverseorder');
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
        if (empty($this->url))
            $this->url = $this->getUrl();
    }

    public function getArticleNumber()
    {
        return count($this->_data);
    }

    public function getUrl()
    {
        if (!empty($this->url))
            return $this->url;

        $url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        $url = preg_replace('/([?&].*)$/', '', $url);
        return $url;
    }

    public function loadData()
    {
        if (file_exists($this->file))
        {
            $this->_data = unserialize(
                gzinflate(
                    base64_decode(
                        substr(
                            file_get_contents($this->file),
                            strlen(PHPPREFIX),
                            -strlen(PHPSUFFIX)))));
            $this->sortData();
            return true;
        }
        return false;
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

    public function formatText($text){
        $text = preg_replace_callback(
            '/\[php\](.*?)\[\/php\]/is',
            create_function(
                '$matches',
                'return highlight_string("<?php$matches[1]?>",true);'),
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

    public function editEntry($id, $title, $text)
    {
        $comments=array();
        if (isset($this->_data[(int)$id])){
            $comments=$this->_data[(int)$id]["comments"];
        }
        $this->_data[(int)$id] = array(
            "title" => $title,
            "text" => $text,
            "comments" => $comments);
    }

    public function addComment($id, $pseudo, $site, $comment)
    {
        $entry = $this->getEntry($id);
        if (!$entry)
            echo "<p>Can't find this entry.</p>";
        else
        {
            $comments=$this->_data[(int)$id]["comments"];
            $comments[time()]=array($pseudo,$site,$comment);
            $this->_data[(int)$id]=array(
                "title" => $entry['title'],
                "text" => $entry['text'],
                "comments" => $comments);
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

$captcha = new Captcha();
Session::init();
$pc = new Blog_Conf(CONFIG_FILE,BLOG_VERSION);
$pb = new Blog(DATA_FILE, $pc);

// Loading data or create dummy entry
if (!$pb->loadData())
{
    $pb->editEntry(
        time(),
        "Your simple and smart (or stupid) blog",
        "Welcome to your <a href=\"http://tontof.net\">blog</a>".
        "(want to learn more about wp:Blog ?).\n\n".
        "Edit this entry to see a bit how this thing works.");
    if (!$pb->writeData())
        die("Can't write to ".$pb->file);

    header('Location: '.$pb->getUrl());
    exit;
}

// We allow the user to have its own stylesheet
if (file_exists(STYLE_FILE))
    $default_css = " @import url('".STYLE_FILE."'); ";

// For translating things
setlocale(LC_TIME, $pc->locale);

///////////// PAGES //////////////////////////////////////////////////////////
// (only if i'm in blog)

if (!defined('FROM_EXTERNAL') || !FROM_EXTERNAL){

    if (isset($_GET['login'])
        && !empty($_POST['login'])
        && !empty($_POST['password'])){  // Login process
        if (Session::login(
                $pc->login,
                $pc->hash,
                $_POST['login'],
                sha1($_POST['password'].$_POST['login'].$pc->salt))){
            header('Location: '.$pb->getUrl());
            exit();
        }
        die("Login failed !");
    } elseif (isset($_GET['logout'])) {  // Logout
        Session::logout();
        header('Location: '.$pb->getUrl());
        exit(); 
    } elseif (isset($_GET['config'])
              && Session::isLogged()) {  // User configuration
        if (isset($_POST['save'])){
            $pc->hydrate($_POST);

            if (!$pc->write())
                die("Can't write to ".$pc->file);

            header('Location: '.$pb->getUrl());
            exit;
        }
        if (isset($_POST['cancel'])) {
            header('Location: '.$pb->getUrl());
            exit;
        }

        echo '
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0
  Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>Configuration</title>
    <style type="text/css">
      '.$default_css.'
    </style>
  </head>
  <body id="config">
    <div id="global">
      <h1>Configuration (version '.$pc->version.')</h1>
      <h2>Why don\'t you <a href="http://github.com/tontof/kriss_blog/">check for a new version</a> ?</h2>
      <div id="content">
        <dl class="tips">
          <dt>Do you know that you can change the style of your blog ?</dt>
          <dd>Just create a new file called style.css in your blog directory and edit it :)<br />
            Bored of your own style ? Just remove or rename the file.</dd>
        </dl>
        <form method="post" action="">
          <fieldset>
            <legend>Blog informations</legend>
            <dl>
              <dt>Blog title</dt>
              <dd><input type="text" name="title" value="'.$pc->title.'" /></dd>
              <dt>Blog description (HTML allowed)</dt>
              <dd><input type="text" name="desc" value="'.$pc->desc.'" /></dd>
            </dl>
          </fieldset>

          <fieldset>
            <legend>Language informations</legend>
            <dl>
              <dt>Locale (eg. en_GB or fr_FR)</dt>
              <dd><input type="text" maxlength="5" name="locale" value="'.$pc->locale.'" /></dd>
              <dt>Date format (<a href="http://php.net/strftime">strftime</a> format)</dt>
              <dd><input type="text" name="dateformat" value="'.$pc->dateformat.'" /></dd>
            </dl>
          </fieldset>

          <fieldset>
            <legend>Blog preferences</legend>
            <dl>
              <dt>Number of entries by page</dt>
              <dd><input type="text" maxlength="3" name="bypage" value="'.(int) $pc->bypage.'" /></dd>
              <dt>Order of entries</dt>
              <dd>
                <label><input type="radio" name="reverseorder" value="0" '.(!$pc->reverseorder ? 'checked="checked"' : '').' /> From the latest to the newest</label><br />
                <label><input type="radio" name="reverseorder" value="1" '.($pc->reverseorder ? 'checked="checked"' : '').' /> <strong>Reverse order:</strong> from the newest to the latest</label>
                </dd>
            </dl>
          </fieldset>

          <p><input type="submit" name="cancel" value="Cancel" /><input type="submit" name="save" value="Save" /></p>
        </form>
      </div>
    </div>
  </body>
</html>';
        exit;
    } elseif (isset($_GET['delete'])
              && Session::isLogged()) { // Deleting an entry
        $pb->deleteEntry($_GET['delete']);
        if (!$pb->writeData())
            die("Can't write to ".$pb->file);
        
        header('Location: '.$pb->getUrl());
        exit;
    } elseif (isset($_GET['edit']) && Session::isLogged()) { // Editing an entry
        if (isset($_POST['save'])){
            $id = (int) $_GET['edit'];

            if (empty($_GET['edit']))
                $id = time();

            if (!empty($_POST['date'])){
                $new_date = strtotime($_POST['date']);

                if ((int)$new_date != (int)$_GET['edit'] && !empty($new_date)){
                    $pb->deleteEntry($_GET['edit']);
                    $id = $new_date;
                }
            }

            $pb->editEntry($id, $_POST['title'], $_POST['text']);
            if (!$pb->writeData())
                die("Can't write to ".$pb->file);

            header('Location: '.$pb->getUrl().'?'.$id);
            exit;
        }
        echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
        <html>
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
            <title>'.strip_tags($pb->formatText($pc->title)).'</title>
            <style type="text/css">
            '.$default_css.'
            </style>
        </head>

        <body>
        <div id="global">
            <h1>'.$pb->formatText($pc->title).'</h1>
            <h2>'.$pb->formatText($pc->desc).'</h2>
            <div id="content">';

        $title ='';
        $text = '';
        if (empty($_GET['edit'])){
            echo '<h3>New entry</h3>';
            if ($pb->getArticleNumber() < 2){
                $title = "New title";
                $text = "Describe your entry here. HTML is <strong>allowed</strong>. URLs are automatically converted.\n\n"
                    .   "You can use wp:Article to link to a wikipedia article. Or maybe, for an article in a specific language, "
                    .   "try wp:lang:Article (eg. wp:nl:Homomonument).\n\nTry it !";
            }
            $date = date('Y-m-d H:i:s');
        } else {
            echo '<h3>Edit '.(int)$_GET['edit'].'</h3>';
            $entry = $pb->getEntry($_GET['edit']);
            $title = $entry['title'];
            $text = $entry['text'];
            $date = date('Y-m-d H:i:s', (int)$_GET['edit']);
        }

        echo '
            <form method="post" class="edit" action="?edit='.(int)$_GET['edit'].'">
            <fieldset>
                <dl>
                    <dt><label for="f_title">Title</label></dt>
                    <dd><input type="text" id="f_title" name="title" value="'.$title.'" /></dd>
                    <dd><textarea name="text" cols="70" rows="50">'.$text.'</textarea></dd>
                    <dt><label for="f_date">Entry date</label></dt>
                    <dd><input type="text" id="f_date" name="date" value="'.$date.'" /></dd>
                </dl>
            </fieldset>
            <p class="submit">
                <input type="submit" name="save" value="Save" />
            </p>
            </form>
            </div>
        </div>
        </body>
        </html>';
        exit;
    }

    // RSS feed
    elseif (isset($_GET['rss']))
    {
        $pc->reverseorder = true;
        $pb->sortData();

        $list = $pb->getList(0);
        $last_update = array_keys($list);
        $last_update = date(DATE_RSS, $last_update[0]);

        header('Content-Type: text/xml');

        echo  '<?xml version="1.0" encoding="UTF-8" ?>
        <rdf:RDF
          xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
          xmlns:dc="http://purl.org/dc/elements/1.1/"
          xmlns:sy="http://purl.org/rss/1.0/modules/syndication/"
          xmlns:content="http://purl.org/rss/1.0/modules/content/"
          xmlns="http://purl.org/rss/1.0/">

        <channel rdf:about="'.$pb->getUrl().'">
          <title>'.strip_tags($pb->formatText($pc->title)).'</title>
          <description><![CDATA['.strip_tags($pb->formatText($pc->desc)).']]></description>
          <link>'.$pb->getUrl().'</link>
          <dc:language>'.substr($pc->locale, 0, 2).'</dc:language>
          <dc:creator>'.$pc->login.'</dc:creator>
          <dc:rights></dc:rights>
          <dc:date>'.$last_update.'</dc:date>

          <sy:updatePeriod>daily</sy:updatePeriod>
          <sy:updateFrequency>1</sy:updateFrequency>
          <sy:updateBase>'.$last_update.'</sy:updateBase>

          <items>
            <rdf:Seq>
            ';

        foreach ($list as $id=>$content)
        {
            echo '  <rdf:li rdf:resource="'.$pb->getUrl().'?'.$id.'" />
            ';
        }

        echo '</rdf:Seq>
          </items>
        </channel>';

        foreach ($list as $id=>$content)
        {
            echo '
                <item rdf:about="'.$pb->getUrl().'?'.$id.'">
                    <title>'.strip_tags($pb->formatText($content['title'])).'</title>
                    <link>'.$pb->getUrl().'?'.$id.'</link>
                    <dc:date>'.date(DATE_RSS, $id).'</dc:date>
                    <dc:language>'.substr($pc->locale, 0, 2).'</dc:language>
                    <dc:creator>'.$pc->login.'</dc:creator>
                    <dc:subject>Simple and smart (or stupid) blog</dc:subject>
                    <description><![CDATA['.$pb->formatText($content['text']).']]></description>
                </item>';
        }

        echo '
        </rdf:RDF>';

        exit;
    }

    // Permalink to an entry
    elseif (!empty($_SERVER['QUERY_STRING']) && is_numeric($_SERVER['QUERY_STRING']))
    {
        $id = (int)$_SERVER['QUERY_STRING'];

        $input_pseudo="";
        $input_comment="";
        $input_site="";
    

        if (isset($_POST['send']))
        {
            $input_captcha=htmlspecialchars($_POST['captcha']);
            $input_pseudo=htmlspecialchars($_POST['pseudo']);
            $input_comment=htmlspecialchars($_POST['comment']);
            $input_site=htmlspecialchars($_POST['site']);
            if (!empty($input_comment) and
                $_SESSION['captcha']==$input_captcha and
                (empty($input_site) or (!empty($input_site) and MyTool::isUrl($input_site)))){
                if (empty($input_pseudo)){
                    $input_pseudo="<em>Anonymous</em>";
                }
                $pb->addComment($id,$input_pseudo,$input_site,$input_comment);
                if (!$pb->writeData())
                    die("Can't write to ".$pb->file);
                header('Location: '.$pb->getUrl().'?'.$id);
                exit;
            }
        }

    
        echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
        <html>
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
            <title>'.strip_tags($pb->formatText($pc->title)).'</title>
            <link rel="alternate" type="application/rss+xml" title="RSS" href="?rss" />
            <style type="text/css">
            '.$default_css.'
            </style>
        </head>

        <body>
        <div id="global">
            <h1>'.$pb->formatText($pc->title).'</h1>
            <h2>'.$pb->formatText($pc->desc).'</h2>
            <div id="content">
            ';

        $entry = $pb->getEntry($id);
        if (!$entry)
            echo "<p>Can't find this entry.</p>";
        else
        {
            echo '
            <div class="item">
                <h3>'.$entry['title'].'</h3>
                <h4>'.strftime($pc->dateformat, $id).'</h4>
                <div class="content">'.$pb->formatText($entry['text']).'</div>';
        }

        echo '
                <p class="link">
                    <a href="?'.$id.'">Permalink</a>';

        if (Session::isLogged())
        {
            echo ' | <a href="?edit='.$id.'" class="admin">Edit</a> |
                            <a href="?delete='.$id.'" class="admin" onclick="if (confirm(\'Sure?\') != true) return false;">Delete</a>';
        }

        echo '</p>
            </div>';
        echo '<div id="comments">
              <h3>Comments</h3>';


        foreach ($entry['comments'] as $key=>$comment){
            echo '<div class="item">';
            echo empty($comment[1])?'<h3>'.$comment[0].'</h3>':'<h3><a href="'.$comment[1].'">'.$comment[0].'</a></h3>';
            echo '
               <div class="content">'.$pb->formatText($comment[2]).'</div>
               <p class="link">'.strftime($pc->dateformat, $key).'</p>
               </div>';
        }

        echo '
               <form id="new_comment" action="#new_comment" method="post">
                 <fieldset>
                   <legend>New comment</legend>
                   <dl>
                     <dt>Pseudo</dt>
                     <dd>
                       <input type="text" placeholder="pseudo (facultatif)" name="pseudo" value="';
        echo $input_pseudo.'">
                     </dd>
                     <dt>Site</dt>
                     <dd>
                       <input type="text" placeholder="site (facultatif)" name="site" value="';
        echo $input_site.'" '.((!empty($input_site) and !MyTool::isUrl($input_site))?'style="border-color:red">':'>');
        echo '
                     </dd>
                     <dt>Comment</dt>
                     <dd>
                       <textarea name="comment"';
        echo (empty($input_comment) and isset($_POST['comment']))?' style="border-color:red">':'>';
        echo $input_comment.'</textarea>
                     </dd>
                     <dt>Captcha</dt>
                     <dd>';
        $_SESSION['captcha']=$captcha->generateString();
        echo $captcha->convertString($_SESSION['captcha']).'<br>';
        echo '
                       <input type="text" placeholder="" name="captcha"';
        echo isset($_POST['captcha'])?' style="border-color:red">':'>';
        echo '
                     </dd>
                   </dl>
                 </fieldset>
                 <p>
                   <input type="submit" value="Send" name="send">
                 </p>
               </form>
               </div>';
        echo '
      <p><a href="'.$pb->getUrl().'">Back to homepage</a></p>
        </div>
        </div>

        </body>
        </html>';
        exit;
    }

    // Entries by page
    else
    {
        $page = 1;

        if (!empty($_SERVER['QUERY_STRING']) && preg_match('/^p([0-9]+)$/', $_SERVER['QUERY_STRING'], $match))
        {
            $page = (int)$match[1];
        }

        $begin = ($page - 1) * $pc->bypage;

        $list = $pb->getList($begin);

        echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
        <html>
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
            <title>'.strip_tags($pb->formatText($pc->title)).'</title>
            <link rel="alternate" type="application/rss+xml" title="RSS" href="?rss" />
            <style type="text/css">
            '.$default_css.'
            </style>
        </head>

        <body>
        <div id="global">
            <h1>'.$pb->formatText($pc->title).'</h1>
            <h2>'.$pb->formatText($pc->desc).'</h2>
            <div id="content">
            ';

        if (Session::isLogged())
            echo '<p><a href="?edit" class="admin">New entry</a></p>';

        if (empty($list))
            echo '<p>No item.</p>';
        else
        {
            foreach ($list as $id=>$content)
            {
                echo '
                <div class="item">
                    <h3><a href="?'.$id.'">'.$content['title'].'</a></h3>
                    <h4>'.strftime($pc->dateformat, $id).'</h4>
                    <div class="content">
                        '.$pb->formatText($content['text']).'
                    </div>
                    <p class="link">
                        <a href="?'.$id.'#comments">'.count($content['comments']).' comment(s)</a>';

                if (Session::isLogged())
                {
                    echo ' | <a href="?edit='.$id.'" class="admin">Edit</a> |
                            <a href="?delete='.$id.'" class="admin" onclick="if (confirm(\'Sure?\') != true) return false;">Delete</a>';
                }

                echo '</p>
                </div>';
            }
        }

        $pages = $pb->getPagination();
        if (!empty($pages))
        {
            echo '
            <ul class="pagination">
            ';

            for ($p = 1; $p <= $pages; $p++)
            {
                echo '<li'.($page == $p ? ' class="selected"' : '').'><a href="?p'.$p.'">'.$p.'</a></li>';
            }

            echo '
            </ul>';
        }

        echo '
            </div>
            <div id="footer">
                <a href="?rss">RSS</a>';

        if (Session::isLogged())
            echo ' | <a href="?config" class="admin">Configuration</a> | <a href="?logout" class="admin">Logout</a>';
        else
        {
            echo '
                 <form method="post" action="?login">
                     <p><label>Login: <input type="text" name="login" /></label></p>
                     <p><label>Password: <input type="password" name="password" /></label></p>
                     <p><input type="submit" value="OK" class="submit" /></p>
                 </form>';
        }

        echo '
            </div>
        </div>
        </body>
        </html>';
    }
}

?>
