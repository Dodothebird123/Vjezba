<?php
session_start();
require_once 'includes/dbh.inc.php';
require_once 'includes/functions.inc.php';

// Process forms
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['dodaj_koktel'])) {
        $naziv = htmlspecialchars($_POST['naziv']);
        $opis = htmlspecialchars($_POST['opis']);
        $tip = htmlspecialchars($_POST['tip']);
        $cijena = floatval($_POST['cijena']);
        
        if (dodaj_koktel($pdo, $naziv, $opis, $tip, $cijena)) {
            $_SESSION['poruka'] = "Koktel '$naziv' je uspješno dodan!";
        } else {
            $_SESSION['greska'] = "Greška pri dodavanju koktela!";
        }
    }
    
    if (isset($_POST['dodaj_sastojak'])) {
        $naziv = htmlspecialchars($_POST['naziv']);
        $jedinica = htmlspecialchars($_POST['jedinica']);
        $cijena = floatval($_POST['cijena']);
        
        if (dodaj_sastojak($pdo, $naziv, $jedinica, $cijena)) {
            $_SESSION['poruka'] = "Sastojak '$naziv' je uspješno dodan!";
        } else {
            $_SESSION['greska'] = "Greška pri dodavanju sastojka!";
        }
    }
    
    if (isset($_POST['dodaj_sastojak_koktelu'])) {
        $koktel_id = intval($_POST['koktel_id']);
        $sastojak_id = intval($_POST['sastojak_id']);
        $kolicina = floatval($_POST['kolicina']);
        
        if (dodaj_sastojak_koktelu($pdo, $koktel_id, $sastojak_id, $kolicina)) {
            $_SESSION['poruka'] = "Sastojak je uspješno dodan koktelu!";
        } else {
            $_SESSION['greska'] = "Greška pri dodavanju sastojka koktelu!";
        }
    }
}

// Process deletions
if (isset($_GET['delete_koktel'])) {
    $id = intval($_GET['delete_koktel']);
    if (obrisi_koktel($pdo, $id)) {
        $_SESSION['poruka'] = "Koktel je uspješno obrisan!";
    } else {
        $_SESSION['greska'] = "Greška pri brisanju koktela!";
    }
    header("Location: index.php");
    exit();
}

if (isset($_GET['delete_sastojak'])) {
    $id = intval($_GET['delete_sastojak']);
    if (obrisi_sastojak($pdo, $id)) {
        $_SESSION['poruka'] = "Sastojak je uspješno obrisan!";
    } else {
        $_SESSION['greska'] = "Greška pri brisanju sastojka!";
    }
    header("Location: index.php");
    exit();
}

if (isset($_GET['delete_sastojak_koktelu'])) {
    $sastojak_id = intval($_GET['delete_sastojak_koktelu']);
    $koktel_id = intval($_GET['koktel_id']);
    if (obrisi_sastojak_koktelu($pdo, $sastojak_id, $koktel_id)) {
        $_SESSION['poruka'] = "Sastojak je uklonjen iz koktela!";
    } else {
        $_SESSION['greska'] = "Greška pri uklanjanju sastojka!";
    }
    header("Location: index.php");
    exit();
}

// Fetch data for display
$kokteli = dohvati_koktele($pdo);
$svi_sastojci = dohvati_sve_sastojke($pdo);
?>

