<?php

namespace kingk85;

class Database {

  // DATABASE PARAMETERS
    public $db_user = "DataBase_Username";
    public $db_password = "DatabasePassword";
    public $db_name = "DataBase_Name";
    public $db_host = "DataBase_Host";
    public $db = null;
 
    //On class instance creation
    function __construct() {
        $this->Connect();
    }

    function __destruct() {
        ;
    }

    //Get a Database link
    public function Connect() {
        $this->db = mysqli_connect($this->db_host, $this->db_user, $this->db_password);
        if ($this->db == FALSE)
            die("DB Connection error");
        $this->ChangeDB($this->db_name);
    }

    //Close the connection
    public function Close() {
        mysqli_close($this->db);
    }

    //Switch the database, chain method
    public function ChangeDB($db_name) {
        $this->db_name = $db_name;
        mysqli_select_db($this->db, $this->db_name) or die("Database " . $this->db_name . " not found");
        return $this;
    }

    //Execute a query, chain support
    public function Execute($query) {
        mysqli_query($this->db, $query);
        return $this;
    }

    public function ExecuteWR($query) {
        $result = mysqli_query($this->db, $query);
        return $result;
    }

    //Get a count from a query
    public function Count($querye) {
        $query = "SELECT COUNT(*) AS count " . $querye;
        $result = $this->ExecuteWR($query);
        $row = mysqli_fetch_array($result);
        $count = $row['count'];
        return $count;
    }

    public function export_csv($result) {
        $header = '';
        $headerOK = 0;
        $rows = '';

        while ($row = mysqli_fetch_array($result)) {
            $rows.='';
            foreach ($row as $key => $value) {
                if (is_numeric($key))
                    continue;

                if ($headerOK == 0) {
                    $header.='"' . $key . '";';
                }
                $rows.= "\"" . str_replace("\"", "", utf8_encode(strtoupper(stripslashes(stripslashes(stripslashes($row[$key])))))) . "\";";
            }
            $headerOK = 1;
            $rows.="\n";
        }

        $header.="\n";
        return $header . $rows;
    }

    public function get_all_row_values($row) {
        $values = array();
        foreach ($row as $key => $value) {
            if (is_numeric($key))
                continue;
            $values[$key] = $row[$key];
        }
        return $values;
    }

    public function dump_table($table_name, $filename, $download_flag) {
        if ($download_flag == true) {
            header('Content-Type: application/download');
            header("Content-Disposition: filename=$filename");
        }

        $creation_query = $this->get_table_structure_query($table_name);

        $query = "select * from $table_name";

        $result = $this->ExecuteWR($query);

        $file = fopen($filename, "w");

        $creation_query .= "\r\n";

        fwrite($file, $creation_query);

        if ($download_flag == true)
            echo $creation_query;


        while ($row = mysqli_fetch_array($result)) {
            $values = "";
            $vars_list = "";

            foreach ($row as $key => $value) {

                if (is_numeric($key))
                    continue;

                if (strlen($vars_list) > 0) {
                    $vars_list .= ", `$key`";
                } else {
                    $vars_list .= "`$key`";
                }
                if (strlen($values) > 0) {
                    $values .= ", '" . str_replace("\r", "\\r", str_replace("\n", "\\n", addslashes($row[$key]))) . "'";
                } else {
                    $values .= "'" . str_replace("\r", "\\r", str_replace("\n", "\\n", addslashes($row[$key]))) . "'";
                }
            }

            $query_bk = "INSERT INTO $table_name ($vars_list) VALUES ($values);\r\n";

            if ($download_flag == true)
                echo $query_bk;

            fwrite($file, $query_bk);
        }
        fclose($file);
    }

    public function dump_db($filename, $download_flag) {

        if ($download_flag == true) {
            header('Content-Type: application/download');
            header("Content-Disposition: filename=$filename");
        }

        $tables = $this->get_all_tables();

        $file = fopen($filename, "w");

        for ($i = 0; $i < count($tables); $i++) {
            $creation_query = $this->get_table_structure_query($tables[$i]);

            $query = "select * from " . $tables[$i];

            $result = $this->ExecuteWR($query);

            $creation_query .= "\r\n";

            fwrite($file, $creation_query);

            if ($download_flag == true)
                echo $creation_query;


            while ($row = mysqli_fetch_array($result)) {
                $values = "";
                $vars_list = "";

                foreach ($row as $key => $value) {

                    if (is_numeric($key))
                        continue;

                    if (strlen($vars_list) > 0) {
                        $vars_list .= ", `$key`";
                    } else {
                        $vars_list .= "`$key`";
                    }
                    if (strlen($values) > 0) {
                        $values .= ", '" . str_replace("\r", "\\r", str_replace("\n", "\\n", addslashes(stripslashes($row[$key])))) . "'";
                    } else {
                        $values .= "'" . str_replace("\r", "\\r", str_replace("\n", "\\n", addslashes(stripslashes($row[$key])))) . "'";
                    }
                }


                $query_bk = "INSERT INTO " . $tables[$i] . " ($vars_list) VALUES ($values);\r\n";

                fwrite($file, $query_bk);

                if ($download_flag == true)
                    echo $query_bk;
            }
        }
        fclose($file);
    }

    public function execute_sql_file($sql_file) {
        $query = "";
        $file_curs = 0;
        $file_size = filesize($sql_file);
        $file = fopen($sql_file, 'r');
        //echo "Opening $sql_file ($file_size bytes)<br>";
        while ($file_curs < $file_size) {
            $file_curs++;
            $theData = fread($file, 1);

            if ($theData[0] != "\r" && $theData[0] != "\n")
                $query .= $theData;

            if ($theData[0] == "\r") {

                $this->Execute($query);
                $query = "";
            }
        }

        fclose($file);
    }

