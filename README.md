# Beem Messenger

A responsive PHP and MySQL web application for sending and tracking **SMS** and **WhatsApp template messages** through Beem APIs.

> **Project status:** Core application, authentication, dashboard, message history, and API request flows are implemented. Sending live messages requires valid Beem credentials, an approved SMS sender ID, and an approved WhatsApp template.

## Project Overview

Beem Messenger is a portfolio project built to demonstrate the integration of multiple Beem communication services in one application.

Users can create an account, sign in, send SMS messages, prepare WhatsApp template messages, review delivery history, and monitor sent or failed message attempts from a central dashboard.

The project is designed for local development with XAMPP and can be extended into a complete communication platform for organizations, developers, and small businesses.

## Features

- User registration, login, logout, and session protection
- Responsive homepage and authenticated dashboard
- Beem SMS API integration
- Beem WhatsApp template-message integration
- SMS and WhatsApp message statistics
- Sent, pending, and failed message tracking
- Combined message-history page with SMS and WhatsApp tabs
- User profile and API-configuration overview
- Masked credential display for improved security
- Prepared statements for safer database operations
- Responsive Bootstrap interface
- Safe example configuration files for GitHub

## Screenshots

Create a `screenshots` folder in the project root and add images using names such as:

| Page | Suggested file |
|---|---|
| Homepage | `screenshots/homepage.png` |
| Login | `screenshots/login.png` |
| Dashboard | `screenshots/dashboard.png` |
| Send SMS | `screenshots/send-sms.png` |
| Send WhatsApp | `screenshots/send-whatsapp.png` |
| Message History | `screenshots/message-history.png` |
| Profile | `screenshots/profile.png` |

After adding the images, you can display them in this README with:

```markdown
![Beem Messenger Homepage](screenshots/homepage.png)
![Beem Messenger Dashboard](screenshots/dashboard.png)
![Message History](screenshots/message-history.png)
```

## Technologies Used

- **PHP 8+** — server-side application logic
- **MySQL / MariaDB** — user and message storage
- **HTML5** — page structure
- **CSS3** — custom interface styling
- **Bootstrap 5** — responsive layout and components
- **JavaScript** — Bootstrap interactions
- **cURL** — requests to Beem APIs
- **Apache** — local web server through XAMPP
- **Git and GitHub** — version control and project hosting

## Project Structure

```text
beem-messenger/
├── assets/
│   └── style.css
├── config/
│   ├── beem.example.php
│   └── database.example.php
├── screenshots/
├── dashboard.php
├── history.php
├── index.php
├── login.php
├── logout.php
├── profile.php
├── register.php
├── send_sms.php
├── send_whatsapp.php
├── .gitignore
└── README.md
```

The real `config/beem.php` and `config/database.php` files are excluded from GitHub through `.gitignore`.

## Requirements

Before installing the project, make sure you have:

- XAMPP with Apache and MySQL
- PHP 8.0 or later
- PHP cURL extension enabled
- Git, if cloning from GitHub
- A modern web browser
- Beem API credentials for live message sending
- An approved Beem SMS sender ID
- An approved Beem WhatsApp template and channel number

## Installation Steps

### 1. Clone the repository

Open Git Bash and run:

```bash
cd /c/xampp/htdocs
git clone https://github.com/aziziyusuph/beem-messenger.git
cd beem-messenger
```

You can also download the repository as a ZIP file and extract it into:

```text
C:\xampp\htdocs\beem-messenger
```

### 2. Start XAMPP

Open the XAMPP Control Panel and start:

- Apache
- MySQL

### 3. Create local configuration files

From the project directory, create working copies of the example files:

```bash
cp config/database.example.php config/database.php
cp config/beem.example.php config/beem.php
```

On Windows Command Prompt, use:

```bat
copy config\database.example.php config\database.php
copy config\beem.example.php config\beem.php
```

### 4. Configure the database connection

Open:

```text
config/database.php
```

Update it with your local database details:

```php
<?php

$host = "localhost";
$username = "root";
$password = "";
$database = "beem_messenger";

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}
```

### 5. Open the application

Visit:

```text
http://localhost/beem-messenger/
```

Register an account, log in, and open the dashboard.

## Database Setup

Open phpMyAdmin:

```text
http://localhost/phpmyadmin/
```

Create a database named:

```sql
CREATE DATABASE beem_messenger
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;
```

Select the new database and run the following SQL.

### Users table

```sql
CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(150) DEFAULT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
```

### SMS messages table

