<?php

// *** some web getting functions ***

// -- string trimer for more nasties

function trimer ($text)
{
    return trim ($text, " \t\n\r\0\x0B\xc2\xa0");
}

// --- gets the real filename from web headers

function realFilename ($headers, $default)
{
    $name = $default;

    foreach ($headers as $header)
    {
        if (strpos (strtolower ($header), 'content-disposition') !== false)
        {
            $parts = explode ('=', $header);

            if (isset ($parts [1]) && $parts [1])
            {
                $name = trim ($parts[1], '";\'');
            }
        }
    }

    return $name;
}

// --- gets and returns a web resource

function webGet ($url, $headers = [], $cookies = '')
{
    $web = curl_init ();

    curl_setopt ($web, CURLOPT_AUTOREFERER   , true);
    curl_setopt ($web, CURLOPT_RETURNTRANSFER, true);
    curl_setopt ($web, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt ($web, CURLOPT_MAXREDIRS     , 10  );
    curl_setopt ($web, CURLOPT_TIMEOUT       , 25  );
    curl_setopt ($web, CURLOPT_URL           , $url);
    curl_setopt ($web, CURLOPT_COOKIE        , $cookies);
    curl_setopt ($web, CURLOPT_HTTPHEADER    , $headers);

    $body = curl_exec ($web);
    curl_close ($web);

    return $body;
}

// --- parses out the parameter xpaths from a web resource

function webValues ($url, $xpaths)
{
    $body = webGet ($url);

    $dom = new DOMDocument;
    @$dom->LoadHTML ($body);

    $doc      = new DOMXPath ($dom);
    $values   = array ();
    $multiple = true;

    if (!is_array ($xpaths))  // asked for single xpath not multiple ones
    {
        $xpaths   = array ('0' => $xpaths);
        $multiple = false;
    }

    foreach ($xpaths as $key=>$xpath)
    {
        $values [$key] = array ();
        $items = $doc->evaluate ($xpath);

        if (is_object ($items))
        {
            foreach ($items as $item)
            {
                $values [$key][] = trimer ($item->nodeValue);
            }
        }
    }

    return $multiple ? $values : $values[0];  // one or multiple, depending on number of xpaths wanted
}
