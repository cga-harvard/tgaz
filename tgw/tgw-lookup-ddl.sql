-- data source entities
--
CREATE TABLE IF NOT EXISTS dsrc (
  id       VARCHAR(10) NOT NULL,  -- an abbreviation in upper case
  name     VARCHAR(64) NOT NULL,
  org      VARCHAR(128),
  uri      VARCHAR(1024),
  note     VARCHAR(1024),

  PRIMARY KEY (id)
) ENGINE = INNODB;

INSERT INTO dsrc VALUES ('CHGIS', 'China Historical GIS', 'Fudan University, Center for Historical Geography', NULL, NULL);
INSERT INTO dsrc VALUES ('CITAS', 'China in Time and Space', 'Center for Studies in Demography and Ecology, University of Washington',
  'http://citas.csde.washington.edu/data/data.html', NULL);
INSERT INTO dsrc VALUES ('NIMA', '', 'National Geospatial-Intelligence Agency (United States)',
  'http://earth-info.nga.mil/gns/html/index.html', 'formerly: National Imagery and Mapping Agency');
INSERT INTO dsrc VALUES ('RAS', 'Russian Academy of Sciences', NULL, NULL, 'See: http://www.fas.harvard.edu/~chgis/data/rus_geo/)');


-- data rules to show level of accuracy in ascribed dates in placenames
--
CREATE TABLE IF NOT EXISTS drule (
  id                           INT,
  name                         VARCHAR(32),
  rule                         VARCHAR(512),

  -- uri for recognized linked data vocabulary ?? more than one
  ld_vocab                     VARCHAR(24),
  ld_uri                       VARCHAR(1028),

  PRIMARY KEY  (id)
) ENGINE = INNODB;


INSERT INTO drule VALUES (1, 'Rule 1', 'Year is set according to a pan-Dynastic period, such as "Qin Han" or "Song Yuan"', NULL, NULL);
INSERT INTO drule VALUES (2, 'Rule 2', 'Year is set according to a Dynastic period, such as "Tang," or "Ming“', NULL, NULL);
INSERT INTO drule VALUES (3, 'Rule 3', 'Year is set according to a Dynastic Title or Reign Period, such as "Shundi" or "Zhizheng"', NULL, NULL);
INSERT INTO drule VALUES (4, 'Rule 4', 'Year is specified, such as "13th Year of the Kangxi Reign Period"', NULL, NULL);
INSERT INTO drule VALUES (5, 'Rule 5', 'Season or Month is specified, such as "4th month of the Lunar year" or "autumn"', NULL, NULL);
INSERT INTO drule VALUES (6, 'Rule 6', 'Date is specified, such as "jiachen day”', NULL, NULL);


-- language script for placename spellings
--
CREATE TABLE IF NOT EXISTS script (
  id                           INT,                  -- ?? decide on authorized codes
  name                         VARCHAR(32) NOT NULL,
  lang                         VARCHAR(8),           -- language being written, typically
  lang_subtype                 VARCHAR(8),           -- e.g. 'fr' for romanized as in French
  note                         VARCHAR(512),

  PRIMARY KEY (id)
) ENGINE = INNODB;

INSERT INTO script VALUES (1, 'traditional Chinese', 'zh', '', NULL);
INSERT INTO script VALUES (2, 'simplified Chinese', 'zh', '', NULL);
INSERT INTO script VALUES (3, 'variant Chinese', 'zh', '', NULL);
INSERT INTO script VALUES (4, 'Kanji', 'ja', '', NULL);
INSERT INTO script VALUES (5, 'Hirigana', 'ja', '', NULL);
INSERT INTO script VALUES (6, 'Katakana', 'ja', '', NULL);
INSERT INTO script VALUES (7, 'Korean characters', 'ko', '', NULL);
INSERT INTO script VALUES (8, 'Hangul', 'ko', '', NULL);
INSERT INTO script VALUES (9, 'Cyrillic', 'ru', '', NULL);
INSERT INTO script VALUES (10, 'Mongolian', 'mn', '', NULL);
INSERT INTO script VALUES (11, 'Uighur', 'ug', '', NULL);
INSERT INTO script VALUES (12, 'Tibetan', 'bo', '', NULL);
INSERT INTO script VALUES (13, 'Arabic', 'ar', '', NULL);
INSERT INTO script VALUES (14, 'Vietnamese', 'vi', '', NULL);
INSERT INTO script VALUES (15, 'Malay', 'ms', '', NULL);        -- code 'ma' ?? from Lex
--INSERT INTO script VALUES (16, '', '', '', NULL);
--INSERT INTO script VALUES (17, '', '', '', NULL);
--INSERT INTO script VALUES (18, '', '', '', NULL);



