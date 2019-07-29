<?php

// *** grabs, analyses and processes subtitles to form utterances ***

include_once ('../lib/logger.php');
include_once ('web.php');

date_default_timezone_set ('UTC'); // important - do not remove and leave as first line

// --- grabs content info from imdb

function imdb ($key)
{
    $info = array ();
    $url  = "http://www.imdb.com/title/$key";

    Log::info ('getting', $url);

    $attribs = array
    (
        ['name' => 'title'   , 'needed' => true , 'single' => true , 'xpath' => "//h1/text()[1]"],
        ['name' => 'year'    , 'needed' => false, 'single' => true , 'xpath' => "//h1/span/a/text()"],
        ['name' => 'image'   , 'needed' => false, 'single' => true , 'xpath' => "//div[contains(@class, 'title-overview')]//div[contains(@class, 'poster')]//img/@src"],
        ['name' => 'country' , 'needed' => false, 'single' => false, 'xpath' => "//div[@id='titleDetails']//a[contains(@href, 'country_of_origin')]/text()"],
        ['name' => 'language', 'needed' => true , 'single' => true , 'xpath' => "//div[@id='titleDetails']//a[contains(@href, 'primary_language')][1]/text()"],
        ['name' => 'duration', 'needed' => false, 'single' => true , 'xpath' => "//div[contains(@class, 'title-overview')]//div[contains(@class, 'subtext')]/time/@datetime"],
        ['name' => 'genres'  , 'needed' => false, 'single' => false, 'xpath' => "//div[contains(@class, 'title-overview')]//div[contains(@class, 'subtext')]//a[contains(@href, 'genres')]/text()"],
        ['name' => 'dir_nm'  , 'needed' => false, 'single' => false, 'xpath' => "//div[contains(@class, 'title-overview')]//div[contains(@class, 'plot_summary')]//h4[contains(text(), 'Director')]/../a[contains(@href, '/name/')]/text()"],
        ['name' => 'dir_id'  , 'needed' => false, 'single' => false, 'xpath' => "//div[contains(@class, 'title-overview')]//div[contains(@class, 'plot_summary')]//h4[contains(text(), 'Director')]/../a[contains(@href, '/name/')]/@href"],
        ['name' => 'cst_nm'  , 'needed' => false, 'single' => false, 'xpath' => "//div[contains(@class, 'title-overview')]//div[contains(@class, 'plot_summary')]//h4[contains(text(), 'Star')]/../a[contains(@href, '/name/')]/text()"],
        ['name' => 'cst_id'  , 'needed' => false, 'single' => false, 'xpath' => "//div[contains(@class, 'title-overview')]//div[contains(@class, 'plot_summary')]//h4[contains(text(), 'Star')]/../a[contains(@href, '/name/')]/@href"],
    );

    $values = webValues ($url, array_column ($attribs, 'xpath', 'name'));
    $errors = false;

    // -- check the results

    foreach ($attribs as $attrib)
    {
        $key = $attrib ['name'];
        $values [$key] = array_filter (array_map ('trim', $values [$key]));

        $found = count ($values [$key]);

        if ($found == 0 && $attrib ['needed'] == false ||
            $found == 1 && $attrib ['single'] == true  ||
            $found >= 1 && $attrib ['single'] == false )
        {
            Log::info ('found', $key, implode (', ', $values [$key]));
            if ($attrib ['single']) $values [$key] = array_shift ($values [$key]);
        }
        else
        {
            $errors = true;

            if ($found == 0 && $attrib ['needed'] == true)
            {
                Log::warn ('mandatory element missing', $key);
            }
            else
            {
                Log::info ('element mismatch', $key, implode (', ', $values [$key]), $found);
            }
        }
    }

    // --- format the results

    if (!$errors)
    {
        $values ['duration'] = preg_replace ("/PT(\d+)M/", '\\1', $values ['duration']);
        $values ['language'] = strtolower ($values ['language']);
        $values ['country' ] = array_map ('strtolower', $values ['country']);
        $values ['genres'  ] = array_map ('strtolower', $values ['genres' ]);
        $values ['director'] = [];
        $values ['cast']     = [];

        foreach ($values ['dir_nm'] as $idx => $name)
        {
            $values ['director'][] = ['name' => $name, 'id' => preg_replace ("/\/name\/(nm\d+)\/.*/", '\\1', $values ['dir_id'][$idx]) ];
        }

        unset ($values ['dir_nm']);
        unset ($values ['dir_id']);

        foreach ($values ['cst_nm'] as $idx => $name)
        {
            $values ['cast'][] = ['name' => $name, 'id' => preg_replace ("/\/name\/(nm\d+)\/.*/", '\\1', $values ['cst_id'][$idx]) ];
        }

        unset ($values ['cst_nm']);
        unset ($values ['cst_id']);
    }

    return $errors ? [] : $values; // all or none
}

// --- unzips a zip file and extracts subtitles

