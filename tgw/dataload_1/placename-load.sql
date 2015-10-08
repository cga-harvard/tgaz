-- sql command to load csv data into intermediate tables
-- and then insert into the target production tables



SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


CREATE TABLE `main_xx` (
  `sys_id` varchar(24) NOT NULL,
  `nm_py` tinytext,
  `nm_simp` tinytext,
  `name_trad` tinytext,
  `orig_ID` varchar(20) DEFAULT NULL,           -- sys_id without prefix hvd_
  `beg_yr` int(8) DEFAULT NULL,
  `end_yr` int(8) DEFAULT NULL,
  `xy_type` varchar(20) DEFAULT NULL,
  `x_coord` varchar(30) DEFAULT NULL,
  `y_coord` varchar(30) DEFAULT NULL,
  `pres_loc` tinytext,
  `type_py` varchar(80) DEFAULT NULL,             -- all NULL
  `type_utf` varchar(40) DEFAULT NULL,
  `type_id` varchar(6) DEFAULT NULL,              -- all NULL
  `type_eng` varchar(80) DEFAULT NULL,
  `lev_rank` char(2) DEFAULT NULL,
  `note_id` varchar(20) DEFAULT NULL,
  `nt_auto` varchar(20) DEFAULT NULL,
  `obj_type` varchar(10) DEFAULT NULL,
  `data_src` varchar(12) DEFAULT NULL,
  `auto_id` int(12) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`auto_id`),
  KEY `sysmain` (`sys_id`),
  KEY `origid` (`orig_ID`),
  KEY `nt` (`note_id`)
);



CREATE TABLE IF NOT EXISTS `gis_xx` (
  `order_id` int(12) NOT NULL default '0',
  `name_py` tinytext,
  `name_utf` tinytext,
  `name_utf_alt` tinytext,
  `sys_id` varchar(30) NOT NULL default '',
  `xy_type` varchar(20) default NULL,
  `x_coord` varchar(30) default NULL,
  `y_coord` varchar(30) default NULL,
  `pres_loc` tinytext,
  `type_py` varchar(80) default NULL,
  `type_utf` varchar(40) default NULL,
  `lev_rank` char(2) default NULL,
  `beg_yr` int(8) default NULL,
  `beg_rule` char(1) default NULL,
  `beg_chg_type` varchar(60) default NULL,
  `end_yr` int(8) default NULL,
  `end_rule` char(1) default NULL,
  `end_chg_type` varchar(60) default NULL,
  `note_id` varchar(20) default NULL,
  `obj_type` varchar(10) default NULL,
  `geo_src` tinytext,
  `compiler` varchar(60) default NULL,
  `geocompiler` varchar(60) default NULL,
  `checker` varchar(60) default NULL,
  `filename` tinytext,
  `src` varchar(12) default NULL,
  PRIMARY KEY  (`order_id`)
);


-- 

LOAD DATA LOCAL INFILE 'work/tgaz/tgw/load/v5_main_20140411.csv'
INTO TABLE main_xx
FIELDS TERMINATED BY '$'  ENCLOSED BY '"'
LINES TERMINATED BY '\n';

LOAD DATA LOCAL INFILE 'work/tgaz/tgw/load/v5_gisA_20140402.csv'
INTO TABLE gis_xx
FIELDS TERMINATED BY '$'  ENCLOSED BY '"'
LINES TERMINATED BY '\n';

LOAD DATA LOCAL INFILE 'work/tgaz/tgw/load/v5_gisB_20140402.csv'
INTO TABLE gis_xx
FIELDS TERMINATED BY '$'  ENCLOSED BY '"'
LINES TERMINATED BY '\n';

-- Note that since source notes can be NULL, an outer join is required to pull all the records

 INSERT INTO placename (sys_id, ftype_id, data_src, data_src_ref, snote_id, lev_rank, beg_yr, beg_rule_id, end_yr, end_rule_id, obj_type,
        xy_type, x_coord, y_coord, geo_src )
SELECT m.sys_id, m.type_id, m.data_src, orig_ID, sn.id, m.lev_rank, g.beg_yr, g.beg_rule, g.end_yr, g.end_rule, g.obj_type,
       'point', g.x_coord, g.y_coord, g.geo_src
FROM (main_xx m JOIN gis_xx g ON m.orig_id = g.sys_id) LEFT JOIN snote sn ON  m.note_id = sn.src_note_ref;


-- spellings

--different query for each form of name:  ch-trad, ch-simp, py, 
-- use sys_id to get placename id

-- Traditional Chinese names --tested

-- script id = 1 for traditional

 INSERT INTO spelling (placename_id, script_id, written_form, exonym_lang, trsys_id, default_per_type, attested_by, note )
 SELECT pn.id, 1, m.name_trad, NULL, 'na', 0, NULL, NULL
 FROM main_xx m, placename pn
 WHERE m.sys_id = pn.sys_id;


-- Simplified Chinese names --tested

-- script id = 2 for simplified

 INSERT INTO spelling (placename_id, script_id, written_form, exonym_lang, trsys_id, default_per_type, attested_by, note )
SELECT pn.id, 2, m.nm_simp, NULL, 'na' , 1, NULL, NULL
FROM main_xx m, placename pn
WHERE m.sys_id = pn.sys_id;


-- Pinyin names --tested

-- script id = 0 for n/a

 INSERT INTO spelling (placename_id, script_id, written_form, exonym_lang, trsys_id, default_per_type, attested_by, note )
SELECT pn.id, 0, m.nm_py, NULL, 'py', 1, NULL, NULL
FROM main_xx m, placename pn
WHERE m.sys_id = pn.sys_id;


-- present location

INSERT INTO present_loc
SELECT NULL, pn.id, 'location', 'cn', m.pres_loc, 'Fudan', NULL 
from main_xx m, placename pn
where pn.sys_id = m.sys_id;


