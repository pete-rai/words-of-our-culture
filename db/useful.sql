
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

-- table of movies by utterance counts

   CREATE
    TABLE content_occurrence
       AS
   SELECT content_id,
          SUM(tally) tally
     FROM occurrence
 GROUP BY content_id
 ORDER BY SUM(tally);

-- top N absolute wordy movies

 SELECT title,
        tally
   FROM content c,
        content_occurrence o
  WHERE c.content_id = o.content_id
ORDER BY tally DESC
  LIMIT 50;

-- top N rate wordy movies

   SELECT title,
          tally / duration
     FROM content c,
          content_occurrence o
    WHERE c.content_id = o.content_id
 ORDER BY tally / duration DESC
    LIMIT 50;

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
