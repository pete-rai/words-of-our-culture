<?php

include_once '../lib/stemmer.php';
include_once '../lib/logger.php';

date_default_timezone_set ('UTC'); // important - do not remove and leave as first line

// --- standard string cleanser

function cleanse ($text)
{
    $text = iconv ('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text); // accented character to 'normal'
    $text = preg_replace ('/[\r\n\s\t]+/xms', ' '    , $text); // normalise whitespace to one space
    $text = preg_replace ('/[^\w\s]+/xms'   , ''     , $text); // remove all punctuation
    return strtolower (trim ($text));                          // lowercase and trimmed
}

// --- parses analysis into lexical parts

function getLexicals ($file)
{
    $lines    = file ($file);
    $text     = '';
    $phrases  = array ();
    $lexicals = array ();

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

                array_push ($phrases, array ('tag' => $tag, 'text' => ''));
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

// --- makes content table SQL

function contentSQL ($key, $details)
{
    $title = trim (str_replace ("'", "\'", $details[0]));
    $place = trim (str_replace ("'", "\'", $details[1]));
    $year  = trim ($details[2]);
    $len   = trim ($details[3]);
    $image = trim (str_replace ("'", "\'", $details[4]));

    return "INSERT INTO content (content_id, title, country, year, duration, script, image) VALUES ('$key', '$title', '$place', $year, $len, '', '$image');";
}

// --- makes utterances table SQL

function utterancesSQL ($key, $script)
{
    $poss   = array ('WORD'); //, 'BI-GRAM'); //, 'TRI-GRAM');
    $count  = count ($poss);
    $words  = explode (' ', cleanse ($script));
    $utters = array ();
    $buffer = array ();

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

    $sqlUtter = array ();
    $sqlOccur = array ();

    $curr = count ($utters);
    $idx  = 0;
    $step = 500;

    foreach ($utters as $utter=>$count)
    {
        $stem = PorterStemmer::Stem ($utter);
        $pos  = $poss [substr_count ($utter, ' ')];
        $ukey = md5 ($utter);

        if ($idx == 0)
        {
        //  $sqlOccur [] = "SELECT CONCAT (now(), ' - ', '$key', ' - ', $curr) info;";
            $sqlOccur [] = "INSERT INTO occurrence (content_id, utterance_id, tally) VALUES ";
        }

        $idx++;
        $curr--;

        $sep = ',';

        if ($idx == $step || $curr == 0)
        {
            $idx = 0;
            $sep = ';';
        }

        $sqlUtter [] = "INSERT INTO utterance (utterance_id, pos, utterance, stem) VALUES ('$ukey', '$pos', '$utter', '$stem');";
        $sqlOccur [] = "('$key', '$ukey', $count) $sep ";
    }

    return array ('utter'=> $sqlUtter, 'occur'=> $sqlOccur);
}

// -- processes all the corpus files into SQL

function process ()
{
    $corpus     = "../corpus/movies/text";
    $filespec   = "$corpus/*_full.txt";
    $sqlContent = "./sql/content.sql";
    $sqlUtter   = "./sql/utter.sql";
    $sqlOccur   = "./sql/occur.sql";
    $sqlTemp    = "./sql/temp.sql";

    $files = glob ($filespec);
    sort ($files);

    file_put_contents ($sqlContent, '');
    file_put_contents ($sqlUtter  , '');
    file_put_contents ($sqlOccur  , '');

    foreach ($files as $file)
    {
        $info = str_replace ('_full', '_info', $file);
        $name = str_replace (array ('.txt', '_full', '_'), array ('', '', ' '), substr ($file, strlen ($corpus) + 1));
        $key  = substr ($file, strlen ($corpus) + 1, 9);

        Log::line ();
        Log::info ($name);

        if (!file_exists ($info))
        {
            Log::warn ('no info file');
        }
        else
        {
            $script  = file_get_contents ($file);
            $details = file ($info);

            if (count ($details) != 5)
            {
                Log::warn ('incorrect info file', count ($details));
            }
            else if (!$script)
            {
                Log::warn ('no script - skipping');
            }
            else
            {
                $sql = contentSQL ($key, $details);
                file_put_contents ($sqlContent, $sql."\n", FILE_APPEND);

                $sql = utterancesSQL ($key, $script);

                file_put_contents ($sqlUtter, implode ("\n", $sql['utter'])."\n", FILE_APPEND);
                file_put_contents ($sqlOccur, implode ("\n", $sql['occur'])."\n", FILE_APPEND);
            }
        }
    }

    Log::line ();
    Log::info ("sorting");
    system ("sort $sqlUtter > $sqlTemp" );

    Log::line ();
    Log::info ("uniqing");
    system ("uniq $sqlTemp  > $sqlUtter");

    Log::line ();
    Log::info ("done");
    unlink ($sqlTemp);

    Log::line ();
}

  process ();

?>
