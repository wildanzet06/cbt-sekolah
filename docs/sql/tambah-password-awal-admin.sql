-- Menyimpan password awal admin supaya tombol mata di Data Admin bisa
-- menampilkannya, mirip dengan master_siswa.password / master_guru.password.
CREATE TABLE IF NOT EXISTS cbt_password_awal_admin (
    user_id INT UNSIGNED NOT NULL PRIMARY KEY,
    username VARCHAR(100) NOT NULL,
    password TEXT NOT NULL,
    updated_on INT UNSIGNED NOT NULL,
    KEY cbt_password_awal_admin_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Backfill untuk admin yang sudah ada.
-- Ganti '12341234' bila password awal admin di instalasi Anda berbeda.
INSERT INTO cbt_password_awal_admin (user_id, username, password, updated_on)
SELECT u.id, u.username, '12341234', UNIX_TIMESTAMP()
FROM users u
JOIN users_groups ug ON ug.user_id = u.id
JOIN `groups` g ON g.id = ug.group_id AND g.name = 'admin'
WHERE u.id = 1
ON DUPLICATE KEY UPDATE
    username = VALUES(username),
    password = VALUES(password),
    updated_on = VALUES(updated_on);
