<?php
/**
 * q(): compacte mysqli helper met prepared statements.
 * - Zonder $params: direct query.
 * - Met $params: bind_param met automatische types (i/d/s).
 * Return: mysqli_result bij SELECT, anders true.
 */
function q(mysqli $conn, string $sql, array $params = []) {
    // Geen parameters → gewone query
    if (empty($params)) {
        $res = $conn->query($sql);
        if ($res === false) { die("dbq"); } // fout bij query
        return $res;
    }

    // Met parameters → prepared statement
    $stmt = $conn->prepare($sql);
    if (!$stmt) { die("prep"); } // voorbereiden mislukt

    // Types bepalen: int=i, float=d, anders string=s
    $types = "";
    $vals  = [];
    foreach ($params as $p) {
        if (is_int($p))        { $types .= "i"; }
        elseif (is_float($p))  { $types .= "d"; }
        else                   { $types .= "s"; }
        $vals[] = $p;
    }

    // Waarden binden en uitvoeren
    $stmt->bind_param($types, ...$vals);
    if (!$stmt->execute()) { die("exec"); } // uitvoeren mislukt

    // Resultaat ophalen (alleen bij SELECT)
    $res = $stmt->get_result();
    return $res ?: true; // SELECT → result, anders true
}

