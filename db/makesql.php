<?php

include_once '../lib/stemmer.php';
include_once '../lib/logger.php';

// --- file locations

define ('FILE_CORPUS'   , '../corpus/text/');
define ('FILE_SQL'      , './sql/');
define ('FILE_SPEC'     ,  FILE_CORPUS.'*_full.txt');
define ('SQL_MOVIE'     ,  FILE_SQL.'movie.sql');
define ('SQL_IMAGE'     ,  FILE_SQL.'image.sql');
define ('SQL_ORIGIN'    ,  FILE_SQL.'origin.sql');
define ('SQL_CATEGORY'  ,  FILE_SQL.'category.sql');
define ('SQL_PERSON'    ,  FILE_SQL.'person.sql');
define ('SQL_CAST'      ,  FILE_SQL.'cast.sql');
define ('SQL_UTTERANCE' ,  FILE_SQL.'utterance.sql');
define ('SQL_OCCURRENCE',  FILE_SQL.'occurrence.sql');

// --- standard string cleanser

function cleanse ($text)
{
    $text = iconv ('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text); // accented character to 'normal'
    $text = preg_replace ('/[\r\n\s\t]+/xms', ' '    , $text); // normalise whitespace to one space
    $text = preg_replace ('/[^\w\s]+/xms'   , ''     , $text); // remove all punctuation
    return strtolower (trim ($text));                          // lowercase and trimmed
}

// --- appends a line in a file

function append ($file, $value)
{
    file_put_contents ($file, "$value\n", FILE_APPEND);
}

// --- wipes a file

function wipe ($file)
{
    file_put_contents ($file, '');
}

// --- escape a string for sql

function esc ($text)
{
    return trim (str_replace ("'", "\'", $text));
}

// --- returns a numeric key

function num ($key)
{
    return str_replace (['tt', 'nm'], '1', $key);  // don't lead with zero
}

// --- parses analysis into lexical parts

function getLexicals ($file)
{
    $lines    = file ($file);
    $text     = '';
    $phrases  = [];
    $lexicals = [];

    foreach ($lines as $line)
    {
        for ($i=0 ; $i<strlen ($line) ; $i++)
        {
            if ($line [$i] == '(')
            {
                $tag = '';

                while ($line [++$i] != ' ')
                {
                    $tag .= $line [$i];
                }

                array_push ($phrases, ['tag' => $tag, 'text' => '']);
            }
            else if ($line[$i] == ')')
            {
                $lexicals [] = array_pop ($phrases);
            }
            else
            {
                foreach ($phrases as &$phrase)
                {
                    $phrase ['text'] .= $line [$i];
                }
            }
        }
    }

    return $lexicals;
}

// --- makes metadata tables SQL

function metadataSQL ($key, $details)
{
    $details->title    = esc ($details->title);
    $details->image    = esc ($details->image);
    $details->packshot = esc ($details->packshot);

    $key = num ($key);

    append (SQL_MOVIE, "INSERT INTO movie (id, title, year, duration) VALUES ($key, '$details->title', $details->year, $details->duration);");
    append (SQL_IMAGE, "INSERT INTO image (movie_id, packshot) VALUES ($key, '$details->packshot');");

    foreach ($details->country as $country)
    {
        $country = esc ($country);
        append (SQL_ORIGIN, "INSERT INTO origin (movie_id, country) VALUES ($key, '$country');");
    }

    foreach ($details->genres as $genre)
    {
        $genre = esc ($genre);
        append (SQL_CATEGORY, "INSERT INTO category (movie_id, genre) VALUES ($key, '$genre');");
    }

    foreach ($details->director as $director)
    {
        $director->id   = num ($director->id);
        $director->name = esc ($director->name);

        append (SQL_PERSON, "INSERT IGNORE INTO person (id, name) VALUES ($director->id, '$director->name');");
        append (SQL_CAST  , "INSERT INTO cast (movie_id, person_id, role) VALUES ($key, $director->id, 'D');");
    }

    foreach ($details->cast as $idx => $cast)
    {
        $cast->id   = num ($cast->id);
        $cast->name = esc ($cast->name);
        $idx += 1;  // so as not zero based

        append (SQL_PERSON, "INSERT IGNORE INTO person (id, name) VALUES ($cast->id, '$cast->name');");
        append (SQL_CAST  , "INSERT INTO cast (movie_id, person_id, role) VALUES ($key, $cast->id, '$idx');");
    }
}

// --- makes utterance tables SQL

function utteranceSQL ($key, $script)
{
    $poss   = ['WORD']; //, 'BI-GRAM']; //, 'TRI-GRAM'];
    $count  = count ($poss);
    $words  = explode (' ', cleanse ($script));
    $utters = [];
    $buffer = [];

    $key = num ($key);

    for ($i=0 ; $i<$count ; $i++)
    {
        $buffer [] = '';
    }

    foreach ($words as $word)
    {
        array_shift ($buffer);
        array_push  ($buffer, $word);

        for ($i=1 ; $i<=$count ; $i++)
        {
            $utterance = trim (implode (' ', array_slice ($buffer, -1 * $i, $i)));

            if (strlen ($utterance) && substr_count ($utterance, ' ') == $i - 1) // no empty string, but allow '0'
            {
                if (!isset ($utters [$utterance]))
                {
                    $utters [$utterance] = 0;
                }

                $utters [$utterance]++;
            }
        }
    }

    $sqlUtter = [];
    $sqlOccur = [];

    $curr = count ($utters);
    $idx  = 0;
    $step = 1000;  // rows per statement

    foreach ($utters as $utter=>$count)
    {
        $stem = PorterStemmer::Stem ($utter);
        $pos  = $poss [substr_count ($utter, ' ')];
        $ukey = md5 ($utter);

        if ($idx == 0)
        {
            $sqlOccur [] = "INSERT INTO occurrence (movie_id, utterance_id, tally) VALUES ";
            $sqlUtter [] = "INSERT IGNORE INTO utterance (id, pos, utterance, stem) VALUES ";
        }

        $idx++;
        $curr--;

        $sep = ',';

        if ($idx == $step || $curr == 0)
        {
            $idx = 0;
            $sep = ';';
        }

        $sqlUtter [] = "('$ukey', '$pos', '$utter', '$stem')$sep";
        $sqlOccur [] = "($key, '$ukey', $count)$sep";
    }

    append (SQL_UTTERANCE , implode ("\n", $sqlUtter));
    append (SQL_OCCURRENCE, implode ("\n", $sqlOccur));
}

// -- processes all the corpus files into SQL

function process ($strict = true)  // strict means stop on errors
{
    $errors = [];
    $files  = glob (FILE_SPEC);
    sort ($files);

    wipe (SQL_MOVIE);
    wipe (SQL_IMAGE);
    wipe (SQL_ORIGIN);
    wipe (SQL_CATEGORY);
    wipe (SQL_PERSON);
    wipe (SQL_CAST);
    wipe (SQL_UTTERANCE);
    wipe (SQL_OCCURRENCE);

    foreach ($files as $file)
    {
        $info = str_replace ('_full', '_info', $file);
        $name = str_replace (['.txt', '_full', '_'], ['', '', ' '], substr ($file, strlen (FILE_CORPUS)));
        $key  = substr ($file, strlen (FILE_CORPUS), 9);

        Log::line ();
        Log::info ($name);

        if (!file_exists ($info))
        {
            Log::warn ('no info file');
            $errors [] = $key;
            if ($strict) die ();
        }
        else
        {
            $script  = file_get_contents ($file);
            $details = json_decode (file_get_contents ($info));

            if (!$details)
            {
                Log::warn ('empty info');
                $errors [] = $key;
                if ($strict) die ();
            }
            else if (!$script)
            {
                Log::warn ('no script');
                $errors [] = $key;
                if ($strict) die ();
            }
            else
            {
                metadataSQL  ($key, $details);
                utteranceSQL ($key, $script);
            }
        }
    }

    Log::line ();

    if ($errors)
    {
        Log::warn ('errors found: '.implode (', ', array_unique ($errors)));
    }
    else
    {
        Log::info ('errors found: none');
    }

    Log::line ();
}

process ();
