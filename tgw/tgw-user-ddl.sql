-- for TGaz v.2
--

CREATE TABLE user (
  id                   INT UNSIGNED auto_increment,
  name                 VARCHAR(64),
  org,                 VARCHAR(128),
  email                VARCHAR(128),
  status               ENUM('inactive', 'active'),
  password             CHAR(256),
  salt                 VARCHAR(12),

  PRIMARY KEY (id)
)ENGINE = INNODB;

--Annotation
CREATE TABLE anno (
  id                   INT UNSIGNED auto_increment,
  placename_id         INT UNSIGNED NOT NULL,
  user_id              INT UNSIGNED NOT NULL,
  anno_type            ENUM('error', 'fixed' , 'comment', 'correction'),
  lang                 VARCHAR(8),      -- ISO code
  note                 VARCHAR(2048),
  src                  VARCHAR(1024),
  added_on             TIMESTAMP DEFAULT NOW (),

  PRIMARY KEY (id),
  INDEX nan_pn_idx (placename_id),
    FOREIGN KEY (placename_id) REFERENCES placename(id),
  INDEX nan_user_idx (user_id),
    FOREIGN KEY (user_id) REFERENCES user(id)
)ENGINE = INNODB;


--Placename changes relating to a specific annotation
--description should be system generated
CREATE TABLE anno_changes (
  id                INT UNSIGNED auto_increment,
  anno_id           INT UNSIGNED NOT NULL,
  description       VARCHAR(256),

  PRIMARY KEY (id),
  INDEX nanch_pn_idx (anno_id),
    FOREIGN KEY (anno_id) REFERENCES anno(id)
)ENGINE = INNODB;
