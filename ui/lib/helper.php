<?php

// --- standard text cleansing

function cleanse ($text)
{
    $text = iconv ('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text); // accented character to 'normal'
    $text = preg_replace ('/[\r\n\s\t]+/xms', ' '    , $text); // normalise whitespace to one space
    $text = preg_replace ('/[^\w\s]+/xms'   , ''     , $text); // remove all punctuation
    return strtolower (trim ($text));                          // lowercase and trimmed
}

// --- gets the named url parameter if its there

function getParam ($name, $default = '')
{
    return isset ($_GET[$name]) ? strtolower (trim (urldecode ($_GET[$name]))) : $default;
}

// --- gets the named url parameter if its there and cleanse it too

function getCleanParam ($name, $default = 0)
{
    return cleanse (getParam ($name, $default));
}

// --- gets the server path of the current page

function getPath ()
{
    $parts = parse_url ($_SERVER ['REQUEST_URI']);
    return $parts ['path'];
}

// --- gets the url parameters of the current page, excluding any specified ones

function getParams ($excludes = [])
{
    $parts = parse_url ($_SERVER ['REQUEST_URI']);
    parse_str ($parts ['query'], $params);  // might be empty

    foreach ($excludes as $exclude)
    {
        if (isset ($params [$exclude]))
        {
            unset ($params [$exclude]);
        }
    }

    ksort ($params);  // sorts by key

    return http_build_query ($params);
}

// --- fetchs a numbered list of params for a given prefix from the post data

function getPostParams ($prefix)
{
    $params = [];
    $idx    = 0;

    do
    {
        $found = false;  // must be no numbering gaps

        if (isset ($_POST [$prefix.$idx]))  // i.e. 'item-' gives item-0, item-1, etc
        {
            $params [$idx] = $_POST [$prefix.$idx];
            $idx++;
            $found = true;
        }
    }
    while ($found);

    return $params;
}

// --- return a topic as a list item

function getListItem ($idx, $value)
{
    $item = $idx ? "<a href='javascript:window.history.go ($idx)'>$value</a>" : $value;  // zeroth item not linked
    return "<li>$item</li>";
}

// --- return a topic as a list item

function getListItems ($values)
{
    $items = [];
    $shift = count ($values) - 1;

    foreach ($values as $idx => $value)
    {
        $items [] = getListItem ($idx - $shift, $value);
    }

    return $items;
}

// --- return a hidden field to act as post parameter in a form

function getPostField ($id, $value)
{
    return "<input type='hidden' id='$id' name='$id' value='$value' />";
}

// --- returns a numbered list of params for a given prefix to put into form post data

function setPostParams ($values, $prefix)
{
    $params = [];

    foreach ($values as $idx => $value)
    {
        $params [] = getPostField ($prefix.$idx, $value);
    }

    return $params;
}

?>
