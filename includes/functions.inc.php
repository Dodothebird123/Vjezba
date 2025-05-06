<?php
function dodaj_koktel($pdo, $naziv, $opis, $tip, $cijena) {
    $stmt = $pdo->prepare("INSERT INTO Koktel (naziv, opis, tip, cijena) VALUES (?, ?, ?, ?)");
    return $stmt->execute([$naziv, $opis, $tip, $cijena]);
}

function dodaj_sastojak($pdo, $naziv, $jedinica, $cijena) {
    $stmt = $pdo->prepare("INSERT INTO Sastojak (naziv, jedinica_mjere, cijena) VALUES (?, ?, ?)");
    return $stmt->execute([$naziv, $jedinica, $cijena]);
}

function dodaj_sastojak_koktelu($pdo, $koktel_id, $sastojak_id, $kolicina) {
    // Check if relationship exists
    $stmt = $pdo->prepare("SELECT * FROM KoktelSastojak WHERE KoktelID = ? AND SastojakID = ?");
    $stmt->execute([$koktel_id, $sastojak_id]);
    
    if ($stmt->rowCount() > 0) {
        // Update existing
        $update = $pdo->prepare("UPDATE KoktelSastojak SET kolicina = kolicina + ? 
                                WHERE KoktelID = ? AND SastojakID = ?");
        return $update->execute([$kolicina, $koktel_id, $sastojak_id]);
    } else {
        // Insert new
        $insert = $pdo->prepare("INSERT INTO KoktelSastojak (KoktelID, SastojakID, kolicina) 
                                VALUES (?, ?, ?)");
        return $insert->execute([$koktel_id, $sastojak_id, $kolicina]);
    }
}

function obrisi_koktel($pdo, $id) {
    $stmt = $pdo->prepare("DELETE FROM Koktel WHERE IDKoktel = ?");
    return $stmt->execute([$id]);
}

function obrisi_sastojak($pdo, $id) {
    $stmt = $pdo->prepare("DELETE FROM Sastojak WHERE IDSastojak = ?");
    return $stmt->execute([$id]);
}

function obrisi_sastojak_koktelu($pdo, $sastojak_id, $koktel_id) {
    $stmt = $pdo->prepare("DELETE FROM KoktelSastojak WHERE SastojakID = ? AND KoktelID = ?");
    return $stmt->execute([$sastojak_id, $koktel_id]);
}

function dohvati_koktele($pdo) {
    $stmt = $pdo->query("SELECT * FROM Koktel ORDER BY naziv");
    return $stmt->fetchAll();
}

function dohvati_sastojke_koktela($pdo, $koktel_id) {
    $stmt = $pdo->prepare("
        SELECT s.IDSastojak, s.naziv, s.jedinica_mjere, ks.kolicina 
        FROM KoktelSastojak ks 
        JOIN Sastojak s ON ks.SastojakID = s.IDSastojak 
        WHERE ks.KoktelID = ?
        ORDER BY s.naziv
    ");
    $stmt->execute([$koktel_id]);
    return $stmt->fetchAll();
}

function dohvati_sve_sastojke($pdo) {
    $stmt = $pdo->query("SELECT * FROM Sastojak ORDER BY naziv");
    return $stmt->fetchAll();
}
?>