<?php
class DBController {
    private $host = "localhost";
    private $user = "root";
    private $password = "";
    private $database = "inventory_management_system";
    private $conn;

    // Constructor - establishes database connection
    public function __construct() {
        $this->conn = $this->connectDB();
    }

    // Connect to database
    private function connectDB() {
        $conn = mysqli_connect($this->host, $this->user, $this->password, $this->database);
        
        if (!$conn) {
            die("Connection failed: " . mysqli_connect_error());
        }
        
        mysqli_set_charset($conn, "utf8mb4");
        return $conn;
    }

    // Get connection object
    public function getConnection() {
        return $this->conn;
    }

    // Execute a query and return results
    public function runQuery($query) {
        $result = mysqli_query($this->conn, $query);
        
        if (!$result) {
            die("Query failed: " . mysqli_error($this->conn));
        }
        
        return $result;
    }

    // Insert data into any table
    public function insert($table, $data) {
        $columns = implode(", ", array_keys($data));
        $values = "'" . implode("', '", array_map(function($value) {
            return mysqli_real_escape_string($this->conn, $value);
        }, array_values($data))) . "'";
        
        $query = "INSERT INTO $table ($columns) VALUES ($values)";
        
        if (mysqli_query($this->conn, $query)) {
            return mysqli_insert_id($this->conn);
        }
        
        return false;
    }

    // Update data in any table
    public function update($table, $data, $where) {
        $set = [];
        foreach ($data as $key => $value) {
            $escapedValue = mysqli_real_escape_string($this->conn, $value);
            $set[] = "$key = '$escapedValue'";
        }
        $setString = implode(", ", $set);
        
        $query = "UPDATE $table SET $setString WHERE $where";
        
        return mysqli_query($this->conn, $query);
    }

    // Delete data from any table
    public function delete($table, $where) {
        $query = "DELETE FROM $table WHERE $where";
        return mysqli_query($this->conn, $query);
    }

    // Select data from any table
    public function select($table, $columns = "*", $where = "", $orderBy = "") {
        $query = "SELECT $columns FROM $table";
        
        if ($where != "") {
            $query .= " WHERE $where";
        }
        
        if ($orderBy != "") {
            $query .= " ORDER BY $orderBy";
        }
        
        $result = mysqli_query($this->conn, $query);
        
        if (!$result) {
            return false;
        }
        
        return $result;
    }

    // Get single row as associative array
    public function getRow($table, $where = "") {
        $result = $this->select($table, "*", $where);
        
        if ($result && mysqli_num_rows($result) > 0) {
            return mysqli_fetch_assoc($result);
        }
        
        return null;
    }

    // Get all rows as associative array
    public function getRows($table, $where = "", $orderBy = "") {
        $result = $this->select($table, "*", $where, $orderBy);
        $rows = [];
        
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $rows[] = $row;
            }
        }
        
        return $rows;
    }

    // Count rows in table
    public function countRows($table, $where = "") {
        $query = "SELECT COUNT(*) as total FROM $table";
        
        if ($where != "") {
            $query .= " WHERE $where";
        }
        
        $result = mysqli_query($this->conn, $query);
        
        if ($result) {
            $row = mysqli_fetch_assoc($result);
            return $row['total'];
        }
        
        return 0;
    }

    // Check if record exists
    public function exists($table, $where) {
        return $this->countRows($table, $where) > 0;
    }

    // Escape string to prevent SQL injection
    public function escape($string) {
        return mysqli_real_escape_string($this->conn, $string);
    }

    // Begin transaction
    public function beginTransaction() {
        mysqli_begin_transaction($this->conn);
    }

    // Commit transaction
    public function commit() {
        mysqli_commit($this->conn);
    }

    // Rollback transaction
    public function rollback() {
        mysqli_rollback($this->conn);
    }

    // Close database connection
    public function closeConnection() {
        if ($this->conn) {
            mysqli_close($this->conn);
        }
    }

    // Destructor - closes connection
    public function __destruct() {
        $this->closeConnection();
    }
}
?>