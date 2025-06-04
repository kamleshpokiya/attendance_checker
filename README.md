# Employee Attendance Validator

A Laravel app to compare paid hours vs. actual worked hours from Excel files.

## 🚀 Getting Started

1. Clone the repo
2. Run `composer install`
3. Copy `.env.example` to `.env` and set DB credentials
4. Start server: `php artisan serve`

## 📂 Usage

-   Upload Employee and Attendance Excel files.
-   View results in JSON.

## 📝 Sample Files

-   [employees.xlsx](samples/employees.xlsx)
-   [attendance.xlsx](samples/attendance.xlsx)

---

## 📂 File Format

### 1. **Employee File**

| employee_id | name       | paid_hours |
| ----------- | ---------- | ---------- |
| E001        | John Doe   | 40         |
| E002        | Jane Smith | 30         |

### 2. **Attendance File**

| employee_id | date       | check_in | check_out |
| ----------- | ---------- | -------- | --------- |
| E001        | 2024-06-01 | 09:00    | 17:00     |
| E002        | 2024-06-01 | 10:00    | 16:00     |

---

📦 Packages Used

Laravel – framework
maatwebsite/excel – for reading Excel files