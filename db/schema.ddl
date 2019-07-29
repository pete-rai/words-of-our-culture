
-- wooc schema

DROP DATABASE IF EXISTS wooc;
CREATE DATABASE wooc;
CONNECT wooc;

SET AUTOCOMMIT=0;

SELECT CONCAT (now(),' - started') info;

-- pos table

SELECT CONCAT (now(),' - pos') info;

CREATE TABLE pos
(
    pos        CHAR(8)      NOT NULL PRIMARY KEY,
    level      VARCHAR(8)   NOT NULL,
    desciption VARCHAR(256) NOT NULL
)
ENGINE=INNODB DEFAULT CHARSET=UTF8;

INSERT
  INTO pos (pos, level, desciption)
VALUES ('WORD'    , 'ngram', 'singluar word'),
       ('BI-GRAM' , 'ngram', 'pairs of words'),
       ('TRI-GRAM', 'ngram', 'triplets of words');

-- movie table

SELECT CONCAT (now(),' - movie') info;

CREATE TABLE movie
(
    id       CHAR(9)         NOT NULL PRIMARY KEY,
    title    VARCHAR(64)     NOT NULL,
    year     INT(4) UNSIGNED NOT NULL,
    duration INT(4) UNSIGNED NOT NULL
)
ENGINE=INNODB DEFAULT CHARSET=UTF8;

SOURCE ./sql/movie.sql
COMMIT;
CREATE INDEX idx_title_on_movie    ON movie (title   , id);
CREATE INDEX idx_year_on_movie     ON movie (year    , id);
CREATE INDEX idx_duration_on_movie ON movie (duration, id);

-- image table

SELECT CONCAT (now(),' - image') info;

CREATE TABLE image
(
    movie_id CHAR(9)  NOT NULL PRIMARY KEY,
    packshot LONGTEXT NOT NULL,
    FOREIGN KEY (movie_id) REFERENCES movie (id)
)
ENGINE=INNODB DEFAULT CHARSET=UTF8;

SOURCE ./sql/image.sql
COMMIT;

-- origin table

SELECT CONCAT (now(),' - origin') info;

CREATE TABLE origin
(
    movie_id CHAR(9)     NOT NULL,
    country  VARCHAR(32) NOT NULL,
    PRIMARY KEY (movie_id, country),
    FOREIGN KEY (movie_id) REFERENCES movie (id)
)
ENGINE=INNODB DEFAULT CHARSET=UTF8;

SOURCE ./sql/origin.sql
COMMIT;
CREATE INDEX idx_country_on_origin ON origin (country, movie_id);

-- genre table

SELECT CONCAT (now(),' - category') info;

CREATE TABLE category
(
    movie_id CHAR(9)     NOT NULL,
    genre    VARCHAR(16) NOT NULL,
    PRIMARY KEY (movie_id, genre),
    FOREIGN KEY (movie_id) REFERENCES movie (id)
)
ENGINE=INNODB DEFAULT CHARSET=UTF8;

SOURCE ./sql/category.sql
COMMIT;
CREATE INDEX idx_genre_on_category ON category (genre, movie_id);

-- person table

SELECT CONCAT (now(),' - person') info;

CREATE TABLE person
(
    id   CHAR(9)     NOT NULL PRIMARY KEY,
    name VARCHAR(32) NOT NULL
)
ENGINE=INNODB DEFAULT CHARSET=UTF8;

SOURCE ./sql/person.sql
COMMIT;
CREATE INDEX idx_name_on_person ON person (name, id);

-- cast table

SELECT CONCAT (now(),' - cast') info;

CREATE TABLE cast
(
    movie_id  CHAR(9)     NOT NULL,
    person_id CHAR(9)     NOT NULL,
    role      VARCHAR(16) NOT NULL,
    PRIMARY KEY (movie_id, person_id, role),
    FOREIGN KEY (movie_id)  REFERENCES movie  (id),
    FOREIGN KEY (person_id) REFERENCES person (id)
)
ENGINE=INNODB DEFAULT CHARSET=UTF8;

SOURCE ./sql/cast.sql
COMMIT;
CREATE INDEX idx_person_on_cast ON cast (person_id, movie_id);

-- utterance table

SELECT CONCAT (now(),' - utterance') info;

CREATE TABLE utterance
(
    id        CHAR(32) NOT NULL PRIMARY KEY,
    pos       CHAR(8)  NOT NULL,
    utterance TEXT     NOT NULL,
    stem      TEXT     NOT NULL,
    FOREIGN KEY (pos) REFERENCES pos (pos),
    UNIQUE (pos, utterance(128))
)
ENGINE=INNODB DEFAULT CHARSET=UTF8;

SOURCE ./sql/utterance.sql
COMMIT;
CREATE INDEX idx_stem_on_utterance ON utterance (pos, stem(128));

-- occurrence table

SELECT CONCAT (now(),' - occurrence') info;

CREATE TABLE occurrence
(
    utterance_id CHAR(32) NOT NULL,
    movie_id     CHAR(9)  NOT NULL,
    tally        INT(6) UNSIGNED NOT NULL,
    PRIMARY KEY (utterance_id, movie_id),
    FOREIGN KEY (movie_id)     REFERENCES movie     (id),
    FOREIGN KEY (utterance_id) REFERENCES utterance (id)
)
ENGINE=INNODB DEFAULT CHARSET=UTF8;

