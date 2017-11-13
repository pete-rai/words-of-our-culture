<?php

  include_once 'lib/helper.php';
  include_once 'lib/model.php';

  define ('CONTENT_COLS', 3);

  $content = Model::getAllContent ();
  $years   = [];

  foreach ($content as $movie)
  {
      if (!isset ($years [$movie->year]))
      {
          $years [$movie->year] = [];
      }

      $years [$movie->year][] = "<div class='item'><a href='bubbles.php?topic=$movie->content_id'>$movie->title</a></div>";
  }

  $rows = [];

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

  $table = implode ('', $rows);

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <title>wooc &raquo; all content</title>
  <meta charset="UTF-8">
  <meta name=viewport content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" />
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap-theme.min.css" />
  <style>

    html { font-family: Calibri, Candara, Segoe, Optima, Arial, sans-serif;}
    body { background-color: #dcdcdc; margin-bottom: 15px;}
    div.item { padding: 8px; margin: 8px; font-size: 1.5em; text-align: center; background-color: white; border-radius: 10px; }
    h1 { text-align: center; color: #000080;}
    h2 { background-color: #000080; color: #dcdcdc; text-align: center; padding: 6px; }
    form { text-align: center; }
    input { color: #000080; border: 1px solid grey; text-align: center; font-size: 1.5em; margin: 10px; margin-top: 15px; width: 70%; }

  </style>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
  <script src="//platform-api.sharethis.com/js/sharethis.js#property=59ed1844899d1d001141cf42&product=unknown" async='async'></script>
</head>
<body>
  <h1>Words of our Culture</h1>
  <h2>Words</h2>
  <form action='bubbles.php'>
    <input id='topic' name='topic' placeholder="type a word and hit return" />
  </form>
  <?php echo $table; ?>
</body>
</html>
