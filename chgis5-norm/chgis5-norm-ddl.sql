--  chgis5 - a normalized schema


CREATE TABLE data_source {
  id       INT,
  abbr     VARCHAR(10) NOT NULL,
  name     VARCHAR(32) NOT NULL,
  org      VARCHAR(64),
  uri      VARCHAR(1000),
  note     VARCHAR(1028)

  PRIMARY KEY (id)
);

INSERT INTO data_source VALUES ( 1, 'CHGIS', 'China Historical GIS', 'Fudan University, Center for Historical Geography', '', '');
INSERT INTO data_source VALUES ( 2, 'CITAS', 'China in Time and Space', 'Center for Studies in Demography and Ecology, University of Washington',
  'http://citas.csde.washington.edu/data/data.html', '');
INSERT INTO data_source VALUES ( 3, 'NIMA', 'National Geospatial-Intelligence Agency (United States)',
  'http://earth-info.nga.mil/gns/html/index.html', 'formerly: National Imagery and Mapping Agency');
INSERT INTO data_source VALUES ( 4, 'RAS', 'Russian Academy of Sciences', '', '', 'See: http://www.fas.harvard.edu/~chgis/data/rus_geo/)');

-- In chgis3, the prefixes are cts [CITAS], nma [NIMA], ras [RAS].

CREATE TABLE feature_type (
  feature_type_id              INT,                -- v4: hv_ft_id

  vernacular_lang              VARCHAR(10),        -- 'zh' for chinese
  vernacular_name              VARCHAR(100),       -- v4: hv_ft_ch

  transcription_system         VARCHAR(10),        -- 'py'
  transcription_name           VARCHAR(100),       -- v4: hv_ft_py

  translation_lang             VARCHAR(16),        -- 'en'
  translation_name             VARCHAR(100),       -- v4: hv_ft_eng

  adl_class                    VARCHAR(64),         -- v4: ads_class

  data_source                  VARCHAR(10),
  citation                     VARCHAR(256),        -- prev: source

  note                         VARCHAR(512),        -- v4: note

  -- uri for recognized linked data vocabulary ?? more than one
  ld_vocab                     VARCHAR(24),
  ld_uri                       VARCHAR(1028),

  PRIMARY KEY  (feature_type_id)
);


-- look up table of 6 rules
CREATE TABLE date_rule (
  id                           INT,
  name                         VARCHAR(32),
  rule                         VARCHAR(512),

  -- uri for recognized linked data vocabulary ?? more than one
  ld_vocab                     VARCHAR(24),
  ld_uri                       VARCHAR(1028),

  PRIMARY KEY  (id)
);


--  combination of main3 and gis_info tables
CREATE TABLE placename (

  placename_id        INT auto_increment,        -- use for joins and internal purposes
                                                 -- dropped order_id
  sys_id              VARCHAR(30) NOT NULL,              -- the chgis primary identifier (not PK)

  feature_type_id     INT NOT NULL,
  data_src_id         INT NOT NULL,              -- FK, otherwise: ENUM ('CHGIS', 'CITAS', 'NIMA', 'RAS'),
  src_note_id         INT,                       -- FK, can be NULL

  level_rank          CHAR(2) default NULL,      -- prev: lev_rank ; administrative level assigned by FUDON


-- temporal   -- prefer to require ISO 8601 as Calendar and formatting
              -- otherwise two fields:  calendar AND format
              -- or multiples in joined table

  beg_chg_type        VARCHAR(60),                  -- from FUDON, leave as is
  beg_chg_eng         VARCHAR(60),
  end_chg_type        VARCHAR(60),
  end_chg_eng         VARCHAR(60),

  beg_yr              INT,                          -- ISO 8601 ?
  beg_rule            INT,                          -- FK, change from char(1)
  end_yr              INT,                          -- ISO 8601 ?
  end_rule            INT,                          -- FK, change from char(1)

  -- outer_begin                                       -- to show range of accuracy
  -- outer_end

-- spatial

  obj_type            ENUM('point', 'polygon', 'line'),      -- prev: varchar(10) default NULL

  xy_type             ENUM('centroid', 'point', 'midpoint', 'point location', 'N/A'),   -- prev: VARCHAR(20),
  x_coord             VARCHAR(30),          -- why not float ??
  y_coord             VARCHAR(30),

  geo_src             VARCHAR(512),                  -- is this a note?
  compiler            VARCHAR(60),
  geocompiler         VARCHAR(60),
  entry_date          VARCHAR(12),                   -- why not a date type?
  filename            VARCHAR(512),

-- historical


-- links

PRIMARY KEY placename_id,

FOREIGN KEY type_id REFERENCES(feature_type.feature_type_id),
FOREIGN KEY data_src_id REFERENCES(data_source.id),
FOREIGN KEY src_note_id REFERENCES(src_note.feature_type_id),
FOREIGN KEY type_id REFERENCES(feature_type.feature_type_id),
-- FK for source note
-- FKs for rules  FIXME

);

