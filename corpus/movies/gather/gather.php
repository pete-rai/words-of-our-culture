<?php

// *** grabs, analyses and processes subtitles to form utterances ***

include_once ('../../../lib/logger.php');
include_once ('web.php');

date_default_timezone_set ('UTC'); // important - do not remove and leave as first line

// --- grabs content info from imdb

function imdb ($key)
{
    $info = array ();
    $url  = "http://www.imdb.com/title/$key";

    Log::info ('getting', $url);

    $xpaths = array
    (
        'title'    => "//h1[@itemprop='name']/text()[1]",
        'year'     => "//h1/span/a/text()",
        'image'    => "//div[@class='poster']//img/@src",
        'country'  => "//div[@id='titleDetails']//a[contains(@href, 'country_of_origin')][1]/text()",
        'lang'     => "//div[@id='titleDetails']//a[contains(@href, 'primary_language')][1]/text()",
        'duration' => "//div[@class='subtext']/time[@itemprop='duration']/text()",
    );

    $values = webValues ($url, $xpaths);
    $errors = false;

    foreach ($xpaths as $key=>$xpath)
    {
        if (count ($values [$key]) == 1) // expect one result per xpath
        {
            $values [$key] = $values [$key][0];
            Log::info ('found', $key, $values [$key]);
        }
        else
        {
            Log::warn ('not found one', count ($values [$key]), $key);

            if ($key == 'title' || $key == 'lang')
            {
                Log::warn ('mandatory element missing', $key);
                $errors = true;
            }
        }
    }

    // tidies up hours/mins to just mins - 1h 32 => 92

    $parts = explode ('h', $values ['duration']);
    $values ['duration'] = $parts [0] * 60 + (isset ($parts [1]) ? $parts [1] : 0);

    return $errors ? array () : $values; // all or none
}

// --- downloads a zip file and extracts subtitles

function processZip ($zipurl)
{
    Log::info ('getting', $zipurl);

    $subfound = '';
    $zipbody  = file_get_contents ($zipurl);
    $zipreal  = realFilename ($http_response_header, 'temp.zip');
    $zipfile  = ".//temp//$zipreal".

    Log::info ('zip file', $zipreal);

    if (strpos ($zipfile, '.zip') === false)
    {
        Log::warn ('not a zip file', $zipreal);
    }
    else
    {
        @unlink ($zipfile);
        usleep (500);

        file_put_contents ($zipfile, $zipbody);
        usleep (500);

        $zip = new ZipArchive ();
        $zip->open ($zipfile);

        $files = array ();

        for ($i=0; $i<$zip->numFiles; $i++)
        {
            $zipitem = $zip->statIndex ($i);
            $zipname = $zipitem ['name'];

            Log::info ('contains', $zipname);

            if (strpos ($zipname, '.srt') !== false)
            {
                if ($zipitem ['size'] > 0 && $zipitem ['comp_size'] > 0)
                {
                    $files [] = $zipname;
                }
                else
                {
                    Log::warn ('corrupted',  $zipitem ['size'], $zipitem ['comp_size']);
                }
            }
        }

        @$zip->close ();

        if (count ($files) == 0)
        {
            Log::warn ('nothing in zip');
        }
        elseif (count ($files) > 1)
        {
            Log::warn ('too much in zip', count ($files));
        }
        else
        {
            Log::info ('unzipped', $files [0]);
            $subfound = file_get_contents ("zip://$zipfile#{$files [0]}");
        }

        @unlink ($zipfile);
        usleep (500);
    }

    return $subfound;
}

// --- grabs subtitles from subseeker

function subseeker ($key, $title)
{
    $found  = array ();
    $title  = urlencode (trim ($title));
    $key    = str_replace ('tt', '', $key);
    $search = "http://www.subtitleseeker.com/{$key}/{$title}/Subtitles/English/";

    Log::info ('getting', $search);

    $results = webValues ($search, "//div[starts-with(@class, 'tab-content')]//div[starts-with(@class, 'boxRowsInner') and contains(., 'Opensubtitles')]/..//a[starts-with(@href, 'http://www.subtitleseeker.com/')]/@href");
    Log::info ('results count', count ($results).' '); // forces zero output

    foreach ($results as $result)
    {
        Log::line ();

        $links = webValues ($result, "//iframe[starts-with(@src, 'http://www.opensubtitles.org')]/@src");

        if (count ($links) != 1)
        {
            Log::warn ('not one link', count ($links));
        }
        else
        {
            if (substr_count ($links[0], 'www.opensubtitles.org') != 1)
            {
                Log::warn ('not opensubs link', $links[0]);
            }
            else
            {
                Log::info ('opensubs link', $links[0]);

                $openkey = '';
                $matches = array();

                if (preg_match ('/\/(\d+)\//', $links[0], $matches)) // grabs the opensubs key from http://www.opensubtitles.org/subtitle/4794094/the-jazz-singer-en
                {
                    $openkey = $matches[1];
                }

                if ($openkey)
                {
                    Log::info ('found openkey', $openkey);

                    $subfound = processZip ("http://www.opensubtitles.org/en/download/sub/{$openkey}");

                    if ($subfound)
                    {
                        $found [] = $subfound;
                    }
                }
                else
                {
                    Log::warn ('no opensubs key found');
                }
            }
        }
    }

    return $found;
}

