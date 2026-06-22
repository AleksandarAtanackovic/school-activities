-- =====================================================================
-- Праћење ваннаставних активности — ЧИСТА / ПРОДУКЦИОНА шема
-- Користите ОВАЈ фајл (уместо schema.sql) за прави cPanel.
-- Прави све табеле БЕЗ демо података, плус један админ налог:
--     корисничко име: admin
--     лозинка:        admin123
-- Админ мора да промени лозинку при првом пријављивању.
-- =====================================================================
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS attendance;
DROP TABLE IF EXISTS sessions;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS applications;
DROP TABLE IF EXISTS activity_teachers;
DROP TABLE IF EXISTS activities;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(120) NOT NULL,
  username    VARCHAR(60)  NOT NULL UNIQUE,
  maticni_broj VARCHAR(20) DEFAULT NULL UNIQUE,
  password    VARCHAR(255) NOT NULL,
  role        ENUM('admin','teacher','student') NOT NULL,
  grade_class VARCHAR(40) DEFAULT NULL,
  active      TINYINT(1) NOT NULL DEFAULT 1,
  must_change_password TINYINT(1) NOT NULL DEFAULT 0,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE activities (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  name          VARCHAR(150) NOT NULL,
  description   TEXT,
  location      VARCHAR(150) DEFAULT NULL,
  schedule_text VARCHAR(150) DEFAULT NULL,
  max_students  INT NOT NULL DEFAULT 20,
  status        ENUM('open','closed','archived') NOT NULL DEFAULT 'open',
  created_by    INT DEFAULT NULL,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_act_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE activity_teachers (
  activity_id INT NOT NULL,
  teacher_id  INT NOT NULL,
  PRIMARY KEY (activity_id, teacher_id),
  CONSTRAINT fk_at_activity FOREIGN KEY (activity_id) REFERENCES activities(id) ON DELETE CASCADE,
  CONSTRAINT fk_at_teacher  FOREIGN KEY (teacher_id)  REFERENCES users(id)      ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE applications (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  student_id  INT NOT NULL,
  activity_id INT NOT NULL,
  status      ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  applied_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  decided_at  DATETIME DEFAULT NULL,
  decided_by  INT DEFAULT NULL,
  UNIQUE KEY uniq_student_activity (student_id, activity_id),
  CONSTRAINT fk_app_student  FOREIGN KEY (student_id)  REFERENCES users(id)      ON DELETE CASCADE,
  CONSTRAINT fk_app_activity FOREIGN KEY (activity_id) REFERENCES activities(id) ON DELETE CASCADE,
  CONSTRAINT fk_app_decider  FOREIGN KEY (decided_by)  REFERENCES users(id)      ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE notifications (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  activity_id INT NOT NULL,
  title       VARCHAR(160) NOT NULL,
  message     TEXT NOT NULL,
  created_by  INT DEFAULT NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_not_activity FOREIGN KEY (activity_id) REFERENCES activities(id) ON DELETE CASCADE,
  CONSTRAINT fk_not_creator  FOREIGN KEY (created_by)  REFERENCES users(id)      ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE sessions (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  activity_id INT NOT NULL,
  session_date DATE NOT NULL,
  start_time  TIME DEFAULT NULL,
  notes       VARCHAR(255) DEFAULT NULL,
  created_by  INT DEFAULT NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_activity_date (activity_id, session_date),
  CONSTRAINT fk_sess_activity FOREIGN KEY (activity_id) REFERENCES activities(id) ON DELETE CASCADE,
  CONSTRAINT fk_sess_creator  FOREIGN KEY (created_by)  REFERENCES users(id)      ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE attendance (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  session_id  INT NOT NULL,
  student_id  INT NOT NULL,
  status      ENUM('present','absent','excused') NOT NULL DEFAULT 'present',
  recorded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  recorded_by INT DEFAULT NULL,
  UNIQUE KEY uniq_session_student (session_id, student_id),
  CONSTRAINT fk_att_session FOREIGN KEY (session_id) REFERENCES sessions(id) ON DELETE CASCADE,
  CONSTRAINT fk_att_student FOREIGN KEY (student_id) REFERENCES users(id)    ON DELETE CASCADE,
  CONSTRAINT fk_att_recorder FOREIGN KEY (recorded_by) REFERENCES users(id)  ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Једини унос: админ, мора да промени лозинку при првом пријављивању.
-- Хеш одговара лозинки "admin123".
INSERT INTO users (name, username, password, role, must_change_password) VALUES
('Администратор','admin','$2y$10$LAHHDhNto2hs5oK3xtIi/.lDqbWXq8n.ZFgb06HiMfgKJcekGOXje','admin',1);
