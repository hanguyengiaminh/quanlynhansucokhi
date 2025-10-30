-- DATABASE: qlns_cokhi
DROP DATABASE IF EXISTS qlns_cokhi;
CREATE DATABASE qlns_cokhi CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE qlns_cokhi;

-- -----------------------------
-- BẢNG users 
-- -----------------------------
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  email VARCHAR(150),
  role ENUM('admin','hr','employee') DEFAULT 'employee',
  employee_id INT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO users (username, password, email, role)
VALUES 
('admin', SHA2('123456',256), 'admin@cokhi.com', 'admin'),
('hr01', SHA2('123456',256), 'hr@cokhi.com', 'hr');

INSERT INTO users (username, password, email, role, employee_id)
VALUES
('nv01', SHA2('123456',256), 'nv01@cokhi.com', 'employee', 1),
('nv02', SHA2('123456',256), 'nv02@cokhi.com', 'employee', 2);

-- -----------------------------
-- BẢNG departments
-- -----------------------------
CREATE TABLE departments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150),
  code VARCHAR(50),
  description TEXT
);

INSERT INTO departments (name, code, description) VALUES
('Phòng Cơ khí chế tạo', 'CKCT', 'Phụ trách gia công chi tiết, cơ cấu máy'),
('Phòng Thiết kế', 'TK', 'Thiết kế máy móc, bản vẽ kỹ thuật'),
('Phòng QA/QC', 'QAQC', 'Kiểm tra chất lượng sản phẩm'),
('Phòng Nhân sự', 'NS', 'Quản lý nhân viên, lương, hợp đồng');

-- -----------------------------
-- BẢNG positions
-- -----------------------------
CREATE TABLE positions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(150),
  level VARCHAR(50),
  description TEXT
);

INSERT INTO positions (title, level, description) VALUES
('Kỹ sư cơ khí', 'Mid', 'Thiết kế, chế tạo cơ khí'),
('Công nhân tiện CNC', 'Junior', 'Vận hành máy CNC'),
('Trưởng phòng cơ khí', 'Senior', 'Quản lý bộ phận cơ khí'),
('Nhân viên nhân sự', 'Mid', 'Quản lý hồ sơ nhân sự');

-- -----------------------------
-- BẢNG employees
-- -----------------------------
CREATE TABLE employees (
  id INT AUTO_INCREMENT PRIMARY KEY,
  employee_code VARCHAR(50) UNIQUE,
  full_name VARCHAR(200),
  dob DATE,
  gender ENUM('M','F'),
  address VARCHAR(255),
  phone VARCHAR(50),
  email VARCHAR(150),
  join_date DATE,
  department_id INT,
  position_id INT,
  FOREIGN KEY (department_id) REFERENCES departments(id),
  FOREIGN KEY (position_id) REFERENCES positions(id)
);

INSERT INTO employees (employee_code, full_name, dob, gender, address, phone, email, join_date, department_id, position_id) VALUES
('NV001', 'Nguyễn Văn An', '1990-05-10', 'M', 'Hà Nội', '0905123456', 'an.nguyen@cokhi.com', '2020-03-15', 1, 1),
('NV002', 'Trần Văn Bình', '1988-11-02', 'M', 'Bắc Ninh', '0906234567', 'binh.tran@cokhi.com', '2019-07-01', 1, 2),
('NV003', 'Lê Thị Hoa', '1995-04-25', 'F', 'Hải Dương', '0907345678', 'hoa.le@cokhi.com', '2021-02-20', 2, 1),
('NV004', 'Phạm Quang Huy', '1992-12-09', 'M', 'Hà Nội', '0908456789', 'huy.pham@cokhi.com', '2018-09-05', 4, 4);

-- -----------------------------
-- BẢNG contracts (Hợp đồng lao động)
-- -----------------------------
CREATE TABLE contracts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  employee_id INT,
  contract_no VARCHAR(100),
  contract_type VARCHAR(100),
  start_date DATE,
  end_date DATE,
  salary_base DECIMAL(12,2),
  status ENUM('active','expired','terminated') DEFAULT 'active',
  FOREIGN KEY (employee_id) REFERENCES employees(id)
);

INSERT INTO contracts (employee_id, contract_no, contract_type, start_date, end_date, salary_base) VALUES
(1, 'HD001', '1 năm', '2024-01-01', '2024-12-31', 12000000),
(2, 'HD002', '3 năm', '2023-01-01', '2025-12-31', 10000000),
(3, 'HD003', '1 năm', '2024-06-01', '2025-05-31', 15000000),
(4, 'HD004', 'Không xác định', '2018-09-05', NULL, 9000000);

