<!DOCTYPE html>
<html>
<head><?php BlogPage::includesTpl(); ?></head>
<body>
<div id="global">
  <?php BlogPage::pageheaderTpl(); ?>
  <?php BlogPage::navTpl(); ?>

  <div id="section">
  <?php
      if (empty($entry)) {
  ?>
    <div class="article">
      <div class="content">
      No entry
      <?php 
          if (Session::isLogged()) {
      ?>
      <p class="link">
        <a href="?edit=<?php echo $id; ?>" class="admin">Create</a>
      </p>
      <?php
          }
      ?>
      </div>
    </div>
  <?php
      } else {
  ?>
    <div class="article"<?php echo ((Session::isLogged() and ($entry['private'] or $id > time()))?' style="border-color:red;"':''); ?>>
      <h3 class="title"><?php echo $entry['title'] ?></h3>
      <?php
          if (is_numeric($id)) {
      ?>
      <h4 class="subtitle"><?php echo utf8_encode(strftime($dateformat, $id)); ?></h4>
      <?php
          }
      ?>
      <div class="content">
        <?php echo MyTool::formatText($entry['text']); ?>
        <p class="link">
         <?php
             if (is_numeric($id)) {
         ?>
         <a href="?<?php echo MyTool::urlize($id, $entry['title']); ?>">Permalink</a>
         <?php
             } else {
         ?>
         <a href="?<?php echo $id; ?>">Permalink</a>
         <?php
             }
         ?>

         <?php
             if (Session::isLogged()) {
         ?>
           | <a href="?edit=<?php echo $id; ?>" class="admin">Edit</a> | <a href="?delete=<?php echo $id; ?>" class="admin" onclick="if (confirm('Sure?') != true) return false;">Delete</a>
         <?php
             }
         ?>
        </p>
      </div>
    </div>

    <div id="comments">
      <?php $numComm = count($entry['comments']);
         if ($numComm > 0) { ?>
            <h3>Comments</h3>
      <?php
         }
      $i = 1;
      foreach ($entry['comments'] as $key => $comment) {
      ?>
        <div class="comment">
          <h4 id="<?php echo $i; ?>"><a href='#<?php echo $i; ?>'><?php echo $i; ?></a> - <a href="#new_comment" onclick="reply('[b]@[<?php echo strip_tags($comment[0]); ?>|#<?php echo $i; ?>][/b]')">@</a> - 
          <?php
              if (MyTool::isUrl($comment[1])) {
          ?>
            <a href="<?php echo $comment[1]; ?>"><?php echo $comment[0]; ?></a>
          <?php
              } else {
                  echo $comment[0];
              }
          ?>
          </h4>
          <div class="content">
            <?php echo MyTool::formatText($comment[2]); ?>
          </div>
          <p class="link">
          <?php
              if ($i==$numComm and isset($_POST['preview'])) {
          ?>
            <strong>Preview</strong>
          <?php
              } else {
                  echo utf8_encode(strftime($dateformat, $key));
                  if (Session::isLogged()) {
          ?>
            | <a href="?<?php echo $id; ?>_<?php echo $key; ?>#new_comment" class="admin">Edit</a>
          <?php
                  }
              }
          ?>
          </p>
        </div>
      <?php
          $i++;
      }
      ?>
    </div>


    <?php
        if (isset($entry['comment']) && $entry['comment']) {
    ?>
        <form id="new_comment" action="#new_comment" method="post">
          <fieldset>
            <legend>New comment</legend>
            <label for="pseudo">Pseudo</label><br>
            <input type="text" placeholder="pseudo (facultatif)" id="pseudo" name="pseudo" value="<?php echo (isset($inputpseudo)?$inputpseudo:''); ?>"><br>
            <label for="site">Site</label><br>
            <input type="text" placeholder="site (facultatif)" id="site" name="site" value="<?php echo (isset($inputsite)?$inputsite:''); ?>" <?php echo ((!empty($inputsite) and !MyTool::isUrl($inputsite))?'style="border-color:red">':'>'); ?>
            <br>
            <div style="display:none;">
            <label for="message">Leave empty</label><br>
            <textarea id="message" name="message" rows="3"></textarea>
            </div>
            <label for="comment">Comment</label><br>
            <textarea id="comment" name="comment" rows="10"<?php echo ((empty($inputcomment) and isset($_POST['comment']))?' style="border-color:red">':'>'); echo (isset($inputcomment)?$inputcomment:''); ?></textarea>
            <p>
              <button onclick="insertTag('[b]','[/b]','comment');" title="bold" type="button"><strong>b</strong></button><button onclick="insertTag('[i]','[/i]','comment');" title="italic" type="button"><em>i</em></button><button onclick="insertTag('[u]','[/u]','comment');" title="underline" type="button"><span style="text-decoration:underline;">u</span></button><button onclick="insertTag('[s]','[/s]','comment');" title="strike through" type="button"><del>s</del></button><button onclick="insertTag('[','|http://]','comment');" title="link" type="button">url</button><button onclick="insertTag('[quote]','[/quote]','comment');" title="quote" type="button">&#171;&nbsp;&#187;</button><button onclick="insertTag('[code]','[/code]','comment');" title="code" type="button">&#60;&#62;</button>
            </p><br>

        <?php
            if (isset($captcha)) {
        ?>
          <label for="captcha">Captcha</label><br>
          <pre><?php echo (isset($captcha)?$captcha:''); ?></pre><br>
          <input type="text" placeholder="Captcha" id="captcha" name="captcha"<?php echo ((isset($_POST['captcha']) && !isset($_POST['preview']))?' style="border-color:red"':''); ?>> <br>
        <?php
            }
        ?>
        <br>
        <?php
            if (strpos($_SERVER['QUERY_STRING'], '_') === false) {
        ?>
          <input type="submit" value="Preview" name="preview">
          <input type="submit" value="Send" name="send">
        <?php
            } else {
                if (Session::isLogged()) {
        ?>
          <input type="submit" value="Edit" name="edit">
        <?php
                }
            }
        ?>
          </fieldset>
        </form><br>
    <?php
        }
    ?>
<script>
// script from http://lehollandaisvolant.net
function reply(com) {
  var c=document.getElementById("comment");
  if (c.value) {
    c.value += "\n\n";
  }
  c.value += com;
  c.focus();
}

function insertTag(startTag, endTag, tag) {
  var field = document.getElementById(tag);
  var startSelection   = field.value.substring(0, field.selectionStart);
  var currentSelection = field.value.substring(field.selectionStart, field.selectionEnd);
  var endSelection     = field.value.substring(field.selectionEnd);
  if (currentSelection == "") {
    currentSelection = "TEXT";
  }
  field.value = startSelection + startTag + currentSelection + endTag + endSelection;
  field.setSelectionRange(startSelection.length + startTag.length, startSelection.length + startTag.length + currentSelection.length);
  field.focus();
}
</script>


  <?php
      }
  ?>
  </div>
  <?php BlogPage::pagefooterTpl(); ?>
</div>
</body>
</html>
