<?php

include_once 'database.php';

// --- the model class for accessing the database schema

class Model extends Database
{
    public static $posWord = 'WORD';

    // --- make a one value map from 'key' to the 'value'

    private static function makeMap ($data, $key, $value)
    {
        $map = [];
        $idx = 0;

        foreach ($data as $item)
        {
            $map [$key ? $item->$key : $idx++] = $item->$value;
        }

        return $map;
    }

    // --- returns a single movie item for the given movie key

    public static function getMovie ($key)
    {
        $sql = "SELECT m.id,
                       m.title,
                       m.year,
                       m.duration,
               (SELECT GROUP_CONCAT(g.genre)
                  FROM category g
                 WHERE g.movie_id = m.id) genres,
               (SELECT GROUP_CONCAT(CONCAT(c.role, '|', p.id, '|', p.name))
                  FROM cast c,
                       person p
                 WHERE c.person_id = p.id
                   AND c.movie_id = m.id ) cast
                  FROM movie m
                 WHERE m.id = :key";

        return array_shift (self::executeQuery ($sql, ['key' => $key]));
    }

    // --- returns every movie item for a given year

    public static function getMoviesByYear ($year)
    {
        $sql = "SELECT m.id,
                       m.title,
                       m.year,
                       m.duration,
               (SELECT GROUP_CONCAT(g.genre)
                  FROM category g
                 WHERE g.movie_id = m.id) genres,
               (SELECT GROUP_CONCAT(CONCAT(c.role, '|', p.id, '|', p.name))
                  FROM cast c,
                       person p
                 WHERE c.person_id = p.id
                   AND c.movie_id = m.id ) cast
                  FROM movie m
                 WHERE m.year = :year
              GROUP BY m.id
              ORDER BY m.title";

        return self::executeQuery ($sql, ['year' => $year]);
    }

    // --- returns every movie item

    public static function getAllMovies ()
    {
        $sql = "SELECT m.id,
                       m.title,
                       m.year,
                       m.duration,
               (SELECT GROUP_CONCAT(g.genre)
                  FROM category g
                 WHERE g.movie_id = m.id) genres,
               (SELECT GROUP_CONCAT(CONCAT(c.role, '|', p.id, '|', p.name))
                  FROM cast c,
                       person p
                 WHERE c.person_id = p.id
                   AND c.movie_id = m.id ) cast
                  FROM movie m
              GROUP BY m.id
              ORDER BY m.year DESC,
                       m.title";

        return self::executeQuery ($sql);
    }

    // --- returns the image for a movie id

    public static function getMovieImage ($key)
    {
        $sql = "SELECT i.packshot
                  FROM image i
                 WHERE i.movie_id = :key";

        $item = array_shift (self::executeQuery ($sql, ['key' => $key]));
        return $item ? $item->packshot : null;
    }

    // --- returns a set of utterances for a given stem

    public static function getUtterances ($pos, $stem)
    {
        $sql = "SELECT u.utterance
                  FROM utterance u
                 WHERE u.stem = ':stem'
                   AND u.pos  = ':pos'
              ORDER BY LENGTH(u.utterance)";

        $data = self::executeQuery ($sql, ['pos' => $pos, 'stem' => $stem]);
        return self::makeMap ($data, null, 'utterance');
    }

    // --- returns the normative count for the given part-of-speech

    public static function getNormativeCount ($pos)
    {
        $sql = "SELECT nl.tally
                  FROM normative_lexicon nl
                 WHERE nl.pos = ':pos'";

        $norm = self::executeQuery ($sql, ['pos' => $pos]);
        return array_shift ($norm)->tally;
    }

    // --- returns the normative word occurrences for the given part-of-speech and stem

    public static function getWordNormativeOccurrences ($pos, $stem)
    {
        $sql = "SELECT u.stem,
                       SUM(no.tally) tally
                  FROM normative_occurrence no,
                       utterance u
                 WHERE no.utterance_id = u.id
                   AND u.pos  = ':pos'
                   AND u.stem = ':stem'";

        $data = self::executeQuery ($sql, ['pos' => $pos, 'stem' => $stem]);
        return self::makeMap ($data, 'stem', 'tally');
    }

