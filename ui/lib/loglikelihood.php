<?php

  // for more info see : http://ucrel.lancs.ac.uk/llwizard.html

  // $n1 = total words in corpus 1 (usually the normative corpus)
  // $n2 = total words in corpus 2
  // $o1 = observed count for the word in corpus 1 (usually the normative corpus)
  // $o2 = observed count for the word in corpus 2

  function logLikelihood ($n1, $o1, $n2, $o2)
  {
      $ll = 0;

      if ($o1 && $o2)
      {
          // calculate expected values

          $e1 = $n1 * ($o1 + $o2) / ($n1 + $n2); // expected counts in corpus 1
          $e2 = $n2 * ($o1 + $o2) / ($n1 + $n2); // expected counts in corpus 2

          // calculate log likelihood

          $ll = (2 * (($o1 * log ($o1 / $e1)) + ($o2 * log ($o2 / $e2))));
      }

      return $ll;
  }

?>