// --- grabs subtitles from subscene

function subscene ($title, $year)
{
    $found  = array ();
    $title  = urlencode (trim ($title));
    $search = "http://subscene.com/subtitles/title?q=$title&l=";

    Log::info ('getting', $search);

    $result = webValues ($search, "//h2[@class='exact']/following-sibling::ul[1]//div[@class='title']/a/@href");

    if (count ($result) > 1)
    {
        Log::info ('narrowing search', $year);
        $result = webValues ($search, "//h2[@class='exact']/following-sibling::ul[1]//div[@class='title']/a[contains(text(), '$year')]/@href");
    }

    if (count ($result) < 1)
    {
        Log::warn ('no exact so looking at popular others with title (year)');
        $result = webValues ($search, "//h2[@class='popular']/following-sibling::ul[1]//div[@class='title']/a[contains(text(), '$title ($year)')]/@href");
    }

    if (count ($result) < 1)
    {
        Log::warn ('still nothing so on to close matches with year');
        $result = webValues ($search, "//h2[@class='close']/following-sibling::ul[1]//div[@class='title']/a[contains(text(), '$year')]/@href");
    }

    // EMERGENCY - When desperate, then release code below to take first of exact multi-matches
/*
    if (count ($result) > 1)
    {
        $result = [array_shift ($result)];
    } */

    if (count ($result) > 1)
    {
        Log::warn ('too many matches');
    }
    else if (count ($result) < 1)
    {
        Log::warn ('no matches');
    }
    else
    {
        Log::info ('match', $result [0]);

// EMERGENCY - remove positive icon
//        $subs    = webValues ("http://subscene.com/{$result [0]}", "//span[contains (text(),'English')]/../@href");

        $subs    = webValues ("http://subscene.com/{$result [0]}", "//span[contains (@class, 'positive-icon') and contains (text(),'English')]/../@href");
        $maxSubs = 16;

        Log::info ('subs count', count ($subs).' '); // forces zero output

        if (count ($subs) > $maxSubs)
        {
            Log::warn ('truncating', "only taking $maxSubs subs");
            $subs = array_slice ($subs, 0, $maxSubs);
        }

        foreach ($subs as $sub)
        {
            Log::line ();
            Log::info ('subs', $sub);

            $link = webValues ("http://subscene.com/$sub", "//div[@class='download']/a/@href");

            if (count ($link) != 1)
            {
                Log::warn ('not one link');
            }
            else
            {
                Log::warn ('found key', $link[0]);

                $subfound = processZip ("http://subscene.com/{$link [0]}");

                if ($subfound)
                {
                    $found [] = $subfound;
                }
            }
        }
    }

    return $found;
}

// --- cleans subtitles into one clean, well puncuated line of text

