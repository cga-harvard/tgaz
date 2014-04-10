

-- Fudan specific fields to add to placename rows
--
CREATE TABLE  IF NOT EXISTS fudan (

  id                    INT auto_increment,
  placename_id          INT,

  beg_chg_type        VARCHAR(60),               -- from Fudan, possibly in other table
  beg_chg_eng         VARCHAR(60),
  end_chg_type        VARCHAR(60),
  end_chg_eng         VARCHAR(60),

  possibly in another table for Fudan
  compiler            VARCHAR(60),
  geocompiler         VARCHAR(60),
  entry_date          VARCHAR(12),                   -- why not a date type?
  filename            VARCHAR(512),


) ENGINE=INNODB;