<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kokteli - Recepti</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; padding-top: 20px; }
        .card { transition: all 0.3s; margin-bottom: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .card:hover { transform: translateY(-5px); box-shadow: 0 5px 15px rgba(0,0,0,0.2); }
        footer { margin-top: 50px; padding: 20px 0; background-color: #343a40; }
        .alert { margin-top: 20px; }
        .sastojci-container {
            max-height: 300px;
            overflow-y: auto;
            margin-bottom: 15px;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 10px;
        }
        .list-group-item { 
            display: flex; 
            justify-content: space-between; 
            align-items: center;
            padding: 8px 12px;
        }
        .badge { cursor: pointer; }
        .input-group { margin-bottom: 10px; }
        .btn-danger { margin-top: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <!-- Notifications -->
        <?php if (isset($_SESSION['poruka'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?= $_SESSION['poruka'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['poruka']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['greska'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?= $_SESSION['greska'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['greska']); ?>
        <?php endif; ?>

        <!-- Navigation -->
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4 rounded">
            <div class="container-fluid">
                <a class="navbar-brand" href="index.php">Kokteli</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav">
                        <li class="nav-item">
                            <a class="nav-link active" href="index.php">Početna</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#koktelModal">Dodaj koktel</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#sastojakModal">Dodaj sastojak</a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

        <!-- Cocktails display -->
        <h1 class="mb-4">Naši kokteli</h1>
        <div class="row">
            <?php if (!empty($kokteli)): ?>
                <?php foreach ($kokteli as $koktel): ?>
                    <?php 
                    $sastojci = dohvati_sastojke_koktela($pdo, $koktel['IDKoktel']);
                    $broj_sastojaka = count($sastojci);
                    ?>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($koktel['naziv']) ?></h5>
                                <h6 class="card-subtitle mb-2 text-muted"><?= htmlspecialchars($koktel['tip']) ?></h6>
                                <p class="card-text"><?= htmlspecialchars($koktel['opis']) ?></p>
                                <p class="card-text"><strong>Cijena: <?= number_format($koktel['cijena'], 2) ?> kn</strong></p>
                                
                                <?php if ($broj_sastojaka > 0): ?>
                                    <h6>Sastojci (<?= $broj_sastojaka ?>):</h6>
                                    <div class="sastojci-container">
                                        <ul class="list-group list-group-flush">
                                            <?php foreach ($sastojci as $sastojak): ?>
                                                <li class="list-group-item">
                                                    <?= htmlspecialchars($sastojak['kolicina']) ?> 
                                                    <?= htmlspecialchars($sastojak['jedinica_mjere']) ?> 
                                                    <?= htmlspecialchars($sastojak['naziv']) ?>
                                                    <a href="?delete_sastojak_koktelu=<?= $sastojak['IDSastojak'] ?>&koktel_id=<?= $koktel['IDKoktel'] ?>" 
                                                       class="badge bg-danger rounded-pill" 
                                                       title="Ukloni sastojak"
                                                       onclick="return confirm('Jeste li sigurni da želite ukloniti ovaj sastojak?')">X</a>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-warning py-2">Ovaj koktel nema dodanih sastojaka.</div>
                                <?php endif; ?>
                                
                                <!-- Form for adding ingredients to cocktail -->
                                <form method="post" class="mt-3">
                                    <input type="hidden" name="koktel_id" value="<?= $koktel['IDKoktel'] ?>">
                                    <div class="input-group">
                                        <select name="sastojak_id" class="form-select" required>
                                            <option value="">Odaberi sastojak...</option>
                                            <?php foreach ($svi_sastojci as $sastojak): ?>
                                                <option value="<?= $sastojak['IDSastojak'] ?>">
                                                    <?= htmlspecialchars($sastojak['naziv']) ?> 
                                                    (<?= number_format($sastojak['cijena'], 2) ?> kn/<?= htmlspecialchars($sastojak['jedinica_mjere']) ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="input-group mt-2">
                                        <input type="number" step="0.01" min="0.01" name="kolicina" 
                                               class="form-control" placeholder="Količina" required>
                                        <button type="submit" name="dodaj_sastojak_koktelu" class="btn btn-primary">
                                            Dodaj sastojak
                                        </button>
                                    </div>
                                </form>
                                
                                <a href="?delete_koktel=<?= $koktel['IDKoktel'] ?>" 
                                   class="btn btn-danger mt-3 w-100"
                                   onclick="return confirm('Jeste li sigurni da želite obrisati ovaj koktel?')">
                                    Obriši koktel
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-info">Nema dostupnih koktela. Dodajte novi koktel.</div>
                </div>
            <?php endif; ?>
        </div>

        <!-- All ingredients display -->
        <h2 class="mt-5 mb-3">Svi sastojci</h2>
        <div class="row">
            <?php if (!empty($svi_sastojci)): ?>
                <?php foreach ($svi_sastojci as $sastojak): ?>
                    <div class="col-md-3 mb-3">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($sastojak['naziv']) ?></h5>
                                <p class="card-text">
                                    <?= number_format($sastojak['cijena'], 2) ?> kn / <?= htmlspecialchars($sastojak['jedinica_mjere']) ?>
                                </p>
                                <a href="?delete_sastojak=<?= $sastojak['IDSastojak'] ?>" 
                                   class="btn btn-sm btn-danger"
                                   onclick="return confirm('Jeste li sigurni da želite obrisati ovaj sastojak?')">
                                    Obriši
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-info">Nema dostupnih sastojaka. Dodajte novi sastojak.</div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal for adding cocktail -->
    <div class="modal fade" id="koktelModal" tabindex="-1" aria-labelledby="koktelModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="koktelModalLabel">Dodaj novi koktel</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="naziv" class="form-label">Naziv koktela</label>
                            <input type="text" class="form-control" id="naziv" name="naziv" required>
                        </div>
                        <div class="mb-3">
                            <label for="opis" class="form-label">Opis</label>
                            <textarea class="form-control" id="opis" name="opis" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="tip" class="form-label">Tip</label>
                            <select class="form-select" id="tip" name="tip" required>
                                <option value="">Odaberi tip...</option>
                                <option value="Alkoholni">Alkoholni</option>
                                <option value="Bezalkoholni">Bezalkoholni</option>
                                <option value="Voćni">Voćni</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="cijena" class="form-label">Cijena (kn)</label>
                            <input type="number" step="0.01" min="0.01" class="form-control" id="cijena" name="cijena" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Odustani</button>
                        <button type="submit" name="dodaj_koktel" class="btn btn-primary">Spremi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal for adding ingredient -->
    <div class="modal fade" id="sastojakModal" tabindex="-1" aria-labelledby="sastojakModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="sastojakModalLabel">Dodaj novi sastojak</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="naziv_sastojka" class="form-label">Naziv sastojka</label>
                            <input type="text" class="form-control" id="naziv_sastojka" name="naziv" required>
                        </div>
                        <div class="mb-3">
                            <label for="jedinica" class="form-label">Jedinica mjere (ml, g, komad...)</label>
                            <input type="text" class="form-control" id="jedinica" name="jedinica" required>
                        </div>
                        <div class="mb-3">
                            <label for="cijena_sastojka" class="form-label">Cijena po jedinici (kn)</label>
                            <input type="number" step="0.01" min="0.01" class="form-control" id="cijena_sastojka" name="cijena" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Odustani</button>
                        <button type="submit" name="dodaj_sastojak" class="btn btn-primary">Spremi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="text-white">
        <div class="container py-4">
            <div class="row">
                <div class="col-md-6">
                    <h5>O nama</h5>
                    <p>Stranica s receptima za najbolje koktele.</p>
                </div>
                <div class="col-md-6">
                    <h5>Kontakt</h5>
                    <p>email: info@kokteli.com</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>