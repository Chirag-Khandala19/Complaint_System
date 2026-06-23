# Personalized Area-Based Complaint Tracking System

## 📌 Overview

The Personalized Area-Based Complaint Tracking System is a web-based application developed using PHP and MySQL that enables users to register, track, and manage complaints efficiently. The system supports role-based access control, complaint assignment, SLA monitoring, complaint timeline tracking, reporting, and file upload functionality.

The project is designed to improve transparency, accountability, and efficiency in complaint resolution processes.

---

## 🚀 Features

### User Features

* User Registration and Login
* Create New Complaint
* Track Complaint Status
* View Complaint History
* Upload Supporting Documents
* Reopen Closed Complaints

### Staff Features

* View Assigned Complaints
* Update Complaint Status
* Upload Resolution Proof
* Add Remarks and Updates

### Supervisor Features

* Assign Complaints to Staff
* Reassign Complaints
* Monitor SLA Compliance
* View Complaint Reports

### System Features

* Role-Based Access Control
* AJAX-Based Dynamic Forms
* Zone → Sector → Spot Location Hierarchy
* Complaint Timeline Tracking
* SLA Monitoring
* File Upload Support
* Dashboard Analytics
* Complaint Reopening
* Advanced Filtering and Search

---

## 🛠️ Tech Stack

### Frontend

* HTML5
* CSS3
* Bootstrap 5
* JavaScript
* AJAX

### Backend

* PHP

### Database

* MySQL

### Development Tools

* XAMPP
* phpMyAdmin
* Visual Studio Code

---

## 📂 Project Structure

```text
complaint-system/
│
├── config/
│   ├── db.php
│   └── auth.php
│
├── includes/
│   ├── navbar.php
│   ├── footer.php
│   └── header.php
│
├── assets/
│   ├── css/
│   │   └── style.css
│   ├── js/
│   └── uploads/
│
├── dashboard.php
├── complaints.php
├── register_complaint.php
├── view_complaint.php
├── manage_users.php
├── manage_categories.php
├── reports.php
├── login.php
├── logout.php
└── index.php
```

---

## 🗄️ Database Modules

### Tables Used

#### Users

Stores user information and roles.

#### Complaints

Stores complaint details and status.

#### Complaint Categories

Stores complaint category information.

#### Actions / Timeline

Stores complaint status history.

#### Areas

Stores Zone, Sector, and Spot information.

#### Status Master

Stores complaint status values.

---

## 🔄 Complaint Lifecycle

```text
Submitted
    ↓
Verified
    ↓
Assigned
    ↓
In Progress
    ↓
Resolved
    ↓
Closed

(Optional)
Closed → Reopened → Assigned Again
```

---

## 📊 SLA Monitoring

The system tracks:

* Initial Response Time
* Resolution Time
* SLA Breach Detection

Automatic alerts are displayed when SLA deadlines are exceeded.

---

## 🔐 Security Features

* Session-Based Authentication
* Role-Based Authorization
* Prepared Statements
* Input Validation
* File Upload Validation

---

## 🌟 Special Features

* Complaint Timeline Tracking
* AJAX-Based Dynamic Forms
* Dynamic Area Selection
* Repeated Complaint Detection
* Complaint Reopening
* File Upload Support
* SLA Monitoring
* Dashboard Analytics
* Dark Mode Support

---

## ⚙️ Installation Guide

### Step 1: Install XAMPP

Download and install XAMPP.

### Step 2: Move Project

Copy the project folder to:

```text
C:\xampp\htdocs\
```

### Step 3: Start Services

Start:

* Apache
* MySQL

### Step 4: Create Database

Open:

```text
http://localhost/phpmyadmin
```

Create Database:

```sql
complaint_system
```

### Step 5: Import Database

Import the provided SQL file.

### Step 6: Configure Database Connection

Edit:

```php
config/db.php
```

Example:

```php
$host = "localhost";
$user = "root";
$password = "";
$database = "complaint_system";
```

### Step 7: Run Application

Open:

```text
http://localhost/complaint-system
```

---

## 👨‍💻 Author

**Chirag Khandala**
B.E. Computer Engineering
Government Engineering College, Bhavnagar

---

## 📄 License

This project is developed for academic and educational purposes.