function readZip ($zipfile)
{
    Log::info ('unzipping', $zipfile);

    $found = '';
    $files = [];

    $zip = new ZipArchive ();
    $zip->open ($zipfile);

    for ($i = 0; $i < $zip->numFiles; $i++)
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
        $found = file_get_contents ("zip://$zipfile#{$files [0]}");
    }

    @unlink ($zipfile);
    usleep (500);

    return $found;
}

// --- grabs subs from opensubtitles

function opensubtitles ($key)
{
    Log::info ('searching opensubtitles', $key);

    // use this URL to re-enter the CAPTCHA test: https://www.opensubtitles.org/en/captcha/

    $session = '---------------------------';  // grab this from an interactive session in the browser - clear cookies and get a new one when it expires
    $results = [];
    $domain  = 'https://www.opensubtitles.org';
    $xpath   =
    [
        'list' => "//td/a[contains(@href,'en/subtitleserve/sub/')]/@href",
        'base' => "//link[@rel='canonical']/@href",
    ];

    $cruds  =
    [
        'Support us and become VIP member',
        'Advertise your product or brand here',
    ];

    $url  = "$domain/en/search2?MovieName={$key}&id=8&action=search&SubLanguageID=eng&SubLanguageID=eng";
    $subs = webValues ($url, $xpath);
    $base = array_shift ($subs ['base']);
    $list = array_slice ($subs ['list'], 0, 5);
    $zip  = "$key.zip";

    Log::info ('referer', $base);
    Log::info ('found candidates', count ($list));

    foreach ($list as $idx => $sub)
    {
        Log::info ('candidate', $domain.$sub);

        $file = fopen ($zip, 'wb');
        fwrite ($file, webGet ($domain.$sub, ["Referer: $base"], "PHPSESSID=$session"));
        fclose ($file);

        $results [] = str_replace ($cruds, '', readZip ($zip));
    }

    $results = array_filter ($results);  // remove the empties

    if ($list && !$results)  // trapdoor to stop when in unattended use and the session id expires
    {
        Log::error ('opensubtitles session has expired');
        die ();
    }

    return $results;
}

// --- cleans subtitles into one clean, well puncuated line of text

function cleanSubs ($idx, $subs)
{
    $junks = ['subtitle', 'http', 'www', '.com'];
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

        $line = preg_replace ('/<font.*>/'         , ''    , $line); // emergency font removal if desperate
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
        $text = '';
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
    $corpus  = "./text";

    Log::line ();
    Log::info ('processing', $key);
    Log::line ();

    if (glob ("$corpus/{$key}_*_full.txt"))
    {
        Log::info ('exists already');
        return;
    }

    $info = imdb ($key);

    if ($info && $info ['language'] != 'english')
    {
        Log::warn ('not english', $info ['language']);
        $info = 0;
        $lang = $info ['language'] ? $info ['language'] : 'unknown';
        file_put_contents ("$corpus/{$key}_{$lang}.txt", '');
    }

    if (!isset ($info ['image']))
    {
        Log::warn ('no image');
        $info = 0;
    }

    if ($info)
    {
        $title = $info ['title'];

        Log::line ();
        Log::info ('processing', $title);

        $subs = opensubtitles ($key);
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

            $info ['packshot'] = base64_encode (webGet ($info ['image']));
            file_put_contents ("{$file}_info.txt", json_encode ($info));

            Log::info ('finished', $name);
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

// -- main entry func

function main ($keys)
{
    foreach ($keys as $key)
    {
        process ($key);
    }
}

// --- harvesting new content by year - output in PHP array format

function harvest ($years)
{
    $wanted = 24; // movies per year
    $stroke = str_repeat ('-', 80);
    $xpaths =
    [
        'title' => "//div[@class='lister-list']//h3//a[contains(@href,'/title/')]/text()",
        'href'  => "//div[@class='lister-list']//h3//a[contains(@href,'/title/')]/@href",
    ];

    foreach ($years as $year)
    {
        $url   = "https://www.imdb.com/search/title/?year=$year&title_type=feature&sort=moviemeter,asc";
        $items = webValues ($url, $xpaths);
        $lines = [];
        $exist = 0;

        foreach ($items ['title'] as $idx => $title)
        {
            $key = str_replace (['/title/', '/?ref_=adv_li_tt'], '', $items ['href'][$idx]);
            $got = glob ("./text-best/{$key}_*.txt");

            if ($got) $exist++;

            $lines [] = ($got ? '//  ' : '    ')."'$key',".' // '.($got ? 'got ' : 'need')." - $year - $title";
        }

        $togo = $wanted - $exist;

        if ($togo != 0)
        {
            echo ("// $stroke\n");
            echo ("//   $year - $exist - $togo \n");
            echo ("// $stroke\n");
            echo (implode ("\n", $lines))."\n";
        }
    }
}

// harvest ([2018]);
// find ./text/*.txt -size 0
// find ./text/*.txt -size 0 -print0 | xargs -0 rm --
// SELECT year, COUNT(year) FROM movie GROUP BY year ORDER BY year;

$script = array_shift ($argv);
main ($argv);
