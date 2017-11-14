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
  $first  = count (getPostParams ('topic-')) == 0 ? 'true' : 'false';  // output to js script later
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
          return "double-click to open the '" + data.name + "' bubble";
      };

      var bubbles = new Bubbles (source, null, onselect, ontip);

      $(document).ready (function()
      {
          $("#steps").slidein ({ open: false, opacity: 0.7, peek: 0, breadth: 300, toOpen: "hover", toClose: "hover", "position": 75 });

          if (<?php echo $first; ?>)
          {
              $("#prompt").delay (2500).fadeIn (600).delay (5500).fadeOut (900);
          }
      });

    </script>
  </head>
  <body>
    <form id="next" action="#" method="post">
      <?php echo $fields; ?>
    </form>
    <h1><?php echo "$type: $subject"; ?></h1>
    <div id="prompt"><span>Double-click the bubbles to open them up<hr/>Open the blue handle on the left for more information</span></div>
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
