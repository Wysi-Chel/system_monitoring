-- The LBA account is now SA (System Admin), so stored LBA values become SA.
-- Covers both workspaces (micei_* and ntr_*) and every column that records who
-- processed, created, reviewed, or approved something.
-- Safe to import again: rows already holding SA are left alone.
-- Run it against system_monitoring_db, and against system_monitoring_test_db
-- when the test server is in use. A database that has not created the ntr_
-- tables yet will report those statements as unknown tables; skip them.

UPDATE micei_system_monitoring SET processed_by = 'SA' WHERE processed_by = 'LBA';
UPDATE ntr_system_monitoring   SET processed_by = 'SA' WHERE processed_by = 'LBA';

UPDATE micei_ticket_monitoring SET created_by = 'SA' WHERE created_by = 'LBA';
UPDATE ntr_ticket_monitoring   SET created_by = 'SA' WHERE created_by = 'LBA';

UPDATE micei_access_requests
SET it_reviewed_by    = CASE WHEN it_reviewed_by = 'LBA' THEN 'SA' ELSE it_reviewed_by END,
    final_reviewed_by = CASE WHEN final_reviewed_by = 'LBA' THEN 'SA' ELSE final_reviewed_by END,
    implemented_by    = CASE WHEN implemented_by = 'LBA' THEN 'SA' ELSE implemented_by END,
    reviewed_by       = CASE WHEN reviewed_by = 'LBA' THEN 'SA' ELSE reviewed_by END
WHERE 'LBA' IN (it_reviewed_by, final_reviewed_by, implemented_by, reviewed_by);

UPDATE ntr_access_requests
SET it_reviewed_by    = CASE WHEN it_reviewed_by = 'LBA' THEN 'SA' ELSE it_reviewed_by END,
    final_reviewed_by = CASE WHEN final_reviewed_by = 'LBA' THEN 'SA' ELSE final_reviewed_by END,
    implemented_by    = CASE WHEN implemented_by = 'LBA' THEN 'SA' ELSE implemented_by END,
    reviewed_by       = CASE WHEN reviewed_by = 'LBA' THEN 'SA' ELSE reviewed_by END
WHERE 'LBA' IN (it_reviewed_by, final_reviewed_by, implemented_by, reviewed_by);

UPDATE micei_user_accesses
SET approved_by    = CASE WHEN approved_by = 'LBA' THEN 'SA' ELSE approved_by END,
    implemented_by = CASE WHEN implemented_by = 'LBA' THEN 'SA' ELSE implemented_by END
WHERE 'LBA' IN (approved_by, implemented_by);

UPDATE ntr_user_accesses
SET approved_by    = CASE WHEN approved_by = 'LBA' THEN 'SA' ELSE approved_by END,
    implemented_by = CASE WHEN implemented_by = 'LBA' THEN 'SA' ELSE implemented_by END
WHERE 'LBA' IN (approved_by, implemented_by);
