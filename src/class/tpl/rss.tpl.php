<?php
$today = time();
$lastUpdate = date(DATE_W3C, $today);
if (count($entries) > 0) {
    $lastUpdate = array_keys($entries);
    $lastUpdate = date(DATE_W3C, $lastUpdate[0]);
}
echo '<?xml version="1.0" encoding="UTF-8" ?>';
?>
<rdf:RDF
  xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
  xmlns:dc="http://purl.org/dc/elements/1.1/"
  xmlns:sy="http://purl.org/rss/1.0/modules/syndication/"
  xmlns:content="http://purl.org/rss/1.0/modules/content/"
  xmlns="http://purl.org/rss/1.0/">

  <channel rdf:about="<?php echo $blogurl; ?>">
    <title><?php echo strip_tags(MyTool::formatText($blogtitle)); ?></title>
    <description><?php echo strip_tags(MyTool::formatText($blogdesc)); ?></description>
    <link><?php echo $blogurl; ?></link>
    <dc:language><?php echo $bloglanguage; ?></dc:language>
    <dc:rights></dc:rights>
    <dc:creator><?php echo $bloglogin; ?></dc:creator>
    <dc:date><?php echo $lastUpdate; ?></dc:date>
    <dc:source>kriss blog</dc:source>

    <sy:updatePeriod>daily</sy:updatePeriod>
    <sy:updateFrequency>1</sy:updateFrequency>
    <sy:updateBase><?php echo $lastUpdate; ?></sy:updateBase>

    <items>
      <rdf:Seq>
    <?php
        foreach ($entries as $id => $entry) {
    ?>
        <rdf:li rdf:resource="<?php echo $entry['link']; ?>" />
    <?php
        }
    ?>
      </rdf:Seq>
    </items>
  </channel>
    <?php
        foreach ($entries as $id => $entry) {
    ?>
    <item rdf:about="<?php echo $entry['link']; ?>">
      <title><?php echo $entry['title']; ?></title>
      <link><?php echo $entry['link']; ?></link>
      <description><![CDATA[<?php echo MyTool::formatText($entry['content']); ?>]]></description>
      <dc:date><?php echo date(DATE_W3C, $id); ?></dc:date>
      <dc:language><?php echo $bloglanguage; ?></dc:language>
      <dc:creator><?php echo $bloglogin; ?></dc:creator>
      <dc:subject><?php echo strip_tags(MyTool::formatText($blogdesc)); ?></dc:subject>
      <content:encoded>
          <![CDATA[<?php echo MyTool::formatText($entry['content']); ?>]]>
      </content:encoded>
    </item>
    <?php
        }
    ?>
</rdf:RDF>
