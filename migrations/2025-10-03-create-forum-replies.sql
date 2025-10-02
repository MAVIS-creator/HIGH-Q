-- Migration: create forum_replies table for replies to community questions
CREATE TABLE IF NOT EXISTS `forum_replies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `question_id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `content` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX (`question_id`),
  CONSTRAINT `fk_forum_replies_question` FOREIGN KEY (`question_id`) REFERENCES `forum_questions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
