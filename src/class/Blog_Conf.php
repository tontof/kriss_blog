<?php
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
