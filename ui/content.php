<?php

  include_once 'lib/helper.php';
  include_once 'lib/model.php';

  $content = Model::getAllContent ();

  foreach ($content as &$movie)
  {
      $movie = "$movie->year - <a href='bubbles.php?topic=$movie->content_id'>$movie->title</a>";
  }

  $list = '<ul><li>'.implode ('</li><li>', $content).'</li></ul>';

?>
<!DOCTYPE html>
<html lang='en'>
  <head>
    <title>wooc &raquo; all content</title>
    <meta charset='UTF-8'>
  </head>
  <body>
    <?php echo $list; ?>
  </body>
</html>
