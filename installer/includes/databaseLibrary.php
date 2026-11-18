<?php

class Database {

    function create_database($data) {
        try { $mysqli = new mysqli($data['hostname'], $data['username'], $data['password'], ''); } catch (Throwable $e) { return false; }
        if (mysqli_connect_errno()) return false;

        $mysqli->query("CREATE DATABASE IF NOT EXISTS " . $data['database']);
        while (mysqli_next_result($mysqli));
        $mysqli->close();
        return true;
    }

    function create_tables($data) {
        try { $mysqli = new mysqli($data['hostname'], $data['username'], $data['password'], $data['database']); } catch (Throwable $e) { return false; }
        if (mysqli_connect_errno()) return false;

        $query = $mysqli->query("SHOW TABLES LIKE 'users'");
        if ($query->num_rows <= 0) {
            $query = file_get_contents('../assets/app/db/master.sql');
            $mysqli->multi_query($query);
            while (mysqli_next_result($mysqli)) ;
            $mysqli->close();
        }
        return true;
    }
}
