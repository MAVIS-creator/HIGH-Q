CREATE TABLE IF NOT EXISTS forum_replies (
  id INT NOT NULL AUTO_INCREMENT,
  question_id INT NOT NULL,
  parent_id INT DEFAULT NULL,
  name VARCHAR(255) DEFAULT NULL,
  content TEXT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY question_idx (question_id),
  KEY parent_idx (parent_id),
  CONSTRAINT fk_forum_question FOREIGN KEY (question_id) REFERENCES forum_questions (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