-- -----------------------------
-- BẢNG hr_actions (Khen thưởng / Kỷ luật)
-- -----------------------------
CREATE TABLE hr_actions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  employee_id INT,
  type ENUM('reward','discipline'),
  title VARCHAR(200),
  description TEXT,
  date DATE,
  amount DECIMAL(12,2),
  FOREIGN KEY (employee_id) REFERENCES employees(id)
);

INSERT INTO hr_actions (employee_id, type, title, description, date, amount) VALUES
(1, 'reward', 'Thưởng dự án A', 'Hoàn thành vượt tiến độ', '2024-12-30', 1000000),
(2, 'discipline', 'Đi làm muộn', 'Trễ 2 giờ', '2024-11-15', 200000);

-- -----------------------------
-- BẢNG shifts (Ca làm việc)
-- -----------------------------
CREATE TABLE shifts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100),
  start_time TIME,
  end_time TIME,
  hours DECIMAL(4,2)
);

INSERT INTO shifts (name, start_time, end_time, hours) VALUES
('Ca sáng', '07:00:00', '15:00:00', 8),
('Ca chiều', '15:00:00', '23:00:00', 8),
('Ca đêm', '23:00:00', '07:00:00', 8);

-- -----------------------------
-- BẢNG attendance (Chấm công)
-- -----------------------------
CREATE TABLE attendance (
  id INT AUTO_INCREMENT PRIMARY KEY,
  employee_id INT,
  date DATE,
  clock_in DATETIME,
  clock_out DATETIME,
  shift_id INT,
  status ENUM('present','absent','leave','ot') DEFAULT 'present',
  FOREIGN KEY (employee_id) REFERENCES employees(id),
  FOREIGN KEY (shift_id) REFERENCES shifts(id)
);

INSERT INTO attendance (employee_id, date, clock_in, clock_out, shift_id, status) VALUES
(1, '2024-10-01', '2024-10-01 07:00:00', '2024-10-01 15:00:00', 1, 'present'),
(1, '2024-10-02', '2024-10-02 07:10:00', '2024-10-02 15:10:00', 1, 'present'),
(2, '2024-10-01', '2024-10-01 15:00:00', '2024-10-01 23:00:00', 2, 'present'),
(3, '2024-10-01', NULL, NULL, 1, 'absent');

-- -----------------------------
-- BẢNG payrolls (Bảng lương)
-- -----------------------------
CREATE TABLE payrolls (
  id INT AUTO_INCREMENT PRIMARY KEY,
  employee_id INT,
  period_from DATE,
  period_to DATE,
  gross_salary DECIMAL(12,2),
  net_salary DECIMAL(12,2),
  status ENUM('draft','finalized','paid') DEFAULT 'draft',
  FOREIGN KEY (employee_id) REFERENCES employees(id)
);

INSERT INTO payrolls (employee_id, period_from, period_to, gross_salary, net_salary, status) VALUES
(1, '2024-09-01', '2024-09-30', 12000000, 11000000, 'paid'),
(2, '2024-09-01', '2024-09-30', 10000000, 9500000, 'paid');

-- -----------------------------
-- BẢNG leaves (Nghỉ phép)
-- -----------------------------
CREATE TABLE leaves (
  id INT AUTO_INCREMENT PRIMARY KEY,
  employee_id INT,
  leave_type ENUM('annual','sick','unpaid'),
  from_date DATE,
  to_date DATE,
  total_days DECIMAL(5,2),
  status ENUM('pending','approved','rejected') DEFAULT 'approved',
  FOREIGN KEY (employee_id) REFERENCES employees(id)
);

INSERT INTO leaves (employee_id, leave_type, from_date, to_date, total_days, status)
VALUES (3, 'annual', '2024-09-20', '2024-09-21', 2, 'approved');


--  bảng reports
CREATE TABLE `reports` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `employee_id` INT NOT NULL,
    `date` DATE NOT NULL,
    `hours_worked` DECIMAL(5,2) DEFAULT 0,
    `tasks_completed` INT DEFAULT 0,
    `overtime_hours` DECIMAL(5,2) DEFAULT 0,
    `notes` TEXT,
    FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Chèn dữ liệu mẫu
INSERT INTO `reports` (`employee_id`, `date`, `hours_worked`, `tasks_completed`, `overtime_hours`, `notes`) VALUES
(1, '2025-10-01', 8.0, 5, 1.5, 'Hoàn thành dự án A'),
(2, '2025-10-01', 7.5, 4, 0.0, 'Hỗ trợ team B'),
(1, '2025-10-02', 8.0, 6, 2.0, 'Hoàn thành báo cáo tuần'),
(3, '2025-10-01', 8.0, 3, 0.5, 'Tham gia đào tạo nội bộ');
