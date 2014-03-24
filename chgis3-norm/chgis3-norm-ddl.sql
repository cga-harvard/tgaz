-- Normalized version of chgis3


CREATE TABLE data_source {
  id       INT NOT NULL,
  abbr     VARCHAR(10) NOT NULL,
  name     VARCHAR(32) NOT NULL,
  org      VARCHAR(64),
  uri      VARCHAR(1000),
  note     TEXT
)  ENGINE=INNODB;

INSERT INTO data_source VALUES ( 1, 'CHGIS', 'China Historical GIS', 'Fudan University, Center for Historical Geography', '', '');
INSERT INTO data_source VALUES ( 2, 'CITAS', 'China in Time and Space', 'Center for Studies in Demography and Ecology, University of Washington',
  'http://citas.csde.washington.edu/data/data.html', '');
INSERT INTO data_source VALUES ( 3, 'NIMA', 'National Geospatial-Intelligence Agency (United States)',
  'http://earth-info.nga.mil/gns/html/index.html', 'formerly: National Imagery and Mapping Agency');
INSERT INTO data_source VALUES ( 4, 'RAS', 'Russian Academy of Sciences', '', '', 'See: http://www.fas.harvard.edu/~chgis/data/rus_geo/)');

-- In chgis3, the prefixes are cts [CITAS], nma [NIMA], ras [RAS].

CREATE TABLE feature_type (
  id INT NOT NULL,
  type_py VARCHAR(64),
  type_utf VARCHAR(32),     -- ?? utf8 is assumed, does this mean western/English term?
  type_utf_alt VARCHAR(64),


  PRIMARY KEY  (id)

)  ENGINE=INNODB;

--  combination of main3 and gis_info tables
CREATE TABLE placename (

  placename_id   INT auto_increment,        -- use for joins and internal purposes
  order_id       INT NOT NULL,              -- the chgis primary identifier (not PK)

  data_src       ENUM ('CHGIS', 'CITAS', 'NIMA', 'RAS'),  -- remove additional '\r' seen in dump files
  feature_type_id INT NOT NULL,

  name_py        VARCHAR(256),
  name_utf       VARCHAR(128),              -- change "utf" - see proposed v5 in table "spelling"
  name_utf_alt   VARCHAR(128),
  pres_loc       VARCHAR(512),              -- is this a discursive note?


--  pgn_note_id    VARCHAR(20) default NULL,     --
--  pt_note_id     VARCHAR(20) default NULL,
  object_note_id INT,

--  pgn_id         VARCHAR(20) default NULL,
--  pt_id          VARCHAR(20) default NULL,
--  line_id        VARCHAR(20) default NULL,


--temporal

  beg_chg_type   VARCHAR(60) default NULL,  -- these should all default to NULL 'by default'
  beg_chg_eng    VARCHAR(60) default NULL,
  end_chg_type   VARCHAR(60) default NULL,
  end_chg_eng    VARCHAR(60) default NULL,

  lev_rank       CHAR(2) default NULL,
  beg_yr int(8) default NULL,
  beg_rule char(1) default NULL,
  end_yr int(8) default NULL,
  end_rule char(1) default NULL,


-- historical context, also see preceeded_by table


-- gis info
                                                    -- why is this a varchar??
                                                    -- prev:  the link for pgn_id, pt_id and line_id
  sys_id         varchar(30) NOT NULL default '',   -- globally unique id - is this a UUID?  -- needs index ?
  xy_type        varchar(20) default NULL,          -- consider ENUM or lookup
  x_coord        varchar(30) default NULL,          -- why not float ??
  y_coord        varchar(30) default NULL,


  obj_type       ENUM('point', 'polygon', 'line'),      -- prev: varchar(10) default NULL
  geo_src        varchar(512),                             -- is this a note?
  compiler       varchar(60) default NULL,
  geocompiler    varchar(60) default NULL,
  entry_date     varchar(12) default NULL,                -- why not a date type?
  filename       VARCHAR(512),
--  src            varchar(12) default NULL,              -- same as data_src?


  PRIMARY KEY  (placename_id),
  FOREIGN KEY type_id REFERENCES(feature_type.id),


) ENGINE=INNODB;

CREATE INDEX pn_type_index ON  placename.type_id;
CREATE INDEX pn_precby_index ON  placename.prec_by_id;
CREATE INDEX pn_succby_index ON  placename.succ_by_id;
-- index name fields ? others

-- current structure implies one altname per placename?? if so, combine
CREATE TABLE alt_name (
  order_id              INT auto_increment,
  name_py tinytext,
  name_utf tinytext,
  name_utf_alt tinytext,
-- removed redundant fields

  parent_utf tinytext,
  parent_py tinytext,

  PRIMARY KEY  (order_id)
) ENGINE=INNODB;


CREATE TABLE prec_by (
  prec_by_id        INT auto_increment,  -- prev:  pby_uniq_id in last place

  placename_id     INT NOT NULL,
  prev_id          INT NOT NULL,

  beg_type varchar(30) default NULL,      -- ?? what are these?, chinese chars
  end_type varchar(30) default NULL,
  begyr int(8) default NULL,              --prev: pby_ ...
  endyr int(8) default NULL,


  PRIMARY KEY  (prec_by_id),
  FOREIGN KEY prec_by_id REFERENCES(placename.placename_id),
  FOREIGN KEY placename_id REFERENCES(placename.placename_id)

) ENGINE=INNODB;


-- assumes multiple ids per placename - otherwise combine with main table
CREATE TABLE src_notes (
  nts_autoid     int(12) NOT NULL auto_increment,

  placename_id   INT NOT NULL,                       -- added

  nts_comp       varchar(60) default NULL,
  nts_noteid     varchar(12) default NULL,
  nts_nmpy       tinytext,
  nts_nmch       varchar(40) default NULL,
  nts_nmft       varchar(40) default NULL,
  nts_fullnote   text,

  PRIMARY KEY  (nts_autoid),
  FOREIGN KEY
) ENGINE=INNODB;

CREATE INDEX -- on foreign key


CREATE TABLE partof (
  id int(12) NOT NULL auto_increment,  -- prev: pot_id

  pot_child_id varchar(12) NOT NULL default '',
--  pot_child_nmpy tinytext,
--  pot_child_nmch varchar(40) default NULL,
--  pot_child_nmft varchar(40) default NULL,

  pot_begyr int(8) default NULL,
  pot_endyr int(8) default NULL,

--  pot_parent_nmpy tinytext,
--  pot_parent_nmch varchar(40) default NULL,
  pot_parent_id varchar(12) NOT NULL default '',
  pot_data_src varchar(20) default NULL,          -- ENUM  or FK ?

  PRIMARY KEY  (id).
  FOREIGN KEY pot_child_id REFERENCES(placename.placename_id),
  FOREIGN KEY pot_parent_id REFERENCES(placename.placename_id),

) ENGINE=INNODB;

CREATE INDEX pot_child_index ON partof.pot_child_id;
CREATE INDEX pot_parent_index ON partof.pot_parent_id;