-- Migration: forum_votes for question/reply up/down votes (session/ip keyed)
CREATE TABLE IF NOT EXISTS `forum_votes` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `question_id` INT NULL,
  `reply_id` INT NULL,
  `vote` TINYINT NOT NULL DEFAULT 0, -- 1 for upvote, -1 for downvote
  `session_id` VARCHAR(128) DEFAULT NULL,
  `ip` VARCHAR(64) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_fv_question` (`question_id`),
  INDEX `idx_fv_reply` (`reply_id`),
  INDEX `idx_fv_session` (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Optional uniqueness guards (apply manually if desired):
-- ALTER TABLE `forum_votes` ADD UNIQUE KEY `uniq_vote_question_session` (`question_id`,`session_id`);
-- ALTER TABLE `forum_votes` ADD UNIQUE KEY `uniq_vote_reply_session` (`reply_id`,`session_id`);
