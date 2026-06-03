# TutorConnect

TutorConnect is a PHP/MySQL web application for booking tutoring sessions. It was built for the CS355 Web Technologies final project requirements:

- Sign-up and sign-in pages
- Database connection with MySQL
- Styled responsive pages
- File upload, download, and delete
- Booking, payment, date change, cancellation, and receipt download
- Clear navigation between pages

## How to run with XAMPP

1. Copy the `tutorconnect` folder into your XAMPP `htdocs` folder.
   Example: `C:\xampp\htdocs\tutorconnect`
2. Start Apache and MySQL from the XAMPP Control Panel.
3. Open phpMyAdmin.
4. Import `database.sql`.
5. Visit `http://localhost/tutorconnect/`.

The database settings are in `config/config.php`. The default configuration uses:

```php
$dbHost = 'localhost';
$dbName = 'tutorconnect_db';
$dbUser = 'root';
$dbPass = '';
```

## Demo flow

1. Create a new account from `Sign up`.
2. Browse tutors and book a session.
3. Complete the demo payment. Use any 16 digit card number, a future expiry date, and any 3 digit CVV.
4. Open `Bookings` to change the date, cancel, or download the receipt.
5. Open `Materials` to upload study files, download them, or delete them.

## Security features included

- Passwords are stored with `password_hash`.
- User names are stored as separate `first_name` and `last_name` columns.
- Sign-up passwords require at least 10 characters, uppercase, lowercase, number, special character, and no spaces/common words/name/email username.
- Login uses `password_verify` and regenerates the session ID.
- SQL queries use PDO prepared statements.
- Forms use CSRF tokens.
- User output is escaped with `htmlspecialchars`.
- Uploads are limited to safe classroom file types and 5 MB.
- Uploaded files are saved with random file names.
- The storage folder blocks direct browsing through `.htaccess`.
- Downloads check the logged-in owner before sending a file.
- The payment demo stores only the last four card digits.

## File upload/download reason

The file feature fits the tutoring idea because students often need to send a PDF assignment brief, screenshot, notes file, or presentation before a session. TutorConnect lets a student attach those study materials to a booking, then download or delete them later from their account.
