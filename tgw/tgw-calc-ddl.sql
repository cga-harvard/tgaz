--tables for derivate data


-- calculated values from recursive part-of relationships
-- one-to-one with placename table
CREATE TABLE admin_hierarchy (
  id                           INT auto_increment,
  placename_id                 INT NOT NULL,
  complete                     CHAR(1),               -- y - yes, n - No
  text_value                   VARCHAR(1028),

  PRIMARY KEY (id),
  INDEX adhr_pn_idx (placename_id),
    FOREIGN KEY (placename_id) REFERENCES placename(id)
) ENGINE = INNODB;