SOURCE ./sql/occurrence.sql
COMMIT;
CREATE INDEX idx_movie_on_occurrence ON occurrence (movie_id, utterance_id);

-- normative_occurrence table - sz = utterance

CREATE TABLE normative_occurrence
(
    utterance_id CHAR(32) NOT NULL PRIMARY KEY,
    tally        INT(6) UNSIGNED NOT NULL,
    FOREIGN KEY (utterance_id) REFERENCES utterance (id)
)
ENGINE=INNODB DEFAULT CHARSET=UTF8;

-- lexicon table - sz = movie

CREATE TABLE lexicon
(
    movie_id CHAR(9) NOT NULL,
    pos      CHAR(8) NOT NULL,
    tally    INT(6) UNSIGNED NOT NULL,
    PRIMARY KEY (movie_id, pos),
    FOREIGN KEY (movie_id) REFERENCES movie (id),
    FOREIGN KEY (pos)      REFERENCES pos   (pos)
)
ENGINE=INNODB DEFAULT CHARSET=UTF8;

-- normative_lexicon table - sz = pos

CREATE TABLE normative_lexicon
(
    pos   CHAR(8) NOT NULL PRIMARY KEY,
    tally INT(6)  UNSIGNED NOT NULL,
    FOREIGN KEY (pos) REFERENCES pos (pos)
)
ENGINE=INNODB DEFAULT CHARSET=UTF8;

-- movie_utterances table - sz = utterances * movie

CREATE TABLE movie_utterance
(
    movie_id   CHAR(9) NOT NULL,
    pos        CHAR(8) NOT NULL,
    stem       TEXT    NOT NULL,
    utterances TEXT    NOT NULL,
    PRIMARY KEY (movie_id, pos, stem(128)),
    FOREIGN KEY (movie_id) REFERENCES movie (id),
    FOREIGN KEY (pos)      REFERENCES pos   (pos)
)
ENGINE=INNODB DEFAULT CHARSET=UTF8;

-- movie_occurrence table - sz = occurrence * movie

CREATE TABLE movie_occurrence
(
    movie_id CHAR(9) NOT NULL,
    pos      CHAR(8) NOT NULL,
    stem     TEXT    NOT NULL,
    tally    INT(6) UNSIGNED NOT NULL,
    PRIMARY KEY (movie_id, pos, stem(128)),
    FOREIGN KEY (movie_id) REFERENCES movie (id),
    FOREIGN KEY (pos)      REFERENCES pos   (pos)
)
ENGINE=INNODB DEFAULT CHARSET=UTF8;

-- stored procedures

DELIMITER //

DROP PROCEDURE IF EXISTS fill_normative_occurrence //
CREATE PROCEDURE fill_normative_occurrence ()
BEGIN
   INSERT
     INTO normative_occurrence (utterance_id, tally)
   SELECT utterance_id, SUM(tally)
     FROM occurrence
 GROUP BY utterance_id;
END //

DROP PROCEDURE IF EXISTS fill_lexicon //
CREATE PROCEDURE fill_lexicon ()
BEGIN
   INSERT
     INTO lexicon (movie_id, pos, tally)
   SELECT o.movie_id, u.pos, SUM(o.tally)
     FROM occurrence o,
          utterance u
    WHERE o.utterance_id = u.id
 GROUP BY o.movie_id, u.pos;
END //

DROP PROCEDURE IF EXISTS fill_normative_lexicon //
CREATE PROCEDURE fill_normative_lexicon ()
BEGIN
   INSERT
     INTO normative_lexicon (pos, tally)
   SELECT pos, SUM(tally)
     FROM lexicon
 GROUP BY pos;
END //

DROP PROCEDURE IF EXISTS fill_movie_utterance //
CREATE PROCEDURE fill_movie_utterance ()
BEGIN
   INSERT
     INTO movie_utterance (movie_id, pos, stem, utterances)
   SELECT o.movie_id,
          u.pos,
          u.stem,
          GROUP_CONCAT(u.stem SEPARATOR ' ') utterances
     FROM utterance u,
          occurrence o
    WHERE u.id = o.utterance_id
 GROUP BY o.movie_id,
          u.pos,
          u.stem;
END //

DROP PROCEDURE IF EXISTS fill_movie_occurrence //
CREATE PROCEDURE fill_movie_occurrence ()
BEGIN
   INSERT
     INTO movie_occurrence (movie_id, pos, stem, tally)
   SELECT m.id,
          u.pos,
          u.stem,
          SUM(o.tally) tally
     FROM occurrence o,
          utterance u,
          movie m
    WHERE o.utterance_id = u.id
      AND o.movie_id = m.id
 GROUP BY o.movie_id,
          u.pos,
          u.stem;
END //

DELIMITER ;
COMMIT;

-- run procedures

SELECT CONCAT (now(),' - filling denormal tables') info;

CALL fill_normative_occurrence;
CALL fill_lexicon;
CALL fill_normative_lexicon;
CALL fill_movie_utterance;
CALL fill_movie_occurrence;

COMMIT;

-- create users

DROP USER IF EXISTS wooc@localhost;
FLUSH PRIVILEGES;
CREATE USER wooc@localhost IDENTIFIED BY 'wooc';
GRANT SELECT ON wooc.* TO wooc@localhost;
COMMIT;

SET AUTOCOMMIT=1;

SELECT CONCAT (now(),' - finished') info;
