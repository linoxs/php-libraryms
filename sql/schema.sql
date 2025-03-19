-- Library Management System Schema

-- Users Table
CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL UNIQUE,
    password TEXT NOT NULL,
    email TEXT NOT NULL UNIQUE,
    full_name TEXT NOT NULL,
    role TEXT NOT NULL CHECK (role IN ('admin', 'member')),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Publishers Table
CREATE TABLE publishers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    address TEXT,
    contact_info TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Books Table
CREATE TABLE books (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    author TEXT NOT NULL,
    publisher_id INTEGER,
    year INTEGER,
    isbn TEXT,
    genre TEXT,
    quantity INTEGER NOT NULL DEFAULT 1,
    available INTEGER NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (publisher_id) REFERENCES publishers(id)
);

-- Transactions Table (for borrowing/returning)
CREATE TABLE transactions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    book_id INTEGER NOT NULL,
    borrowed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    due_date TIMESTAMP NOT NULL,
    returned_at TIMESTAMP,
    status TEXT NOT NULL CHECK (status IN ('borrowed', 'returned', 'overdue')),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (book_id) REFERENCES books(id)
);

-- Password Resets Table
CREATE TABLE password_resets (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email TEXT NOT NULL,
    token TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL
);

-- Insert default admin user (password: admin123)
INSERT INTO users (username, password, email, full_name, role)
VALUES ('admin', '$2y$10$8tGmGX0QVlO.Lqh9OMfuS.FXs1jQ1YM.IC0m3oFvKfNgmIGQsMRbm', 'admin@library.com', 'System Administrator', 'admin');

-- Insert some sample publishers
INSERT INTO publishers (name, address, contact_info)
VALUES 
('Penguin Random House', '1745 Broadway, New York, NY', 'contact@penguinrandomhouse.com'),
('HarperCollins', '195 Broadway, New York, NY', 'contact@harpercollins.com'),
('Simon & Schuster', '1230 Avenue of the Americas, New York, NY', 'contact@simonandschuster.com');

-- Insert some sample books
INSERT INTO books (title, author, publisher_id, year, isbn, genre, quantity, available)
VALUES 
('To Kill a Mockingbird', 'Harper Lee', 1, 1960, '978-0446310789', 'Fiction', 5, 5),
('1984', 'George Orwell', 2, 1949, '978-0451524935', 'Dystopian', 3, 3),
('The Great Gatsby', 'F. Scott Fitzgerald', 3, 1925, '978-0743273565', 'Classic', 4, 4),
('Pride and Prejudice', 'Jane Austen', 1, 1813, '978-0141439518', 'Romance', 2, 2),
('The Hobbit', 'J.R.R. Tolkien', 2, 1937, '978-0547928227', 'Fantasy', 6, 6);
