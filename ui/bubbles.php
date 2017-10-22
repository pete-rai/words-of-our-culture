<?php

  include_once 'lib/helper.php';
  include_once 'lib/model.php';

  $topic = getCleanParam ('topic');

  if (strpos ($topic, 'tt') === 0)
  {
      $content = Model::getContent ($topic);
      $subject = "$content->title ($content->year)";
      $type    = 'Movie';
  }
  else
  {
      $utterances = Model::getUtterances (Model::$posWord, $topic);
      $subject    = implode (', ', $utterances);
      $type       = count ($utterances) == 1 ? 'Word' : 'Words';
  }

  $title  = 'wooc'.($subject ? " &raquo; $subject" : '');
  $source = "ngrams.php?topic=$topic";  // data source url
  $items  = array_merge (getPostParams ('topic-'), [$subject]);
  $steps  = implode ("\n", getListItems  ($items))."\n";
  $fields = implode ("\n", setPostParams ($items, 'topic-'))."\n";

?>
<!DOCTYPE html>
<html lang='en'>
  <head>
    <title><?php echo $title; ?></title>
    <meta charset='UTF-8'>
    <link rel='stylesheet' href='css/jquery.slidein.css'>
    <link rel='stylesheet' href='css/bubbles.css'>
    <script src='//cdnjs.cloudflare.com/ajax/libs/p5.js/0.5.8/p5.min.js'></script>
    <script src='//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js'></script>
    <script src='//cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js'></script>
    <script src='js/jquery.slidein.js'></script>
    <script src='js/bubbles.p5.js'></script>
    <script>

      var source   = '<?php echo $source; ?>';
      var onselect = function (key)
      {
          $('#next').attr ('action', 'bubbles.php?topic=' + key);
          $('input#topic').val (key);
          $('#next').submit ();
      };

      var ontip = function (key)
      {
          return "double click to drill down";
      };

      var bubbles = new Bubbles (source, null, onselect, ontip);

      $(document).ready (function()
      {
          $('#steps').slidein ({ open: false, opacity: 0.7, peek: 0, breadth: 300, toClose: 'hover' });
      });

    </script>
  </head>
  <body>
    <form id='next' action='' method='post'>
      <?php echo $fields; ?>
    </form>
    <h1><?php echo "$type: $subject"; ?></h1>
    <div id='steps'>
      <ol>
        <?php echo $steps; ?>
      </ol>
    </div>
  </body>
</html>
