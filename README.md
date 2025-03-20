# 📚 Library Management System

A comprehensive web-based library management system built with PHP that allows administrators to manage books, publishers, users, and transactions, while providing members with an intuitive interface to browse and borrow books.

![Library Management System](https://img.shields.io/badge/Library-Management_System-blue)
![PHP](https://img.shields.io/badge/PHP-8.0+-777BB4?logo=php&logoColor=white)
![SQLite](https://img.shields.io/badge/SQLite-3-003B57?logo=sqlite&logoColor=white)
![HTMX](https://img.shields.io/badge/HTMX-Enhanced_UI-3366CC)

## ✨ Features

### 👥 User Management
- **Role-based Access Control**: Admin and Member roles with appropriate permissions
- **User Authentication**: Secure login, registration, and password management
- **Profile Management**: Users can update their personal information

### 📖 Book Management
- **Comprehensive Catalog**: Store and display detailed book information
- **Publisher Management**: Track publishers and their associated books
- **Inventory Tracking**: Monitor book availability and quantity

### 🔄 Transaction System
- **Borrowing Process**: Members can borrow available books
- **Return Handling**: Track and process book returns
- **Due Date Management**: Set and monitor due dates for borrowed books
- **Overdue Notifications**: Highlight overdue books for both members and admins

### 📊 Admin Dashboard
- **System Overview**: Statistics on books, users, and transactions
- **User Management**: Add, edit, and manage user accounts
- **Book Management**: Add, edit, and manage book inventory
- **Publisher Management**: Add, edit, and manage publishers
- **Transaction Monitoring**: View and manage all book transactions
- **Overdue Book Tracking**: Easily identify and manage overdue books

### 👤 Member Dashboard
- **Personal Overview**: View borrowing history and current loans
- **Active Loans**: Track currently borrowed books and due dates
- **Overdue Alerts**: Receive notifications for overdue books

## 🏗️ Tech Stack

- **Backend**: Procedural PHP for business logic
- **Database**: SQLite for data storage
- **UI Structure**: PHP classes with static methods for layout and HTML structure
- **Frontend Interactivity**: HTMX for enhanced user experience
- **Styling**: Custom CSS for a clean, responsive interface

## 🚀 Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/yourusername/php-libraryms.git
   cd php-libraryms
   ```

2. **Set up the database**
   ```bash
   sqlite3 library.db < sql/schema.sql
   ```

3. **Configure web server**
   - Point your web server (Apache, Nginx, etc.) to the project directory
   - Ensure PHP 8.0+ is installed and configured

4. **Access the application**
   - Navigate to `http://localhost/` in your web browser
   - Default admin credentials: 
     - Username: `admin`
     - Password: `admin123`

## 📂 Project Structure

```
php-libraryms/
├── admin/              # Admin-specific pages
├── assets/             # CSS, JS, and image files
├── auth/               # Authentication pages
├── components/         # Reusable UI components
├── includes/           # Core functionality and utilities
├── layouts/            # Page layout templates
├── member/             # Member-specific pages
├── sql/                # Database schema and migrations
├── index.php           # Application entry point
└── README.md           # Project documentation
```

## 🔒 Security Features

- Password hashing using bcrypt
- Input sanitization to prevent SQL injection and XSS attacks
- CSRF protection for form submissions
- Secure session management
- Role-based access control


## 🛠️ Development

### Prerequisites
- PHP 8.0+
- SQLite 3
- Web server (Apache, Nginx, etc.)

### Local Development
1. Clone the repository
2. Set up the database using the schema in `sql/schema.sql`
3. Configure your web server to point to the project directory
4. Start developing!

---

Made with ❤️ by Desilino Muharyadi Putra
