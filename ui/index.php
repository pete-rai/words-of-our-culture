<!DOCTYPE html>
<html lang="en">
<head>
  <title>wooc &raquo; Movie Index</title>
  <meta charset="UTF-8">
  <meta name=viewport content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.7/css/bootstrap.min.css" />
  <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.7/css/bootstrap-theme.css" />
  <link rel="stylesheet" href="css/index.css">
  <script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
  <script src="//cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.7/js/bootstrap.min.js"></script>
</head>
<body>
  <h1>Words of our Culture</h1>
  <h2>Words</h2>
  <form action="bubbles.php">
    <input id="topic" name="topic" placeholder="type word + hit return" />
  </form>
  <h2>Random</h2>
  <div class='container'>
    <div class='col-md-6'>
      <div class="tile noart">
        <a href="random.php?type=movie">Random Movie</a>
      </div>
    </div>
    <div class='col-md-6'>
      <div class="tile noart">
        <a href="random.php?type=word">Random Word</a>
      </div>
    </div>
  </div>
  <?php $year = 2018; include 'list.php'; ?>
  <?php $year = 2017; include 'list.php'; ?>
  <script>

    var year = 2017;
    var last = 1932;

    $(window).scroll (function ()
    {
        if (year >= last && $(window).scrollTop () > $(document).height () - $(window).height () * 2)  // 2 = conservative load
        {
            year--;
            $.get ("list.php?year=" + year, function (movies) { $("body").append (movies); });
        }
    });

  </script>
</body>
</html>
