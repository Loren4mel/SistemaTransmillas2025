<?php
// class Database {
//   public function connect() {
//     $host = "localhost";
//     $user = "u713516042_jose2";
//     $pass = "Dobarli23@transmillas";
//     $dbname = "u713516042_transmillas2";

//     $conn = new mysqli($host, $user, $pass, $dbname);
//     if ($conn->connect_error) {
//       die("Error de conexión: " . $conn->connect_error);
//     }
//     return $conn;
//   }
// }


class Database {

    private static $conn = null;

    public function connect(): mysqli {

        if (self::$conn === null) {

            $dbname="u713516042_transmillas"; 
            $host="srv912.hstgr.io";
            $user="u713516042_jose";
            $pass="0?jBMSc4GUcN";



            // $host   = "localhost";
            // $user   = "u713516042_jose2";
            // $pass   = "Dobarli23@transmillas";
            // $dbname = "u713516042_transmillas2";

            // $dbname="transmillaslocal"; 
            // $host="localhost";
            // $user="root";
            // $pass="";

            // 👇 Sin named arguments
            self::$conn = new mysqli($host, $user, $pass, $dbname);

            if (self::$conn->connect_error) {
                die("Error de conexión: " . self::$conn->connect_error);
            }

            self::$conn->set_charset("utf8mb4");

            // 🔥 Se registra solo una vez
            register_shutdown_function(function () {
                if (Database::$conn !== null) {
                    Database::$conn->close();
                    Database::$conn = null;
                }
            });
        }

        return self::$conn;
    }
}