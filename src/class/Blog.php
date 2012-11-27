<?php
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
