-- upgrade tgaz ddl to v.1.3 from v.1.2

-- in tgw-views.sql there are corrections in existing views and a new views
-- run that script on test and production

-- in tgw-mat-vw-search.sql, the materialized view is significantly revised
-- to change how the parent id is obtained - now maintained as a regular
-- column in the placename table.  First, however, the placename table needs
-- to be addressed

ALTER TABLE placename ADD default_parent_id INT UNSIGNED;
ALTER TABLE placename ADD INDEX pn_def_prnt_idx (default_parent_id);
ALTER TABLE placename ADD FOREIGN KEY (default_parent_id) REFERENCES placename(placename_id);

ALTER TABLE placename ADD parent_status ENUM('earliest', 'preferred') DEFAULT 'earliest';

-- populate default parent id field from current/old materialized view

UPDATE placename JOIN mv_pn_srch ON (placename.id = mv_pn_srch.id) SET placename.default_parent_id = mv_pn_srch.parent_id WHERE mv_pn_srch.parent_id IS NOT NULL;


-- late change  ADD CONSTRAINT

ALTER TABLE spelling   MODIFY written_form VARCHAR(256) NOT NULL;
ALTER TABLE spelling   MODIFY default_per_type TINYINT(4) NOT NULL DEFAULT 0;

UPDATE script SET lang = 'xx' where id = 0;


--for my local mariadb
ALTER TABLE placename ADD def_parent_id INT;
ALTER TABLE placename ADD INDEX pn_d_prnt_idx (def_parent_id);
ALTER TABLE placename ADD FOREIGN KEY (def_parent_id) REFERENCES placename(placename_id);
