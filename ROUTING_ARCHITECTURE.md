# Routing System Architecture

This document explains the routing system implemented in the Library Management System.

## Overview

The application uses a **Front Controller** pattern. Instead of accessing individual PHP files directly (e.g., `admin/manage_books.php`), all requests are routed through a single entry point: `index.php`.

## Components

### 1. Web Server Configuration (`.htaccess`)

The `.htaccess` file intercepts all incoming requests.

- If a request is for a real file (like an image or CSS file), it serves it directly.
- If the file doesn't exist (like `/admin/books`), it rewrites the request to `index.php`.

### 2. Front Controller (`index.php`)

This is the application bootstrap file.

1.  **Loads Dependencies:** Includes authentication middleware, URL helpers, and the Router class.
2.  **Initializes Router:** Sets up the router with the application's base path.
3.  **Defines Routes:** Maps URL paths (e.g., `/login`, `/admin/dashboard`) to specific code blocks.
4.  **Dispatches:** Checks the current request URL and executes the matching route.

### 3. The Router Class (`includes/Router.php`)

Handles the logic of parsing URLs and matching them to defined routes.

- **`add($method, $path, $callback)`**: Registers a new route.
- **`dispatch($method, $uri)`**: Finds the matching route for the current request and executes the callback.
- **Features:**
  - Supports GET and POST methods.
  - specialized `__DIR__` handling ensures files are included correctly from the root context.

## Route Mapping

| URL Path              | HTTP Method | Target File                | Description                  |
| --------------------- | ----------- | -------------------------- | ---------------------------- |
| `/`                   | GET         | -                          | Redirects based on user role |
| `/login`              | GET/POST    | `auth/login.php`           | User Login                   |
| `/logout`             | GET         | `auth/logout.php`          | User Logout                  |
| `/signup`             | GET/POST    | `auth/signup.php`          | Member Registration          |
| **Admin Routes**      |             |                            |                              |
| `/admin/dashboard`    | GET         | `admin/dashboard.php`      | Admin Dashboard              |
| `/admin/books`        | GET/POST    | `admin/manage_books.php`   | Book Catalog Management      |
| `/admin/books/add`    | GET/POST    | `admin/add_book.php`       | Add New Book                 |
| `/admin/members`      | GET/POST    | `admin/manage_members.php` | Member Management            |
| `/admin/members/edit` | GET/POST    | `admin/edit_member.php`    | Edit Member Details          |
| `/admin/issue`        | GET/POST    | `admin/issue_book.php`     | Issue Book Page              |
| `/admin/return`       | GET/POST    | `admin/return_book.php`    | Return Book Page             |
| `/admin/transactions` | GET         | `admin/transactions.php`   | Transaction History          |
| `/admin/search`       | GET         | `admin/search.php`         | Admin Advanced Search        |
| `/admin/profile`      | GET/POST    | `admin/profile.php`        | Admin Profile                |
| **Member Routes**     |             |                            |                              |
| `/member`             | GET         | `member/index.php`         | Member Home / Catalog        |
| `/member/loans`       | GET         | `member/my_loans.php`      | Member's Active Loans        |
| `/member/search`      | GET         | `member/search.php`        | Member Advanced Search       |

## Development Guidelines

### Adding a New Page

1.  **Create the View File:** Create your PHP file (e.g., `admin/new_page.php`).
    - **Crucial:** Use `__DIR__` for all `require` and `include` paths inside this file.
    - Example: `require_once __DIR__ . '/../includes/header.php';`
2.  **Register the Route:** Open `index.php` and add the route.
    ```php
    $router->get('/admin/new-page', function() {
        require __DIR__ . '/admin/new_page.php';
    });
    ```
3.  **Use the URL:** Link to it using the clean path.
    - PHP: `echo url('admin/new-page');`
    - HTML: `<a href="<?php echo url('admin/new-page'); ?>">Link</a>`

### Path Handling

Since all code technically runs inside `index.php` (at the project root):

- **Relative Paths:** Do NOT use simple relative paths like `../includes/header.php` inside included files. They are unreliable because the "current working directory" is effectively the root.
- **Absolute Paths:** ALWAYS use `__DIR__ . '/relative/path'` to anchor includes to the file's physical location.
