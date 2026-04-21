-- Persist reviewer and execution notes for AI queue items.

ALTER TABLE ai_action_queue
  ADD COLUMN review_note VARCHAR(500) NULL AFTER status,
  ADD COLUMN reviewed_by INT NULL AFTER review_note,
  ADD COLUMN reviewed_at DATETIME NULL AFTER reviewed_by,
  ADD COLUMN execution_note VARCHAR(500) NULL AFTER reviewed_at,
  ADD COLUMN executed_by INT NULL AFTER execution_note,
  ADD COLUMN executed_at DATETIME NULL AFTER executed_by;

CREATE INDEX idx_ai_action_queue_reviewed_by ON ai_action_queue (reviewed_by);
CREATE INDEX idx_ai_action_queue_executed_by ON ai_action_queue (executed_by);
