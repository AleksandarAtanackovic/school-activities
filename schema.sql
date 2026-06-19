-- =====================================================================
-- Праћење ваннаставних активности — структура базе + демо подаци
-- Увезите овај фајл једном преко phpMyAdmin (картица „Увоз“).
-- Може се поново покренути: брише и поново креира табеле.
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
  email       VARCHAR(190) NOT NULL UNIQUE,
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

-- =====================================================================
-- ДЕМО ПОДАЦИ
-- Лозинке: админ=admin123  наставник=teacher123  ученик=student123
-- =====================================================================
INSERT INTO users (id, name, email, password, role, grade_class) VALUES
(1,'Администратор школе','admin@school.test','$2y$10$LAHHDhNto2hs5oK3xtIi/.lDqbWXq8n.ZFgb06HiMfgKJcekGOXje','admin',NULL),
(2,'Марко Марковић','adams@school.test','$2y$10$2jOCPb6kxH2OtxJj1XY.Mef2x03FR6G588o0cnnfR5XhAHPlZjQRu','teacher',NULL),
(3,'Јелена Јовановић','baker@school.test','$2y$10$2jOCPb6kxH2OtxJj1XY.Mef2x03FR6G588o0cnnfR5XhAHPlZjQRu','teacher',NULL),
(4,'Петар Петровић','cohen@school.test','$2y$10$2jOCPb6kxH2OtxJj1XY.Mef2x03FR6G588o0cnnfR5XhAHPlZjQRu','teacher',NULL),
(10,'Ана Новак','anna@school.test','$2y$10$oH7T6OSEvYIxhhna58UJ1OoCJsj/wbtur2agtPvwFiVoEbHTCBcKC','student','10-А'),
(11,'Бранко Костић','ben@school.test','$2y$10$oH7T6OSEvYIxhhna58UJ1OoCJsj/wbtur2agtPvwFiVoEbHTCBcKC','student','10-А'),
(12,'Клара Ђурић','clara@school.test','$2y$10$oH7T6OSEvYIxhhna58UJ1OoCJsj/wbtur2agtPvwFiVoEbHTCBcKC','student','10-Б'),
(13,'Давид Илић','david@school.test','$2y$10$oH7T6OSEvYIxhhna58UJ1OoCJsj/wbtur2agtPvwFiVoEbHTCBcKC','student','10-Б'),
(14,'Ена Лазић','ella@school.test','$2y$10$oH7T6OSEvYIxhhna58UJ1OoCJsj/wbtur2agtPvwFiVoEbHTCBcKC','student','11-А'),
(15,'Филип Гајић','felix@school.test','$2y$10$oH7T6OSEvYIxhhna58UJ1OoCJsj/wbtur2agtPvwFiVoEbHTCBcKC','student','11-А'),
(16,'Гина Хорват','gina@school.test','$2y$10$oH7T6OSEvYIxhhna58UJ1OoCJsj/wbtur2agtPvwFiVoEbHTCBcKC','student','11-Б'),
(17,'Хуго Ивановић','hugo@school.test','$2y$10$oH7T6OSEvYIxhhna58UJ1OoCJsj/wbtur2agtPvwFiVoEbHTCBcKC','student','11-Б'),
(18,'Ирис Јовић','iris@school.test','$2y$10$oH7T6OSEvYIxhhna58UJ1OoCJsj/wbtur2agtPvwFiVoEbHTCBcKC','student','12-А'),
(19,'Јакша Ким','jack@school.test','$2y$10$oH7T6OSEvYIxhhna58UJ1OoCJsj/wbtur2agtPvwFiVoEbHTCBcKC','student','12-А');

INSERT INTO activities (id, name, description, location, schedule_text, max_students, status, created_by) VALUES
(1,'Математичка секција','Такмичарска математика и решавање задатака.','Учионица 204','четвртком 15:00',6,'open',1),
(2,'Драмска секција','Глума, сценски наступи и пролећна представа.','Свечана сала','понедељком 16:00',12,'open',1),
(3,'Роботика','Прављење и програмирање робота за регионално такмичење.','Лабораторија Б','средом 15:30',8,'open',1),
(4,'Хор','Школски хор, пробе и концерти.','Музички кабинет','петком 14:00',20,'open',1);

-- доделе наставника (активност 3 има два наставника; наставник 2 има две активности)
INSERT INTO activity_teachers (activity_id, teacher_id) VALUES
(1,2),(2,3),(3,2),(3,4),(4,3);

INSERT INTO applications (student_id, activity_id, status, applied_at, decided_at, decided_by) VALUES
(10,1,'approved',NOW(),NOW(),2),
(11,1,'approved',NOW(),NOW(),2),
(12,1,'pending',NOW(),NULL,NULL),
(13,2,'approved',NOW(),NOW(),3),
(14,2,'approved',NOW(),NOW(),3),
(15,2,'pending',NOW(),NULL,NULL),
(16,3,'approved',NOW(),NOW(),2),
(17,3,'pending',NOW(),NULL,NULL),
(18,4,'approved',NOW(),NOW(),3),
(19,4,'rejected',NOW(),NOW(),3),
(10,2,'pending',NOW(),NULL,NULL);

INSERT INTO sessions (id, activity_id, session_date, start_time, created_by) VALUES
(1,1,'2026-04-02','15:00:00',2),
(2,1,'2026-04-09','15:00:00',2),
(3,1,'2026-04-16','15:00:00',2);

INSERT INTO attendance (session_id, student_id, status, recorded_by) VALUES
(1,10,'present',2),(1,11,'present',2),
(2,10,'present',2),(2,11,'absent',2),
(3,10,'present',2),(3,11,'present',2);

INSERT INTO notifications (activity_id, title, message, created_by) VALUES
(1,'Понесите калкулаторе','У четвртак почињемо област геометрије.',2),
(2,'Аудиције следеће недеље','Припремите једноминутни монолог.',3);