```sql
CREATE TABLE sms_messages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    phone_number VARCHAR(20) NOT NULL,
    message TEXT NOT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'pending',
    beem_message_id VARCHAR(255) DEFAULT NULL,
    beem_response LONGTEXT DEFAULT NULL,
    error_message TEXT DEFAULT NULL,
    sent_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_sms_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB;
```

### WhatsApp messages table

```sql
CREATE TABLE whatsapp_messages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    phone_number VARCHAR(20) NOT NULL,
    message_type VARCHAR(30) NOT NULL DEFAULT 'template',
    template_name VARCHAR(150) NOT NULL,
    message_content TEXT DEFAULT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'pending',
    beem_message_id VARCHAR(255) DEFAULT NULL,
    beem_response LONGTEXT DEFAULT NULL,
    error_message TEXT DEFAULT NULL,
    sent_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_whatsapp_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB;
```

> If you already created these tables during development, do not recreate them. Confirm that the existing column names match the PHP files in your project.

## Beem SMS Integration

The SMS feature allows authenticated users to submit a recipient number and message through `send_sms.php`.

### Configure SMS credentials

Open:

```text
config/beem.php
```

Add the values provided through your Beem account:

```php
<?php

define("BEEM_API_KEY", "YOUR_BEEM_API_KEY");
define("BEEM_SECRET_KEY", "YOUR_BEEM_SECRET_KEY");
define("BEEM_SENDER_ID", "YOUR_APPROVED_SENDER_ID");
define("BEEM_SMS_API_URL", "YOUR_BEEM_SMS_API_ENDPOINT");
```

The application should:

1. Validate the phone number and message.
2. Save the message attempt as `pending`.
3. Send the request to the Beem SMS API.
4. Update the database record to `sent` or `failed`.
5. Store the Beem response for troubleshooting.
6. Display the result in SMS history and dashboard statistics.

Use Tanzanian numbers in international format, for example:

```text
2557XXXXXXXX
```

## Beem WhatsApp Integration

The WhatsApp feature uses approved Beem template messages.

The user supplies:

- Recipient WhatsApp number
- Approved template ID
- Template language
- Template variables

A template request follows this general structure:

```php
$postData = [
    "from_addr" => $beem_from_addr,
    "destination_addr" => [
        [
            "phoneNumber" => $phone_number,
            "params" => $template_parameters
        ]
    ],
    "template_id" => (int) $template_id
];
```

### Configure WhatsApp values

Add the WhatsApp settings provided by Beem:

```php
define("BEEM_WHATSAPP_API_KEY", "YOUR_WHATSAPP_API_KEY");
define("BEEM_WHATSAPP_SECRET_KEY", "YOUR_WHATSAPP_SECRET_KEY");
define("BEEM_WHATSAPP_FROM_ADDR", "YOUR_BEEM_WHATSAPP_NUMBER");
define("BEEM_WHATSAPP_API_URL", "YOUR_BEEM_WHATSAPP_ENDPOINT");
```

The current `send_whatsapp.php` may contain placeholder variables near the top of the file. Replace those placeholders when Beem provides your credentials, or update the page to read the constants from `config/beem.php`.

Live WhatsApp sending also requires:

- An approved Beem WhatsApp channel
- A valid sender or `from_addr`
- An approved template ID
- Template parameters in the correct order

The application stores each attempt in `whatsapp_messages` and updates the status after receiving the API response.

## Security

Never commit real credentials to GitHub.

The `.gitignore` file should include:

```gitignore
config/beem.php
config/database.php
.env
.env.*
*.log
```

Only commit safe examples:

```text
config/beem.example.php
config/database.example.php
```

Additional security recommendations:

- Use environment variables in production.
- Keep database and API errors out of public pages.
- Validate all submitted values.
- Continue using prepared SQL statements.
- Enable SSL verification for production API requests.
- Regenerate credentials immediately if they are exposed.

## Usage

1. Register a new account.
2. Log in.
3. Open the dashboard.
4. Select **Send SMS** or **Send WhatsApp**.
5. Enter the message details.
6. Submit the request.
7. Open **Message History** to review its status.
8. Use **Profile & Settings** to check configuration status.

## Future Improvements

- Move all WhatsApp credentials into `config/beem.php`
- Add delivery-status callbacks or webhooks
- Add message search, filters, and pagination
- Add contact management
- Add bulk SMS support
- Add CSV recipient import
- Add reusable WhatsApp template selection
- Add administrator roles
- Add automated tests
- Deploy the project to a production server

## Author

**Azizi Yusuph**

GitHub: [@aziziyusuph](https://github.com/aziziyusuph)

## Acknowledgements

This project was created to demonstrate practical integration with Beem communication APIs using PHP and MySQL.
