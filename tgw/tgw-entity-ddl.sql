--
--
CREATE TABLE  IF NOT EXISTS placename (

  id                  INT auto_increment,        -- use for joins and internal purposes
                                                 -- dropped order_id
                                                 -- may be possible to use sys-Id as PK but no auto-increment
  sys_id              VARCHAR(30) NOT NULL,              -- the chgis primary identifier (not PK)

  ftype_id            INT NOT NULL,              -- should be NOT NULL with an 'Unknown' value
  dsrc_id             VARCHAR(10) NOT NULL,      -- FK

--  dsrc_ref            VARCHAR(32),               -- original id from source notes ?? discard after migration ?
                                                 -- use query to set this id from main_xx and snote.src_note_ref

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


-- historical


-- links

-- processing
  added_on              TIMESTAMP DEFAULT CURRENT_TIMESTAMP,  -- no auto update


PRIMARY KEY (id),

INDEX ftype_idx (ftype_id),
  FOREIGN KEY (ftype_id) REFERENCES ftype(id),
INDEX dsrc_idx (dsrc_id),
  FOREIGN KEY (dsrc_id) REFERENCES dsrc(id),
INDEX snote_idx (snote_id),
  FOREIGN KEY (snote_id) REFERENCES snote(id),
INDEX alt_of_idx (alt_of_id),
  FOREIGN KEY (alt_of_id) REFERENCES placename(id),
INDEX beg_rule_idx (beg_rule_id),
  FOREIGN KEY (beg_rule_id) REFERENCES drule(id),
INDEX end_rule_idx (end_rule_id),
  FOREIGN KEY (end_rule_id) REFERENCES drule(id)

) ENGINE = INNODB;


CREATE TABLE spelling (

  id                        INT auto_increment,
  placename_id              INT NOT NULL,
  script_id                 INT NOT NULL,
  written_form              VARCHAR(128),     -- i.e. the glyph, or text form
  exonym_lang               VARCHAR(8),       -- ISO 2-char, e.g. 'es' for Spanish in the case of 'Las Vegas'

  trsys_id                  VARCHAR(10) NOT NULL,              -- for type 'transcription', otherwise null

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

