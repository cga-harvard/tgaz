-- SQL to update NULL handles to sequentially assigned values with a prefix, here 'ru-'.
-- The first statement puts the highest 'ru' number in the variable @h.
-- The second assigns new handles based on the starting point in @h.



select @h := max(cast(substr(handle from 4) as unsigned)) from ftype_ingest where handle like 'ru-%';

update ftype_ingest set handle = concat('ru-', (@h := @h + 1)) where handle is null;

