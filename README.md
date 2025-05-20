# 📚 LMS-CAHCET

A powerful, PHP-based Learning Management System designed for CAHCET, leveraging MySQL for database management and running on XAMPP.

🔗 **Live Demo**: [LMS-CAHCET](https://lms.cahcet.in/)

---

## 🌟 Features

### 🎓 Institution Login
- Secure access with administrative privileges
- Modules for:
  - ✅ Marks & attendance management
  - ✅ Subject & grade entry
  - ✅ Revaluation updates
  - 📊 Reports including result analysis, marklist, progress report, CAPA form, consolidated results, university progress report, parent feedback, and counseling records

### 🏫 Student Login
- Individual profiles with academic details
- 📈 Semester-wise marks & attendance tracking
- 🎓 University grades overview

---

## 🛠️ Setup Guide

### 📌 Prerequisites
- XAMPP installed
- MySQL database set up
- PHP (compatible version for MySQL integration)

### 🚀 Installation Steps
1. Clone the repository:
   ```bash
   git clone https://github.com/not-abdulbari/lms-cahcet.git
   ```
2. Move the files into the XAMPP `htdocs` directory:
   ```bash
   mv lms-cahcet /opt/lampp/htdocs/  # For Linux/Mac
   # For Windows, move to C:/xampp/htdocs
   ```
3. Start Apache & MySQL via the XAMPP Control Panel.
4. Import the provided SQL database:
   ```sql
   CREATE DATABASE lms_cahcet;
   USE lms_cahcet;
   SOURCE /opt/lampp/htdocs/lms-cahcet/database.sql;
   ```
5. Configure the database connection in `faculty/db_connect.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   define('DB_NAME', 'lms_cahcet');
   ```
6. Open `http://localhost/lms-cahcet/` in your browser.

---

## 🧑‍💻 Tech Stack

- **PHP** (backend)
- **MySQL** (database)
- **XAMPP** (local server environment)
- **YAML** (configuration)
- **Turnstile Captcha**, **hCaptcha** (security)


---



## 🤝 Contributing

Want to enhance LMS-CAHCET?  
- Fork the repository  
- Make improvements  
- Submit a pull request  

Community contributions are always welcome!

---

## 📧 Contact

For inquiries or support, reach out via:  
✉️ **22602.abdulbari.cse@cahcet.edu.in**

---

An efficient, user-friendly LMS built for academic excellence at CAHCET! 🚀
