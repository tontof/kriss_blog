<?php
/**
 * Blog
 */
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

    /**
     * __construct
     * 
     * @param string   $dataFile filename of data
     * @param string   $menuFile filename of menu
     * @param BlogConf $bc       BlogConf
     */
    public function __construct($dataFile, $menuFile, $bc)
    {
        $this->bc = $bc;
        $this->file = $dataFile;
        $this->menuFile = $menuFile;
    }

    /**
     * loadData
     * 
     */
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

    /**
     * sortData
     * 
     */
    public function sortData()
    {
        if ($this->bc->reverseorder) {
            krsort($this->_data);
        } else {
            ksort($this->_data);
        }
    }

    /**
     * writeData
     * 
     * @return boolean true if write success false otherwise
     */
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
    /**
     * getMenu
     * 
     * @param string $which menu or extra
     *
     * @return array entry of menu or extra
     */
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

    /**
     * getEntry
     * 
     * @param string/integer $id identifiant of the entry
     *
     * @return array/empty/boolean array of the entry, false/empty if not find
     */
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

    /**
     * filter data with entry or page (entry by default)
     * 
     * @param boolean $page if true filter pages, entries otherwise
     *
     * @return list of selected entries or pages
     */
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

    /**
     * getList with pagination
     * 
     * @param integer $begin
     *
     * @return selected entries
     */
    public function getList($begin=0)
    {
        if (!$this->_filtered) {
            $this->filter();
        }

        return array_slice($this->_data, $begin, $this->bc->bypage, true);
    }

    /**
     * getComments
     * 
     * @return array list of last comments
     */
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

    /**
     * editEntry create/edit entry/page
     * 
     * @param string  $id      identifiant of the entry/page
     * @param string  $title   title of the entry/page
     * @param string  $text    content of the entry/page
     * @param integer $comment is commentable
     * @param integer $private is private
     * @param string  $tags    list of tags
     */
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

    /**
     * addComment
     * 
     * @param string $id      identifiant of the commented entry/page
     * @param string $pseudo  author of the comment
     * @param string $site    site of the author
     * @param string $comment comment
     */
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
    /**
     * editComment
     * 
     * @param string $idE     identifiant of the entry
     * @param string $idC     identifiant of the comment
     * @param string $pseudo  author of the comment
     * @param string $site    site of the author
     * @param string $comment comment
     */
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

    /**
     * deleteEntry
     * 
     * @param string $id identifiant of the entry/page
     */
    public function deleteEntry($id)
    {
        unset($this->_data[$id]);
    }

    /**
     * getPagination
     * 
     * @return boolean/integer corresponding of the number of pages
     */
    public function getPagination()
    {
        if (count($this->_data) <= $this->bc->bypage) {
            return false;
        }

        $pages = ceil(count($this->_data) / $this->bc->bypage);

        return $pages;
    }
}