-- transcription systems for placename spellings
--
CREATE TABLE IF NOT EXISTS trsys (
  id                           VARCHAR(10),      -- an abbreviation in lower case
  name                         VARCHAR(32),
  lang                         VARCHAR(8),       -- ISO 2 char code, with possible extension
  dialect                      VARCHAR(32),
  note                         VARCHAR(512),

  PRIMARY KEY (id)
) ENGINE = INNODB;


INSERT INTO trsys VALUES ('py', 'Pinyin', 'zh', 'Mandarin', NULL);
INSERT INTO trsys VALUES ('wg', 'Wade-Giles', 'zh', 'Mandarin', NULL);
INSERT INTO trsys VALUES ('rj', 'Romaji', 'ja', '', NULL);
--INSERT INTO trsys VALUES ('');
--INSERT INTO trsys VALUES ('');
--INSERT INTO trsys VALUES ('');


-- geographic feature types - in-house vocabulary
--
-- load existing data into ftype_xx table
-- tranfer with:
-- insert into ftype select id, name_ch, name_py, name_rm, adl_class, "CHGIS", NULL, note, NULL, NULL, NULL from ftype_xx;
CREATE TABLE IF NOT EXISTS ftype (
  id                           INT AUTO_INCREMENT,  -- v4: hv_ft_id

  name_ch                      VARCHAR(100),        -- v4: hv_ft_ch
  name_py                      VARCHAR(100),        -- v4: hv_ft_py
  name_rm                      VARCHAR(100),        -- v4: hv_ft_eng

  adl_class                    VARCHAR(64),         -- v4: ads_class

  data_src                     VARCHAR(10),
  citation                     VARCHAR(256),        -- prev: source

  note                         VARCHAR(512),        -- v4: note

  -- uri for recognized linked data vocabulary ?? more than one
  ld_vocab                     VARCHAR(24),
  ld_uri                       VARCHAR(1028),

  added_on                     TIMESTAMP,

  PRIMARY KEY  (id),
  INDEX data_src_idx (data_src),
    FOREIGN KEY (data_src)  REFERENCES dsrc(id)  ON DELETE SET NULL
) ENGINE = INNODB;




-- needs citation field?
--
-- load existing data into table snote_xx
-- transfer with:
-- insert into snote select NULL, nts_noteid, 'Fudan', nts_comp, 'zh',  nts_nmft, NULL, NULL, nts_fullnote from snote_xx;
CREATE TABLE IF NOT EXISTS snote (
  id                           INT auto_increment,

  src_note_ref                 VARCHAR(32),       -- a 5 digit number, sometimes prefixed with 'relig_'
                                                  -- prev: note_id
                                                  -- UNIQUE ??
  source                       ENUM('Fudan', 'Wikipedia'),
  compiler                     VARCHAR(64),          -- person's name, prev: nts_comp

  lang                         VARCHAR(8),           -- ISO 2 char code
  topic                        VARCHAR(128),         -- prev: nts_nmft  vc40
  uri                          VARCHAR(1024),        -- required if src_note_ref is null

  full_text                    VARCHAR(2048),        -- prev: nts_fullnote

  PRIMARY KEY  (id),
  INDEX src_note_ref_idx (src_note_ref)
) ENGINE = INNODB;
