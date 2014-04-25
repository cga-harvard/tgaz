SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


CREATE TABLE IF NOT EXISTS `precby_xx` (
  `pby_id` varchar(30) NOT NULL default '',
  `pby_nmpy` tinytext,
  `pby_nmch` varchar(40) default NULL,
  `pby_nmft` varchar(40) default NULL,
  `pby_obj_type` varchar(20) default NULL,
  `pby_prev_id` varchar(30) default NULL,
  `pby_prev_nmpy` tinytext,
  `pby_prev_nmch` varchar(40) default NULL,
  `pby_prev_nmft` varchar(40) default NULL,
  `pby_uniq_id` int(12) NOT NULL auto_increment,
  PRIMARY KEY  (`pby_uniq_id`)
) DEFAULT CHARSET=utf8;


-- 
LOAD DATA LOCAL INFILE '/home/whays/work/tgaz/tgw/load/data/v5_precby_20140402.csv'
INTO TABLE precby_xx
FIELDS TERMINATED BY '$'  ENCLOSED BY '"'
LINES TERMINATED BY '\n'
IGNORE 1 LINES
-- (pby_id, ...)


-- load precby-xx data into prec_by table

INSERT INTO prec_by 
  SELECT NULL, pn1.id, pn2.id 
  FROM precby_xx px, placename pn1, placename pn2
  WHERE px.pby_id = pn1.sys_id AND px.pby_prev_id = pn2.sys_id;

