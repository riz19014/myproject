# Laravel Users CRUD

A Laravel project with full CRUD (Create, Read, Update, Delete) operations for users.

## Database Configuration

The project is configured to use your MySQL database:

- **Host:** localhost
- **User:** root
- **Password:** (empty)
- **Database:** mydb

The `users` table structure:
- `id` (bigint, primary key, auto increment)
- `name` (varchar 255)
- `email` (varchar 255)
- `password` (varchar 255)
- `is_active` (tinyint, default 1)

## Getting Started

1. **Ensure MySQL is running** (XAMPP MySQL)

2. **Create the database** (if not exists):
   ```sql
   CREATE DATABASE IF NOT EXISTS mydb;
   ```

3. **Create the users table** (if not exists):
   ```sql
   CREATE TABLE `users` (
     `id` bigint(20) NOT NULL AUTO_INCREMENT,
     `name` varchar(255) DEFAULT NULL,
     `email` varchar(255) DEFAULT NULL,
     `password` varchar(255) DEFAULT NULL,
     `is_active` tinyint(1) NOT NULL DEFAULT 1,
     PRIMARY KEY (`id`)
   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
   ```

4. **Start the development server:**
   ```bash
   php artisan serve
   ```

5. **Access the application:**
   - Open http://localhost:8000 in your browser
   - You'll be redirected to the Users list
   - Use "Add User" to create new users
   - Edit, View, and Delete from the list

## Routes

| Method | URI | Action |
|--------|-----|--------|
| GET | / | Redirect to users |
| GET | /users | List all users |
| GET | /users/create | Create form |
| POST | /users | Store new user |
| GET | /users/{id} | Show user |
| GET | /users/{id}/edit | Edit form |
| PUT/PATCH | /users/{id} | Update user |
| DELETE | /users/{id} | Delete user |

## Using with XAMPP

If using XAMPP's Apache, point your document root to:
```
D:\xampp82\htdocs\myproject\public
```

Or access via: http://localhost/myproject/public
