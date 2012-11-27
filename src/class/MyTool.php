<?php
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
                'return highlight_string("<?php $matches[1]",true);'),
            $text);
        $text = preg_replace('/<br \/>/is','',$text);

        $text = preg_replace(
            '#(^|\s)([a-z]+://([^\s\w/]?[-\w/])*)(\s|$)#im',
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
