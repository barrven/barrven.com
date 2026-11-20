-- Initial schema (from the original projects/tierlist/sql/boostrap_database.sql).
-- users.home_directory is legacy: uploads now live in storage/uploads/tierlist/<user_id>/.

CREATE TABLE IF NOT EXISTS users (
    user_id         INTEGER PRIMARY KEY AUTOINCREMENT,
    username        VARCHAR(50) UNIQUE NOT NULL,
    home_directory  VARCHAR(100) NOT NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS lists (
    list_id    INTEGER PRIMARY KEY AUTOINCREMENT,
    list_name  VARCHAR(100) NOT NULL,
    user_id    INTEGER NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS list_files (
    file_id     INTEGER PRIMARY KEY AUTOINCREMENT,
    list_id     INTEGER NOT NULL,
    file_name   VARCHAR(100) NOT NULL,
    file_tier   CHAR(1),
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (list_id)
        REFERENCES lists(list_id)
        ON DELETE CASCADE
);
