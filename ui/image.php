<?php

  include_once 'lib/helper.php';
  include_once 'lib/model.php';

  $movie = getCleanParam ('topic');

  if ($movie)
  {
      $raw = Model::getMovieImage ($movie);

      if ($raw)
      {
          header ('Content-type: image/jpeg');
          echo imagejpeg (imagecreatefromstring (base64_decode ($raw)));
      }
  }

  // warning: don't close the php tag else trailing newlines will go into the binary return document
