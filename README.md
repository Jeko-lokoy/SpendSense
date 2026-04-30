# SpendSense

SpendSense is a personal expense tracker system built with PHP, MySQL, HTML, and CSS. It is designed for students and beginners who want a simple way to record expenses, manage monthly budgets, and view spending summaries in a clean dashboard.

## System Overview

The system allows users to create an account, log in securely, and manage their own financial records. Each user has a private dashboard where they can:

- View total expenses
- View monthly total expenses
- Set a monthly budget
- See remaining budget
- Get a warning when spending goes over budget
- Review category-based spending summaries
- Search and filter expenses
- Add, edit, and delete expense records

Every user only sees their own data. Expenses, budgets, and dashboard summaries are separated by user account.

## Core Features

### 1. User Authentication

- User registration
- User login
- User logout
- Secure password storage using PHP password hashing

### 2. Personal Dashboard

After logging in, the user can access a dashboard that includes:

- Total expenses across the account
- Monthly expense summary
- Budget status for the selected month and year
- Remaining budget
- Category spending summary
- Recent expenses
- Search and filter tools

### 3. Expense Management

Users can:

- Add a new expense
- View their expense list
- Edit an existing expense
- Delete an expense with confirmation

Each expense contains:

- Title
- Amount
- Category
- Expense date
- Optional note

### 4. Budget Tracking

Users can set a monthly budget based on month and year. The dashboard compares the monthly total expenses against the saved budget and shows whether the user is still within budget or already over budget.

### 5. Search and Filter

Users can search expenses by:

- Title
- Category
- Note

Users can also filter by:

- Exact date
- Month
- Year
- Category

## Suggested Categories

The system currently includes these categories:

- Food
- Transportation
- School
- Bills
- Shopping
- Savings
- Others

## Database Tables

The system uses three main tables:

### `users`

Stores account information:

- `id`
- `name`
- `email`
- `password`
- `created_at`

### `expenses`

Stores user expense records:

- `id`
- `user_id`
- `title`
- `amount`
- `category`
- `expense_date`
- `note`
- `created_at`
- `updated_at`

### `budgets`

Stores monthly budget records:

- `id`
- `user_id`
- `month`
- `year`
- `budget_limit`
- `created_at`
- `updated_at`

## Technologies Used

- PHP
- MySQL
- HTML5
- CSS3
- XAMPP

## Project Structure

```text
SpendSense/
├── assets/
│   └── style.css
├── partials/
│   ├── footer.php
│   └── header.php
├── auth.php
├── config.php
├── dashboard.php
├── database.sql
├── delete_expense.php
├── expense_form.php
├── functions.php
├── index.php
├── login.php
├── logout.php
├── register.php
└── README.md
```

## How to Run the System

### 1. Move the project to XAMPP

Place the project inside:

```text
C:\xampp\htdocs\SpendSense
```

### 2. Start XAMPP Services

Open XAMPP Control Panel and start:

- Apache
- MySQL

### 3. Create the Database

Import the `database.sql` file into MySQL using phpMyAdmin or the MySQL command line.

### 4. Check Database Configuration

Open `config.php` and confirm the database settings match your local XAMPP setup:

- Host
- Port
- Database name
- Username
- Password

### 5. Open the System

Open this URL in your browser:

```text
http://localhost/SpendSense/
```

## Security Notes

- Passwords are stored securely using `password_hash()`
- Login access is protected using PHP sessions
- Each query is scoped to the logged-in user
- Users cannot access other users' expenses or budgets

## Purpose of the Project

SpendSense is a beginner-friendly web-based expense tracker created for learning and practical personal finance use. It demonstrates how authentication, CRUD operations, filtering, budget tracking, and dashboard summaries can work together in one system.
