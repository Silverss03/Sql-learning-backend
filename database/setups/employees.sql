CREATE TABLE employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    department VARCHAR(50),
    salary DECIMAL(10,2),
    hire_date DATE
);

CREATE TABLE departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    manager_id INT
);

INSERT INTO employees VALUES
(1, 'John Doe', 'Engineering', 75000.00, '2020-01-15'),
(2, 'Jane Smith', 'Marketing', 65000.00, '2019-06-10'),
(3, 'Bob Johnson', 'Engineering', 80000.00, '2021-03-22');

INSERT INTO departments VALUES
(1, 'Engineering', 3),
(2, 'Marketing', 2);
