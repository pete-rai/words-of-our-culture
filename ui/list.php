<?php

  include_once 'lib/helper.php';
  include_once 'lib/model.php';

  if (!isset ($year)) $year = getCleanParam ('year');
  $movies = $year ? Model::getMoviesByYear (intval ($year)) : [];

  foreach ($movies as $idx => $movie)
  {
      $cast = [];

      foreach (explode (',', $movie->cast) as $billings)
      {
          $billing = explode ('|', $billings);
          $cast [array_shift ($billing)] = ['id' => array_shift ($billing), 'name' => array_shift ($billing)];
      }

      $movies [$idx]->cast     = $cast;
      $movies [$idx]->genres   = explode (',', $movie->genres);
      $movies [$idx]->duration = intdiv ($movie->duration, 60).':'.str_pad ($movie->duration % 60, 2, '0', STR_PAD_LEFT);
  }

?>
<?php if ($movies) { ?>
  <h2><?= $year ?></h2>
  <div class='container'>
  <?php foreach ($movies as $movie) { ?>
    <div class="col-md-3 col-sm-4">
      <div class="tile">
        <img class="poster" src="image.php?topic=<?= $movie->id ?>" alt="<?= $movie->title ?>" />
        <div class="info">
          <h3><a href="bubbles.php?type=movie&topic=<?= $movie->id ?>"><?= $movie->title ?></a></h3>
          <p><?= implode (', ', $movie->genres) ?></p>
          <small><b>cast</b></small>
          <ul>
            <li><a href=""><?= $movie->cast ['1']['name'] ?></a></li>
            <li><a href=""><?= $movie->cast ['2']['name'] ?></a></li>
            <li><a href=""><?= $movie->cast ['3']['name'] ?></a></li>
          </ul>
          <small><b>director</b></small>
          <ul>
            <li><a href=""><?= $movie->cast ['1']['name'] ?></a></li>
          </ul>
        </div>
      </div>
    </div>
  <? } ?>
  </div>
<? } ?>
