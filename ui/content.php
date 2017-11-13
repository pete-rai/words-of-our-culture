<?php

  include_once 'lib/helper.php';
  include_once 'lib/model.php';

  define ('CONTENT_COLS', 3);

  function item ($href, $text)
  {
      return "<div class='item'><a href='$href'>$text</a></div>";
  }

  $content = Model::getAllContent ();
  $years   = [];

  foreach ($content as $movie)
  {
      if (!isset ($years [$movie->year]))
      {
          $years [$movie->year] = [];
      }

      $years [$movie->year][] = item ("bubbles.php?topic=$movie->content_id", $movie->title);
  }

  $randMovie = item ('random.php?type=movie', 'Random Movie');
  $randWord  = item ('random.php?type=word' , 'Random Word' );

  $rows = [];

  $rows [] = "<h2>Random</h2>";
  $rows [] = "<div class='container'>";
  $rows [] = "<div class='col-md-6'>$randMovie</div><div class='col-md-6'>$randWord</div>";
  $rows [] = "</div>";

  foreach ($years as $year => $movies)
  {
      $rows [] = "<h2>$year</h2>";
      $rows [] = "<div class='container'>";

      for ($idx = 0 ; $idx < count ($movies) ; $idx += CONTENT_COLS)
      {
          $row = '';

          for ($n = 0 ; $n < CONTENT_COLS ; $n++)
          {
              if (isset ($movies [$idx + $n]))
              {
                  $row .= "<div class='col-md-4'>".$movies [$idx + $n]."</div>";
              }
          }

          $rows [] = $row;
      }

      $rows [] = "</div>";
  }

  $table = implode ("\n", $rows);

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <title>wooc &raquo; Content Index</title>
  <meta charset="UTF-8">
  <meta name=viewport content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" />
  <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap-theme.min.css" />
  <link rel="stylesheet" href="css/content.css">
  <script src="//maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
  <script src="//platform-api.sharethis.com/js/sharethis.js#property=59ed1844899d1d001141cf42&product=unknown" async="async"></script>
</head>
<body>
  <h1>Words of our Culture</h1>
  <h2>Words</h2>
  <form action="bubbles.php">
    <input id="topic" name="topic" placeholder="type word + hit return" />
  </form>
  <?php echo $table; ?>
</body>
</html>
