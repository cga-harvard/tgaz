--
-- primary entity
-- each will have at least one spelling - see business rules document
--
CREATE TABLE  IF NOT EXISTS placename (
  id                  INT auto_increment,        -- use for joins and internal purposes
                                                 -- dropped order_id
                                                 -- may be possible to use sys-Id as PK but no auto-increment
  sys_id              VARCHAR(30) NOT NULL,      -- the chgis primary identifier (not PK)

  ftype_id            INT NOT NULL,              --
  data_src            VARCHAR(10) NOT NULL,      -- FK
  data_src_ref        VARCHAR(32),               -- primary identifier used in source data
                                                 -- for CHGIS before tgaz, the sys_id (without hvd_ prefix

  snote_id            INT,                       -- FK, can be NULL
  alt_of_id           INT,                       -- FK, can be NULL
  lev_rank            CHAR(2),                   -- level_rank ; administrative level assigned by FUDON

-- temporal
  beg_yr              INT,
  beg_rule_id         SMALLINT,                          -- FK, change from char(1)
  end_yr              INT,
  end_rule_id         SMALLINT,                          -- FK, change from char(1)

-- spatial
  obj_type            ENUM('POINT', 'POLYGON', 'LINE', 'ENTITY'),      -- prev: varchar(10) default NULL

  xy_type             ENUM('centroid', 'point', 'midpoint', 'point location', 'N/A'),   -- prev: VARCHAR(20),
                                                     -- value assigned based on later determination (not loaded)
  x_coord             VARCHAR(30),                   -- don't convert to float
  y_coord             VARCHAR(30),

  geo_src             VARCHAR(512),                  --

-- processing
  added_on              TIMESTAMP DEFAULT CURRENT_TIMESTAMP,  -- no auto update

PRIMARY KEY (id),
INDEX pn_sysid_idx (sys_id),

INDEX ftype_idx (ftype_id),
  FOREIGN KEY (ftype_id) REFERENCES ftype(id),
INDEX datasrc_idx (data_src),
  FOREIGN KEY (data_src) REFERENCES data_src(id),
INDEX datasrcref_idx (data_src_ref),
  -- no fk here
INDEX snote_idx (snote_id),
  FOREIGN KEY (snote_id) REFERENCES snote(id),
INDEX alt_of_idx (alt_of_id),
  FOREIGN KEY (alt_of_id) REFERENCES placename(id),
INDEX beg_rule_idx (beg_rule_id),
  FOREIGN KEY (beg_rule_id) REFERENCES drule(id),
INDEX end_rule_idx (end_rule_id),
  FOREIGN KEY (end_rule_id) REFERENCES drule(id)

) ENGINE = INNODB;

--
--
CREATE TABLE spelling (
  id                        INT auto_increment,
  placename_id              INT NOT NULL,
  script_id                 INT NOT NULL,
  written_form              VARCHAR(256),     -- i.e. the glyph, or text form
  exonym_lang               VARCHAR(8),       -- ISO 2-char, e.g. 'es' for Spanish in the case of 'Las Vegas'

  trsys_id                  VARCHAR(10) NOT NULL,  -- for type 'transcription', otherwise use 'na'

  attested_by               VARCHAR(128),
  note                      VARCHAR(512),

  PRIMARY KEY (id),
  INDEX sp_pn_idx (placename_id),
    FOREIGN KEY (placename_id) REFERENCES placename(id),
  INDEX sp_script_idx (script_id),
    FOREIGN KEY (script_id) REFERENCES script(id),
  INDEX sp_trsys_idx (trsys_id),
    FOREIGN KEY (trsys_id) REFERENCES trsys(id)
) ENGINE = INNODB;


--historical context tables


-- present location and present jurisdiction notes
-- the notion of 'present' is a relative one
CREATE TABLE pres_loc (
  id                           INT auto_increment,
  placename_id                 INT NOT NULL,
  type                         ENUM('location', 'jurisdiction'),
  country_code                 VARCHAR(8),
  text_value                   VARCHAR(128),
  source                       ENUM('Fudan', 'Google', 'Other') NOT NULL,
  attestation                  VARCHAR(512),

  PRIMARY KEY (id),
  INDEX presloc_pn_idx (placename_id),
    FOREIGN KEY (placename_id) REFERENCES placename(id)
) ENGINE = INNODB;


-- preceeded by - immediate precedence relationship between placenames
CREATE TABLE prec_by (
  id                           INT auto_increment,     -- prev: pby_uniq_id
  placename_id                 INT NOT NULL,           -- prev: pby_by_id vc12
  prec_id                      INT NOT NULL,           -- prev: pby_prev_id  vc12

  PRIMARY KEY  (id),
  INDEX precby_pn_idx (placename_id),
    FOREIGN KEY (placename_id) REFERENCES placename(id),
  INDEX precby_pr_idx (prec_id),
    FOREIGN KEY (prec_id) REFERENCES placename(id)

) ENGINE = INNODB;


-- relationship between placenames: "child is partof parent"
-- consider placename - container terminology
CREATE TABLE part_of (
  id                           INT auto_increment,
  child_id                     INT NOT NULL,        -- prev: pot_child_id  vc12
  parent_id                    INT NOT NULL,        -- prev: pot_parent_id vc12

  begin_year                   SMALLINT,            -- prev:  pot_begyr int8
  end_year                     SMALLINT,            -- prev:  pot_endyr int8

  PRIMARY KEY  (id),
  INDEX ptof_ch_idx (child_id),
    FOREIGN KEY (child_id) REFERENCES placename(id),
  INDEX ptof_prnt_idx (parent_id),
    FOREIGN KEY (parent_id) REFERENCES placename(id)

) ENGINE = INNODB;

--
--
CREATE TABLE admin_seat (
  id                          INT auto_increment,
  placename_id                INT NOT NULL,                  -- governed admin unit
  seat_id                     INT NOT NULL,
  begin_date                  VARCHAR(10),
  end_date                    VARCHAR(10),
  note                        VARCHAR(1028),

  PRIMARY KEY (id),
  INDEX adst_pn_idx (placename_id),
    FOREIGN KEY (placename_id) REFERENCES placename(id),
  INDEX adst_st_idx (seat_id),
    FOREIGN KEY (seat_id) REFERENCES placename(id)

) ENGINE = INNODB;

--
--
CREATE TABLE temporal_annotation (
  id                        INT auto_increment,
  placename_id              INT NOT NULL,
  temporal_type             ENUM('begin', 'end'),
  calendar_standard         ENUM('ISO 8601', 'Chinese Lunar', 'Other'),  -- or lookup table
  rule_id                   SMALLINT,             -- accuracy rule ?? type
  attested_by               VARCHAR(128),
  equivalent                VARCHAR(128),    -- what is this??
  lang                      VARCHAR(8),      -- ISO code
  note                      VARCHAR(512),

  PRIMARY KEY (id),
  INDEX tan_pn_idx (placename_id),
    FOREIGN KEY (placename_id) REFERENCES placename(id),
  INDEX tan_rule_idx (rule_id),
    FOREIGN KEY (rule_id) REFERENCES drule(id)

)ENGINE = INNODB;


-- spatial

-- well known text format
-- this table cannot be so generalized due to the varying datatypes of the object
-- solution:  create a different table for each type, in this case WKT
--            this creates a problem for the system_ref table join ??
CREATE TABLE wkt_definition (
  id                      INT auto_increment,
  placename_id            INT NOT NULL,
  object_type             ENUM('point', 'polygon', 'linestring', 'multipoint', 'multilinestring',
                                  'multipolygon', 'geometrycollection') NOT NULL,
--  definition_type         ENUM('wkt') NOT NULL,        -- unnecessary ??

  object_text_value          TEXT,        -- are there size issues?

  PRIMARY KEY (id),
  INDEX wktdef_pn_idx (placename_id),
    FOREIGN KEY (placename_id) REFERENCES placename(id)

)ENGINE = INNODB;


-- external GIS citation, as in an index
CREATE TABLE spatial_system_ref (
  id                          INT auto_increment,
  placename_id                INT NOT NULL,

  system_name                 ENUM('Geohex', 'Watershed', 'Other') NOT NULL,
  level                       INT,                           -- for geohex, only

  location_uri                VARCHAR(1028),
  location_id                 VARCHAR(100),                  -- ?? what is this  ??

  PRIMARY KEY (id),
  INDEX spsysref_pn_idx (placename_id),
    FOREIGN KEY (placename_id) REFERENCES placename(id)

)ENGINE = INNODB;



-- web context tables

--
--
CREATE TABLE link (
  id                        INT auto_increment,
  placename_id              INT NOT NULL,
  type                      ENUM('map', 'place description', 'authority record', 'other') NOT NULL,
  source                    VARCHAR(32) NOT NULL,
  uri                       VARCHAR(1028) NOT NULL,
  lang                      VARCHAR(8),

  PRIMARY KEY (id),

  INDEX lnk_pn_idx (placename_id),
    FOREIGN KEY (placename_id) REFERENCES placename(id)
) ENGINE = INNODB;