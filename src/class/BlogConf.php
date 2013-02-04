<?php
/**
 * BlogConf
 */
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

    /**
     * __construct
     * 
     * @param string  $configFile filename of config file
     * @param integer $version    current version of KrISS blog
     */
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

    /**
     * _install
     * 
     */
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

    /**
     * hydrate
     * 
     * @param array $donnees POST value to hydrate
     */
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

    /**
     * setLogin
     * 
     * @param string $login login
     */
    public function setLogin($login)
    {
        $this->login=$login;
    }

    /**
     * setSite
     * 
     * @param string $site site
     */
    public function setSite($site)
    {
        $this->site=$site;
    }

    /**
     * setHash
     * 
     * @param string $pass password
     */
    public function setHash($pass)
    {
        $this->hash=sha1($pass.$this->login.$this->salt);
    }

    /**
     * setSalt
     * 
     * @param string $salt salt
     */
    public function setSalt($salt)
    {
        $this->salt=$salt;
    }

    /**
     * setTitle
     * 
     * @param string $title title of the blog
     */
    public function setTitle($title)
    {
        $this->title=$title;
    }

    /**
     * setDesc
     * 
     * @param string $desc description of the blog
     */
    public function setDesc($desc)
    {
        $this->desc=$desc;
    }

    /**
     * setLocale
     * 
     * @param string $locale en_GB, fr_FR...
     */
    public function setLocale($locale)
    {
        $this->locale=preg_match('/^[a-z]{2}_[A-Z]{2}$/', $_POST['locale'])
            ? $_POST['locale']
            : $this->locale;
    }

    /**
     * setDateformat
     * 
     * @param string $dateformat format to show for entry
     */
    public function setDateformat($dateformat)
    {
        $this->dateformat=$dateformat;
    }

    /**
     * setBypage
     * 
     * @param integer $bypage number of article on index/rss
     */
    public function setBypage($bypage)
    {
        $this->bypage=$bypage;
    }

    /**
     * setComments
     * 
     * @param boolean $comments tells if entry/page are commentable
     */
    public function setComments($comments)
    {
        if ($this->comments!=$comments) {
            $this->comments=$comments;
            MyTool::rrmdir(CACHE_DIR);
        }
    }

    /**
     * setCache
     * 
     * @param boolean $cache tells if blog uses cache
     */
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

    /**
     * setReverseorder
     * 
     * @param boolean $reverseorder if true, show last entries first
     */
    public function setReverseorder($reverseorder)
    {
        $this->reverseorder=$reverseorder;
    }

    /**
     * write config file
     *
     * @return boolean true if success false otherwise
     */
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