-- textual representation of a placename
-- for type 'vernacular', require only one per placename
-- for types 'present location' and 'present jurisdiction' only allow at most one of each per placename
CREATE TABLE spelling (

  spelling_id        INT auto_increment,
  placename_id       INT NOT NULL,
  spelling_type      ENUM('vernacular', 'exonym', 'transcription') NOT NULL,
  lang               VARCHAR(8),             -- when type transcription, lang of original
  script             VARCHAR(16),            -- simplified, traditional  CJK?
                     -- ENUM('traditional chinese', 'simplified chinese',
                     -- does 'simplified' also apply to Japanese Korean and Vietnamese?
                     -- would also apply to many languages that alternatively use Arabic script
--  default_form       BOOLEAN NOT NULL DEFAULT false,
                                             -- can infer preferred form in app from other fields

  written_form       VARCHAR(128),           -- i.e. the glyph, or text form

-- for type 'transcription'
  transcription_system      ENUM('py', 'wg', 'cyrillic', 'arabic', 'romaji'),                  --
  transcription_of_id       INT,             -- FK for vernacular spelling
                                             -- ?? use actual text since FK is difficult to manage
                                             -- or name the spelling type to reference other spelling ?
                                             --
                                             -- NULL for other types

-- how is 'alternate' (as in chgis3) expressed here?
  alternate_of_id           INT,

-- note field?  source info?  citation?  attestation
  attested_by               VARCHAR(128),

  PRIMARY KEY (spelling_id),
  FOREIGN KEY placename_id REFERENCES placename.placename_id,
  FOREIGN KEY transcription_of_id REFERENCES spelling.spelling_id,
  FOREIGN KEY alternate_of_id REFERENCES spelling.spelling_id

);

--
CREATE TABLE temporal_annotation (
  id                        INT auto_increment,
  placename_name_id         INT NOT NULL,
  temporal_type             ENUM('begin', 'end'),
  calendar_standard         ENUM('ISO 8601', 'Chinese Lunar', 'Other'),  -- or lookup table
  accuracy_rule_id          INT,             -- ?? type
  attested_by               VARCHAR(128),
  equivalent                VARCHAR(128),    -- what is this??
  lang                      VARCHAR(8),      -- ISO code
  note                      VARCHAR(512),

  PRIMARY KEY (spelling_id),
  FOREIGN KEY placename_id REFERENCES placename.placename_id,
  FOREIGN KEY accuracy_rule_id REFERENCES(data_rule.id)       -- is this linked to the right table?

);

-- well known text format
-- this table cannot be so generalized due to the varying datatypes of the object
-- solution:  create a different table for each type, in this case WKT
--            this creates a problem for the system_ref table join ??
CREATE TABLE wkt_definition (
  spatial_definition_id      INT auto_increment,
  placename_id               INT NOT NULL,
  object_type_id             ENUM('point', 'polygon', 'linestring', 'multipoint', 'multilinestring',
                                  'multipolygon', 'geometrycollection') NOT NULL,
  definition_type            ENUM('wkt') NOT NULL,        -- unnecessary ??

--how to encode common point coordinates as in chgis3??
--this seems like a standard gis thing

--bounding box??

  object_text_value          TEXT,        -- are there size issues?

  PRIMARY KEY (spatial_definition_id),
  FOREIGN KEY placename_id REFERENCES placename.placename_id
);

-- external GIS citation, as in an index
-- FIXME  what is the joined table ?
CREATE TABLE spatial_system_ref (
  spatial_system_ref_id       INT auto_increment,
  spatial_definition_id       INT NOT NULL,

  system_name                 VARCHAR(20),    -- lookup, e.g. geohex or watersheds
  level                       INT,            -- is this applicable to all systems?
  location_uri                VARCHAR(100),
  location_id                 VARCHAR(100),


  PRIMARY KEY (spatial_system_ref_id),
  FOREIGN KEY spatial_definition_id REFERENCES spatial_definition.spatial_definition_id
);

-- can this, or should this, be generalized ? with a relationship_type field
-- combine with prec?
CREATE TABLE historical_context (   -- admin_relationship
  id                          INT auto_increment,
  placename_id                INT NOT NULL,                  -- governed admin unit
  seat_id                     INT NOT NULL,
  relationship_type           ENUM( 'a', 'b', 'c'),          -- FIXME
  begin_date                  VARCHAR(10),
  end_date                    VARCHAR(10),
  note                        VARCHAR(1028)

  PRIMARY KEY (id),
  FOREIGN KEY placename_id REFERENCES placename.placename_id,
  FOREIGN KEY seat_id REFERENCES placename.placename_id
);


-- assumes multiple ids per placename - otherwise combine with main table
-- n to 1!!!!
CREATE TABLE src_note (
  src_note_id                 INT auto_increment,

  note_id                      VARCHAR(12),  -- a 5 digit number, sometimes prefixed with 'relig_'
                                             -- FIXME - make sure the links in the main table go to the PK
                                             -- find a better field name
  compiler                     VARCHAR(64),          -- person's name, prev: nts_comp

  vernacular_lang              VARCHAR(8),
  vernacular_name              VARCHAR(100),         -- prev: nts_nmch  vc40

  -- FIXME
  -- other vernacular: simplified vs trad.   -- prev:  nts_nmft vc40

  transcription_system         VARCHAR(8),
  transcription_name           VARCHAR(100),         -- prev: nts_nmpy  tinytext

  translation_lang             VARCHAR(8),
  translation_name             VARCHAR(100),

  full_text                    VARCHAR(1028),        -- prev: nts_fullnote

  PRIMARY KEY  (src_note_id)
);


-- present location and present jurisdiction notes
-- is there a source, attestation or other field?    FIXME
-- is the notion of 'present' a relative one?
CREATE TABLE present_location (
  id                           INT auto_increment,
  placename_id                 INT NOT NULL,
  type                         ENUM('location', 'jurisdiction'),
  vernacular_lang              VARCHAR(8),
  vernacular_name              VARCHAR(100),

  transcription_system         VARCHAR(8),
  transcription_name           VARCHAR(100),

  translation_lang             VARCHAR(8),
  translation_name             VARCHAR(100),

  note                         VARCHAR(512),      -- is this needed?

  PRIMARY KEY id,
  FOREIGN KEY placename_id REFERENCES placename.placename_id
};


-- preceeded by - immediate precedence relationship between placenames
CREATE TABLE prec_by (
  id                           INT auto_increment,     -- prev: pby_uniq_id
  placename_id                 INT NOT NULL,           -- prev: pby_by_id vc12
  pn_prec_id                   INT NOT NULL,           -- prev: pby_prev_id  vc12

  begin_year                   SMALLINT,            -- prev:  pby_begyr int8
  end_year                     SMALLINT,            -- prev:  pby_endyr int8

  PRIMARY KEY  (id),
  FOREIGN KEY placename_id REFERENCES placename.placename_id,
  FOREIGN KEY pn_prec_id REFERENCES placename.placename_id
);


-- relationship between placenames: "child is partof parent"
-- consider placename - container terminology
CREATE TABLE partof (
  partof_id                    INT auto_increment,  -- prev: pot_id
  child_id                     INT NOT NULL,        -- prev: pot_child_id  vc12
  parent_id                    INT NOT NULL,        -- prev: pot_parent_id vc12

  begin_year                   SMALLINT,            -- prev:  pot_begyr int8
  end_year                     SMALLINT,            -- prev:  pot_endyr int8

  PRIMARY KEY  (id),
  FOREIGN KEY child_id REFERENCES(placename.placename_id),
  FOREIGN KEY parent_id REFERENCES(placename.placename_id),

);

CREATE INDEX partof_child_index ON partof.child_id;
CREATE INDEX partof_parent_index ON partof.parent_id;


