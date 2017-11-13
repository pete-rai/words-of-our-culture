
-- complete movie list

  SELECT year,
         content_id,
         title
    FROM content
ORDER BY year,
         content_id;

-- movies by title

  SELECT year,
         content_id,
         title
    FROM content
   WHERE LOWER(title)
    LIKE '%godfather%'
ORDER BY title;

-- movies counts by year

  SELECT year,
         COUNT(year) count
    FROM content
GROUP BY year
ORDER BY year;

-- longest movie titles

  SELECT title,
         LENGTH(title) length
    FROM content
ORDER BY LENGTH(title) DESC
   LIMIT 25;

-- top N absolute wordy movies

  SELECT title,
         tally
        FROM (SELECT content_id,
                     SUM(tally) tally
                FROM content_occurrence
            GROUP BY content_id) t,
         CONTENT c
   WHERE t.content_id = c.content_id
ORDER BY t.tally DESC
   LIMIT 50;

-- top N rate wordy movies

  SELECT 0 pos,
         CONCAT('[',c.title,'](http://rai.org.uk/wooc/bubbles.php?topic=',c.content_id,')') movie,
         tally / duration words_per_minute
    FROM (SELECT content_id,
                 SUM(tally) tally
            FROM content_occurrence
        GROUP BY content_id) t,
         CONTENT c
   WHERE t.content_id = c.content_id
ORDER BY t.tally / duration DESC
   LIMIT 50;

-- word rates by year

  SELECT t.year,
         t.tally / d.duration rate
    FROM (SELECT year,
                 SUM(duration) duration
            FROM content
        GROUP BY year) d,
         (SELECT year,
                 SUM(tally) tally
            FROM content c,
                 occurrence o
           WHERE c.content_id = o.content_id
        GROUP BY year) t
   WHERE t.year = d.year
ORDER BY year;

-- utterance counts by movie title

  SELECT utterance,
         tally
    FROM content c,
         occurrence o,
         utterance u
   WHERE o.content_id   = c.content_id
     AND o.utterance_id = u.utterance_id
     AND c.title = 'Double Indemnity'
ORDER BY tally;

-- utterance counts in movies by utterance

  SELECT title,
         tally
    FROM content c,
         occurrence o,
         utterance u
   WHERE o.content_id   = c.content_id
     AND o.utterance_id = u.utterance_id
     AND u.utterance = 'jesus'
ORDER BY tally;

-- balance of utterance of 'you' vs 'i' in movies

  SELECT c.title,
         y.tally / i.tally tally
    FROM content c,
         (SELECT c.content_id,
                 tally
            FROM content c,
                 occurrence o,
                 utterance u
           WHERE o.content_id   = c.content_id
             AND o.utterance_id = u.utterance_id
             AND u.utterance = 'i') i,
       (SELECT c.content_id,
               tally
          FROM content c,
               occurrence o,
               utterance u
         WHERE o.content_id   = c.content_id
           AND o.utterance_id = u.utterance_id
           AND u.utterance = 'you') y
    WHERE c.content_id = i.content_id
      AND c.content_id = y.content_id
ORDER BY y.tally / i.tally;
