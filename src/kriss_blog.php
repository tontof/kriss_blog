<?php
/**
 * kriss_blog simple and smart (or stupid) blogging tool
 * Copyleft (C) 2012 Tontof - http://tontof.net
 *
 * PHP version 5
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

define('DATA_FILE', DATA_DIR.'/data.php');
define('CONFIG_FILE', DATA_DIR.'/config.php');
define('MENU_FILE', DATA_DIR.'/menu.php');

define('STYLE_FILE', 'style.css');
define('BLOG_VERSION', 5);

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
                . " - " . strip_tags(MyTool::formatText($pb->pc->title)),
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
                    . " - " . strip_tags(MyTool::formatText($pb->pc->title)),
                    $pp->entryPage($pb, $id)
                );
            } else {
                $ids=explode("_", $_SERVER['QUERY_STRING']);
                $entry = $pb->getEntry($id);;
                if (!empty($entry['comments'][$ids[1]])) {
                    echo $pp->htmlPage(
                        $pb->getTitle($id)
                        . " - " . strip_tags(MyTool::formatText($pb->pc->title)),
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
                        . " - " . strip_tags(MyTool::formatText($pb->pc->title)),
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
                        . " - " . strip_tags(MyTool::formatText($pb->pc->title)),
                        $pp->entryPage($pb, $id)
                    );
                    exit();
                }
            }
        }
    }
}
