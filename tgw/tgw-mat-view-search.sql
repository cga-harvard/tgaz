
-- materialized view used by search.php for the compound results display

-- only the default vernacular name and default transcription are used

-- includes the earliest parent as a representative (used for disambiguation)
-- in the case of concurrent earliest parents, one is deleted based on
-- an added 'counter_id' via the @r variable (MYSQL only)

-- This script should be run at reasonable intervals when data changes have been made.

DROP TABLE IF EXISTS mv_pn_srch;

SET @r := 0;

CREATE TABLE mv_pn_srch AS
SELECT pn.id, pn.sys_id, pn.data_src, sp_vn.written_form name, sp_tr.written_form transcription,
  pn.beg_yr, pn.end_yr, pn.x_coord, pn.y_coord, ftype.name_vn ftype_vn, ftype.name_tr ftype_tr
  pof.parent_id, pof.parent_sys_id, pof.parent_vn, pof.parent_tr, (@r := @r + 1) counter_id
FROM placename pn JOIN ftype ON (pn.ftype_id = ftype.id)
LEFT JOIN spelling sp_vn ON (pn.id = sp_vn.placename_id)
LEFT JOIN spelling sp_tr ON (pn.id = sp_tr.placename_id)
LEFT JOIN (
  SELECT pof1.child_id, pof1.parent_id, p_pn.sys_id parent_sys_id, pof1.begin_year,
         p_vn.written_form parent_vn, p_tr.written_form parent_tr
  FROM
    (SELECT child_id, MIN(begin_year) earliest_pyear
     FROM   part_of
     GROUP BY child_id) pof2
     JOIN part_of pof1 ON (pof1.child_id = pof2.child_id
       AND pof1.begin_year = pof2.earliest_pyear)
     JOIN placename p_pn ON (p_pn.id = pof1.parent_id)
     LEFT JOIN spelling p_vn ON (p_vn.placename_id = p_pn.id
       AND p_vn.script_id > 0 AND p_vn.default_per_type = 1)
     LEFT JOIN spelling p_tr ON (p_tr.placename_id = p_pn.id
       AND p_tr.trsys_id != 'na' AND p_tr.default_per_type = 1)) pof
  ON (pof.child_id = pn.id)
WHERE sp_vn.default_per_type = 1 AND sp_vn.script_id > 0
AND sp_tr.default_per_type = 1 AND sp_tr.trsys_id != 'na';

-- remove one of any duplicates where parent begin year is repeated

DELETE mv_pn_srch
FROM mv_pn_srch
JOIN (
   select distinct id rid, parent_id, counter_id
   from mv_pn_srch group by id having count(id) > 1
) as kprows ON (mv_pn_srch.id = kprows.rid)
WHERE mv_pn_srch.counter_id != kprows.counter_id;

-- add the index

ALTER TABLE mv_pn_srch add PRIMARY KEY(id);








