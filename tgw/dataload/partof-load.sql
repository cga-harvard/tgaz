
-- abbreviated data 
-- child and parent ids correspond to placename.sys_id with the hvd_ prefix,


CREATE TABLE IF NOT EXISTS `partof_xx` (
  `order_id` int(12) NOT NULL,
  `child_id` varchar(30),
  `parent_id` varchar(30),
  `beg_yr` varchar(40) default NULL,
  `end_yr` varchar(40) default NULL

) DEFAULT CHARSET=utf8;

-- 
LOAD DATA LOCAL INFILE '/home/whays/work/tgaz/tgw/load/data/v5_pof_20140416.csv'
INTO TABLE partof_xx
FIELDS TERMINATED BY '$'  
LINES TERMINATED BY '\n'
-- IGNORE 1 LINES
-- (pby_id, ...)


-- load partof-xx data into part_of table

INSERT INTO part_of 
  SELECT NULL, pn1.id, pn2.id, px.beg_yr, px.end_yr  
  FROM partof_xx px, placename pn1, placename pn2
  WHERE px.child_id = pn1.sys_id AND px.parent_id = pn2.sys_id;


