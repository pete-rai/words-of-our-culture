<?php

include_once 'lib/helper.php';

function getRandomMovie ()
{
    $movies = array
    (
        '10032138', // the wizard of oz
        '10038650', // its a wonderful life
        '10050083', // 12 angry men
        '10053291', // some like it hot
        '10054215', // psycho
        '10054331', // spartacus
        '10064505', // the italian job
        '10070047', // the exorcist
        '10070239', // jesus christ superstar
        '10073195', // jaws
        '10073812', // tommy
        '10074119', // all the presidents men
        '10075005', // the omen
        '10075148', // rocky
        '10076759', // star wars episode iv - a new hope
        '10077631', // grease
        '10079417', // kramer vs kramer
        '10079470', // life of brian
        '10082971', // raiders of the lost ark
        '10083866', // e t the extra-terrestrial
        '10083987', // gandhi
        '10086465', // trading places
        '10086567', // wargames
        '10087332', // ghostbusters
        '10088247', // terminator
        '10088763', // back to the future
        '10096438', // who framed roger rabbit
        '10102138', // jfk
        '10108052', // schindlers list
        '10109830', // forrest gump
        '10111161', // the shawshank redemption
        '10114709', // toy story
        '10119654', // men in black
        '10120338', // titanic
        '10120815', // saving private ryan
        '10126029', // shrek
        '10133093', // the matrix
        '10145660', // austin powers the spy who shagged me
        '10167261', // the lord of the rings the two towers
        '10172495', // gladiator
        '10266543', // finding nemo
        '10266697', // kill bill vol 1
        '10317705', // the incredibles
        '10361748', // inglourious basterds
        '10365748', // shaun of the dead
        '10454876', // life of pi
        '10468569', // the dark knight
        '10796366', // star trek
        '10988045', // sherlock holmes
        '11074638', // skyfall
        '11099212', // twilight
        '11179056', // a nightmare on elm street
        '11179904', // paranormal activity
        '11194173', // the bourne legacy
        '11201607', // harry po1er and the deathly hallows part 2
        '11285016', // the social network
        '11375666', // inception
        '11392170', // the hunger games
        '11853728', // django unchained
        '12357129', // jobs
    );

    return $movies [array_rand ($movies)];
}

function getRandomWord ()
{
    $words = array
    (
        'red', 'fuck', 'boat', 'happy', 'cia', 'war', 'nuclear',
        'water', 'summer', 'shark', 'fish', 'prison', 'gun', 'taxes',
        'honour', 'porn', 'shit', 'warp', 'vietnam', 'kennedy', 'fbi',
        'nixon', 'india', 'football', 'soviet', 'red', 'france',
        'satan', 'god', 'hell', 'london', 'peking', 'car', 'hitler',
        'sex', 'satellite', 'power', 'congress', 'love', 'wedding',
    );

    return $words [array_rand ($words)];
}

$type  = getCleanParam ('type');
$topic = $type == 'word' ? getRandomWord () : getRandomMovie ();

?>
<html>
  <head>
    <script>
      window.location.href = 'bubbles.php?type=<?= $type ?>&topic=<?= $topic ?>';
    </script>
  </head>
</html>
