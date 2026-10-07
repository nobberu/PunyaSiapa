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

* Web Server (Apache/Nginx) - e.g., XAMPP, Laragon, or MAMP.

* **Important for Apache users:** Make sure `mod_rewrite` is enabled in your `httpd.conf` for the MVC routing to work.

* PHP >= 7.4 (Ensure `pdo_pgsql` and `pgsql` extensions are enabled in your `php.ini`)

* PostgreSQL

* Database Manager (e.g., pgAdmin, DBeaver, or TablePlus)

### Steps

1. **Clone the repository:**

   ```
   git clone https://github.com/yourusername/punyasiapa.git
   
   ```

2. **Move to server directory:**
   Move the `PunyaSIapa` folder into your web server's root directory (e.g., `htdocs` for XAMPP or `www` for Laragon).

3. **Database Configuration:**

   * Open your database manager (e.g., pgAdmin or DBeaver).

   * Create a new database named `punyasiapa`.

   * Import the SQL file located in `database/example.sql` into your new database.

4. **Environment Setup:**

   * Navigate to `app/config/config.php` (create it if it doesn't exist yet).

   * Update the database credentials and `BASE_URL` to match your local setup:

     ```
     <?php
     define('BASEURL', 'http://localhost/PunyaSIapa/public');
     
     define('DB_HOST', 'localhost');
     define('DB_USER', 'postgres'); // Update to your local Postgres user
     define('DB_PASS', '');         // Update to your local Postgres password
     define('DB_NAME', 'punyasiapa');
     
     ```

5. **Run the Application:**
   Open your web browser and navigate to the `BASEURL` (e.g., `http://localhost/PunyaSIapa/public`).

### Important Note Regarding Routing (404 Errors)

This application uses a Custom MVC Routing system managed by `public/.htaccess`.

* If you are using **Apache (XAMPP/Laragon)**, the app is ready to use. Just ensure the `mod_rewrite` extension is active.

* If you experience a *404 Not Found* error when navigating away from the home page, please check your Apache `AllowOverride All` settings to ensure the `.htaccess` file is being read by the server.
