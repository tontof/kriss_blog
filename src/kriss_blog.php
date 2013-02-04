<?php
/**
 * kriss_blog simple and smart (or stupid) blogging tool
 * Copyleft (C) 2012 Tontof - http://tontof.net
 *
 * PHP version 6
 *
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

/**
 * autoload class
 *
 * @param string $className The name of the class to load
 */
function __autoload($className)
{
    require_once 'class/'. $className . '.php';
}

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

        if (!$kbc->write(CONFIG_FILE)) {
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
