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

    // --- returns a single content item for the given content key

    public static function getContent ($key)
    {
        $sql = "SELECT c.title,
                       c.image,
                       c.country,
                       c.year,
                       c.duration
                  FROM content c
                 WHERE c.content_id = ':key'";

        $content = self::executeQuery ($sql, ['key' => $key]);
        return array_shift ($content);
    }

    // --- returns every content item

    public static function getAllContent ()
    {
        $sql = "SELECT c.content_id,
                       c.title,
                       c.image,
                       c.country,
                       c.year,
                       c.duration
                  FROM content c
              ORDER BY c.year,
                       c.title";

        return self::executeQuery ($sql);
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
                 WHERE no.utterance_id = u.utterance_id
                   AND u.pos  = ':pos'
                   AND u.stem = ':stem'";

        $data = self::executeQuery ($sql, ['pos' => $pos, 'stem' => $stem]);
        return self::makeMap ($data, 'stem', 'tally');
    }

    // --- returns the normative word occurrences for the given part-of-speech and content key

    public static function getContentNormativeOccurrences ($pos, $key)
    {
        $sql = "SELECT u.stem,
                       SUM(no.tally) tally
                  FROM normative_occurrence no,
                       utterance u,
                       occurrence o
                 WHERE no.utterance_id = u.utterance_id
                   AND u.pos = ':pos'
                   AND o.content_id = ':key'
                   AND o.utterance_id = u.utterance_id
              GROUP BY u.stem";

        $data = self::executeQuery ($sql, ['pos' => $pos, 'key' => $key]);
        return self::makeMap ($data, 'stem', 'tally');
    }

    // --- returns the content counts for the given part-of-speech

    public static function getContentCounts ($pos)
    {
        $sql = "SELECT l.content_id,
                       l.tally
                  FROM lexicon l
                 WHERE l.pos = ':pos'";

        $data = self::executeQuery ($sql, ['pos' => $pos]);
        return self::makeMap ($data, 'content_id', 'tally');
    }

    // --- returns the word occurrences for the given part-of-speech and stem

    public static function getWordOccurrences ($pos, $stem)
    {
        $sql = "SELECT c.content_id,
                       c.title,
                       u.stem,
                       u.stem utterance,
                       SUM(o.tally) tally
                  FROM occurrence o,
                       utterance u,
                       content c
                 WHERE o.utterance_id = u.utterance_id
                   AND o.content_id = c.content_id
                   AND u.pos  = ':pos'
                   AND u.stem = ':stem'
              GROUP BY c.content_id";

        return self::executeQuery ($sql, ['pos' => $pos, 'stem' => $stem]);
    }

    // --- returns the word occurrences for the given part-of-speech and content key

    public static function getContentOccurrences ($pos, $key)
    {
        $sql = "SELECT c.content_id,
                       c.title,
                       u.stem,
                       us.utterances utterance,
                       SUM(o.tally) tally
                  FROM occurrence o,
                       utterance u,
                       content c,
                      (SELECT u.stem,
                              GROUP_CONCAT(u.utterance SEPARATOR ' ') utterances
                         FROM utterance u,
                              occurrence o
                        WHERE o.content_id = ':key'
                          AND u.pos = ':pos'
                          AND u.utterance_id = o.utterance_id
                     GROUP BY u.stem) us
                 WHERE o.utterance_id = u.utterance_id
                   AND o.content_id = c.content_id
                   AND c.content_id = ':key'
                   AND u.pos  = ':pos'
                   AND u.stem = us.stem
              GROUP BY u.stem";

        return self::executeQuery ($sql, ['pos' => $pos, 'key' => $key]);
    }
}

?>
