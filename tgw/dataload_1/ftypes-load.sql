-- geographic feature types - raw input 
-- v5 2014-04-10
-- 
CREATE TABLE IF NOT EXISTS ftype_xx (
  order_id                     INT,

  lang                         VARCHAR(8),         -- v5

  name_py                      VARCHAR(100),       -- v4: hv_ft_py
  name_ch                      VARCHAR(100),       -- v4: hv_ft_ch

  id                           VARCHAR(16),                -- v4: hv_ft_id

  name_en                      VARCHAR(100),       -- v4: hv_ft_eng
  name_alt                     VARCHAR(100),       -- v5

  adl_class                    VARCHAR(64),        -- v4: adl_class

  period                      VARCHAR(100),        -- v5 
  cit_src                     VARCHAR(32),        -- v5 "cit source"

  note                         VARCHAR(512),        -- v4: note

  status                       VARCHAR(64),         -- v5, moved position


  ts_added                     TIMESTAMP,  

  PRIMARY KEY  (order_id)

);

-- load feature type data into intermediate table
LOAD DATA LOCAL INFILE '/home/whays/work/tgaz/tgw/load/v5_feature_types_20140411.csv'
INTO TABLE ftype_xx
FIELDS TERMINATED BY '$'  ENCLOSED BY '"'
LINES TERMINATED BY '\r\n'
IGNORE 1 LINES
 (order_id, lang, name_py, name_ch, id, name_en, name_alt, adl_class, period, cit_src, note, status)
SET ts_added = CURRENT_TIMESTAMP


-- insert into end product
insert into ftype select id, name_ch, name_alt, name_py, name_en, period, adl_class, cit_src, NULL, note, NULL, NULL from ftype_xx;


