<?php

  // --- called to provide the json document to power the bubble drawing

  include_once 'lib/model.php';
  include_once 'lib/helper.php';
  include_once 'lib/loglikelihood.php';

  // --- calculates the log likelihood for the given context

  function getLogLikelihood ($context, $movie, $limit)
  {
      $results = [];

      $pos = Model::$posWord;  // we only handle work ngrams as yet
      $n1  = Model::getNormativeCount ($pos); // num words on corpus 1
      $n2s = Model::getMovieCounts    ($pos); // num words on corpus 2

      if ($movie)
      {
          $o1s = Model::getMovieNormativeOccurrences ($pos, $context); // observed words on corpus 1
          $o2s = Model::getMovieOccurrences          ($pos, $context); // observed words on corpus 2
      }
      else
      {
          $o1s = Model::getWordNormativeOccurrences  ($pos, $context); // observed words on corpus 1
          $o2s = Model::getWordOccurrences           ($pos, $context); // observed words on corpus 2
      }

      foreach ($o2s as $item)
      {
          $n2 = $n2s [$item->id];
          $o1 = $o1s [$item->stem];
          $o2 = $item->tally;

          $ll  = logLikelihood ($n1, $o1, $n2, $o2);  // the magic lies in here ;)
          $ref = $movie ? $item->stem : $item->id;

          $results ['_'.$ref] =  // underscore ensures that all keys are strings - else some are ints
          [
              'name'  => $movie ? $item->utterance : $item->title,
              'topic' => $movie ? shortest (explode (' ', $item->utterance)) : $item->id,
              'count' => $ll,
          ];
      }

      uasort ($results, function ($a, $b)
      {
          return $a ['count'] < $b ['count'];  // sort into loglikelihood order
      });

      return json_encode (array_slice ($results, 0, $limit), JSON_FORCE_OBJECT);
  }

  // --- provide the json document to power the bubble drawing

  function getNGrams ($type, $topic, $limit)
  {
      $ngrams = '{}';

      if ($topic)
      {
          if ($type == 'movie')
          {
              $ngrams = getLogLikelihood ($topic, true, $limit);
          }
          else if ($type == 'word')
          {
              $ngrams = getLogLikelihood ($topic, false, $limit);
          }
          else if ($type == 'person')
          {

          }
          else if ($type == 'genre')
          {

          }

      }

      return $ngrams;
  }

  define ('MAX_ITEMS', 75);  // the maximum number of items to return to the bubbles
  header ('Content-Type: application/json');
  echo getNGrams (getCleanParam ('type'), getCleanParam ('topic'), max (0, getParam ('limit', MAX_ITEMS)));

// don't close the PHP tag else traling new lines will go into the json response document
