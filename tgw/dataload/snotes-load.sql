SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";



CREATE TABLE IF NOT EXISTS `snote_xx` (
  `nts_comp` varchar(60) default NULL,
  `nts_noteid` varchar(12) default NULL,
  `nts_nmpy` tinytext,
  `nts_nmch` varchar(40) default NULL,
  `nts_nmft` varchar(40) default NULL,
  `nts_fullnote` text,
  `nts_autoid` int(20) auto_increment,
  PRIMARY KEY  (`nts_autoid`)
);

--

LOAD DATA LOCAL INFILE 'work/tgaz/tgw/load/notes_20140402.csv'
INTO TABLE snote_xx
FIELDS TERMINATED BY '$'  ENCLOSED BY '"'
LINES TERMINATED BY '\n'
 (nts_comp, nts_noteid , nts_nmpy, nts_nmch, nts_nmft, nts_fullnote, nts_autoid);


-- Note the default values for the current data

insert into snote select NULL, nts_noteid, 'Fudan', nts_comp, 'zh',  nts_nmft, NULL, NULL, nts_fullnote from snote_xx; 

