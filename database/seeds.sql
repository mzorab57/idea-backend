USE idea_foundation_db;
INSERT INTO users (full_name, email, password, role) VALUES
('Main Admin', 'admin@idea.foundation', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('Ali Employee', 'ali@idea.foundation', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employee');
INSERT INTO settings (setting_key, setting_value) VALUES
('site_name', 'Idea Foundation'),
('contact_email', 'info@idea.foundation'),
('maintenance_mode', '0');
