-- Base de données : pause_management
CREATE DATABASE IF NOT EXISTS pause_management DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE pause_management;

-- Table des administrateurs
CREATE TABLE IF NOT EXISTS admins (
  id INT NOT NULL AUTO_INCREMENT,
  username VARCHAR(50) NOT NULL,
  password VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table des employés
CREATE TABLE IF NOT EXISTS employees (
  id INT NOT NULL AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table des créneaux de pause
CREATE TABLE IF NOT EXISTS break_slots (
  id INT NOT NULL AUTO_INCREMENT,
  period ENUM('morning', 'afternoon') NOT NULL,
  start_time TIME NOT NULL,
  end_time TIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY period_start_time (period, start_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table des réservations de pause
CREATE TABLE IF NOT EXISTS break_reservations (
  id INT NOT NULL AUTO_INCREMENT,
  employee_id INT NOT NULL,
  slot_id INT NOT NULL,
  reservation_date DATE NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY employee_slot_date (employee_id, slot_id, reservation_date),
  FOREIGN KEY (employee_id) REFERENCES employees (id) ON DELETE CASCADE,
  FOREIGN KEY (slot_id) REFERENCES break_slots (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insertion d'un administrateur par défaut (username: admin, password: admin123)
INSERT INTO admins (username, password) VALUES ('admin', '$2y$10$8MILqtJ.Ug1rO81p7wtMkuP89pa5BzQYRdTN0q5h.JLRDn5mN8JxW');
insert into admins (username, password) values ('admins', 'admin124');
-- Insertion des créneaux de pause du matin
INSERT INTO break_slots (period, start_time, end_time) VALUES 
('morning', '09:00:00', '09:10:00'),
('morning', '09:15:00', '09:25:00'),
('morning', '09:30:00', '09:40:00'),
('morning', '09:45:00', '09:55:00'),
('morning', '10:00:00', '10:10:00'),
('morning', '10:15:00', '10:25:00'),
('morning', '10:30:00', '10:40:00'),
('morning', '10:45:00', '10:55:00'),
('morning', '11:00:00', '11:10:00'),
('morning', '11:15:00', '11:25:00'),
('morning', '11:30:00', '11:40:00'),
('morning', '11:45:00', '11:55:00'),
('morning', '12:00:00', '12:10:00'),
('morning', '12:15:00', '12:25:00'),
('morning', '12:30:00', '12:40:00');

-- Insertion des créneaux de pause de l'après-midi
INSERT INTO break_slots (period, start_time, end_time) VALUES 
('afternoon', '14:00:00', '14:10:00'),
('afternoon', '14:15:00', '14:25:00'),
('afternoon', '14:30:00', '14:40:00'),
('afternoon', '14:45:00', '14:55:00'),
('afternoon', '15:00:00', '15:10:00'),
('afternoon', '15:15:00', '15:25:00'),
('afternoon', '15:30:00', '15:40:00'),
('afternoon', '15:45:00', '15:55:00'),
('afternoon', '16:00:00', '16:10:00'),
('afternoon', '16:15:00', '16:25:00'),
('afternoon', '16:30:00', '16:40:00'),
('afternoon', '16:45:00', '16:55:00'),
('afternoon', '17:00:00', '17:10:00'),
('afternoon', '17:15:00', '17:25:00'),
('afternoon', '17:30:00', '17:40:00');
ALTER TABLE break_reservations 
ADD COLUMN status ENUM('reserved', 'started', 'completed', 'missed', 'delayed') DEFAULT 'reserved',
ADD COLUMN start_timestamp DATETIME NULL,
ADD COLUMN end_timestamp DATETIME NULL;

-- Mettre à jour les réservations existantes
UPDATE break_reservations SET status = 'reserved';