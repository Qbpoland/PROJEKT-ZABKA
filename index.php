<?php
require 'vendor/autoload.php';

$app = new \Slim\App;

function connect_db() {
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "zabka";

    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    return $conn;
}

$app->get('/sklepy[/{param}]', function ($request, $response, $args) {
    $conn = connect_db();
    
    // Sprawdź, czy parametr został przekazany
    $param = isset($args['param']) ? $args['param'] : null;

    if ($param) {
        // Jeśli parametr jest dostępny, wykonaj zapytanie z filtrem
        $param1 = "%some value%"; // Zakładam, że chcesz wyszukać ten ciąg znaków

        // Sprawdź, czy parametr jest liczbą (id sklepu)
        if (is_numeric($param)) {
            $stmt = $conn->prepare("SELECT * FROM sklepy WHERE id = ?");
            $stmt->bind_param("i", $param);
        } 
        // Sprawdź, czy parametr jest liczbą (ilość pracowników)
        elseif (strpos($param, 'pracownicy') !== false) {
            $stmt = $conn->prepare("SELECT * FROM sklepy WHERE lb_pracownikow = ?");
            $param = filter_var($param, FILTER_SANITIZE_NUMBER_INT);
            $stmt->bind_param("i", $param);
        } 
        // Sprawdź, czy parametr jest liczbą (id dzielnicy)
        elseif (strpos($param, 'dzielnica') !== false) {
            $stmt = $conn->prepare("SELECT * FROM sklepy WHERE id_dzielnice = ?");
            $param = filter_var($param, FILTER_SANITIZE_NUMBER_INT);
            $stmt->bind_param("i", $param);
        } 
        // Jeśli nie jest liczbą, traktuj jako ogólny filtr
        else {
            $stmt = $conn->prepare("SELECT * FROM sklepy WHERE nazwa LIKE ? OR wlasciciel LIKE ? OR adres LIKE ?");
            $stmt->bind_param("sss", $param1, $param, $param);
        }

        // Wykonaj zapytanie
        $stmt->execute();

        $result = $stmt->get_result();
        $data = [];

        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }

        $stmt->close();
    } else {
        // Jeśli parametr nie jest dostępny, zwróć całą tabelę
        $stmt = $conn->prepare("SELECT * FROM sklepy");

        // Wykonaj zapytanie
        $stmt->execute();

        $result = $stmt->get_result();
        $data = [];

        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }

        $stmt->close();
    }

    $conn->close();

    return $response->withJson($data);
});

$app->run();
