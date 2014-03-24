--  chgis5 - a normalized schema


CREATE TABLE data_source {
  id       INT NOT NULL,
  abbr     VARCHAR(10) NOT NULL,
  name     VARCHAR(32) NOT NULL,
  org      VARCHAR(64),
  uri      VARCHAR(1000),
  note     TEXT
);

INSERT INTO data_source VALUES ( 1, 'CHGIS', 'China Historical GIS', 'Fudan University, Center for Historical Geography', '', '');
INSERT INTO data_source VALUES ( 2, 'CITAS', 'China in Time and Space', 'Center for Studies in Demography and Ecology, University of Washington',
  'http://citas.csde.washington.edu/data/data.html', '');
INSERT INTO data_source VALUES ( 3, 'NIMA', 'National Geospatial-Intelligence Agency (United States)',
  'http://earth-info.nga.mil/gns/html/index.html', 'formerly: National Imagery and Mapping Agency');
INSERT INTO data_source VALUES ( 4, 'RAS', 'Russian Academy of Sciences', '', '', 'See: http://www.fas.harvard.edu/~chgis/data/rus_geo/)');

-- In chgis3, the prefixes are cts [CITAS], nma [NIMA], ras [RAS].

CREATE TABLE feature_type (
  feature_type_id              INT,

  -- uri for recognized linked data vocabulary ?? more than one
  ld_vocab                     VARCHAR(24),
  ld_uri                       VARCHAR(1028),

  -- this arrangement of names assumes only one of each type of name, unlike with placename spellings
  vernacular_lang              VARCHAR(8),
  vernacular_name              VARCHAR(100),

  transcription_system         VARCHAR(8),
  transcription_name           VARCHAR(100),

  translation_lang             VARCHAR(8),
  translation_name             VARCHAR(100),

  note                         VARCHAR(512)

  PRIMARY KEY  (feature_type_id)

);

-- look up table of 6 rules
CREATE TABLE date_rule (
  id                           INT,
  rule                         VARCHAR(512),

  PRIMARY KEY  (id)

);


--  combination of main3 and gis_info tables
CREATE TABLE placename (

  placename_id        INT auto_increment,        -- use for joins and internal purposes
  order_id            INT NOT NULL,              -- the chgis primary identifier (not PK)

  data_src            ENUM ('CHGIS', 'CITAS', 'NIMA', 'RAS'),
  feature_type_id     INT NOT NULL,

  lev_rank            CHAR(2) default NULL,      -- WHAT IS THIS?

-- temporal   -- prefer to require ISO 8601 as Calendar and formatting
              -- otherwise two fields:  calendar AND format
              -- or multiples in joined table

  beg_chg_type        VARCHAR(60),                  -- are these lookups?, e.g. type 'time-slice'
  beg_chg_eng         VARCHAR(60),
  end_chg_type        VARCHAR(60),
  end_chg_eng         VARCHAR(60),

  beg_yr              INT,                          -- ISO 8601 ?
  beg_rule            INT,                          -- FK, change from char(1)
  end_yr              INT,                          -- ISO 8601 ?
  end_rule            INT,                          -- FK, change from char(1)



-- spatial

  --  sys_id         varchar(30) NOT NULL default '',   -- globally unique id
                                                        -- is this used or exposed?
  obj_type       ENUM('point', 'polygon', 'line'),      -- prev: varchar(10) default NULL

  xy_type        varchar(20) default NULL,          -- consider ENUM or lookup
  x_coord        varchar(30) default NULL,          -- why not float ??
  y_coord        varchar(30) default NULL,

  geo_src        varchar(512),                             -- is this a note?
  compiler       varchar(60) default NULL,
  geocompiler    varchar(60) default NULL,
  entry_date     varchar(12) default NULL,                -- why not a date type?
  filename       VARCHAR(512),


-- present location and jurisdiction are types of placename spelling
   -- unless they refer to a specific object of a certain type and can differ


-- historical


-- links



FOREIGN KEY type_id REFERENCES(feature_type.feature_type_id)

);


-- textual representation of a placename
-- for type 'vernacular', require only one per placename
-- for types 'present location' and 'present jurisdiction' only allow at most one of each per placename
CREATE TABLE spelling (

  spelling_id        INT auto_increment,
  placename_id       INT NOT NULL,
  spelling_type      ENUM('vernacular', 'exonym', 'transcription', 'present location',
                          'present jurisdiction') NOT NULL,
  lang               VARCHAR(8),             -- when type transcription, lang of original
  script             VARCHAR(16),
  default_form       BOOLEAN NOT NULL DEFAULT false,

  written_form       VARCHAR(100),          -- i.e. the glyph, or text form

-- for type 'transcription'
  transcription_of_id  INT,                  -- NULL for other types
  transcription_system ENUM('py', 'wg', 'cyrillic', 'other'),                  --

-- how is 'alternate' (as in chgis3) expressed here?
  alternate_of_id      INT,

-- note field?  source info?  citation?  attestation
  attested_by         VARCHAR(128),

  PRIMARY KEY (spelling_id),
  FOREIGN KEY placename_id REFERENCES placename.placename_id,
  FOREIGN KEY transcription_of_id REFERENCES spelling.spelling_id,
  FOREIGN KEY alternate_of_id REFERENCES spelling.spelling_id


);


CREATE TABLE spatial_definition (
  spatial_definition_id      INT auto_increment,
  placename_id               INT NOT NULL,
  object_type_id             ENUM('point', 'centroid', 'polygon', 'line') NOT NULL,
  definition_type            ENUM('coord', 'wkt', 'geo_json') NOT NULL,

--how to encode common point coordinates as in chgis3??
--this seems like a standard gis thing

  x_value                    DOUBLE,
  x_bearing                  CHAR(1),

  y_value                    DOUBLE,
  y_bearing                  CHAR(1),

  z_value                    DOUBLE,
  z_bearing                  CHAR(1),

  object_text_value          text,        -- what is the max here ?

  PRIMARY KEY (spatial_definition_id),
);


-- if only one of these per parent, then combine
CREATE TABLE spatial_system_ref (
  spatial_system_ref_id       INT auto_increment,
  spatial_definition_id       INT NOT NULL,

  system_name                 VARCHAR(20),    -- lookup
  level                       INT,            -- is this applicable to all systems?
  location_uri                VARCHAR(100),
  location_id                 VARCHAR(100),


  PRIMARY KEY (spatial_system_ref_id),
  FOREIGN KEY spatial_definition_id REFERENCES spatial_definition.spatial_definition_id
);

-- can this, or should this, be generalized ? with a relationship_type field
CREATE TABLE admin_seat (
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


