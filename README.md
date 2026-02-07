# 📚 Library Management System

A modern, full-featured Library Management System built with PHP, MySQL, and Tailwind CSS. This system provides comprehensive tools for managing books, members, and transactions with an intuitive and responsive user interface.

![PHP](https://img.shields.io/badge/PHP-8.0+-777BB4?style=flat&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0+-4479A1?style=flat&logo=mysql&logoColor=white)
![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-3.0+-06B6D4?style=flat&logo=tailwind-css&logoColor=white)

## ✨ Features

### 📖 Book Management

- **Add New Books** - Add books with multiple authors support
- **Manage Books** - Browse, search, edit, and delete books with grid layout
- **Edit Books** - Update book details (except stock quantity)
- **Soft Delete** - Safely remove books without losing transaction history
- **Advanced Search** - Search by title, ISBN, or author
- **Category Filtering** - Filter books by categories
- **Status Tracking** - Real-time availability status (Available/Issued)
- **Multiple Authors** - Support for books with multiple authors

### 👥 Member Management

- **Member Registration** - Add new library members with user accounts
- **Member Profiles** - View and edit member information
- **Status Management** - Active/Inactive member status with visual badges
- **Active Loans Tracking** - See how many books each member has borrowed
- **Search & Filter** - Search members by name, email, or student ID
- **Pagination** - 10 members per page for better performance

### 📋 Transaction Management

- **Issue Books** - Issue multiple books to a member at once
- **Return Books** - Return multiple books in a single transaction
- **Transaction History** - Complete borrowing history with pagination (15 per page)
- **Status Filters** - View Active, Overdue, Returned, or All transactions
- **Automatic Fine Calculation** - $0.50 per day for overdue books
- **Due Date Management** - Default 14-day loan period

### 🔍 Advanced Search

- **Global Search** - Search across books, members, and transactions
- **Real-time Results** - Instant search results as you type
- **Category Filtering** - Narrow down results by category

### 📊 Dashboard

- **Statistics Overview** - Total books, members, and active loans
- **Quick Actions** - Fast access to common tasks
- **Recent Activity** - View recent transactions

### 🔐 Authentication & Authorization

- **Role-Based Access Control** - Admin and Member roles
- **Secure Login** - Password hashing with PHP's `password_hash()`
- **Session Management** - Secure session handling
- **Protected Routes** - Middleware-based route protection

### 🎨 User Interface

- **Modern Design** - Clean, professional interface with Tailwind CSS
- **Responsive Layout** - Works on desktop, tablet, and mobile
- **Lucide Icons** - Beautiful, consistent iconography
- **Visual Feedback** - Status badges, hover effects, and transitions
- **Dark Mode Ready** - Prepared for dark mode implementation

### 🚀 Performance Features

- **Pagination** - All list pages support pagination
- **Efficient Queries** - Optimized SQL queries with proper indexing
- **Reusable Components** - Shared pagination and UI components
- **AJAX Ready** - Structure supports AJAX enhancements

## 📁 Project Structure

```
LibraryHub/
├── admin/                      # Admin panel pages
│   ├── add_book.php           # Route: /admin/books/add - Add new books
│   ├── dashboard.php          # Route: /admin/dashboard - Admin dashboard
│   ├── edit_book.php          # Route: /admin/books/edit - Edit book
│   ├── edit_member.php        # Route: /admin/members/edit - Edit member
│   ├── issue_book.php         # Route: /admin/issue - Issue books
│   ├── manage_books.php       # Route: /admin/books - Book management
│   ├── manage_members.php     # Route: /admin/members - Member management
│   ├── profile.php            # Route: /admin/profile - Admin profile
│   ├── return_book.php        # Route: /admin/return - Return books
│   ├── search.php             # Route: /admin/search - Advanced search
│   └── transactions.php       # Route: /admin/transactions - History
│
├── auth/                       # Authentication pages
│   ├── login.php              # Route: /login
│   ├── logout.php             # Route: /logout
│   └── register.php           # Route: /signup
│
├── member/                     # Member portal pages
│   ├── dashboard.php          # Route: /member - Member dashboard
│   ├── my_books.php           # Route: /member/loans - Borrowed books
│   └── profile.php            # Route: /member/profile - Member profile
│
├── config/                     # Configuration files
│   └── db_config.php          # Database connection settings
│
├── includes/                   # Reusable components
│   ├── auth_middleware.php    # Authentication middleware
│   ├── footer.php             # Page footer
│   ├── header.php             # Page header with navigation
│   ├── pagination.php         # Reusable pagination component
│   └── sidebar.php            # Navigation sidebar
│
├── assets/                     # Static assets
│   └── css/
│       └── style.css          # Custom styles and Tailwind config
│
├── schema_migration.sql       # Database schema
├── index.php                  # Landing page
├── .gitignore                 # Git ignore file
└── README.md                  # This file
```

## 🗄️ Database Schema

### Core Tables

#### `users`

- **Purpose**: Authentication and role management
- **Fields**: user_id, username, password (hashed), role_id
- **Relationships**: Links to roles, members

#### `roles`

- **Purpose**: User role definitions
- **Fields**: role_id, role_name
- **Values**: 'admin', 'member'

#### `members`

- **Purpose**: Library member information
- **Fields**: member_id, user_id, full_name, email, join_date, status, deleted_at
- **Features**: Soft delete support, status tracking

#### `books`

- **Purpose**: Book catalog
- **Fields**: book_id, title, isbn, category_id, status_id
- **Relationships**: Links to categories, status, authors (many-to-many)

#### `authors`

- **Purpose**: Author information
- **Fields**: author_id, name
- **Relationships**: Many-to-many with books via book_authors

#### `book_authors`

- **Purpose**: Junction table for books and authors
- **Fields**: book_id, author_id
- **Type**: Many-to-many relationship

#### `categories`

- **Purpose**: Book categorization
- **Fields**: category_id, category_name

#### `status`

- **Purpose**: Book availability status
- **Fields**: status_id, status_name
- **Values**: 'Available', 'Issued', 'Lost', 'Damaged'

#### `issues`

- **Purpose**: Book transactions (issue/return)
- **Fields**: issue_id, book_id, member_id, issue_date, due_date, return_date, fine_amount
- **Features**: Automatic fine calculation, transaction history

### Database Normalization

- **3NF Compliant**: All tables follow Third Normal Form
- **Foreign Keys**: Proper referential integrity
- **Cascading**: Appropriate CASCADE and RESTRICT rules
- **Indexing**: Primary and foreign keys indexed for performance

## 🚀 Installation

### Prerequisites

- **XAMPP** (or similar) with:
  - PHP 8.0 or higher
  - MySQL 8.0 or higher
  - Apache Web Server
- **Web Browser** (Chrome, Firefox, Edge, Safari)

### Step-by-Step Installation

1. **Clone or Download the Project**

   ```bash
   cd E:\xampp\htdocs\lib_system
   git clone <repository-url> library_system
   # OR download and extract the ZIP file
   ```

2. **Start XAMPP Services**
   - Start Apache
   - Start MySQL

3. **Create Database**
   - Open phpMyAdmin: `http://localhost/phpmyadmin`
   - Create a new database named `library_db`
   - Import `schema_migration.sql` file

4. **Configure Database Connection**
   - Open `config/db_config.php`
   - Update credentials if needed:
     ```php
     $host = 'localhost';
     $dbname = 'library_db';
     $username = 'root';
     $password = ''; // Your MySQL password
     ```

5. **Run Migrations** (if needed)
   - Visit migration files in browser to run them:
     - `http://localhost/<folder_path>/<migration_name>.php`
     -

6. **Create Admin User**
   - Register a new user via `/signup`
   - Manually update the role in database:
     ```sql
     UPDATE users SET role_id = 1 WHERE username = 'your_username';
     ```

7. **Access the Application**
   - **Landing Page**: `http://localhost/<folder_path>/`
   - **Login**: `http://localhost/<folder_path>/login`
   - **Admin Dashboard**: `http://localhost/<folder_path>/admin/dashboard`

## 🎯 Usage Guide

### For Administrators

#### Managing Books

1. Navigate to **Manage Books**
2. Click **Add New Book** to add books
3. Use the **Edit** button to update book details
4. Use the **Delete** button to remove books (Soft Delete)
   - Books with active loans cannot be deleted
5. Search and filter books by category

#### Managing Members

1. Go to **Member Management**
2. Add new members with user accounts
3. Filter by status (Active/Inactive)
4. Search by name, email, or student ID
5. Edit member information as needed

#### Issuing Books

1. Navigate to **Issue Book**
2. Select a member
3. Add multiple books to issue
4. Set due date (default: 14 days)
5. Submit to issue all books at once

#### Returning Books

1. Go to **Return Book**
2. Select member with active loans
3. Check boxes for books to return
4. Review automatic fine calculations
5. Process return for multiple books

#### Viewing Transactions

1. Access **Transactions**
2. Filter by status:
   - Active (currently issued)
   - Overdue (past due date)
   - Returned (completed)
   - All History
3. Search by member, book, or transaction ID
4. Use pagination to browse history

### For Members

1. Login with member credentials
2. View borrowed books
3. Check due dates
4. Update profile information

## 🎨 Design Principles

### UI/UX Design

- **Clean & Modern**: Professional library interface
- **Consistent**: Uniform design language throughout
- **Intuitive**: Easy to navigate and understand
- **Responsive**: Works on all device sizes
- **Accessible**: Proper contrast and readable fonts

### Color Scheme

- **Primary**: Indigo (#4F46E5) - Actions and links
- **Success**: Green (#10B981) - Available, Active status
- **Warning**: Yellow (#F59E0B) - Warnings and notices
- **Danger**: Red (#EF4444) - Overdue, Inactive status
- **Neutral**: Gray shades - Text and backgrounds

### Typography

- **Headings**: Bold, clear hierarchy
- **Body**: Readable sans-serif font
- **Monospace**: ISBN and IDs
- **Sizes**: Responsive text sizing

### Components

- **Cards**: Elevated cards with shadows
- **Badges**: Status indicators with colors
- **Buttons**: Clear call-to-action buttons
- **Forms**: Well-structured input fields
- **Tables**: Clean, scannable data tables
- **Pagination**: Consistent navigation

## 🔧 Technical Details

### Backend

- **Language**: PHP 8.0+
- **Database**: MySQL 8.0+
- **PDO**: Prepared statements for security
- **Sessions**: Secure session management
- **Password**: bcrypt hashing

### Frontend

- **CSS Framework**: Tailwind CSS 3.0+
- **Icons**: Lucide Icons
- **JavaScript**: Vanilla JS for interactions
- **Responsive**: Mobile-first approach

### Security Features

- **SQL Injection Prevention**: PDO prepared statements
- **XSS Protection**: `htmlspecialchars()` on all outputs
- **CSRF Protection**: Session-based validation
- **Password Security**: PHP `password_hash()` and `password_verify()`
- **Role-Based Access**: Middleware authentication
- **Input Validation**: Server-side validation

### Performance Optimizations

- **Pagination**: Reduces data load
- **Indexed Queries**: Fast database lookups
- **Lazy Loading**: Load data as needed
- **Efficient Joins**: Optimized SQL queries
- **Reusable Components**: DRY principle

## 📊 Key Features Implementation

### Pagination Component

- **Reusable**: Used across all list pages
- **Responsive**: Mobile and desktop layouts
- **Smart**: Shows page ranges intelligently
- **State Preservation**: Maintains search/filter state
- **Customizable**: Configurable items per page

### Multiple Books Issue/Return

- **Batch Processing**: Handle multiple books at once
- **Transaction Safety**: All-or-nothing database transactions
- **Validation**: Checks availability before processing
- **User Feedback**: Clear success/error messages
- **Fine Calculation**: Automatic for each book

### Advanced Search

- **Multi-table**: Searches across books, members, transactions
- **Real-time**: Instant results
- **Filtering**: Category and status filters
- **Highlighting**: Search term highlighting (ready)

### Status Management

- **Visual Badges**: Color-coded status indicators
- **Real-time Updates**: Status changes immediately
- **Filtering**: Filter by status on all pages
- **Tracking**: Complete status history

## 🐛 Troubleshooting

### Common Issues

**Database Connection Error**

- Check XAMPP MySQL is running
- Verify credentials in `config/db_config.php`
- Ensure database `library_db` exists

**Login Not Working**

- Clear browser cache and cookies
- Check user exists in database
- Verify password was hashed correctly

**Books Not Showing**

- Run migrations if needed
- Check status table has 'Available' and 'Issued'
- Verify foreign key relationships

**Pagination Not Appearing**

- Ensure you have more items than items-per-page
- Check `$totalItems` is calculated correctly
- Verify pagination.php is included

## 🔄 Future Enhancements

### Planned Features

- [ ] Email notifications for due dates
- [ ] Book reservations system
- [ ] QR code for books
- [ ] Export reports (PDF/Excel)
- [ ] Advanced analytics dashboard
- [ ] Book recommendations
- [ ] Member reading history
- [ ] Fine payment integration
- [ ] Dark mode toggle
- [ ] Multi-language support

### Technical Improvements

- [ ] RESTful API
- [ ] AJAX-based interactions
- [ ] Real-time notifications
- [ ] Image upload for books
- [ ] Barcode scanning
- [ ] Automated backups
- [ ] Audit logging
- [ ] Rate limiting

## 📝 License

This project is open-source and available for educational purposes.

## 👥 Contributors

- **Developer**:
- **Design**: Modern UI/UX Principles
- **Database**: Normalized Schema Design

## 📞 Support

For issues, questions, or contributions:

- Create an issue in the repository
- Contact the development team
- Check documentation

## 🙏 Acknowledgments

- **Tailwind CSS** - For the beautiful UI framework
- **Lucide Icons** - For the icon library
- **PHP Community** - For excellent documentation
- **MySQL** - For robust database management

---

**Built with ❤️ for efficient library management**

_Last Updated: February 2026_
