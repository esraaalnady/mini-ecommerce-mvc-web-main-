<?php
// Database helpers — uses the shared $pdo from init.php

function executeQuery($sql, $params = []) {
    global $pdo;
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        die("خطأ في تنفيذ الاستعلام:" . $e->getMessage());
    }
}

function fetchAll($sql, $params = []) {
    return executeQuery($sql, $params)->fetchAll();
}

function fetchOne($sql, $params = []) {
    return executeQuery($sql, $params)->fetch();
}

function getLastInsertId() {
    global $pdo;
    return $pdo->lastInsertId();
}