    public function show_fulltable($result) {
        $header = '<tr>';
        $headerOK = 0;
        $rows = '';

        while ($row = mysqli_fetch_array($result)) {
            $rows.='<tr>';
            foreach ($row as $key => $value) {
                if (is_numeric($key))
                    continue;
                if ($headerOK == 0) {
                    $header.='<td> ' . $key . ' </td>';
                }

                $rows.= "<td>" . strtoupper($row[$key]) . "</td>";
            }
            $headerOK = 1;
            $rows.='</tr>';
        }


        $header.='</tr>';

        return '<table>' . $header . $rows . '</table>';
    }

    //ECHO A LIST OF ALL TABLES
    public function show_all_databases() {
        $res = mysqli_query("SHOW DATABASES", $this->db);
        while ($row = mysqli_fetch_assoc($res)) {
            echo "<br> -" . $row['Database'] . "\n";
        }
    }

    //GET AN ARRAY OF ALL DATABASES
    public function get_all_databases() {
        $dbarray = array();
        $res = $this->ExecuteWR("SHOW DATABASES");
        while ($row = mysqli_fetch_assoc($res)) {
            $dbarray[] = $row['Database'];
        }
        return $dbarray;
    }

    //PRINT A LIST OF ALL AVAILABLE TABLES
    public function show_all_tables() {
        $sql = "SHOW TABLES FROM $this->db_name";
        $result = $this->ExecuteWR($sql);
        if (!$result) {
            echo "DB Error, could not list tables\n";
            echo 'MySQL Error: ' . mysqli_error();
            exit;
        }
        while ($row = mysqli_fetch_row($result)) {
            echo "<br> -  {$row[0]} ";
        }
        mysqli_free_result($result);
    }

//GET AN ARRAY OF ALL TABLES AVAILABLE
    public function get_all_tables() {
        $tabarray = array();
        $sql = "SHOW TABLES FROM $this->db_name";
        $result = $this->ExecuteWR($sql);
        if (!$result) {
            echo "DB Error, could not list tables\n";
            echo 'MySQL Error: ' . mysqli_error();
            exit;
        }
        while ($row = mysqli_fetch_row($result)) {
            //echo "<br> -  {$row[0]} ";
            $tabarray[] = $row[0];
        }
        mysqli_free_result($result);
        return $tabarray;
    }

    public function get_table_details($table_name) {
        $fieldcount = 0;
        $keys = "";
        $query = "CREATE TABLE IF NOT EXISTS `$table_name` (";
        $result = $this->ExecuteWR("SHOW COLUMNS FROM $table_name");
        if (!$result) {
            echo 'Could not run query: ' . mysqli_error();
            exit;
        }
        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                echo "<br>";
                print_r($row);
                if ($fieldcount > 0)
                    $query .= ",";

                $query .= "`" . $row['Field'] . "`";
                $query .= " " . $row['Type'] . "";

                if ($row['Null'] == "NO")
                    $query .= " NOT NULL ";

                if ($row['Default'] != "")
                    $query .= " DEFAULT '" . $row['Default'] . "' ";

                if ($row['Null'] == "YES" && $row['Extra'] != "auto_increment" && $row['Default'] == "")
                    $query .= " DEFAULT NULL ";

                if ($row['Extra'] == "auto_increment")
                    $query .= " AUTO_INCREMENT ";

                if ($row['Key'] == 'PRI')
                    $keys .= ", PRIMARY KEY (`" . $row['Field'] . "`)";

                if ($row['Key'] == 'MUL')
                    $keys .= ", KEY (`" . $row['Field'] . "`)";

                if ($row['Key'] == 'UNI')
                    $keys .= ", UNIQUE KEY (`" . $row['Field'] . "`)";
                $fieldcount++;
            }
        }
        $query .= "$keys );";
    }

    public function get_table_structure_query($table_name) {
        $fieldcount = 0;
        $keys = "";
        $query = "CREATE TABLE IF NOT EXISTS `$table_name` (";
        $result =  $this->ExecuteWR("SHOW COLUMNS FROM $table_name");
        if (!$result) {
            exit;
        }
        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                //echo "<br>";
                //print_r($row);
                if ($fieldcount > 0)
                    $query .= ",";

                $query .= "`" . $row['Field'] . "`";
                $query .= " " . $row['Type'] . "";

                if ($row['Null'] == "NO")
                    $query .= " NOT NULL ";

                if ($row['Default'] != "")
                    $query .= " DEFAULT '" . $row['Default'] . "' ";

                if ($row['Null'] == "YES" && $row['Extra'] != "auto_increment" && $row['Default'] == "")
                    $query .= " DEFAULT NULL ";

                if ($row['Extra'] == "auto_increment")
                    $query .= " AUTO_INCREMENT ";

                if ($row['Key'] == 'PRI')
                    $keys .= ", PRIMARY KEY (`" . $row['Field'] . "`)";

                if ($row['Key'] == 'MUL')
                    $keys .= ", KEY (`" . $row['Field'] . "`)";

                if ($row['Key'] == 'UNI')
                    $keys .= ", UNIQUE KEY (`" . $row['Field'] . "`)";
                $fieldcount++;
            }
        }
        $query .= "$keys );";
        return $query;
    }

}

?>
