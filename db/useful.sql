
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
