-- AquaFix Pro — Live Chat

CREATE TABLE IF NOT EXISTS chat_conversations (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  guest_token     VARCHAR(64) UNIQUE,            -- for guests; NULL for logged-in
  user_id         INT NULL,                       -- for logged-in users
  visitor_name    VARCHAR(120),
  visitor_email   VARCHAR(160),
  last_message_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  is_closed       TINYINT(1) DEFAULT 0,
  created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_last_msg (last_message_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS chat_messages (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  conversation_id INT NOT NULL,
  sender_type     ENUM('visitor','admin') NOT NULL,
  sender_id       INT NULL,                       -- admin user id if admin
  body            TEXT NOT NULL,
  is_read         TINYINT(1) DEFAULT 0,
  created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (conversation_id) REFERENCES chat_conversations(id) ON DELETE CASCADE,
  INDEX idx_conv_time (conversation_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;