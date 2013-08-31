<title><?php echo $pagetitle;?></title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">

<link rel="alternate" type="application/rss+xml" href="?rss" title="<?php echo $pagetitle;?>">
<link rel="alternate" type="application/rss+xml" href="?rss=comments" title="<?php echo $pagetitle;?> RSS comments">
<!-- <link href="images/favicon.ico" rel="shortcut icon" type="image/x-icon"> -->
<?php
    if (is_file('inc/style.css')) {
?>
<link type="text/css" rel="stylesheet" href="inc/style.css?version=<?php echo $version;?>" />
<?php
    } else {
?>
<style>
<?php include("inc/style.css"); ?>
</style>
<?php
    }
?>
<?php
    if (is_file('inc/user.css')) {
?>
<link type="text/css" rel="stylesheet" href="inc/user.css?version=<?php echo $version;?>" />
<?php
    }
?>
<meta name="format-detection" content="telephone=no">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
