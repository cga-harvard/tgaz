
CREATE VIEW v_placename AS
SELECT pn.*, ftype.name_vn ftype_vn, ftype.name_alt ftype_alt, ftype.name_tr ftype_tr,
ftype.name_en ftype_en, snote.src_note_ref snote_ref, snote.full_text snote_text
FROM (placename pn JOIN ftype ON pn.ftype_id = ftype.id)
LEFT JOIN snote ON pn.snote_id = snote.id;

CREATE VIEW v_spelling AS
SELECT sp.*, script.name script, trsys.name trsys
FROM spelling sp, script, trsys
WHERE sp.script_id = script.id AND sp.trsys_id = trsys.id;

CREATE VIEW v_partof AS
SELECT po.*, pn.sys_id parent_sys_id, sp_vn.written_form parent_vn, sp_tr.written_form parent_tr
FROM part_of po, placename pn, spelling sp_vn, spelling sp_tr
WHERE po.parent_id = pn.id
AND sp_vn.placename_id = pn.id AND sp_tr.placename_id = pn.id
AND sp_tr.trsys_id != 'na'
AND sp_vn.script_id in (2, 4, 7, 10, 11, 12, 13, 14);

-- need better way to find a single vernacular name for a place

CREATE VIEW v_precby AS
SELECT pb.*, pn.sys_id parent_sys_id, sp_vn.written_form pb_vn, sp_tr.written_form pb_tr
FROM prec_by pb, placename pn, spelling sp_vn, spelling sp_tr
WHERE pb.prec_id = pn.id
AND sp_vn.placename_id = pn.id AND sp_tr.placename_id = pn.id
AND sp_tr.trsys_id != 'na'
AND sp_vn.script_id in (2, 4, 7, 10, 11, 12, 13, 14);
