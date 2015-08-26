
-- materialized view used by search.php for the compound results display

-- only the default vernacular name and default transcription are used

-- includes the default parent as a representative (used for disambiguation)

-- This script should be run at reasonable intervals when data changes have been made.

DROP TABLE IF EXISTS mv_pn_srch;

CREATE TABLE mv_pn_srch AS
SELECT pn.id, pn.sys_id, pn.data_src, sp_vn.written_form name, sp_tr.written_form transcription,
  pn.beg_yr, pn.end_yr, pn.x_coord, pn.y_coord, ftype.name_vn ftype_vn, ftype.name_tr ftype_tr,
  pn.default_parent_id parent_id, ppn.sys_id parent_sys_id, p_sp_vn.written_form parent_vn, p_sp_tr.written_form parent_tr
FROM placename pn JOIN ftype ON (pn.ftype_id = ftype.id)
LEFT JOIN spelling sp_vn ON (pn.id = sp_vn.placename_id
   AND sp_vn.default_per_type = 1 AND sp_vn.script_id > 0)
LEFT JOIN spelling sp_tr ON (pn.id = sp_tr.placename_id
   AND sp_tr.default_per_type = 1 AND sp_tr.trsys_id != 'na')
LEFT JOIN placename ppn  ON (pn.default_parent_id = ppn.id)
LEFT JOIN spelling p_sp_vn ON (ppn.id = p_sp_vn.placename_id
   AND p_sp_vn.default_per_type = 1 AND p_sp_vn.script_id > 0)
LEFT JOIN spelling p_sp_tr ON (ppn.id = p_sp_tr.placename_id
   AND p_sp_tr.default_per_type = 1 AND p_sp_tr.trsys_id != 'na');


-- add the index, this will fail if duplicate pn.id are present

ALTER TABLE mv_pn_srch add PRIMARY KEY(id);