function cleanSubs ($idx, $subs)
{
    $junks = array ('subtitle', 'http', 'www', '.com');
    $bom   = pack ('H*','EFBBBF'); // utf8 byte order mark
    $lines = explode ("\n", $subs);
    $text  = '';

    foreach ($lines as $line)
    {
        $line = trim (preg_replace("/^$bom/", '', $line));    // utf8 byte order mark

        if (preg_match ('/^\d+$/'       , $line)) $line = ''; // number at start
        if (preg_match ('/^\d+:\d+:\d+/', $line)) $line = ''; // time code

        foreach ($junks as $junk)
        {
            if (strpos (strtolower ($line), $junk) !== false)  $line = ''; // junk like web links
        }

        $line = str_replace ('<i>' , '' , $line); // mark-up
        $line = str_replace ('</i>', '' , $line);
        $line = str_replace ('<b>' , '' , $line); // mark-up
        $line = str_replace ('</b>', '' , $line);
        $line = str_replace ('<u>' , '' , $line); // mark-up
        $line = str_replace ('</u>', '' , $line);
        $line = str_replace ('♪'   , '' , $line); // music symbol
        $line = str_replace ('#'   , '' , $line); // music symbol
        $line = str_replace ('¶'   , '' , $line); // music symbol
        $line = str_replace ('´'   , "'", $line); // apos
        $line = str_replace ('`'   , "'", $line); // apos
        $line = str_replace ("''"  , "'", $line); // double apos

// EMERGENCY font removal if desperate

        $line = preg_replace ('/<font.*>/'         , ''    , $line);
        $line = preg_replace ('/<\/font>/'         , ''    , $line);

        $line = preg_replace ('/^-(.*)/'           , '\\1' , $line); // dash at start
        $line = preg_replace ('/^--(.*)/'          , '\\1' , $line); // double dash at start
        $line = preg_replace ('/^(.*)--/'          , '\\1' , $line); // double dash at end
        $line = preg_replace ('/^\.\.\.(.*)/'      , '\\1' , $line); // ... at start
        $line = preg_replace ('/^(.*)\.\.\./'      , '\\1' , $line); // ... at end
        $line = preg_replace ('/\[(.*)\]/'         , ''    , $line); // remove bracketted text
        $line = preg_replace ('/^\w+\s?:/'         , ''    , $line); // speaker markers at line start - normally CAPS
        $line = preg_replace ('/^\w+ \w+\s?:/'     , ''    , $line);
        $line = preg_replace ('/^\w+ \w+ \w+\s?:/' , ''    , $line);

        $line = trim ($line);

        if ($line)
        {
            $sep = ' ';

            if (ctype_alnum (substr ($text,   -1)) &&  // end with alpha num and
                ctype_upper (substr ($line, 0, 1)))    // next line start with caps
            {
                $sep = '. '; // insert a fullstop
            }

            $text .= $sep.$line;
         }
     }

     while (strpos ($text, '. .') !== false)
     {
         $text = str_replace ('. .', ' ', $text); // weird ". . . . ."
     }

     while (strpos ($text, '..') !== false)
     {
         $text = str_replace ('..', '.', $text); // multiple stops to one stop
     }

     $text = str_replace ('('   , '['  , $text);  // brackets reserved in opennlp
     $text = str_replace (')'   , ']'  , $text);
     $text = str_replace ('{'   , '['  , $text);  // all brakets removed
     $text = str_replace ('}'   , ']'  , $text);
     $text = str_replace ('." ' , '". ', $text);  // stop outside quote
     $text = str_replace ('. " ', '". ', $text);  // stop outside quote
     $text = str_replace ('* *' , '. ' , $text);  // stop outside quote

     $text = preg_replace ('/\[[^\]]+\]/' , '' , $text); // remove bracketted text
     $text = preg_replace ('/\s+/'        , ' ', $text); // normalise white space

     $text = utf8_encode (trim ($text));    // utf8 encode is standard

     $minLength   = 10000; // 10K characters - norm is ~ 40K
     $minSentence = 100;   // 100 sentences - norm is ~500

     if (strlen ($text) < $minLength || substr_count ($text, '.') < $minSentence)
     {
         Log::warn ('incomplete', $idx, 'text='.strlen ($text), 'sentences='.substr_count ($text, '.'));
//           $text = '';
     }

     if (substr_count ($text, 'I') > substr_count ($text, 'l')) // never more caps Is than lower ls unless ocr failer
     {
         Log::warn ('ocr errors', $idx, 'I='.substr_count ($text, 'I'), 'l='.substr_count ($text, 'l'));
         $text = '';
     }

     if (substr_count ($text, '<font'))
     {
         Log::warn ('complex markup');
         $text = '';
     }

     if (substr_count ($text, chr(0)))
     {
         Log::warn ('complex multibyte');
         $text = '';
     }

     $text = str_replace ('�'       , "'"       , $text); // apos
     $text = str_replace ('`'       , "'"       , $text); // apos
     $text = str_replace ("''"      , "'"       , $text); // double apos
     $text = str_replace (" l "     , " I "     , $text); // ocr
     $text = str_replace (" lf "    , " If "    , $text); // ocr
     $text = str_replace (" ln "    , " In "    , $text); // ocr
     $text = str_replace (" lt "    , " It "    , $text); // ocr
     $text = str_replace (" lt'll " , " It'll " , $text); // ocr
     $text = str_replace ("l've"    , "I've"    , $text); // ocr
     $text = str_replace ("l'm"     , "I'm"     , $text); // ocr
     $text = str_replace ("l'll"    , "I'll"    , $text); // ocr

     return $text;
 }

 // --- scores the subtitle set and returns the best one

 function bestSubs ($subs)
 {
     Log::info ('cleaning');

     $best    = '';
     $cleaned = array ();

     for ($i = 0 ; $i < count ($subs) ; $i++)
     {
         if ($clean = cleanSubs ($i, $subs [$i]))
         {
             $cleaned [] = $clean;
         }
     }

     Log::info ('clean found', count ($cleaned) ? count ($cleaned) : 'none');

     if (count ($cleaned) == 1)
     {
         Log::warn ('only one left');
         $best = $cleaned [0];
     }
     elseif (count ($cleaned) > 1)
     {
         Log::info ('scoring');

         $scored   = array ();
         $percent  = 0;
         $goodtogo = 99.95;

         for ($i = 0 ; $i < count ($cleaned)-1 && $percent < $goodtogo ; $i++)
         {
             for ($j = $i+1 ; $j < count ($cleaned) && $percent < $goodtogo ; $j++)
             {
                 similar_text ($cleaned [$i], $cleaned [$j], $percent);
                 $scored [$percent * 10000] = $i;

                 Log::info ('comparing', $i, $j, $percent);
             }
         }

         ksort ($scored);
         $winner = array_pop ($scored);
         $best   = $cleaned [$winner];

         Log::info ('chosen', $winner.' '); // force output of zero too
     }

     if (!$best)
     {
         Log::warn ('no best');
     }

     return $best;
 }

 // --- processes a single imdb key into files and sql

 function process ($key)
 {
     $corpus  = "./temp";

     Log::line ();
     Log::info ('processing', $key);
     Log::line ();

     if (glob ("$corpus/{$key}_*_full.txt"))
     {
         Log::info ('exists already');
         return;
     }

     $info = imdb ($key);

     if ($info && $info ['lang'] != 'English')
     {
         Log::warn ('not english', $info ['lang']);
         $info = 0;
         $lang = $info ['lang'] ? $info ['lang'] : 'unknown';
         file_put_contents ("$corpus/{$key}_{$lang}.txt", '');
     }

     if (!isset ($info['image']))
     {
         Log::warn ('no image');
         $info = 0;
     }

     if ($info)
     {
         $title = $info ['title'];

         Log::line ();
         Log::info ('processing', $title);

// CHOOSE ONE SUBS VENDOR FROM BELOW:

         $subs = subscene ($title, $info['year']);
//           $subs = subseeker ($key, $title);

         $name = preg_replace ('/[^A-Za-z0-9_\-]/', '_', str_replace ("'", '', strtolower ($title)));

         while (strpos ($name, '__') != false)  // only one underscore
         {
             $name = str_replace ('__', '_', $name);
         }

         $file = "$corpus/{$key}_{$name}";

         Log::line ();
         Log::info ('subs found', count ($subs) ? count ($subs) : 'none');

         $best = bestSubs ($subs);

         if ($best)
         {
             file_put_contents ("{$file}_full.txt", $best);

             $image = webGet ($info['image']);
             $image = base64_encode ($image);

             $len = trim (str_replace ('min', '', $info['duration']));

             $infolines = $info['title']."\n".$info['country']."\n".$info['year']."\n".$len."\n".$image."\n";
             file_put_contents ("{$file}_info.txt", $infolines);

             Log::info ('finished', "{$name}.sql");
         }
         else
         {
             file_put_contents ("{$file}_full.txt", '');
         }
     }
     else
     {
         file_put_contents ("$corpus/{$key}_none.txt", '');
     }
 }

 // tidies up files pairs _info and _full

 function cleanFilePairs ()
 {
     $files = glob ("../text/*.txt");

     foreach ($files as $file)
     {
         $other = '';
         $del   = false;

         if (strstr ($file, '_full'))
         {
             $other = str_replace ('_full', '_info', $file);
         }
         else if (strstr ($file, '_info'))
         {
             $other = str_replace ('_info', '_full', $file);
         }

         if (!$other)
         {
             echo "\n\n# rougue file - $file\n";
             $del = true;
         }
         else if (!file_exists ($other))
         {
             echo "\n\n# missing pair - $file\n";
             $del = true;
         }

         if (!filesize ($file))
         {
             echo "\n\n# empty file - $file\n";
             $del = true;
         }

         if ($del)
         {
             echo "rm -f $file\n";
             echo "rm -f $other\n";
         }
         else
         {
         //    echo "# ok - $file\n";
         }
     }
 }

 // -- main entry func

 function main ($argc, $argv)
 {
     for ($i = 1 ; $i < count ($argv) ; $i++)
     {
         process ($argv [$i]);
     }
 }

 main ($argc, $argv);

 //  echo cleanSubs (1, file_get_contents("The.Crying.Game.1992.480p.BluRay.x264-mSD.srt"));
 //  cleanFilePairs ();

?>
