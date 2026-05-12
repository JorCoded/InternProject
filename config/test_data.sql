-- ============================================================
-- HRMS TEST DATA (Updated)
-- Run this AFTER setup.php has created the database & schema
-- Go to phpMyAdmin → select "hrms" DB → SQL tab → paste & run
-- ============================================================

USE hrms;

-- ============================================================
-- USERS (password for ALL = "password")
-- ============================================================
INSERT INTO users (employee_id, first_name, last_name, email, password, role, department, position, phone, local_leaves, sick_leaves, unpaid_leaves, is_active) VALUES
('EMP002', 'Sarah',   'Mitchell',  'hr@hrms.com',        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'hr',        'Human Resources', 'HR Manager',        '+230 5712 3456', 18, 12, 0, 1),
('EMP003', 'James',   'Thornton',  'exec@hrms.com',      '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'executive', 'Management',      'Director',          '+230 5723 4567', 22, 15, 0, 1),
('EMP004', 'Emily',   'Rodriguez', 'emily@hrms.com',     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employee',  'Engineering',     'Software Developer','+230 5734 5678', 20, 13, 0, 1),
('EMP005', 'Michael', 'Chen',      'michael@hrms.com',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employee',  'Engineering',     'Backend Developer', '+230 5745 6789', 22, 15, 0, 1),
('EMP006', 'Priya',   'Sharma',    'priya@hrms.com',     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employee',  'Marketing',       'Marketing Lead',    '+230 5756 7890', 15, 10, 2, 1),
('EMP007', 'David',   'Fontaine',  'david@hrms.com',     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employee',  'Finance',         'Accountant',        '+230 5767 8901', 22, 14, 0, 1),
('EMP008', 'Amina',   'Osman',     'amina@hrms.com',     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employee',  'Design',          'UI/UX Designer',    '+230 5778 9012', 19, 15, 0, 1),
('EMP009', 'Lucas',   'Petrov',    'lucas@hrms.com',     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employee',  'Engineering',     'QA Engineer',       '+230 5789 0123', 21, 11, 0, 1),
('EMP010', 'Nina',    'Dubois',    'nina@hrms.com',      '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employee',  'Sales',           'Sales Executive',   '+230 5790 1234',  0,  0, 5, 1),
-- New employees
('EMP011', 'Kevin',   'Balgobin',  'kevin@hrms.com',     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employee',  'Engineering',     'DevOps Engineer',   '+230 5801 2345', 22, 15, 0, 1),
('EMP012', 'Fatima',  'Noor',      'fatima@hrms.com',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employee',  'HR',              'HR Officer',        '+230 5812 3456', 22, 15, 0, 1);

-- ============================================================
-- ATTENDANCE (last 14 days)
-- clock_in_location and clock_out_location now store resolved
-- geo-location strings: "City, Region, CountryCode (lat, lon)"
-- ============================================================

-- EMP004 Emily Rodriguez — Port Louis office
INSERT INTO attendance (user_id, date, clock_in, clock_out, clock_in_location, clock_out_location, total_hours, status) VALUES
(4, CURDATE() - INTERVAL 13 DAY, CONCAT(CURDATE() - INTERVAL 13 DAY, ' 08:28:00'), CONCAT(CURDATE() - INTERVAL 13 DAY, ' 17:05:00'), 'Port Louis, Port Louis, MU (-20.1609, 57.4977)', 'Port Louis, Port Louis, MU (-20.1609, 57.4977)', 8.62, 'present'),
(4, CURDATE() - INTERVAL 12 DAY, CONCAT(CURDATE() - INTERVAL 12 DAY, ' 08:45:00'), CONCAT(CURDATE() - INTERVAL 12 DAY, ' 17:00:00'), 'Port Louis, Port Louis, MU (-20.1609, 57.4977)', 'Port Louis, Port Louis, MU (-20.1609, 57.4977)', 8.25, 'late'),
(4, CURDATE() - INTERVAL 11 DAY, CONCAT(CURDATE() - INTERVAL 11 DAY, ' 08:30:00'), CONCAT(CURDATE() - INTERVAL 11 DAY, ' 17:10:00'), 'Port Louis, Port Louis, MU (-20.1609, 57.4977)', 'Port Louis, Port Louis, MU (-20.1609, 57.4977)', 8.67, 'present'),
(4, CURDATE() - INTERVAL 10 DAY, CONCAT(CURDATE() - INTERVAL 10 DAY, ' 08:29:00'), CONCAT(CURDATE() - INTERVAL 10 DAY, ' 17:00:00'), 'Port Louis, Port Louis, MU (-20.1609, 57.4977)', 'Port Louis, Port Louis, MU (-20.1609, 57.4977)', 8.52, 'present'),
(4, CURDATE() - INTERVAL 9  DAY, CONCAT(CURDATE() - INTERVAL 9  DAY, ' 09:15:00'), CONCAT(CURDATE() - INTERVAL 9  DAY, ' 17:00:00'), 'Curepipe, Plaines Wilhems, MU (-20.3167, 57.5167)', 'Port Louis, Port Louis, MU (-20.1609, 57.4977)', 7.75, 'late'),
(4, CURDATE() - INTERVAL 8  DAY, CONCAT(CURDATE() - INTERVAL 8  DAY, ' 08:31:00'), CONCAT(CURDATE() - INTERVAL 8  DAY, ' 17:00:00'), 'Port Louis, Port Louis, MU (-20.1609, 57.4977)', 'Port Louis, Port Louis, MU (-20.1609, 57.4977)', 8.48, 'present'),
(4, CURDATE() - INTERVAL 7  DAY, NULL, NULL, NULL, NULL, 0, 'absent'),
(4, CURDATE() - INTERVAL 6  DAY, CONCAT(CURDATE() - INTERVAL 6  DAY, ' 08:30:00'), CONCAT(CURDATE() - INTERVAL 6  DAY, ' 17:00:00'), 'Port Louis, Port Louis, MU (-20.1609, 57.4977)', 'Port Louis, Port Louis, MU (-20.1609, 57.4977)', 8.50, 'present'),
(4, CURDATE() - INTERVAL 5  DAY, CONCAT(CURDATE() - INTERVAL 5  DAY, ' 08:28:00'), CONCAT(CURDATE() - INTERVAL 5  DAY, ' 17:05:00'), 'Port Louis, Port Louis, MU (-20.1609, 57.4977)', 'Port Louis, Port Louis, MU (-20.1609, 57.4977)', 8.62, 'present'),
(4, CURDATE() - INTERVAL 4  DAY, CONCAT(CURDATE() - INTERVAL 4  DAY, ' 08:55:00'), CONCAT(CURDATE() - INTERVAL 4  DAY, ' 17:00:00'), 'Port Louis, Port Louis, MU (-20.1609, 57.4977)', 'Port Louis, Port Louis, MU (-20.1609, 57.4977)', 8.08, 'late'),
(4, CURDATE() - INTERVAL 3  DAY, CONCAT(CURDATE() - INTERVAL 3  DAY, ' 08:30:00'), CONCAT(CURDATE() - INTERVAL 3  DAY, ' 17:00:00'), 'Port Louis, Port Louis, MU (-20.1609, 57.4977)', 'Port Louis, Port Louis, MU (-20.1609, 57.4977)', 8.50, 'present'),
(4, CURDATE() - INTERVAL 2  DAY, CONCAT(CURDATE() - INTERVAL 2  DAY, ' 08:30:00'), CONCAT(CURDATE() - INTERVAL 2  DAY, ' 17:00:00'), 'Port Louis, Port Louis, MU (-20.1609, 57.4977)', 'Port Louis, Port Louis, MU (-20.1609, 57.4977)', 8.50, 'present'),
(4, CURDATE() - INTERVAL 1  DAY, CONCAT(CURDATE() - INTERVAL 1  DAY, ' 08:30:00'), CONCAT(CURDATE() - INTERVAL 1  DAY, ' 17:00:00'), 'Port Louis, Port Louis, MU (-20.1609, 57.4977)', 'Port Louis, Port Louis, MU (-20.1609, 57.4977)', 8.50, 'present');

-- EMP005 Michael Chen — Ebene Cybercity
INSERT INTO attendance (user_id, date, clock_in, clock_out, clock_in_location, clock_out_location, total_hours, status) VALUES
(5, CURDATE() - INTERVAL 13 DAY, CONCAT(CURDATE() - INTERVAL 13 DAY, ' 08:25:00'), CONCAT(CURDATE() - INTERVAL 13 DAY, ' 17:00:00'), 'Ebene, Plaines Wilhems, MU (-20.2380, 57.4822)', 'Ebene, Plaines Wilhems, MU (-20.2380, 57.4822)', 8.58, 'present'),
(5, CURDATE() - INTERVAL 12 DAY, CONCAT(CURDATE() - INTERVAL 12 DAY, ' 08:30:00'), CONCAT(CURDATE() - INTERVAL 12 DAY, ' 17:00:00'), 'Ebene, Plaines Wilhems, MU (-20.2380, 57.4822)', 'Ebene, Plaines Wilhems, MU (-20.2380, 57.4822)', 8.50, 'present'),
(5, CURDATE() - INTERVAL 11 DAY, CONCAT(CURDATE() - INTERVAL 11 DAY, ' 08:30:00'), CONCAT(CURDATE() - INTERVAL 11 DAY, ' 17:00:00'), 'Ebene, Plaines Wilhems, MU (-20.2380, 57.4822)', 'Ebene, Plaines Wilhems, MU (-20.2380, 57.4822)', 8.50, 'present'),
(5, CURDATE() - INTERVAL 10 DAY, NULL, NULL, NULL, NULL, 0, 'absent'),
(5, CURDATE() - INTERVAL 9  DAY, CONCAT(CURDATE() - INTERVAL 9  DAY, ' 08:30:00'), CONCAT(CURDATE() - INTERVAL 9  DAY, ' 17:00:00'), 'Ebene, Plaines Wilhems, MU (-20.2380, 57.4822)', 'Ebene, Plaines Wilhems, MU (-20.2380, 57.4822)', 8.50, 'present'),
(5, CURDATE() - INTERVAL 8  DAY, CONCAT(CURDATE() - INTERVAL 8  DAY, ' 09:00:00'), CONCAT(CURDATE() - INTERVAL 8  DAY, ' 17:30:00'), 'Quatre Bornes, Plaines Wilhems, MU (-20.2648, 57.4791)', 'Ebene, Plaines Wilhems, MU (-20.2380, 57.4822)', 8.50, 'late'),
(5, CURDATE() - INTERVAL 7  DAY, CONCAT(CURDATE() - INTERVAL 7  DAY, ' 08:30:00'), CONCAT(CURDATE() - INTERVAL 7  DAY, ' 17:00:00'), 'Ebene, Plaines Wilhems, MU (-20.2380, 57.4822)', 'Ebene, Plaines Wilhems, MU (-20.2380, 57.4822)', 8.50, 'present'),
(5, CURDATE() - INTERVAL 6  DAY, CONCAT(CURDATE() - INTERVAL 6  DAY, ' 08:30:00'), CONCAT(CURDATE() - INTERVAL 6  DAY, ' 17:00:00'), 'Ebene, Plaines Wilhems, MU (-20.2380, 57.4822)', 'Ebene, Plaines Wilhems, MU (-20.2380, 57.4822)', 8.50, 'present'),
(5, CURDATE() - INTERVAL 5  DAY, CONCAT(CURDATE() - INTERVAL 5  DAY, ' 08:30:00'), CONCAT(CURDATE() - INTERVAL 5  DAY, ' 17:00:00'), 'Ebene, Plaines Wilhems, MU (-20.2380, 57.4822)', 'Ebene, Plaines Wilhems, MU (-20.2380, 57.4822)', 8.50, 'present'),
(5, CURDATE() - INTERVAL 4  DAY, CONCAT(CURDATE() - INTERVAL 4  DAY, ' 08:30:00'), CONCAT(CURDATE() - INTERVAL 4  DAY, ' 17:00:00'), 'Ebene, Plaines Wilhems, MU (-20.2380, 57.4822)', 'Ebene, Plaines Wilhems, MU (-20.2380, 57.4822)', 8.50, 'present'),
(5, CURDATE() - INTERVAL 3  DAY, CONCAT(CURDATE() - INTERVAL 3  DAY, ' 08:30:00'), CONCAT(CURDATE() - INTERVAL 3  DAY, ' 17:00:00'), 'Ebene, Plaines Wilhems, MU (-20.2380, 57.4822)', 'Ebene, Plaines Wilhems, MU (-20.2380, 57.4822)', 8.50, 'present'),
(5, CURDATE() - INTERVAL 2  DAY, CONCAT(CURDATE() - INTERVAL 2  DAY, ' 08:30:00'), CONCAT(CURDATE() - INTERVAL 2  DAY, ' 17:00:00'), 'Ebene, Plaines Wilhems, MU (-20.2380, 57.4822)', 'Ebene, Plaines Wilhems, MU (-20.2380, 57.4822)', 8.50, 'present'),
(5, CURDATE() - INTERVAL 1  DAY, CONCAT(CURDATE() - INTERVAL 1  DAY, ' 08:30:00'), CONCAT(CURDATE() - INTERVAL 1  DAY, ' 17:00:00'), 'Ebene, Plaines Wilhems, MU (-20.2380, 57.4822)', 'Ebene, Plaines Wilhems, MU (-20.2380, 57.4822)', 8.50, 'present');

-- EMP006 Priya Sharma — Rose Hill
INSERT INTO attendance (user_id, date, clock_in, clock_out, clock_in_location, clock_out_location, total_hours, status) VALUES
(6, CURDATE() - INTERVAL 13 DAY, CONCAT(CURDATE() - INTERVAL 13 DAY, ' 08:30:00'), CONCAT(CURDATE() - INTERVAL 13 DAY, ' 17:00:00'), 'Rose Hill, Plaines Wilhems, MU (-20.2333, 57.4667)', 'Rose Hill, Plaines Wilhems, MU (-20.2333, 57.4667)', 8.50, 'present'),
(6, CURDATE() - INTERVAL 12 DAY, NULL, NULL, NULL, NULL, 0, 'on_leave'),
(6, CURDATE() - INTERVAL 11 DAY, NULL, NULL, NULL, NULL, 0, 'on_leave'),
(6, CURDATE() - INTERVAL 10 DAY, CONCAT(CURDATE() - INTERVAL 10 DAY, ' 08:30:00'), CONCAT(CURDATE() - INTERVAL 10 DAY, ' 17:00:00'), 'Rose Hill, Plaines Wilhems, MU (-20.2333, 57.4667)', 'Rose Hill, Plaines Wilhems, MU (-20.2333, 57.4667)', 8.50, 'present'),
(6, CURDATE() - INTERVAL 9  DAY, CONCAT(CURDATE() - INTERVAL 9  DAY, ' 08:30:00'), CONCAT(CURDATE() - INTERVAL 9  DAY, ' 17:00:00'), 'Rose Hill, Plaines Wilhems, MU (-20.2333, 57.4667)', 'Rose Hill, Plaines Wilhems, MU (-20.2333, 57.4667)', 8.50, 'present'),
(6, CURDATE() - INTERVAL 8  DAY, CONCAT(CURDATE() - INTERVAL 8  DAY, ' 10:00:00'), CONCAT(CURDATE() - INTERVAL 8  DAY, ' 17:00:00'), 'Beau Bassin, Plaines Wilhems, MU (-20.2167, 57.4667)', 'Rose Hill, Plaines Wilhems, MU (-20.2333, 57.4667)', 7.00, 'late'),
(6, CURDATE() - INTERVAL 7  DAY, CONCAT(CURDATE() - INTERVAL 7  DAY, ' 08:30:00'), CONCAT(CURDATE() - INTERVAL 7  DAY, ' 17:00:00'), 'Rose Hill, Plaines Wilhems, MU (-20.2333, 57.4667)', 'Rose Hill, Plaines Wilhems, MU (-20.2333, 57.4667)', 8.50, 'present'),
(6, CURDATE() - INTERVAL 6  DAY, CONCAT(CURDATE() - INTERVAL 6  DAY, ' 08:30:00'), CONCAT(CURDATE() - INTERVAL 6  DAY, ' 17:00:00'), 'Rose Hill, Plaines Wilhems, MU (-20.2333, 57.4667)', 'Rose Hill, Plaines Wilhems, MU (-20.2333, 57.4667)', 8.50, 'present'),
(6, CURDATE() - INTERVAL 5  DAY, CONCAT(CURDATE() - INTERVAL 5  DAY, ' 08:30:00'), CONCAT(CURDATE() - INTERVAL 5  DAY, ' 17:00:00'), 'Rose Hill, Plaines Wilhems, MU (-20.2333, 57.4667)', 'Rose Hill, Plaines Wilhems, MU (-20.2333, 57.4667)', 8.50, 'present'),
(6, CURDATE() - INTERVAL 4  DAY, CONCAT(CURDATE() - INTERVAL 4  DAY, ' 08:30:00'), CONCAT(CURDATE() - INTERVAL 4  DAY, ' 17:00:00'), 'Rose Hill, Plaines Wilhems, MU (-20.2333, 57.4667)', 'Rose Hill, Plaines Wilhems, MU (-20.2333, 57.4667)', 8.50, 'present'),
(6, CURDATE() - INTERVAL 3  DAY, CONCAT(CURDATE() - INTERVAL 3  DAY, ' 08:30:00'), CONCAT(CURDATE() - INTERVAL 3  DAY, ' 17:00:00'), 'Rose Hill, Plaines Wilhems, MU (-20.2333, 57.4667)', 'Rose Hill, Plaines Wilhems, MU (-20.2333, 57.4667)', 8.50, 'present'),
(6, CURDATE() - INTERVAL 2  DAY, CONCAT(CURDATE() - INTERVAL 2  DAY, ' 08:30:00'), CONCAT(CURDATE() - INTERVAL 2  DAY, ' 17:00:00'), 'Rose Hill, Plaines Wilhems, MU (-20.2333, 57.4667)', 'Rose Hill, Plaines Wilhems, MU (-20.2333, 57.4667)', 8.50, 'present'),
(6, CURDATE() - INTERVAL 1  DAY, CONCAT(CURDATE() - INTERVAL 1  DAY, ' 08:30:00'), CONCAT(CURDATE() - INTERVAL 1  DAY, ' 17:00:00'), 'Rose Hill, Plaines Wilhems, MU (-20.2333, 57.4667)', 'Rose Hill, Plaines Wilhems, MU (-20.2333, 57.4667)', 8.50, 'present');

-- EMP007 David Fontaine — Floreal
INSERT INTO attendance (user_id, date, clock_in, clock_out, clock_in_location, clock_out_location, total_hours, status) VALUES
(7, CURDATE() - INTERVAL 13 DAY, CONCAT(CURDATE() - INTERVAL 13 DAY, ' 08:20:00'), CONCAT(CURDATE() - INTERVAL 13 DAY, ' 17:05:00'), 'Floreal, Plaines Wilhems, MU (-20.2833, 57.5167)', 'Floreal, Plaines Wilhems, MU (-20.2833, 57.5167)', 8.75, 'present'),
(7, CURDATE() - INTERVAL 12 DAY, CONCAT(CURDATE() - INTERVAL 12 DAY, ' 08:30:00'), CONCAT(CURDATE() - INTERVAL 12 DAY, ' 17:00:00'), 'Floreal, Plaines Wilhems, MU (-20.2833, 57.5167)', 'Floreal, Plaines Wilhems, MU (-20.2833, 57.5167)', 8.50, 'present'),
(7, CURDATE() - INTERVAL 11 DAY, CONCAT(CURDATE() - INTERVAL 11 DAY, ' 08:30:00'), CONCAT(CURDATE() - INTERVAL 11 DAY, ' 17:00:00'), 'Floreal, Plaines Wilhems, MU (-20.2833, 57.5167)', 'Floreal, Plaines Wilhems, MU (-20.2833, 57.5167)', 8.50, 'present'),
(7, CURDATE() - INTERVAL 10 DAY, CONCAT(CURDATE() - INTERVAL 10 DAY, ' 08:30:00'), CONCAT(CURDATE() - INTERVAL 10 DAY, ' 17:00:00'), 'Floreal, Plaines Wilhems, MU (-20.2833, 57.5167)', 'Floreal, Plaines Wilhems, MU (-20.2833, 57.5167)', 8.50, 'present'),
(7, CURDATE() - INTERVAL 9  DAY, CONCAT(CURDATE() - INTERVAL 9  DAY, ' 08:30:00'), CONCAT(CURDATE() - INTERVAL 9  DAY, ' 17:00:00'), 'Floreal, Plaines Wilhems, MU (-20.2833, 57.5167)', 'Floreal, Plaines Wilhems, MU (-20.2833, 57.5167)', 8.50, 'present'),
(7, CURDATE() - INTERVAL 8  DAY, CONCAT(CURDATE() - INTERVAL 8  DAY, ' 08:45:00'), CONCAT(CURDATE() - INTERVAL 8  DAY, ' 17:00:00'), 'Curepipe, Plaines Wilhems, MU (-20.3167, 57.5167)', 'Floreal, Plaines Wilhems, MU (-20.2833, 57.5167)', 8.25, 'late'),
(7, CURDATE() - INTERVAL 7  DAY, CONCAT(CURDATE() - INTERVAL 7  DAY, ' 08:30:00'), CONCAT(CURDATE() - INTERVAL 7  DAY, ' 17:00:00'), 'Floreal, Plaines Wilhems, MU (-20.2833, 57.5167)', 'Floreal, Plaines Wilhems, MU (-20.2833, 57.5167)', 8.50, 'present'),
(7, CURDATE() - INTERVAL 6  DAY, NULL, NULL, NULL, NULL, 0, 'absent'),
(7, CURDATE() - INTERVAL 5  DAY, CONCAT(CURDATE() - INTERVAL 5  DAY, ' 08:30:00'), CONCAT(CURDATE() - INTERVAL 5  DAY, ' 17:00:00'), 'Floreal, Plaines Wilhems, MU (-20.2833, 57.5167)', 'Floreal, Plaines Wilhems, MU (-20.2833, 57.5167)', 8.50, 'present'),
(7, CURDATE() - INTERVAL 4  DAY, CONCAT(CURDATE() - INTERVAL 4  DAY, ' 08:30:00'), CONCAT(CURDATE() - INTERVAL 4  DAY, ' 17:00:00'), 'Floreal, Plaines Wilhems, MU (-20.2833, 57.5167)', 'Floreal, Plaines Wilhems, MU (-20.2833, 57.5167)', 8.50, 'present'),
(7, CURDATE() - INTERVAL 3  DAY, CONCAT(CURDATE() - INTERVAL 3  DAY, ' 08:30:00'), CONCAT(CURDATE() - INTERVAL 3  DAY, ' 17:00:00'), 'Floreal, Plaines Wilhems, MU (-20.2833, 57.5167)', 'Floreal, Plaines Wilhems, MU (-20.2833, 57.5167)', 8.50, 'present'),
(7, CURDATE() - INTERVAL 2  DAY, CONCAT(CURDATE() - INTERVAL 2  DAY, ' 08:30:00'), CONCAT(CURDATE() - INTERVAL 2  DAY, ' 17:00:00'), 'Floreal, Plaines Wilhems, MU (-20.2833, 57.5167)', 'Floreal, Plaines Wilhems, MU (-20.2833, 57.5167)', 8.50, 'present'),
(7, CURDATE() - INTERVAL 1  DAY, CONCAT(CURDATE() - INTERVAL 1  DAY, ' 08:30:00'), CONCAT(CURDATE() - INTERVAL 1  DAY, ' 17:00:00'), 'Floreal, Plaines Wilhems, MU (-20.2833, 57.5167)', 'Floreal, Plaines Wilhems, MU (-20.2833, 57.5167)', 8.50, 'present');

-- EMP008 Amina Osman — Vacoas (includes break records)
INSERT INTO attendance (user_id, date, clock_in, clock_out, break_start, break_end, clock_in_location, clock_out_location, total_hours, status) VALUES
(8, CURDATE() - INTERVAL 13 DAY, CONCAT(CURDATE() - INTERVAL 13 DAY, ' 08:30:00'), CONCAT(CURDATE() - INTERVAL 13 DAY, ' 17:00:00'), CONCAT(CURDATE() - INTERVAL 13 DAY, ' 12:00:00'), CONCAT(CURDATE() - INTERVAL 13 DAY, ' 13:00:00'), 'Vacoas, Plaines Wilhems, MU (-20.2985, 57.4781)', 'Vacoas, Plaines Wilhems, MU (-20.2985, 57.4781)', 7.50, 'present'),
(8, CURDATE() - INTERVAL 12 DAY, CONCAT(CURDATE() - INTERVAL 12 DAY, ' 08:30:00'), CONCAT(CURDATE() - INTERVAL 12 DAY, ' 17:00:00'), CONCAT(CURDATE() - INTERVAL 12 DAY, ' 12:00:00'), CONCAT(CURDATE() - INTERVAL 12 DAY, ' 13:00:00'), 'Vacoas, Plaines Wilhems, MU (-20.2985, 57.4781)', 'Vacoas, Plaines Wilhems, MU (-20.2985, 57.4781)', 7.50, 'present'),
(8, CURDATE() - INTERVAL 11 DAY, CONCAT(CURDATE() - INTERVAL 11 DAY, ' 08:30:00'), CONCAT(CURDATE() - INTERVAL 11 DAY, ' 17:00:00'), NULL, NULL, 'Vacoas, Plaines Wilhems, MU (-20.2985, 57.4781)', 'Vacoas, Plaines Wilhems, MU (-20.2985, 57.4781)', 8.50, 'present'),
(8, CURDATE() - INTERVAL 10 DAY, CONCAT(CURDATE() - INTERVAL 10 DAY, ' 09:05:00'), CONCAT(CURDATE() - INTERVAL 10 DAY, ' 17:00:00'), NULL, NULL, 'Phoenix, Plaines Wilhems, MU (-20.2833, 57.4833)', 'Vacoas, Plaines Wilhems, MU (-20.2985, 57.4781)', 7.92, 'late'),
(8, CURDATE() - INTERVAL 9  DAY, CONCAT(CURDATE() - INTERVAL 9  DAY, ' 08:30:00'), CONCAT(CURDATE() - INTERVAL 9  DAY, ' 17:00:00'), CONCAT(CURDATE() - INTERVAL 9  DAY, ' 12:30:00'), CONCAT(CURDATE() - INTERVAL 9  DAY, ' 13:30:00'), 'Vacoas, Plaines Wilhems, MU (-20.2985, 57.4781)', 'Vacoas, Plaines Wilhems, MU (-20.2985, 57.4781)', 7.50, 'present'),
(8, CURDATE() - INTERVAL 8  DAY, CONCAT(CURDATE() - INTERVAL 8  DAY, ' 08:30:00'), CONCAT(CURDATE() - INTERVAL 8  DAY, ' 17:00:00'), NULL, NULL, 'Vacoas, Plaines Wilhems, MU (-20.2985, 57.4781)', 'Vacoas, Plaines Wilhems, MU (-20.2985, 57.4781)', 8.50, 'present'),
(8, CURDATE() - INTERVAL 7  DAY, CONCAT(CURDATE() - INTERVAL 7  DAY, ' 08:30:00'), CONCAT(CURDATE() - INTERVAL 7  DAY, ' 17:00:00'), NULL, NULL, 'Vacoas, Plaines Wilhems, MU (-20.2985, 57.4781)', 'Vacoas, Plaines Wilhems, MU (-20.2985, 57.4781)', 8.50, 'present'),
(8, CURDATE() - INTERVAL 6  DAY, CONCAT(CURDATE() - INTERVAL 6  DAY, ' 08:30:00'), CONCAT(CURDATE() - INTERVAL 6  DAY, ' 17:00:00'), NULL, NULL, 'Vacoas, Plaines Wilhems, MU (-20.2985, 57.4781)', 'Vacoas, Plaines Wilhems, MU (-20.2985, 57.4781)', 8.50, 'present'),
(8, CURDATE() - INTERVAL 5  DAY, CONCAT(CURDATE() - INTERVAL 5  DAY, ' 08:30:00'), CONCAT(CURDATE() - INTERVAL 5  DAY, ' 17:00:00'), NULL, NULL, 'Vacoas, Plaines Wilhems, MU (-20.2985, 57.4781)', 'Vacoas, Plaines Wilhems, MU (-20.2985, 57.4781)', 8.50, 'present'),
(8, CURDATE() - INTERVAL 4  DAY, NULL, NULL, NULL, NULL, NULL, NULL, 0, 'absent'),
(8, CURDATE() - INTERVAL 3  DAY, CONCAT(CURDATE() - INTERVAL 3  DAY, ' 08:30:00'), CONCAT(CURDATE() - INTERVAL 3  DAY, ' 17:00:00'), NULL, NULL, 'Vacoas, Plaines Wilhems, MU (-20.2985, 57.4781)', 'Vacoas, Plaines Wilhems, MU (-20.2985, 57.4781)', 8.50, 'present'),
(8, CURDATE() - INTERVAL 2  DAY, CONCAT(CURDATE() - INTERVAL 2  DAY, ' 08:30:00'), CONCAT(CURDATE() - INTERVAL 2  DAY, ' 17:00:00'), NULL, NULL, 'Vacoas, Plaines Wilhems, MU (-20.2985, 57.4781)', 'Vacoas, Plaines Wilhems, MU (-20.2985, 57.4781)', 8.50, 'present'),
(8, CURDATE() - INTERVAL 1  DAY, CONCAT(CURDATE() - INTERVAL 1  DAY, ' 08:30:00'), CONCAT(CURDATE() - INTERVAL 1  DAY, ' 17:00:00'), NULL, NULL, 'Vacoas, Plaines Wilhems, MU (-20.2985, 57.4781)', 'Vacoas, Plaines Wilhems, MU (-20.2985, 57.4781)', 8.50, 'present');

-- EMP009 Lucas Petrov — Moka
INSERT INTO attendance (user_id, date, clock_in, clock_out, clock_in_location, clock_out_location, total_hours, status) VALUES
(9, CURDATE() - INTERVAL 13 DAY, CONCAT(CURDATE() - INTERVAL 13 DAY, ' 08:30:00'), CONCAT(CURDATE() - INTERVAL 13 DAY, ' 17:00:00'), 'Moka, Moka, MU (-20.2333, 57.5000)', 'Moka, Moka, MU (-20.2333, 57.5000)', 8.50, 'present'),
(9, CURDATE() - INTERVAL 12 DAY, CONCAT(CURDATE() - INTERVAL 12 DAY, ' 08:30:00'), CONCAT(CURDATE() - INTERVAL 12 DAY, ' 17:00:00'), 'Moka, Moka, MU (-20.2333, 57.5000)', 'Moka, Moka, MU (-20.2333, 57.5000)', 8.50, 'present'),
(9, CURDATE() - INTERVAL 11 DAY, CONCAT(CURDATE() - INTERVAL 11 DAY, ' 08:50:00'), CONCAT(CURDATE() - INTERVAL 11 DAY, ' 17:00:00'), 'Moka, Moka, MU (-20.2333, 57.5000)', 'Moka, Moka, MU (-20.2333, 57.5000)', 8.17, 'late'),
(9, CURDATE() - INTERVAL 10 DAY, CONCAT(CURDATE() - INTERVAL 10 DAY, ' 08:30:00'), CONCAT(CURDATE() - INTERVAL 10 DAY, ' 17:00:00'), 'Moka, Moka, MU (-20.2333, 57.5000)', 'Moka, Moka, MU (-20.2333, 57.5000)', 8.50, 'present'),
(9, CURDATE() - INTERVAL 9  DAY, CONCAT(CURDATE() - INTERVAL 9  DAY, ' 08:30:00'), CONCAT(CURDATE() - INTERVAL 9  DAY, ' 17:00:00'), 'Moka, Moka, MU (-20.2333, 57.5000)', 'Moka, Moka, MU (-20.2333, 57.5000)', 8.50, 'present'),
(9, CURDATE() - INTERVAL 8  DAY, CONCAT(CURDATE() - INTERVAL 8  DAY, ' 08:30:00'), CONCAT(CURDATE() - INTERVAL 8  DAY, ' 17:00:00'), 'Moka, Moka, MU (-20.2333, 57.5000)', 'Moka, Moka, MU (-20.2333, 57.5000)', 8.50, 'present'),
(9, CURDATE() - INTERVAL 7  DAY, CONCAT(CURDATE() - INTERVAL 7  DAY, ' 08:30:00'), CONCAT(CURDATE() - INTERVAL 7  DAY, ' 17:00:00'), 'Moka, Moka, MU (-20.2333, 57.5000)', 'Moka, Moka, MU (-20.2333, 57.5000)', 8.50, 'present'),
(9, CURDATE() - INTERVAL 6  DAY, CONCAT(CURDATE() - INTERVAL 6  DAY, ' 08:30:00'), CONCAT(CURDATE() - INTERVAL 6  DAY, ' 17:00:00'), 'Moka, Moka, MU (-20.2333, 57.5000)', 'Moka, Moka, MU (-20.2333, 57.5000)', 8.50, 'present'),
(9, CURDATE() - INTERVAL 5  DAY, NULL, NULL, NULL, NULL, 0, 'absent'),
(9, CURDATE() - INTERVAL 4  DAY, CONCAT(CURDATE() - INTERVAL 4  DAY, ' 08:30:00'), CONCAT(CURDATE() - INTERVAL 4  DAY, ' 17:00:00'), 'Moka, Moka, MU (-20.2333, 57.5000)', 'Moka, Moka, MU (-20.2333, 57.5000)', 8.50, 'present'),
(9, CURDATE() - INTERVAL 3  DAY, CONCAT(CURDATE() - INTERVAL 3  DAY, ' 08:30:00'), CONCAT(CURDATE() - INTERVAL 3  DAY, ' 17:00:00'), 'Moka, Moka, MU (-20.2333, 57.5000)', 'Moka, Moka, MU (-20.2333, 57.5000)', 8.50, 'present'),
(9, CURDATE() - INTERVAL 2  DAY, CONCAT(CURDATE() - INTERVAL 2  DAY, ' 08:30:00'), CONCAT(CURDATE() - INTERVAL 2  DAY, ' 17:00:00'), 'Moka, Moka, MU (-20.2333, 57.5000)', 'Moka, Moka, MU (-20.2333, 57.5000)', 8.50, 'present'),
(9, CURDATE() - INTERVAL 1  DAY, CONCAT(CURDATE() - INTERVAL 1  DAY, ' 09:30:00'), CONCAT(CURDATE() - INTERVAL 1  DAY, ' 17:00:00'), 'Moka, Moka, MU (-20.2333, 57.5000)', 'Moka, Moka, MU (-20.2333, 57.5000)', 7.50, 'late');

-- EMP010 Nina Dubois — Grand Baie (no leaves left)
INSERT INTO attendance (user_id, date, clock_in, clock_out, clock_in_location, clock_out_location, total_hours, status) VALUES
(10, CURDATE() - INTERVAL 13 DAY, CONCAT(CURDATE() - INTERVAL 13 DAY, ' 08:30:00'), CONCAT(CURDATE() - INTERVAL 13 DAY, ' 17:00:00'), 'Grand Baie, Riviere du Rempart, MU (-20.0128, 57.5833)', 'Grand Baie, Riviere du Rempart, MU (-20.0128, 57.5833)', 8.50, 'present'),
(10, CURDATE() - INTERVAL 12 DAY, NULL, NULL, NULL, NULL, 0, 'on_leave'),
(10, CURDATE() - INTERVAL 11 DAY, NULL, NULL, NULL, NULL, 0, 'on_leave'),
(10, CURDATE() - INTERVAL 10 DAY, NULL, NULL, NULL, NULL, 0, 'on_leave'),
(10, CURDATE() - INTERVAL 9  DAY, CONCAT(CURDATE() - INTERVAL 9  DAY, ' 08:30:00'), CONCAT(CURDATE() - INTERVAL 9  DAY, ' 17:00:00'), 'Grand Baie, Riviere du Rempart, MU (-20.0128, 57.5833)', 'Grand Baie, Riviere du Rempart, MU (-20.0128, 57.5833)', 8.50, 'present'),
(10, CURDATE() - INTERVAL 8  DAY, CONCAT(CURDATE() - INTERVAL 8  DAY, ' 08:30:00'), CONCAT(CURDATE() - INTERVAL 8  DAY, ' 17:00:00'), 'Grand Baie, Riviere du Rempart, MU (-20.0128, 57.5833)', 'Grand Baie, Riviere du Rempart, MU (-20.0128, 57.5833)', 8.50, 'present'),
(10, CURDATE() - INTERVAL 7  DAY, CONCAT(CURDATE() - INTERVAL 7  DAY, ' 08:30:00'), CONCAT(CURDATE() - INTERVAL 7  DAY, ' 17:00:00'), 'Grand Baie, Riviere du Rempart, MU (-20.0128, 57.5833)', 'Grand Baie, Riviere du Rempart, MU (-20.0128, 57.5833)', 8.50, 'present'),
(10, CURDATE() - INTERVAL 6  DAY, CONCAT(CURDATE() - INTERVAL 6  DAY, ' 08:30:00'), CONCAT(CURDATE() - INTERVAL 6  DAY, ' 17:00:00'), 'Grand Baie, Riviere du Rempart, MU (-20.0128, 57.5833)', 'Grand Baie, Riviere du Rempart, MU (-20.0128, 57.5833)', 8.50, 'present'),
(10, CURDATE() - INTERVAL 5  DAY, CONCAT(CURDATE() - INTERVAL 5  DAY, ' 08:30:00'), CONCAT(CURDATE() - INTERVAL 5  DAY, ' 17:00:00'), 'Grand Baie, Riviere du Rempart, MU (-20.0128, 57.5833)', 'Grand Baie, Riviere du Rempart, MU (-20.0128, 57.5833)', 8.50, 'present'),
(10, CURDATE() - INTERVAL 4  DAY, CONCAT(CURDATE() - INTERVAL 4  DAY, ' 10:30:00'), CONCAT(CURDATE() - INTERVAL 4  DAY, ' 17:00:00'), 'Pamplemousses, Pamplemousses, MU (-20.1000, 57.5833)', 'Grand Baie, Riviere du Rempart, MU (-20.0128, 57.5833)', 6.50, 'late'),
(10, CURDATE() - INTERVAL 3  DAY, CONCAT(CURDATE() - INTERVAL 3  DAY, ' 08:30:00'), CONCAT(CURDATE() - INTERVAL 3  DAY, ' 17:00:00'), 'Grand Baie, Riviere du Rempart, MU (-20.0128, 57.5833)', 'Grand Baie, Riviere du Rempart, MU (-20.0128, 57.5833)', 8.50, 'present'),
(10, CURDATE() - INTERVAL 2  DAY, CONCAT(CURDATE() - INTERVAL 2  DAY, ' 08:30:00'), CONCAT(CURDATE() - INTERVAL 2  DAY, ' 17:00:00'), 'Grand Baie, Riviere du Rempart, MU (-20.0128, 57.5833)', 'Grand Baie, Riviere du Rempart, MU (-20.0128, 57.5833)', 8.50, 'present'),
(10, CURDATE() - INTERVAL 1  DAY, CONCAT(CURDATE() - INTERVAL 1  DAY, ' 08:30:00'), CONCAT(CURDATE() - INTERVAL 1  DAY, ' 17:00:00'), 'Grand Baie, Riviere du Rempart, MU (-20.0128, 57.5833)', 'Grand Baie, Riviere du Rempart, MU (-20.0128, 57.5833)', 8.50, 'present');

-- HR (user 2) and Executive (user 3) attendance
INSERT INTO attendance (user_id, date, clock_in, clock_out, clock_in_location, clock_out_location, total_hours, status) VALUES
(2, CURDATE() - INTERVAL 3 DAY, CONCAT(CURDATE() - INTERVAL 3 DAY, ' 08:28:00'), CONCAT(CURDATE() - INTERVAL 3 DAY, ' 17:05:00'), 'Port Louis, Port Louis, MU (-20.1609, 57.4977)', 'Port Louis, Port Louis, MU (-20.1609, 57.4977)', 8.62, 'present'),
(2, CURDATE() - INTERVAL 2 DAY, CONCAT(CURDATE() - INTERVAL 2 DAY, ' 08:30:00'), CONCAT(CURDATE() - INTERVAL 2 DAY, ' 17:00:00'), 'Port Louis, Port Louis, MU (-20.1609, 57.4977)', 'Port Louis, Port Louis, MU (-20.1609, 57.4977)', 8.50, 'present'),
(2, CURDATE() - INTERVAL 1 DAY, CONCAT(CURDATE() - INTERVAL 1 DAY, ' 09:10:00'), CONCAT(CURDATE() - INTERVAL 1 DAY, ' 17:00:00'), 'Port Louis, Port Louis, MU (-20.1609, 57.4977)', 'Port Louis, Port Louis, MU (-20.1609, 57.4977)', 7.83, 'late'),
(3, CURDATE() - INTERVAL 3 DAY, CONCAT(CURDATE() - INTERVAL 3 DAY, ' 08:30:00'), CONCAT(CURDATE() - INTERVAL 3 DAY, ' 17:00:00'), 'Ebene, Plaines Wilhems, MU (-20.2380, 57.4822)', 'Ebene, Plaines Wilhems, MU (-20.2380, 57.4822)', 8.50, 'present'),
(3, CURDATE() - INTERVAL 2 DAY, CONCAT(CURDATE() - INTERVAL 2 DAY, ' 08:30:00'), CONCAT(CURDATE() - INTERVAL 2 DAY, ' 17:00:00'), 'Ebene, Plaines Wilhems, MU (-20.2380, 57.4822)', 'Ebene, Plaines Wilhems, MU (-20.2380, 57.4822)', 8.50, 'present'),
(3, CURDATE() - INTERVAL 1 DAY, CONCAT(CURDATE() - INTERVAL 1 DAY, ' 08:30:00'), CONCAT(CURDATE() - INTERVAL 1 DAY, ' 17:00:00'), 'Ebene, Plaines Wilhems, MU (-20.2380, 57.4822)', 'Ebene, Plaines Wilhems, MU (-20.2380, 57.4822)', 8.50, 'present');

-- New employees EMP011 Kevin, EMP012 Fatima
INSERT INTO attendance (user_id, date, clock_in, clock_out, clock_in_location, clock_out_location, total_hours, status) VALUES
(11, CURDATE() - INTERVAL 5 DAY, CONCAT(CURDATE() - INTERVAL 5 DAY, ' 08:30:00'), CONCAT(CURDATE() - INTERVAL 5 DAY, ' 17:00:00'), 'Ebene, Plaines Wilhems, MU (-20.2380, 57.4822)', 'Ebene, Plaines Wilhems, MU (-20.2380, 57.4822)', 8.50, 'present'),
(11, CURDATE() - INTERVAL 4 DAY, CONCAT(CURDATE() - INTERVAL 4 DAY, ' 08:30:00'), CONCAT(CURDATE() - INTERVAL 4 DAY, ' 17:00:00'), 'Ebene, Plaines Wilhems, MU (-20.2380, 57.4822)', 'Ebene, Plaines Wilhems, MU (-20.2380, 57.4822)', 8.50, 'present'),
(11, CURDATE() - INTERVAL 3 DAY, CONCAT(CURDATE() - INTERVAL 3 DAY, ' 08:30:00'), CONCAT(CURDATE() - INTERVAL 3 DAY, ' 17:00:00'), 'Ebene, Plaines Wilhems, MU (-20.2380, 57.4822)', 'Ebene, Plaines Wilhems, MU (-20.2380, 57.4822)', 8.50, 'present'),
(11, CURDATE() - INTERVAL 2 DAY, CONCAT(CURDATE() - INTERVAL 2 DAY, ' 08:45:00'), CONCAT(CURDATE() - INTERVAL 2 DAY, ' 17:00:00'), 'Rose Hill, Plaines Wilhems, MU (-20.2333, 57.4667)', 'Ebene, Plaines Wilhems, MU (-20.2380, 57.4822)', 8.25, 'late'),
(11, CURDATE() - INTERVAL 1 DAY, CONCAT(CURDATE() - INTERVAL 1 DAY, ' 08:30:00'), CONCAT(CURDATE() - INTERVAL 1 DAY, ' 17:00:00'), 'Ebene, Plaines Wilhems, MU (-20.2380, 57.4822)', 'Ebene, Plaines Wilhems, MU (-20.2380, 57.4822)', 8.50, 'present'),
(12, CURDATE() - INTERVAL 5 DAY, CONCAT(CURDATE() - INTERVAL 5 DAY, ' 08:30:00'), CONCAT(CURDATE() - INTERVAL 5 DAY, ' 17:00:00'), 'Port Louis, Port Louis, MU (-20.1609, 57.4977)', 'Port Louis, Port Louis, MU (-20.1609, 57.4977)', 8.50, 'present'),
(12, CURDATE() - INTERVAL 4 DAY, CONCAT(CURDATE() - INTERVAL 4 DAY, ' 08:30:00'), CONCAT(CURDATE() - INTERVAL 4 DAY, ' 17:00:00'), 'Port Louis, Port Louis, MU (-20.1609, 57.4977)', 'Port Louis, Port Louis, MU (-20.1609, 57.4977)', 8.50, 'present'),
(12, CURDATE() - INTERVAL 3 DAY, NULL, NULL, NULL, NULL, 0, 'absent'),
(12, CURDATE() - INTERVAL 2 DAY, CONCAT(CURDATE() - INTERVAL 2 DAY, ' 08:30:00'), CONCAT(CURDATE() - INTERVAL 2 DAY, ' 17:00:00'), 'Port Louis, Port Louis, MU (-20.1609, 57.4977)', 'Port Louis, Port Louis, MU (-20.1609, 57.4977)', 8.50, 'present'),
(12, CURDATE() - INTERVAL 1 DAY, CONCAT(CURDATE() - INTERVAL 1 DAY, ' 08:30:00'), CONCAT(CURDATE() - INTERVAL 1 DAY, ' 17:00:00'), 'Port Louis, Port Louis, MU (-20.1609, 57.4977)', 'Port Louis, Port Louis, MU (-20.1609, 57.4977)', 8.50, 'present');

-- ============================================================
-- ATTENDANCE MODIFICATION LOG (PHR compliance demo)
-- ============================================================
UPDATE attendance SET
    clock_in = CONCAT(DATE(clock_in), ' 08:30:00'),
    clock_out = CONCAT(DATE(clock_out), ' 17:00:00'),
    modified_by = 1,
    modification_reason = 'Employee forgot to clock in on time due to system issue. Corrected after review of CCTV.',
    original_clock_in = CONCAT(DATE(clock_in), ' 09:45:00'),
    original_clock_out = CONCAT(DATE(clock_out), ' 17:00:00'),
    total_hours = 8.50
WHERE user_id = 4 AND date = CURDATE() - INTERVAL 9 DAY;

INSERT INTO attendance_modification_log (attendance_id, modified_by, original_clock_in, original_clock_out, new_clock_in, new_clock_out, reason)
SELECT id, 1,
    CONCAT(date, ' 09:15:00'),
    CONCAT(date, ' 17:00:00'),
    CONCAT(date, ' 08:30:00'),
    CONCAT(date, ' 17:00:00'),
    'Employee forgot to clock in on time due to system issue. Corrected after review of CCTV.'
FROM attendance WHERE user_id = 4 AND date = CURDATE() - INTERVAL 9 DAY;

-- ============================================================
-- LEAVE REQUESTS
-- ============================================================
INSERT INTO leave_requests (user_id, leave_type, start_date, end_date, total_days, reason, status, reviewed_by, reviewed_at) VALUES
-- Approved
(4, 'local',  CURDATE() - INTERVAL 30 DAY, CURDATE() - INTERVAL 28 DAY, 3, 'Family event - cousin wedding.', 'approved', 1, NOW() - INTERVAL 32 DAY),
(5, 'sick',   CURDATE() - INTERVAL 20 DAY, CURDATE() - INTERVAL 19 DAY, 2, 'Fever and flu, doctor confirmed.', 'approved', 2, NOW() - INTERVAL 21 DAY),
(6, 'local',  CURDATE() - INTERVAL 15 DAY, CURDATE() - INTERVAL 14 DAY, 2, 'Personal travel arrangement.', 'approved', 1, NOW() - INTERVAL 16 DAY),
(6, 'unpaid', CURDATE() - INTERVAL 13 DAY, CURDATE() - INTERVAL 12 DAY, 2, 'All paid leaves exhausted. Urgent family matter.', 'approved', 1, NOW() - INTERVAL 14 DAY),
(7, 'sick',   CURDATE() - INTERVAL 10 DAY, CURDATE() - INTERVAL 10 DAY, 1, 'Medical appointment - annual checkup.', 'approved', 2, NOW() - INTERVAL 11 DAY),
(10, 'local', CURDATE() - INTERVAL 25 DAY, CURDATE() - INTERVAL 18 DAY, 8, 'Annual family vacation.', 'approved', 1, NOW() - INTERVAL 26 DAY),
(10, 'sick',  CURDATE() - INTERVAL 12 DAY, CURDATE() - INTERVAL 10 DAY, 3, 'Hospitalized - appendix removal.', 'approved', 1, NOW() - INTERVAL 13 DAY),
(10, 'unpaid',CURDATE() - INTERVAL 9  DAY, CURDATE() - INTERVAL 7  DAY, 3, 'Recovery time after surgery. All leave exhausted.', 'approved', 1, NOW() - INTERVAL 10 DAY),
-- Rejected
(8, 'local',  CURDATE() - INTERVAL 5 DAY, CURDATE() - INTERVAL 3 DAY, 3, 'Going on a short trip abroad.', 'rejected', 2, NOW() - INTERVAL 6 DAY),
(9, 'sick',   CURDATE() - INTERVAL 8 DAY, CURDATE() - INTERVAL 7 DAY, 2, 'Not feeling well.', 'rejected', 1, NOW() - INTERVAL 9 DAY),
-- Pending (for testing approve/reject + email notification)
(4, 'local',  CURDATE() + INTERVAL 3 DAY,  CURDATE() + INTERVAL 5 DAY,  3, 'Need to attend a family function out of town.', 'pending', NULL, NULL),
(5, 'sick',   CURDATE() + INTERVAL 1 DAY,  CURDATE() + INTERVAL 1 DAY,  1, 'Doctor appointment for blood test follow-up.', 'pending', NULL, NULL),
(7, 'local',  CURDATE() + INTERVAL 7 DAY,  CURDATE() + INTERVAL 9 DAY,  3, 'Annual leave - planned vacation with family.', 'pending', NULL, NULL),
(8, 'local',  CURDATE() + INTERVAL 14 DAY, CURDATE() + INTERVAL 16 DAY, 3, 'National holiday extension requested.', 'pending', NULL, NULL),
(9, 'local',  CURDATE() + INTERVAL 5 DAY,  CURDATE() + INTERVAL 5 DAY,  1, 'Personal appointment.', 'pending', NULL, NULL),
(11, 'sick',  CURDATE() + INTERVAL 2 DAY,  CURDATE() + INTERVAL 3 DAY,  2, 'Scheduled medical check-up.', 'pending', NULL, NULL),
(12, 'local', CURDATE() + INTERVAL 10 DAY, CURDATE() + INTERVAL 11 DAY, 2, 'Attending a professional development workshop.', 'pending', NULL, NULL);

-- ============================================================
-- TASKS (delayed tasks can now also be completed)
-- ============================================================
INSERT INTO tasks (title, description, assigned_to, assigned_by, due_date, priority, status, delay_reason, completed_at) VALUES
-- Completed
('Set up CI/CD pipeline',         'Configure GitHub Actions for automated deployment to staging and production.', 5, 1, CURDATE() - INTERVAL 10 DAY, 'high',   'completed', NULL, NOW() - INTERVAL 9 DAY),
('Design new dashboard mockups',  'Create wireframes and high-fidelity mockups for the new client dashboard in Figma.', 8, 1, CURDATE() - INTERVAL 8 DAY, 'high',   'completed', NULL, NOW() - INTERVAL 7 DAY),
('Q3 Financial Report',           'Prepare and review the quarterly financial report for management review.', 7, 1, CURDATE() - INTERVAL 6 DAY, 'high',   'completed', NULL, NOW() - INTERVAL 5 DAY),
('Fix login page bug',            'Users report intermittent 500 error on login. Investigate and fix.', 4, 1, CURDATE() - INTERVAL 5 DAY, 'high',   'completed', NULL, NOW() - INTERVAL 4 DAY),
('Write unit tests for API',      'Add unit tests for all REST API endpoints using PHPUnit. Minimum 80% coverage.', 9, 1, CURDATE() - INTERVAL 3 DAY, 'medium', 'completed', NULL, NOW() - INTERVAL 2 DAY),
('Update employee handbook',      'Revise the employee handbook with updated HR policies for 2025.', 6, 1, CURDATE() - INTERVAL 2 DAY, 'low',    'completed', NULL, NOW() - INTERVAL 1 DAY),
-- Delayed (employees can complete these directly now)
('Migrate database to AWS RDS',   'Move MySQL database from on-premise server to AWS RDS with zero downtime.', 5, 1, CURDATE() - INTERVAL 3 DAY, 'high',   'delayed', 'Waiting for AWS account approval from finance department. Blocked by procurement.', NULL),
('Create social media campaign',  'Plan and schedule Q4 social media content calendar for all platforms.', 6, 1, CURDATE() - INTERVAL 2 DAY, 'medium', 'delayed', 'Waiting for brand guidelines update from design team before proceeding.', NULL),
('Performance audit',             'Run full performance audit on production application and identify bottlenecks.', 9, 1, CURDATE() - INTERVAL 1 DAY, 'medium', 'delayed', 'Production environment was locked for maintenance window. Will resume tomorrow.', NULL),
('Docker containerisation',       'Containerise all microservices using Docker and update docker-compose.yml.', 11, 1, CURDATE() - INTERVAL 2 DAY, 'high',  'delayed', 'Base image compatibility issue with the legacy auth service. Investigating fix.', NULL),
-- Pending (for testing complete/delay + email notification)
('Implement dark mode',           'Add dark mode toggle to the web application with user preference persistence.', 4, 1, CURDATE() + INTERVAL 3 DAY, 'medium', 'pending', NULL, NULL),
('Onboarding documentation',      'Create step-by-step onboarding documentation for new hires joining next month.', 2, 1, CURDATE() + INTERVAL 5 DAY, 'medium', 'pending', NULL, NULL),
('Security vulnerability scan',   'Run OWASP ZAP security scan on staging environment and fix critical findings.', 9, 1, CURDATE() + INTERVAL 2 DAY, 'high',   'pending', NULL, NULL),
('Monthly payroll reconciliation','Reconcile this month payroll figures with attendance and leave records.', 7, 1, CURDATE() + INTERVAL 1 DAY, 'high',   'pending', NULL, NULL),
('Redesign landing page',         'Refresh the company landing page with new branding. Coordinate with marketing.', 8, 1, CURDATE() + INTERVAL 7 DAY, 'medium', 'pending', NULL, NULL),
('Client presentation deck',      'Prepare Q4 results presentation deck for the board meeting next week.', 6, 1, CURDATE() + INTERVAL 4 DAY, 'high',   'pending', NULL, NULL),
('Code review - auth module',     'Review pull request #47 for the authentication module refactor.', 5, 1, CURDATE() + INTERVAL 1 DAY, 'high',   'pending', NULL, NULL),
('Update API documentation',      'Update Swagger/OpenAPI docs to reflect recent endpoint changes.', 4, 1, CURDATE() + INTERVAL 6 DAY, 'low',    'pending', NULL, NULL),
('Set up monitoring dashboard',   'Configure Grafana + Prometheus monitoring dashboard for all services.', 11, 1, CURDATE() + INTERVAL 4 DAY, 'medium', 'pending', NULL, NULL),
('HR compliance audit',           'Prepare all HR documentation for the annual compliance audit next month.', 12, 1, CURDATE() + INTERVAL 8 DAY, 'high',   'pending', NULL, NULL),
-- Overdue
('Fix mobile responsiveness',     'Several pages break on mobile screens below 375px. Fix all reported pages.', 8, 1, CURDATE() - INTERVAL 2 DAY, 'high',   'pending', NULL, NULL),
('Update SSL certificate',        'SSL cert expires in 5 days. Renew and deploy to all environments.', 5, 1, CURDATE() - INTERVAL 1 DAY, 'high',   'pending', NULL, NULL);

-- ============================================================
-- ANNOUNCEMENTS
-- ============================================================
INSERT INTO announcements (title, content, posted_by, is_active) VALUES
('🎉 Welcome to our new HR System!',
'Dear Team,

We are excited to launch our new HR Management System. This system will help us manage attendance, leaves, and tasks more efficiently.

Please ensure you:
• Log in every morning to register your attendance
• Submit leave requests at least 2 days in advance
• Check your assigned tasks daily

If you face any issues, please contact the IT department.

Best regards,
Management', 1, 1),

('📅 Public Holiday Notice - End of Year',
'Please be informed that the office will be closed on the following dates:
• December 25 – Christmas Day
• December 26 – Boxing Day
• January 1 – New Year''s Day

Employees who are required to work on these dates will receive compensatory leave.

HR Department', 2, 1),

('🔔 Leave Balance Reminder',
'Dear All,

As we approach the end of the year, please note that unused annual leave will NOT be carried over to the next year.

Remaining leaves will be reset to 22 local and 15 sick on January 1st.

HR Team', 2, 1),

('💻 System Maintenance – This Weekend',
'The HRMS will undergo scheduled maintenance on Saturday from 10PM to 2AM.

During this time, you will not be able to access the system. Please ensure all your tasks and requests are submitted before 10PM on Saturday.

Thank you for your cooperation.
IT Department', 1, 1),

('🏆 Employee of the Month – November',
'Congratulations to Emily Rodriguez (EMP004) for being our Employee of the Month for November!

Emily has shown exceptional dedication, completing all assigned tasks ahead of schedule and maintaining perfect attendance.

Well done, Emily! 🎊

Management', 1, 1),

('⚠️ Reminder: Break Policy',
'As a reminder to all staff:

Breaks must not exceed 1 hour in duration and must be taken before 3:00 PM.

Please register your break start and end time in the system. Unregistered breaks may affect your total hours calculation.

HR Department', 2, 0);

-- ============================================================
-- NOTIFICATIONS
-- ============================================================
INSERT INTO notifications (user_id, type, title, message, is_read, related_id) VALUES
(1, 'leave_request', 'New Leave Request', 'Emily Rodriguez requested 3 day(s) of Local leave.', 0, 11),
(1, 'leave_request', 'New Leave Request', 'Michael Chen requested 1 day(s) of Sick leave.', 0, 12),
(1, 'leave_request', 'New Leave Request', 'David Fontaine requested 3 day(s) of Local leave.', 0, 13),
(1, 'leave_request', 'New Leave Request', 'Kevin Balgobin requested 2 day(s) of Sick leave.', 0, 16),
(1, 'task_complete', 'Task Completed', 'Michael Chen completed: Set up CI/CD pipeline', 0, 1),
(1, 'task_complete', 'Task Completed', 'Amina Osman completed: Design new dashboard mockups', 1, 2),
(1, 'task_delayed',  'Task Delayed',    'Michael Chen delayed: Migrate database to AWS RDS. Reason: Waiting for AWS account approval.', 0, 7),
(1, 'task_delayed',  'Task Delayed',    'Priya Sharma delayed: Create social media campaign. Reason: Waiting for brand guidelines.', 0, 8),
(1, 'task_delayed',  'Task Delayed',    'Kevin Balgobin delayed: Docker containerisation. Reason: Base image compatibility issue.', 0, 10),
(1, 'task_due',      'Task Overdue',    'Amina Osman''s task ''Fix mobile responsiveness'' is Overdue.', 0, 21),
(1, 'task_due',      'Task Overdue',    'Michael Chen''s task ''Update SSL certificate'' is Overdue.', 0, 22),
(4, 'task',    'New Task Assigned', 'You have been assigned: Implement dark mode', 0, 11),
(4, 'task',    'New Task Assigned', 'You have been assigned: Update API documentation', 0, 18),
(4, 'leave',   'Leave Approved',    'Your Local leave request (3 days) has been approved.', 1, 1),
(5, 'task',    'New Task Assigned', 'You have been assigned: Code review - auth module', 0, 17),
(5, 'task',    'New Task Assigned', 'You have been assigned: Update SSL certificate', 0, 22),
(5, 'leave',   'Leave Approved',    'Your Sick leave request has been approved.', 1, 2),
(5, 'leave',   'Leave Pending',     'Your leave request for 1 day is pending approval.', 0, 12),
(6, 'task',    'New Task Assigned', 'You have been assigned: Client presentation deck', 0, 16),
(6, 'leave',   'Leave Rejected',    'Your Local leave request (3 days) has been rejected.', 1, 9),
(7, 'task',    'New Task Assigned', 'You have been assigned: Monthly payroll reconciliation', 0, 14),
(8, 'task',    'New Task Assigned', 'You have been assigned: Redesign landing page', 0, 15),
(8, 'leave',   'Leave Rejected',    'Your Local leave request (3 days) has been rejected.', 0, 9),
(9, 'task',    'New Task Assigned', 'You have been assigned: Security vulnerability scan', 0, 13),
(9, 'leave',   'Leave Rejected',    'Your Sick leave request (2 days) has been rejected.', 1, 10),
(11, 'task',   'New Task Assigned', 'You have been assigned: Set up monitoring dashboard', 0, 19),
(11, 'leave',  'Leave Pending',     'Your Sick leave request (2 days) is pending approval.', 0, 16),
(12, 'task',   'New Task Assigned', 'You have been assigned: HR compliance audit', 0, 20),
(12, 'leave',  'Leave Pending',     'Your Local leave request (2 days) is pending approval.', 0, 17),
(2, 'leave_request', 'New Leave Request', 'Amina Osman requested leave - pending your review.', 0, 14),
(2, 'leave_request', 'New Leave Request', 'Lucas Petrov requested 1 day leave.', 0, 15);

-- ============================================================
-- VERIFY
-- ============================================================
SELECT 'Users' as entity, COUNT(*) as total FROM users
UNION ALL SELECT 'Attendance Records', COUNT(*) FROM attendance
UNION ALL SELECT 'Leave Requests', COUNT(*) FROM leave_requests
UNION ALL SELECT 'Tasks', COUNT(*) FROM tasks
UNION ALL SELECT 'Announcements', COUNT(*) FROM announcements
UNION ALL SELECT 'Notifications', COUNT(*) FROM notifications;
