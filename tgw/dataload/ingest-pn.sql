
SET @data_src := 'TBRC';
SET @beg_rule_id := 0;
SET @end_rule_id := 0;
SET @obj_type := 'POINT';
SET @parent_status := 'preferred';

SET @tr_sys_id := 'rj';

SET @presloc_type := 'location';
SET @presloc_src  := 'Google';


-- Note that since source notes can be NULL, an outer join is required to pull all the records



INSERT INTO placename (sys_id, ftype_id, data_src, data_src_ref, snote_id,
	                   beg_yr, beg_rule_id, end_yr, end_rule_id,
                       obj_type, xy_type, x_coord, y_coord, geo_src,
                       default_parent_id )
SELECT m.sys_id, m.ftype_REF, @data_src, NULL, snote_REF,
       m.beg_yr, @beg_rule_id, m.end_yr, @end_rule_id,
       @obj_type, m.xy_type, m.x_coord, m.y_coord, m.geo_src,
       parent_pn.id
FROM main_ingest m LEFT JOIN placename parent_pn ON (m.default_parent_sys_id = parent_pn.sys_id);

-- JOIN ftype
-- LEFT JOIN snote sn ON  m.note_id = sn.src_note_ref;

-- spelling 1 == transcription
INSERT INTO spelling (placename_id, script_id, written_form, trsys_id, default_per_type)
SELECT pn.id, 0, m.sp_1_written_form, @tr_sys_id, 1
FROM main_ingest m, placename pn
WHERE m.sys_id = pn.sys_id;

-- spelling 2 == primary vernacular
INSERT INTO spelling (placename_id, script_id, written_form, trsys_id, default_per_type)
SELECT pn.id, 1, m.sp_2_written_form, 'na', 1
FROM main_ingest m, placename pn
WHERE m.sys_id = pn.sys_id;

-- spelling 3 == secondary vernacular
INSERT INTO spelling (placename_id, script_id, written_form, trsys_id, default_per_type)
SELECT pn.id, 1, m.sp_3_written_form, 'na', 0
FROM main_ingest m, placename pn
WHERE m.sys_id = pn.sys_id;


INSERT INTO present_loc (placename_id, type, country_code, text_value, source)
SELECT pn.id, @presloc_type, m.ploc_country_code, m.ploc_text_value, @presloc_src
FROM main_ingest m, placename pn
WHERE pn.sys_id = m.sys_id;

-- preceded by  - use sys_id as REF

INSERT INTO prec_by (placename_id, prec_id)
SELECT pn.id, prec_pn.id
FROM main_ingest m, placename pn, placename prec_pn
WHERE pn.sys_id = m.sys_id AND prec_pn.sys_id = m.prec_by_sys_id;

-- part_of
-- all the placenames have been inserted, so parent child relationships can be built in any order
INSERT INTO part_of (child_id, parent_id, begin_yr, end_yr)
SELECT pn.id, par_pn, m.beg_yr, m.end_yr
FROM main_ingest m LEFT JOIN placename parent_pn ON (m.default_parent_sys_id = parent_pn.sys_id);