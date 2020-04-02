<?php

include_once 'lib/helper.php';
include_once 'lib/model.php';
include_once 'lib/stemmer.php';

$word = getCleanParam ('topic');

$chances = [];

foreach (range (1930, 2016) as $year)
{
    $chances [$year] = 0;
}

$stats = Model::getWordProbabilityByYear (Model::$posWord, PorterStemmer::stem ($word));

foreach ($stats as $stat)
{
    $chances [$stat->year] = $stat->chance;
}

$data = [];

foreach ($chances as $year => $chance)
{
    $counts = Model::getWordOccurrencesInYear (Model::$posWord, PorterStemmer::stem ($word), $year);

    $info  = '';
    $info .= '<table class="info">';
    $info .= '<tr><th colspan=2>'.$year.'</th></tr>';

    foreach ($counts as $count)
    {
        $info .= '<tr><td>'.$count->title.'</td><td>'.$count->tally.'</td></tr>';
    }

    $info .= '</table>';

    $data [] = ['short_year' => substr ($year, -2), 'info'=> $info, 'chance' => floatval ($chance)];
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <title>wooc &raquo; Movie Index</title>
  <meta charset="UTF-8">
  <meta name=viewport content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.7/css/bootstrap.min.css" />
  <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.7/css/bootstrap-theme.css" />
  <link rel="stylesheet" href="css/index.css">
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
  <style>
  #chartdiv {
    width: 100%;
    height: 500px;
  }

  table.info tr:last-child td
  {
      padding-bottom: 5px;
  }

  table.info tr td
  {
      border-bottom: 1px solid #ddd;
  }

  table.info tr:last-child td
  {
      padding-bottom: 5px;
      border-bottom: 0;
  }


  table.info th
  {
      text-align: center;
  }

  table.info td,
  table.info th
  {
      padding: 2px;
  }

  table.info tr td:last-child
  {
      padding-left: 6px;
      text-align: right;
  }


  </style>
  <script src="https://www.amcharts.com/lib/4/core.js"></script>
  <script src="https://www.amcharts.com/lib/4/charts.js"></script>
  <script src="https://www.amcharts.com/lib/4/themes/animated.js"></script>
  <script src="//xxplatform-api.sharethis.com/js/sharethis.js#property=59ed1844899d1d001141cf42&product=unknown" async="async"></script>
</head>
<body>
<div id="chartdiv"></div>
<script>
am4core.ready(function() {

// Themes begin
am4core.useTheme(am4themes_animated);
// Themes end

// Create chart instance
var chart = am4core.create("chartdiv", am4charts.XYChart);

// Add data
chart.data = <?= json_encode ($data) ?>;

// Create axes

var categoryAxis = chart.xAxes.push(new am4charts.CategoryAxis());
categoryAxis.dataFields.category = "short_year";
categoryAxis.renderer.grid.template.location = 0;
categoryAxis.renderer.minGridDistance = 30;

categoryAxis.renderer.labels.template.adapter.add("dy", function(dy, target) {
  if (target.dataItem && target.dataItem.index & 2 == 2) {
    return dy + 25;
  }
  return dy;
});

var valueAxis = chart.yAxes.push(new am4charts.ValueAxis());

// Create series
var series = chart.series.push(new am4charts.ColumnSeries());
series.dataFields.valueY = "chance";
series.dataFields.categoryX = "short_year";
series.name = "Visits";
series.columns.template.tooltipHTML = "{info}";
series.columns.template.fillOpacity = .8;

var columnTemplate = series.columns.template;
columnTemplate.strokeWidth = 2;
columnTemplate.strokeOpacity = 1;


}); // end am4core.ready()




</script>
  <script>
    (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
    (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
    m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
    })(window,document,'script','https://www.google-analytics.com/analytics.js','ga');
    ga('create', 'UA-88629800-1', 'auto');
    ga('send', 'pageview');
  </script>
</body>
</html>
