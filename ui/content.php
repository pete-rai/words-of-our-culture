<?php

  include_once 'lib/helper.php';
  include_once 'lib/model.php';

  define ('CONTENT_COLS', 3);

  $content = Model::getAllContent ();
  $years   = [];
  $words   = ['president', 'nuclear', ''];

  foreach ($content as $movie)
  {
      if (!isset ($years [$movie->year]))
      {
          $years [$movie->year] = [];
      }

      $years [$movie->year][] = "<td><a href='bubbles.php?topic=$movie->content_id'>$movie->title</a></td>";
  }

  $rows = [];

  foreach ($years as $year => $movies)
  {
      $rows [] = "<tr><th colspan='".CONTENT_COLS."'>$year</th></tr>";

      for ($idx = 0 ; $idx < count ($movies) ; $idx += CONTENT_COLS)
      {
          $row = '';

          for ($n = 0 ; $n < CONTENT_COLS ; $n++)
          {
              $row .= isset ($movies [$idx + $n]) ? $movies [$idx + $n] : '<td></td>';
          }

          $rows [] = "<tr>$row</tr>";
      }
  }

  $table = '<table>'.implode ('', $rows).'</table>';

?>
<!DOCTYPE html>
<html lang='en'>
  <head>
    <title>wooc &raquo; all content</title>
    <meta charset='UTF-8'>
    <style>

    html
    {
        font-family: Calibri, Candara, Segoe, Optima, Arial, sans-serif;
    }

    table
    {
        border: 1px solid darkgrey;
        border-collapse: collapse;
        margin: 5px;
    }

    td, th
    {
        border: 1px solid darkgrey;
        padding: 5px;
        text-align: center;
    }

    th
    {
        background-color: lightgrey;
    }
    </style>
  </head>
  <body>
    <h1>Word of our Culture</h1>
    <form action='bubbles.php'>
        <input id='topic' name='topic' placeholder="word" />
        <input type='submit' value='go for it'/>
    </form>


    <?php echo $table; ?>
  </body>
</html>
