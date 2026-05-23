<?php

    class Connection {
        private $DBHOST = "localhost:3306";
        private $DBUSER = "root";
        private $DBPASS = "1234";
        private $DBNAME = "mlsi_nipt";

        public function getConnection() {
            $dsn = 'mysql:host=' . $this->DBHOST . ';dbname=' . $this->DBNAME;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ];
    
            try {
                $pdo = new PDO($dsn, $this->DBUSER, $this->DBPASS, $options);
                return $pdo;
            } catch (PDOException $e) {
                echo 'Connection failed: ' . $e->getMessage();
                exit;
            }
        }
    }


    // Determine this page
    $currentPage = $_SERVER['PHP_SELF'];
    $currentPage = basename($currentPage);
    // Remove the file extension if present
    $currentPage = str_replace('.php', '', $currentPage);

?>