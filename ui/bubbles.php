<?php

  include_once 'lib/stemmer.php';
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
      $topic   = PorterStemmer::stem ($topic);
      $subject = implode (', ', Model::getUtterances (Model::$posWord, $topic));
      $type    = strpos ($subject, ',') ? 'Words' : 'Word';
  }

  $title  = 'wooc'.($subject ? " &raquo; $subject" : '');
  $source = "ngrams.php?topic=$topic";  // data source url
  $first  = count (getPostParams ('topic-')) == 0;
  $items  = array_merge (getPostParams ('topic-'), [$subject]);
  $steps  = implode ("\n", getListItems  ($items))."\n";
  $fields = implode ("\n", setPostParams ($items, 'topic-'))."\n";

?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <title><?php echo $title; ?></title>
    <meta charset="UTF-8">
    <meta name=viewport content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="css/jquery.slidein.css">
    <link rel="stylesheet" href="css/bubbles.css">
    <script src="//cdnjs.cloudflare.com/ajax/libs/p5.js/0.5.8/p5.min.js"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
    <script src="js/whenpresent.js"></script>
    <script src="js/jquery.slidein.js"></script>
    <script src="js/bubbles.p5.js"></script>
    <script src="//platform-api.sharethis.com/js/sharethis.js#property=59ed1844899d1d001141cf42&product=unknown" async="async"></script>
    <script>

      whenPresent (".st-sticky-share-buttons", function (control)
      {
          control.css ("opacity", "0.7");
      });

      var source   = "<?php echo $source; ?>";
      var onselect = function (key, data)
      {
          $("#next").attr ("action", "bubbles.php?topic=" + data.topic);
          $("input#topic").val (key);
          $("#next").submit ();
      };

      var ontip = function (key, data)
      {
          return "double click to open the '" + data.name + "' bubble";
      };

      var bubbles = new Bubbles (source, null, onselect, ontip);

      $(document).ready (function()
      {
          $("#steps").slidein ({ open: false, opacity: 0.7, peek: 0, breadth: 300, toOpen: "hover", toClose: "hover", prompt: "info" });
      });

    </script>
  </head>
  <body>
    <form id="next" action="#" method="post">
      <?php echo $fields; ?>
    </form>
    <h1><?php echo "$type: $subject"; ?></h1>
    <div id="steps">
      <h2>Words of our Culture</h2>
      <ol>
        <?php echo $steps; ?>
      </ol>
      <span id="footer">
        <a href="https://github.com/pete-rai/words-of-our-culture#words-of-our-culture">About</a>&nbsp;&nbsp;|&nbsp;&nbsp;
        <a href="content.php">Index</a>&nbsp;&nbsp;|&nbsp;&nbsp;
        <a href="http://rai.org.uk">Contact</a>
      </span>
    </div>
  </body>
</html>
