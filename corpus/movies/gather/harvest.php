<?php

// *** some content harvesting helper functions ***

include_once 'web.php';

// grab some ids from imdb via genre

function harvestByGenre ()
{
    $genres = array
    (
        "Action", "Adventure", "Animation", "Biography", "Comedy", "Crime", "Documentary", "Drama",
        "Family", "Fantasy", "Film-Noir", "Game-Show", "History", "Horror", "Music", "Musical",
        "Mystery", "News", "Reality-TV", "Romance", "Sci-Fi", "Short", "Sport", "Talk-Show",
        "Thriller", "War", "Western"
    );

    $keys = array ();

    foreach ($genres as $genre)
    {
        $page = 1;

        do
        {
            echo "-- $genre - $page\n";

            $url = "http://www.imdb.com/search/title?at=0&genres=$genre&sort=moviemeter,asc&start=$page&title_type=feature";
            $ids = webValues ($url, "//a[starts-with(@href, '/title/tt')]/@href");

            foreach ($ids as $id)
            {
                $parts   = explode ('/', $id);
                $keys [] = $parts [2];
            }

            $page += 50;
        }
        while ($page < 551);
    }

    asort ($keys);

    echo implode ("\n", array_unique ($keys));
}

// grab some ids from imdb via year

function harvestByYear ()
{
    $base = "//h3//a[starts-with(@href, '/title/tt')]";

    for ($year=1930 ; $year<2017 ; $year++)
    {
        $url   = "http://www.imdb.com/search/title?year=$year,$year&title_type=feature&sort=moviemeter,asc";
        $data  = webValues ($url, ["$base/@href", "$base/text()"]);
        $count = count ($data [1]);

        $films [$year] = [];

        for ($i = 0 ; $i < $count ; $i++)
        {
            $parts = explode ('/', $data [0][$i]);
            $got   = glob ('../text/'.$parts [2].'_*.txt');
            echo $year.' | '.($got ? 1 : 0).' | '.$parts [2].' | '.$data [1][$i]."\n";
        }
    }
}

// harvestByGenre ();
harvestByYear ();

?>
