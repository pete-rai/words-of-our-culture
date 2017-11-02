-- NOTE: You will see a lot of constraints commented out in the code below.
--       That is intentional. Basically its a read only schema and there will
--       be no write backs. Having the constraints just uses up space and
--       machine cycles.

-- wooc schema

DROP   DATABASE wooc;
CREATE DATABASE wooc;
CONNECT wooc;

SELECT CONCAT (now(),' - started') info;

-- pos table

CREATE TABLE pos
(
    pos        CHAR(8)      NOT NULL,
    level      VARCHAR(8)   NOT NULL,
    desciption VARCHAR(256) NOT NULL,
    PRIMARY KEY (pos)
)
ENGINE=INNODB DEFAULT CHARSET=UTF8;

INSERT
  INTO pos (pos, level, desciption)
VALUES ('WORD'    , 'ngram', 'singluar word'),
       ('BI-GRAM' , 'ngram', 'pairs of words'),
       ('TRI-GRAM', 'ngram', 'triplets of words');

-- content table

CREATE TABLE content
(
    content_id CHAR(9)         NOT NULL,
    title      VARCHAR(64)     NOT NULL,
    image      LONGTEXT        NOT NULL,
    country    VARCHAR(16)     NOT NULL,
    year       INT(4) UNSIGNED NOT NULL,
    duration   INT(4) UNSIGNED NOT NULL,
    script     LONGTEXT        NOT NULL
)
ENGINE=INNODB DEFAULT CHARSET=UTF8;

SELECT CONCAT (now(),' - inserting content') info;

SET AUTOCOMMIT=0;
SOURCE ./sql/content.sql
COMMIT;
SET AUTOCOMMIT=1;

SELECT CONCAT (now(),' - content indexes') info;

ALTER TABLE content ADD CONSTRAINT PRIMARY KEY pk_on_content (content_id);

CREATE INDEX idx_title_on_content    ON content (title   , content_id);
CREATE INDEX idx_country_on_content  ON content (country , content_id);
CREATE INDEX idx_year_on_content     ON content (year    , content_id);
CREATE INDEX idx_duration_on_content ON content (duration, content_id);

-- utterance table

CREATE TABLE utterance
(
    utterance_id CHAR(32) NOT NULL,
    pos          CHAR(8)  NOT NULL,
    utterance    TEXT     NOT NULL,
    stem         TEXT     NOT NULL
)
ENGINE=INNODB DEFAULT CHARSET=UTF8;

SELECT CONCAT (now(),' - inserting utterances') info;

SET AUTOCOMMIT=0;
SOURCE ./sql/utter.sql

COMMIT;
SET AUTOCOMMIT=1;

SELECT CONCAT (now(),' - utterance indexes') info;

ALTER TABLE utterance ADD CONSTRAINT PRIMARY KEY pk_on_utter (utterance_id);
-- ALTER TABLE utterance ADD CONSTRAINT FOREIGN KEY fk_pos_on_utter (pos) REFERENCES pos (pos);
-- ALTER TABLE utterance ADD CONSTRAINT uc_utterance UNIQUE (pos, utterance(128));

CREATE INDEX idx_stem_on_utterance ON utterance (pos, stem(128));

-- occurrence table

CREATE TABLE occurrence
(
    utterance_id CHAR(32) NOT NULL,
    content_id   CHAR(9)  NOT NULL,
    tally        INT(6) UNSIGNED NOT NULL
)
ENGINE=INNODB DEFAULT CHARSET=UTF8;

SELECT CONCAT (now(),' - inserting occurrences') info;

SET AUTOCOMMIT=0;
SOURCE ./sql/occur.sql
COMMIT;
SET AUTOCOMMIT=1;

SELECT CONCAT (now(),' - occurrence indexes') info;

ALTER TABLE occurrence ADD CONSTRAINT PRIMARY KEY pk_on_occur (utterance_id, content_id);
-- ALTER TABLE occurrence ADD CONSTRAINT FOREIGN KEY fk_content_id_on_occurr   (content_id  ) REFERENCES content   (content_id  );
-- ALTER TABLE occurrence ADD CONSTRAINT FOREIGN KEY fk_utterance_id_on_occurr (utterance_id) REFERENCES utterance (utterance_id);

CREATE INDEX idx_content_on_occurrence ON occurrence (content_id, utterance_id);

-- normative_occurrence table - sz = utterance

CREATE TABLE normative_occurrence
(
    utterance_id CHAR(32) NOT NULL,
    tally        INT(6) UNSIGNED NOT NULL,
--    CONSTRAINT FOREIGN KEY fk_utterance_id_on_norm_occur (utterance_id) REFERENCES utterance (utterance_id),
    PRIMARY KEY (utterance_id)
)
ENGINE=INNODB DEFAULT CHARSET=UTF8;

-- lexicon table - sz = content

CREATE TABLE lexicon
(
    content_id CHAR(9) NOT NULL,
    pos        CHAR(8) NOT NULL,
    tally      INT(6) UNSIGNED NOT NULL,
--    CONSTRAINT FOREIGN KEY fk_content_id_on_lex (content_id) REFERENCES content (content_id),
--    CONSTRAINT FOREIGN KEY fk_pos_on_lex        (pos       ) REFERENCES pos     (pos       ),
    PRIMARY KEY (content_id, pos)
)
ENGINE=INNODB DEFAULT CHARSET=UTF8;

-- normative_lexicon table - sz = pos

CREATE TABLE normative_lexicon
(
    pos   CHAR(8) NOT NULL,
    tally INT(6)  UNSIGNED NOT NULL,
--    CONSTRAINT FOREIGN KEY fk_pos_on_norm_lex (pos) REFERENCES pos (pos),
    PRIMARY KEY (pos)
)
ENGINE=INNODB DEFAULT CHARSET=UTF8;

-- content_utterances table - sz = utterances * content

CREATE TABLE content_utterances
(
    content_id CHAR(9) NOT NULL,
    pos        CHAR(8) NOT NULL,
    stem       TEXT    NOT NULL,
    utterances TEXT    NOT NULL,
--    CONSTRAINT FOREIGN KEY fk_content_id_on_cont_utter (content_id) REFERENCES content (content_id),
--    CONSTRAINT FOREIGN KEY fk_pos_on_cont_utter        (pos)        REFERENCES pos     (pos),
    PRIMARY KEY (content_id, pos, stem(128))
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
     INTO lexicon (content_id, pos, tally)
   SELECT o.content_id, u.pos, SUM(o.tally)
     FROM occurrence o,
          utterance u
    WHERE o.utterance_id = u.utterance_id
 GROUP BY o.content_id, u.pos;
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

zDROP PROCEDURE IF EXISTS fill_content_utterances //
CREATE PROCEDURE fill_content_utterances ()
BEGIN
   INSERT
     INTO content_utterances (content_id, pos, stem, utterances)
   SELECT o.content_id,
          u.pos,
          u.stem,
          GROUP_CONCAT(u.utterance SEPARATOR ' ') utterances
     FROM utterance u,
          occurrence o
    WHERE u.utterance_id = o.utterance_id
 GROUP BY o.content_id,
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
CALL fill_content_utterances;

COMMIT;

-- create users

DROP USER web@localhost;
FLUSH PRIVILEGES;
CREATE USER web@localhost IDENTIFIED BY 'web';
GRANT SELECT ON wooc.* TO web@localhost;
COMMIT;

SELECT CONCAT (now(),' - finished') info;
