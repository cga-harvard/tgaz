
CREATE OR REPLACE VIEW v_placename AS
SELECT pn.*, ftype.name_vn ftype_vn, ftype.name_alt ftype_alt, ftype.name_tr ftype_tr,
ftype.name_en ftype_en, snote.src_note_ref snote_ref, snote.full_text snote_text
FROM (placename pn JOIN ftype ON pn.ftype_id = ftype.id)
LEFT JOIN snote ON pn.snote_id = snote.id;

CREATE OR REPLACE VIEW v_spelling AS
SELECT sp.*, script.name script, script.default_per_lang script_def, trsys.name trsys
FROM spelling sp, script, trsys
WHERE sp.script_id = script.id AND sp.trsys_id = trsys.id;

-- outer joins are needed since spellings are not always there for a given type
CREATE OR REPLACE VIEW v_partof AS
SELECT po.*, pn.sys_id parent_sys_id, sp_vn.written_form parent_vn, sp_tr.written_form parent_tr
FROM part_of po JOIN  placename pn ON (po.parent_id = pn.id)
LEFT JOIN spelling sp_vn ON (pn.id = sp_vn.placename_id)
LEFT JOIN spelling sp_tr ON (pn.id = sp_tr.placename_id)
WHERE sp_vn.script_id > 0 AND sp_vn.default_per_type = 1
AND sp_tr.trsys_id != 'na' AND sp_vn.default_per_type = 1;


CREATE OR REPLACE VIEW v_precby AS
SELECT pb.*, pn.sys_id pb_sys_id, sp_vn.written_form pb_vn, sp_tr.written_form pb_tr
FROM prec_by pb JOIN  placename pn ON (pb.prec_id = pn.id)
LEFT JOIN spelling sp_vn ON (pn.id = sp_vn.placename_id)
LEFT JOIN spelling sp_tr ON (pn.id = sp_tr.placename_id)
WHERE sp_vn.script_id > 0 AND sp_vn.default_per_type = 1
AND sp_tr.trsys_id != 'na' AND sp_vn.default_per_type = 1;

-- for search query
-- CREATE OR REPLACE VIEW v_pn_srch AS
-- SELECT pn.id, pn.sys_id, sp_vn.written_form name, sp_tr.written_form transcription,
--   pn.beg_yr, pn.end_yr, ftype.name_vn ftype_vn, ftype.name_tr ftype_tr
-- FROM placename pn JOIN ftype ON (pn.ftype_id = ftype.id)
-- LEFT JOIN spelling sp_vn ON (pn.id = sp_vn.placename_id)
-- LEFT JOIN spelling sp_tr ON (pn.id = sp_tr.placename_id)
-- WHERE sp_vn.default_per_type = 1
-- AND sp_vn.script_id > 0
-- AND sp_tr.default_per_type = 1
-- AND sp_tr.trsys_id != 'na';


-- materialized view for pn search
-- 35x faster than using the dynamic view
-- requires updating as needed - see script ??
DROP TABLE IF EXISTS mv_pn_srch;

CREATE TABLE mv_pn_srch AS
SELECT pn.id, pn.sys_id, pn.data_src, sp_vn.written_form name, sp_tr.written_form transcription,
  pn.beg_yr, pn.end_yr, ftype.name_vn ftype_vn, ftype.name_tr ftype_tr
FROM placename pn JOIN ftype ON (pn.ftype_id = ftype.id)
LEFT JOIN spelling sp_vn ON (pn.id = sp_vn.placename_id)
LEFT JOIN spelling sp_tr ON (pn.id = sp_tr.placename_id)
WHERE sp_vn.default_per_type = 1
AND sp_vn.script_id > 0
AND sp_tr.default_per_type = 1
AND sp_tr.trsys_id != 'na';

ALTER TABLE mv_pn_srch add PRIMARY KEY(id);