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
  <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.7/css/bootstrap.min.css" />
  <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.7/css/bootstrap-theme.css" />
  <link rel="stylesheet" href="css/content.css">
  <link rel="apple-touch-icon" sizes="57x57" href="/icons/apple-icon-57x57.png">
  <link rel="apple-touch-icon" sizes="60x60" href="/icons/apple-icon-60x60.png">
  <link rel="apple-touch-icon" sizes="72x72" href="/icons/apple-icon-72x72.png">
  <link rel="apple-touch-icon" sizes="76x76" href="/icons/apple-icon-76x76.png">
  <link rel="apple-touch-icon" sizes="114x114" href="/icons/apple-icon-114x114.png">
  <link rel="apple-touch-icon" sizes="120x120" href="/icons/apple-icon-120x120.png">
  <link rel="apple-touch-icon" sizes="144x144" href="/icons/apple-icon-144x144.png">
  <link rel="apple-touch-icon" sizes="152x152" href="/icons/apple-icon-152x152.png">
  <link rel="apple-touch-icon" sizes="180x180" href="/icons/apple-icon-180x180.png">
  <link rel="icon" type="image/png" sizes="192x192"  href="/icons/android-icon-192x192.png">
  <link rel="icon" type="image/png" sizes="32x32" href="/icons/favicon-32x32.png">
  <link rel="icon" type="image/png" sizes="96x96" href="/icons/favicon-96x96.png">
  <link rel="icon" type="image/png" sizes="16x16" href="/icons/favicon-16x16.png">
  <link rel="manifest" href="/icons/manifest.json">
  <meta name="msapplication-TileColor" content="#ffffff">
  <meta name="msapplication-TileImage" content="/icons/ms-icon-144x144.png">
  <meta name="theme-color" content="#ffffff">
  <script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
  <script src="//cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.7/js/bootstrap.min.js"></script>
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
