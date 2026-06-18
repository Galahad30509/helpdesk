-- เพิ่มคอลัมน์ status ถ้ายังไม่มี
ALTER TABLE user ADD COLUMN status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending';
