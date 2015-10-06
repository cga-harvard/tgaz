


CREATE TEMPORARY TABLE main_ingest (
  sys_id              VARCHAR(30) NOT NULL,      -- the dataset primary identifier (not PK)

-- PROBLEMS with these 3
  ftype_REF           VARCHAR(30),               --
  snote_REF            VARCHAR(30),              -- FK, can be NULL
  alt_of_REF           VARCHAR(30),              -- FK, can be NULL

-- temporal  (beg_rule_id = NULL, end_rule_id = NULL)
  beg_yr              INT,
  end_yr              INT,

-- spatial
  xy_type             ENUM('centroid', 'point', 'midpoint', 'point location', 'N/A'),
  x_coord             VARCHAR(30),                   -- don't convert to float
  y_coord             VARCHAR(30),

  geo_src             VARCHAR(512),                  --

-- context, just parent -- note that the part_of row is also created!!
  default_parent_sys_id   VARCHAR(30),


-- sp 1 == transcription (trsys_id, exonym_lang, attested by from config, default per type = 1)

  sp_1_written_form              VARCHAR(256) NOT NULL,     -- i.e. the glyph, or text form
  sp_1_note                      VARCHAR(512),

-- sp 2  == primary spelling (script_id, attested by from config, default per type = 1)

  sp_2_written_form              VARCHAR(256),     -- i.e. the glyph, or text form
  sp_2_note                      VARCHAR(512),


-- sp 3 == secondary spelling (script_id, attested by from config, default per type = 0)

  sp_3_written_form              VARCHAR(256),     -- i.e. the glyph, or text form
  sp_3_note                      VARCHAR(512),


-- present location

  ploc_country_code                 VARCHAR(8),
  ploc_text_value                   VARCHAR(128),

-- prec_by

  prec_by_sys_id                  VARCHAR(30)

);

LOAD DATA LOCAL INFILE 'work/tgaz/tgw/dataload/test/pn_1.csv'
INTO TABLE main_ingest
FIELDS TERMINATED BY '$'  ENCLOSED BY '"'
LINES TERMINATED BY '\n';