    // --- returns the normative word occurrences for the given part-of-speech and movie key

    public static function getMovieNormativeOccurrences ($pos, $key)
    {
        $sql = "SELECT u.stem,
                       SUM(no.tally) tally
                  FROM normative_occurrence no,
                       utterance u,
                       occurrence o
                 WHERE no.utterance_id = u.id
                   AND u.pos = ':pos'
                   AND o.movie_id = :key
                   AND o.utterance_id = u.id
              GROUP BY u.stem";

        $data = self::executeQuery ($sql, ['pos' => $pos, 'key' => $key]);
        return self::makeMap ($data, 'stem', 'tally');
    }

    // --- returns the movie counts for the given part-of-speech

    public static function getMovieCounts ($pos)
    {
        $sql = "SELECT l.movie_id,
                       l.tally
                  FROM lexicon l
                 WHERE l.pos = ':pos'";

        $data = self::executeQuery ($sql, ['pos' => $pos]);
        return self::makeMap ($data, 'movie_id', 'tally');
    }

    // --- returns the word occurrences for the given part-of-speech and stem

    public static function getWordOccurrences ($pos, $stem)
    {
        $sql = "SELECT m.id,
                       m.title,
                       u.stem,
                       u.stem utterance,
                       SUM(o.tally) tally
                  FROM occurrence o,
                       utterance u,
                       movie m
                 WHERE o.utterance_id = u.id
                   AND o.movie_id = m.id
                   AND u.pos  = ':pos'
                   AND u.stem = ':stem'
              GROUP BY m.id";

        return self::executeQuery ($sql, ['pos' => $pos, 'stem' => $stem]);
    }

    // --- returns the word occurrences for the given part-of-speech and stem for a given year

    public static function getWordOccurrencesInYear ($pos, $stem, $year)
    {
        $sql = "SELECT m.id,
                       m.title,
                       SUM(o.tally) tally
                  FROM occurrence o,
                       utterance u,
                       movie m
                 WHERE o.utterance_id = u.id
                   AND o.movie_id = m.id
                   AND m.year =  :year
                   AND u.pos  = ':pos'
                   AND u.stem = ':stem'
              GROUP BY o.movie_id
              ORDER BY SUM(o.tally) desc";

        return self::executeQuery ($sql, ['pos' => $pos, 'stem' => $stem, 'year' => $year]);
    }

    // --- returns the word occurrences for the given part-of-speech and movie key

    public static function getMovieOccurrences ($pos, $key)
    {
        $sql = "SELECT m.id,
                       m.title,
                       cu.utterances utterance,
                       co.stem,
                       co.tally
                  FROM movie m,
                       movie_utterance cu,
                       movie_occurrence co
                 WHERE cu.movie_id = co.movie_id
                   AND cu.pos = co.pos
                   AND cu.stem = co.stem
                   AND co.movie_id = m.id
                   AND co.movie_id = :key
                   AND co.pos = ':pos'";

        return self::executeQuery ($sql, ['pos' => $pos, 'key' => $key]);
    }

    // --- returns the probability of a word occurrences for the given part-of-speech and stem by year

    public static function getWordProbabilityByYear ($pos, $stem)
    {
        $sql = "SELECT m.year,
                       t.tally movies,
                       SUM(o.tally) tally,
                       SUM(o.tally) / t.tally chance
                  FROM occurrence o,
                       utterance u,
                       movie m,
                       (SELECT m.year,
                               COUNT(m.year) tally
                          FROM movie m
                      GROUP BY m.year) t
                 WHERE o.utterance_id = u.id
                   AND o.movie_id = m.id
                   AND t.year = m.year
                   AND u.pos  = ':pos'
                   AND u.stem = ':stem'
              GROUP BY m.year
              ORDER BY m.year";

        return self::executeQuery ($sql, ['pos' => $pos, 'stem' => $stem]);
    }
}

?>
