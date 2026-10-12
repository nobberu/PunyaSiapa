# PunyaSIapa - A PBL Project

Platform forum terbuka untuk melaporkan dan menemukan barang hilang di lingkungan Polinema dan sekitarnya.

![Project Status](https://img.shields.io/badge/Status-Development-orange)
![PHP](https://img.shields.io/badge/PHP-Native-777BB4?logo=php&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-Vanilla-F7DF1E?logo=javascript&logoColor=white)
![PostgreSQL](https://img.shields.io/badge/PostgreSQL-316192?logo=postgresql&logoColor=white)
![Architecture](https://img.shields.io/badge/Architecture-MVC-brightgreen)

**PunyaSIapa** is a web-based Project Based Learning (PBL) application built with Native PHP and Vanilla JavaScript. It serves as a community-driven open forum specifically designed for the Politeknik Negeri Malang (Polinema) community to report, search, and claim lost and found items.

## Features

* **Lost & Found Reporting:** Easily create posts for items you have lost or items you have found.

* **Search & Filter:** Quickly search for items by keywords, categories (e.g., electronics, documents, accessories), and dates.

* **User Authentication:** Secure registration and login system for students and staff.

* **Discussion System:** Comment on posts to coordinate item returns or ask for details.

* **Responsive UI:** Clean, accessible interface powered by native HTML/CSS and enhanced with Vanilla JS.

## Technology Stack

This project is built from scratch without the use of heavy backend frameworks like Laravel or CodeIgniter, serving as a deep dive into fundamental web development patterns.

* **Backend:** Native PHP (v7.4 / v8.x)

* **Frontend:** HTML5, CSS3, Vanilla JavaScript

* **Database:** PostgreSQL

* **Architecture Pattern:** Custom MVC (Model-View-Controller)

## Directory Structure

The application follows a custom MVC architecture to keep the code organized, scalable, and maintainable.

```
PunyaSIapa/
├── app/                    # Application logic
│   ├── config/             # Configuration files (Database, Base URL)
│   ├── controllers/        # Handles user requests and connects Model to View
│   ├── core/               # Core classes (App routing, Controller base, Database wrapper)
│   ├── models/             # Database queries and business logic
│   └── views/              # UI templates (HTML/PHP mixed)
├── database/               # SQL dump files for easy setup
│   └── example.sql         
├── public/                 # Publicly accessible files (Document Root)
│   ├── css/                # Stylesheets
│   ├── js/                 # Vanilla JavaScript files
│   ├── .htaccess           # Apache configuration for custom URL routing
│   └── index.php           # Front Controller (entry point of the app)
├── .gitattributes          # Git configuration file
└── README.md               # Project documentation

```

## Installation & Setup

To run this project locally:

### Prerequisites

* PHP >= 7.4 (Ensure `pdo_pgsql` and `pgsql` extensions are enabled in your `php.ini`)

* PostgreSQL

* Database Manager (e.g., pgAdmin, DBeaver, or TablePlus)

### Steps

1. **Clone the repository:**

   ```bash
   git clone https://github.com/nobberu/punyasiapa.git
   ```

2. **Database Configuration:**

   * Open your database manager (e.g., pgAdmin or DBeaver).
   * Create a new database named `punyasiapa`.
   * Import the SQL file located in `database/example.sql` into your new database.

3. **Environment Setup:**

   * Copy `app/config/config.example.php` and rename it to `config.php`.
   * Update the database credentials to match your local setup:

     ```php
     <?php
     $db_host = 'localhost';
     $db_port = '5432';
     $db_user = 'postgres'; // Update to your local Postgres user
     $db_pass = '';         // Update to your local Postgres password
     $db_name = 'punyasiapa';
     ```

4. **Run the Application:**

   From the project root directory, run the built-in PHP server:

   ```bash
   php -S localhost:<yourport> -t public public/index.php
   ```

   Then open your web browser and navigate to `http://localhost:<yourport>`.


