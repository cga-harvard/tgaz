-- data source entities
--
CREATE TABLE IF NOT EXISTS data_src (
  id       VARCHAR(10),               -- an abbreviation in upper case
  name     VARCHAR(64) NOT NULL,
  org      VARCHAR(128),
  uri      VARCHAR(1024),
  note     VARCHAR(1024),

  PRIMARY KEY (id),
  UNIQUE KEY name_unique (name)
) ENGINE = INNODB;

INSERT INTO data_src VALUES ('CHGIS', 'China Historical GIS', 'Fudan University, Center for Historical Geography', NULL, NULL);
INSERT INTO data_src VALUES ('CITAS', 'China in Time and Space', 'Center for Studies in Demography and Ecology, University of Washington',
  'http://citas.csde.washington.edu/data/data.html', NULL);
INSERT INTO data_src VALUES ('GNS', 'GNS Country Files', 'National Geospatial-Intelligence Agency (United States)',
  'http://earth-info.nga.mil/gns/html/index.html', 'formerly: National Imagery and Mapping Agency');
INSERT INTO data_src VALUES ('RAS', 'Russian Academy of Sciences', NULL, NULL, 'See: http://www.fas.harvard.edu/~chgis/data/rus_geo/)');
INSERT INTO data_src VALUES ('TBRC', 'Tibetan Buddhist Resource Center', '', 'http://www.tbrc.org', NULL);
INSERT INTO data_src VALUES ('DDBC', 'Dharma Drum Buddhist College', '', 'http://authority.ddbc.edu.tw/place/', NULL);
INSERT INTO data_src VALUES ('CBDB', 'China Biographical Database', '', 'http://isites.harvard.edu/icb/icb.do?keyword=k16229', NULL);
INSERT INTO data_src VALUES ('HGR', 'Historical Gazetteer of Russia', 'Fung Library, Davis Center for Russian and Eurasian Studies, Harvard', 'https://github.com/jaguillette/fungHGR/', NULL);
-- data rules to show level of accuracy in ascribed dates in placenames
--
CREATE TABLE IF NOT EXISTS drule (
  id                           SMALLINT,
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
INSERT INTO drule VALUES (7, 'Rule 7', '', NULL, NULL);
INSERT INTO drule VALUES (8, 'Rule 8', 'Used where assigned value was blank in CHGIS 3/5', NULL, NULL);
INSERT INTO drule VALUES (9, 'Rule 9', '', NULL, NULL);
INSERT INTO drule VALUES (0, 'Rule 0', '', NULL, NULL);


-- language script for placename spellings
-- see ISO-639-2  language codes
--
CREATE TABLE IF NOT EXISTS script (
  id                           INT,                  -- ?? decide on authorized codes
  name                         VARCHAR(32) NOT NULL,
  lang                         VARCHAR(8) NOT NULL,  -- language being written, typically
  dialect                      VARCHAR(8),           -- for example Cantonese specific chars
  default_per_lang             SMALLINT NOT NULL DEFAULT 0, -- 0/1 boolean, allows determination of default spelling
  note                         VARCHAR(512),

  PRIMARY KEY (id)
) ENGINE = INNODB;

INSERT INTO script VALUES (0, 'n/a', 'xx', '', 0, NULL);
INSERT INTO script VALUES (1, 'traditional Chinese', 'zh', '', 1, NULL);
INSERT INTO script VALUES (2, 'simplified Chinese', 'zh', '', 0, NULL);
INSERT INTO script VALUES (3, 'variant Chinese', 'zh', '', 0, NULL);
INSERT INTO script VALUES (4, 'Kanji', 'ja', '', 1, NULL);
INSERT INTO script VALUES (5, 'Hirigana', 'ja', '', 0, NULL);
INSERT INTO script VALUES (6, 'Katakana', 'ja', '', 0, NULL);
INSERT INTO script VALUES (7, 'Korean characters', 'ko', '', 1, NULL);
INSERT INTO script VALUES (8, 'Hangul', 'ko', '', 0, NULL);
INSERT INTO script VALUES (9, 'Cyrillic', 'ru', '', 1, NULL);
INSERT INTO script VALUES (10, 'Mongolian', 'mn', '', 1, NULL);
INSERT INTO script VALUES (11, 'Uighur', 'ug', '', 1, NULL);
INSERT INTO script VALUES (12, 'Tibetan', 'bo', '', 1, NULL);
INSERT INTO script VALUES (13, 'Arabic', 'ar', '', 1, NULL);
INSERT INTO script VALUES (14, 'Vietnamese', 'vi', '', 1, NULL);
INSERT INTO script VALUES (15, 'Manchu', 'mnc', '', 1, NULL);
--INSERT INTO script VALUES (16, '', '', '', 1, NULL);
--INSERT INTO script VALUES (17, '', '', '', 1, NULL);
--INSERT INTO script VALUES (18, '', '', '', 1, NULL);



-- transcription systems for placename spellings
--
CREATE TABLE IF NOT EXISTS trsys (
  id                           VARCHAR(10),            -- an abbreviation in lower case
  name                         VARCHAR(32) NOT NULL,
  lang                         VARCHAR(8) NOT NULL,    -- ISO 2 char code, with possible extension
  lang_subtype                 VARCHAR(32),            -- e.g. 'fr' for romanized as in French
  note                         VARCHAR(512),

  PRIMARY KEY (id)
) ENGINE = INNODB;


INSERT INTO trsys VALUES ('na', 'n/a', 'xx', '', NULL);
INSERT INTO trsys VALUES ('py', 'Pinyin', 'zh', 'Mandarin', NULL);
INSERT INTO trsys VALUES ('wg', 'Wade-Giles', 'zh', 'Mandarin', NULL);
INSERT INTO trsys VALUES ('rj', 'Romaji', 'ja', '', NULL);
INSERT INTO trsys VALUES ('ru_iso', 'Russian ISO-1995', 'ru', '', NULL);
--INSERT INTO trsys VALUES ('');
--INSERT INTO trsys VALUES ('');
--INSERT INTO trsys VALUES ('');


-- geographic feature types - in-house vocabulary
--  name_vn is not unique
--     recommendation: add field 'qualifier' to establish unique constraint with name_vn
-- load existing data into ftype_xx table
-- tranfer with:
-- insert into ftype select id, name_ch, name_py, name_rm, adl_class, "CHGIS", NULL, note, NULL, NULL, NULL from ftype_xx;
CREATE TABLE IF NOT EXISTS ftype (
  id                           INT AUTO_INCREMENT,

  name_vn                      VARCHAR(100),        -- v5 nm_trad
  name_alt                     VARCHAR(100),        -- v5 nm_simp
  name_tr                      VARCHAR(100),        -- v5 nm_py
  name_en                      VARCHAR(100),        -- v5 nm_eng

  period                       VARCHAR(64),         -- time period descriptions, disambiguates name_vn

  adl_class                    VARCHAR(64),

  cit_src                      VARCHAR(20),         -- groups the citations by origin
  citation                     VARCHAR(256),        -- prev: source

  note                         VARCHAR(512),

  ld_uri                       VARCHAR(1028),       -- uri for recognized linked data vocabulary

  added_on                     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,  -- no auto update

  PRIMARY KEY  (id)
) ENGINE = INNODB;

-- special 'unknown' row
-- INSERT INTO ftype VALUES (0, '未知', '未知', 'wei zhi', 'unknown', NULL, NULL, 'CHGIS', NULL, NULL, NULL, NULL);
-- id = 0 didn't work probably due to the auto_increment, gave id as 1120


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
  source                       ENUM('Fudan', 'HGRussia', 'Wikipedia'),
  compiler                     VARCHAR(64),          -- person's name, prev: nts_comp

  lang                         VARCHAR(8),           -- ISO 2 char code
  topic                        VARCHAR(128),         -- prev: nts_nmft  vc40
  uri                          VARCHAR(1024),        -- required if src_note_ref is null

  full_text                    VARCHAR(2048),        -- prev: nts_fullnote

  added_on                     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,  -- no auto update

  PRIMARY KEY  (id),
  INDEX src_note_ref_idx (src_note_ref)
) ENGINE = INNODB;
