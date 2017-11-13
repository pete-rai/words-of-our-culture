<?php

include_once 'lib/helper.php';

function getRandomContent ()
{
    $content = array
    (
        'tt0032138', // the_wizard_of_oz
        'tt0038650', // its_a_wonderful_life
        'tt0050083', // 12_angry_men
        'tt0053291', // some_like_it_hot
        'tt0054215', // psycho
        'tt0054331', // spartacus
        'tt0064505', // the_italian_job
        'tt0070047', // the_exorcist
        'tt0070239', // jesus_christ_superstar
        'tt0073195', // jaws
        'tt0073812', // tommy
        'tt0074119', // all_the_presidents_men
        'tt0075005', // the_omen
        'tt0075148', // rocky
        'tt0076759', // star_wars_episode_iv_-_a_new_hope
        'tt0077631', // grease
        'tt0079417', // kramer_vs_kramer
        'tt0079470', // life_of_brian
        'tt0082971', // raiders_of_the_lost_ark
        'tt0083866', // e_t_the_extra-terrestrial
        'tt0083987', // gandhi
        'tt0086465', // trading_places
        'tt0086567', // wargames
        'tt0087332', // ghostbusters
        'tt0088247', // terminator
        'tt0088763', // back_to_the_future
        'tt0096438', // who_framed_roger_rabbit
        'tt0102138', // jfk
        'tt0108052', // schindlers_list
        'tt0109830', // forrest_gump
        'tt0111161', // the_shawshank_redemption
        'tt0114709', // toy_story
        'tt0119654', // men_in_black
        'tt0120338', // titanic
        'tt0120815', // saving_private_ryan
        'tt0126029', // shrek
        'tt0133093', // the_matrix
        'tt0145660', // austin_powers_the_spy_who_shagged_me
        'tt0167261', // the_lord_of_the_rings_the_two_towers
        'tt0172495', // gladiator
        'tt0266543', // finding_nemo
        'tt0266697', // kill_bill_vol_1
        'tt0317705', // the_incredibles
        'tt0361748', // inglourious_basterds
        'tt0365748', // shaun_of_the_dead
        'tt0454876', // life_of_pi
        'tt0468569', // the_dark_knight
        'tt0796366', // star_trek
        'tt0988045', // sherlock_holmes
        'tt1074638', // skyfall
        'tt1099212', // twilight
        'tt1179056', // a_nightmare_on_elm_street
        'tt1179904', // paranormal_activity
        'tt1194173', // the_bourne_legacy
        'tt1201607', // harry_potter_and_the_deathly_hallows_part_2
        'tt1285016', // the_social_network
        'tt1375666', // inception
        'tt1392170', // the_hunger_games
        'tt1853728', // django_unchained
        'tt2357129', // jobs
    );

    return $content [array_rand ($content)];
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

$topic = getCleanParam ('type') == 'word' ? getRandomWord () : getRandomContent ();

?>
<html>
  <head>
    <script>
      window.location.href = 'bubbles.php?topic=<?php echo $topic; ?>';
    </script>
  </head>
</html>
