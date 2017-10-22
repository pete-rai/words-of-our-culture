<?php

  // --- called to provide the json document to power the bubble drawing

  include_once 'lib/model.php';
  include_once 'lib/helper.php';
  include_once 'lib/stemmer.php';
  include_once 'lib/loglikelihood.php';

  define ('MAX_ITEMS', 100);  // the maximum number of items to return to the bubbles

  // --- calculates the log likelihood for the given context

  function getLogLikelihood ($context, $content)
  {
      $results = [];

      $pos = Model::$posWord;  // we only handle work ngrams as yet
      $n1  = Model::getNormativeCount ($pos); // num words on corpus 1
      $n2s = Model::getContentCounts  ($pos); // num words on corpus 2

      if ($content)
      {
          $o1s = Model::getContentNormativeOccurrences ($pos, $context); // observed words on corpus 1
          $o2s = Model::getContentOccurrences          ($pos, $context); // observed words on corpus 2
      }
      else
      {
          $o1s = Model::getWordNormativeOccurrences    ($pos, $context); // observed words on corpus 1
          $o2s = Model::getWordOccurrences             ($pos, $context); // observed words on corpus 2
      }

      // calculate the loglikelihood for each item

      foreach ($o2s as $item)
      {
          $n2 = $n2s [$item->content_id];
          $o1 = $o1s [$item->stem];
          $o2 = $item->tally;

          $ll  = logLikelihood ($n1, $o1, $n2, $o2);  // the magic lies in here ;)
          $ref = $content ? $item->stem : $item->content_id;

          $results [$ref] =
          [
              'content_id' => $item->content_id,
                   'title' => $item->title,
               'utterance' => $item->utterance,
                      'll' => $ll
          ];
      }

      array_walk ($results, function ($a) use (&$max)
      {
          $max = max ($max, $a ['ll']);  // find the max loglikelihood
      });

      array_walk ($results, function (&$a) use ($max)
      {
          $a ['percent'] = $a ['ll'] / $max * 100;  // rebase all to be a percentage of the maximum
      });

      uasort ($results, function ($a, $b)
      {
          return $a ['ll'] < $b ['ll'];  // sort into loglikelihood order
      });

      return $results;
  }

  // --- make a list in the format that the bubbles are expecting

  function makeList ($items, $content)
  {
      $tags  = [];
      $items = array_slice ($items, 0, MAX_ITEMS);

      foreach ($items as $id=>$item)
      {
          $tags [$id] =
          [
              'name'  => $item [$content ? 'utterance' : 'title'],
              'count' => $item ['ll'],
          ];
      }

      return json_encode ($tags, JSON_FORCE_OBJECT);
  }

  // --- provide the json document to power the bubble drawing

  function getNGrams ($topic)
  {
      $json = '{}';

      if ($topic)
      {
          $content = strpos ($topic, 'tt') === 0;
          $json    = makeList (getLogLikelihood ($topic, $content), $content);
      }

      return $json;
  }

  header ('Content-Type: application/json');
  echo getNGrams (getCleanParam ('topic'));

?>
