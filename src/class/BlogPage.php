<?php
/**
 * BlogPage
 */
class BlogPage
{
    public static $var = array();
    private static $_instance;

    /**
     * initialize private instance of BlogPage class
     *
     * @param array $var list of useful variables for template
     */
    public static function init($var)
    {
        BlogPage::$var = $var;
    }

    /**
     * installTpl
     * 
     */
    public static function installTpl()
    {
        extract(BlogPage::$var);
?>
<?php include("tpl/install.tpl.php"); ?>
<?php
    }

    /**
     * includesTpl
     * 
     */
    public static function includesTpl()
    {
        extract(BlogPage::$var);
?>
<?php include("tpl/includes.tpl.php"); ?>
<?php
    }

    /**
     * pageheaderTpl
     * 
     */
    public static function pageheaderTpl()
    {
        extract(BlogPage::$var);
?>
<?php include("tpl/page.header.tpl.php"); ?>
<?php
    }

    /**
     * navTpl
     * 
     */
    public static function navTpl()
    {
        extract(BlogPage::$var);
?>
<?php include("tpl/nav.tpl.php"); ?>
<?php
    }

    /**
     * pagefooterTpl
     * 
     */
    public static function pagefooterTpl()
    {
        extract(BlogPage::$var);
?>
<?php include("tpl/page.footer.tpl.php"); ?>
<?php
    }

    /**
     * configTpl
     * 
     */
    public function configTpl()
    {
        extract(BlogPage::$var);
?>
<?php include("tpl/config.tpl.php"); ?>
<?php
    }

    /**
     * loginTpl
     * 
     */
    public static function loginTpl()
    {
        extract(BlogPage::$var);
?>
<?php include("tpl/login.tpl.php"); ?>
<?php
    }

    /**
     * rssTpl
     * 
     */
    public function rssTpl()
    {
        extract(BlogPage::$var);
?>
<?php include("tpl/rss.tpl.php"); ?>
<?php 
    }

    /**
     * editTpl
     * 
     */
    public function editTpl()
    {
        extract(BlogPage::$var);
?>
<?php include("tpl/edit.tpl.php"); ?>
<?php
    }

    /**
     * indexTpl
     * 
     */
    public function indexTpl()
    {
        extract(BlogPage::$var);
?>
<?php include("tpl/index.tpl.php"); ?>
<?php
    }

    /**
     * entryTpl
     * 
     */
    public function entryTpl()
    {
        extract(BlogPage::$var);
?>
<?php include("tpl/entry.tpl.php"); ?>
<?php
    }
}
