<?php
include 'koneksi.php';

$error = '';

// ==========================================
// A. TAMBAH DATA
// ==========================================
if (isset($_POST['tambah'])) {
    $npm  = trim($_POST['npm'] ?? '');
    $nama = trim($_POST['nama'] ?? '');
    $c1 = $_POST['c1'] ?? '';
    $c2 = $_POST['c2'] ?? '';
    $c3 = $_POST['c3'] ?? '';
    $c4 = $_POST['c4'] ?? '';
    $c5 = $_POST['c5'] ?? '';

    if (
        $npm === '' || $nama === '' ||
        !is_numeric($c1) || !is_numeric($c2) ||
        !is_numeric($c3) || !is_numeric($c4) ||
        !is_numeric($c5) ||
        (float)$c1 < 0 || (float)$c2 < 0 ||
        (float)$c3 < 0 || (float)$c3 > 4 ||
        (float)$c4 < 0 || (float)$c4 > 100 ||
        (float)$c5 < 1 || (float)$c5 > 5
    ) {
        $error = 'Periksa kembali data dan rentang nilai yang dimasukkan.';
    } else {
        $c1 = (float)$c1;
        $c2 = (float)$c2;
        $c3 = (float)$c3;
        $c4 = (float)$c4;
        $c5 = (float)$c5;

        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO mahasiswa (npm,nama,c1,c2,c3,c4,c5)
             VALUES (?,?,?,?,?,?,?)"
        );

        if ($stmt) {
            mysqli_stmt_bind_param(
                $stmt, "ssddddd",
                $npm, $nama, $c1, $c2, $c3, $c4, $c5
            );

            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
                header("Location: index.php#data");
                exit;
            }

            $error = mysqli_stmt_errno($stmt) == 1062
                ? 'NPM sudah terdaftar.'
                : 'Gagal menambahkan data: ' . mysqli_stmt_error($stmt);
            mysqli_stmt_close($stmt);
        } else {
            $error = 'Query tambah data gagal disiapkan.';
        }
    }
}

// ==========================================
// B. UPDATE DATA
// ==========================================
if (isset($_POST['update'])) {
    $id = (int)($_POST['id'] ?? 0);
    $npm = trim($_POST['npm'] ?? '');
    $nama = trim($_POST['nama'] ?? '');
    $c1 = $_POST['c1'] ?? '';
    $c2 = $_POST['c2'] ?? '';
    $c3 = $_POST['c3'] ?? '';
    $c4 = $_POST['c4'] ?? '';
    $c5 = $_POST['c5'] ?? '';

    if (
        $id < 1 || $npm === '' || $nama === '' ||
        !is_numeric($c1) || !is_numeric($c2) ||
        !is_numeric($c3) || !is_numeric($c4) ||
        !is_numeric($c5) ||
        (float)$c1 < 0 || (float)$c2 < 0 ||
        (float)$c3 < 0 || (float)$c3 > 4 ||
        (float)$c4 < 0 || (float)$c4 > 100 ||
        (float)$c5 < 1 || (float)$c5 > 5
    ) {
        $error = 'Data tidak valid. Periksa kembali semua kolom.';
    } else {
        $c1 = (float)$c1;
        $c2 = (float)$c2;
        $c3 = (float)$c3;
        $c4 = (float)$c4;
        $c5 = (float)$c5;

        $stmt = mysqli_prepare(
            $conn,
            "UPDATE mahasiswa
             SET npm=?, nama=?, c1=?, c2=?, c3=?, c4=?, c5=?
             WHERE id=?"
        );

        if ($stmt) {
            mysqli_stmt_bind_param(
                $stmt, "ssdddddi",
                $npm, $nama, $c1, $c2, $c3, $c4, $c5, $id
            );

            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
                header("Location: index.php#data");
                exit;
            }

            $error = mysqli_stmt_errno($stmt) == 1062
                ? 'NPM sudah terdaftar.'
                : 'Gagal mengubah data: ' . mysqli_stmt_error($stmt);
            mysqli_stmt_close($stmt);
        } else {
            $error = 'Query edit data gagal disiapkan.';
        }
    }
}

// ==========================================
// C. HAPUS DATA
// ==========================================
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];

    if ($id > 0) {
        $stmt = mysqli_prepare(
            $conn,
            "DELETE FROM mahasiswa WHERE id=?"
        );

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    }

    header("Location: index.php#data");
    exit;
}

// ==========================================
// D. DATA EDIT
// ==========================================
$edit_data = null;

if (isset($_GET['edit'])) {
    $id_edit = (int)$_GET['edit'];

    if ($id_edit > 0) {
        $stmt = mysqli_prepare(
            $conn,
            "SELECT * FROM mahasiswa WHERE id=?"
        );

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "i", $id_edit);
            mysqli_stmt_execute($stmt);
            $result_edit = mysqli_stmt_get_result($stmt);

            if ($result_edit) {
                $edit_data = mysqli_fetch_assoc($result_edit);
            }

            mysqli_stmt_close($stmt);
        }
    }
}

// ==========================================
// E. AMBIL KRITERIA
// ==========================================
$kriteria = array();

$q_kriteria = mysqli_query(
    $conn,
    "SELECT * FROM kriteria ORDER BY kode"
);

if ($q_kriteria) {
    while ($row = mysqli_fetch_assoc($q_kriteria)) {
        $kriteria[$row['kode']] = array(
            'nama' => $row['nama'],
            'type' => $row['type'],
            'bobot' => (float)$row['bobot']
        );
    }
}

// ==========================================
// F. AMBIL MAHASISWA
// ==========================================
$mahasiswa = array();

$q_mhs = mysqli_query(
    $conn,
    "SELECT * FROM mahasiswa ORDER BY id"
);

if ($q_mhs) {
    while ($row = mysqli_fetch_assoc($q_mhs)) {
        $mahasiswa[] = $row;
    }
}

// ==========================================
// G. PERHITUNGAN SAW
// ==========================================
$hasil_saw = array();

$total_bobot = array_sum(array_column($kriteria, 'bobot'));

$bobot_valid = !empty($kriteria)
    && abs($total_bobot - 1.0) < 0.0001;

if ($bobot_valid && !empty($mahasiswa)) {
    $acuan = array();

    foreach ($kriteria as $kode => $attr) {
        $kolom = strtolower($kode);

        $values = array_map(
            function ($mhs) use ($kolom) {
                return (float)$mhs[$kolom];
            },
            $mahasiswa
        );

        if ($attr['type'] === 'cost') {
            $positif = array_values(
                array_filter(
                    $values,
                    function ($v) {
                        return $v > 0;
                    }
                )
            );

            $acuan[$kode] = !empty($positif)
                ? min($positif)
                : 0;
        } else {
            $acuan[$kode] = max($values);
        }
    }

    foreach ($mahasiswa as $mhs) {
        $nilai_v = 0;

        foreach ($kriteria as $kode => $attr) {
            $kolom = strtolower($kode);
            $nilai = (float)$mhs[$kolom];
            $pembagi = $acuan[$kode];

            if ($attr['type'] === 'benefit') {
                $normalisasi = $pembagi > 0
                    ? $nilai / $pembagi
                    : 0;
            } else {
                $normalisasi = ($nilai > 0 && $pembagi > 0)
                    ? $pembagi / $nilai
                    : 0;
            }

            $nilai_v += $attr['bobot'] * $normalisasi;
        }

        $hasil_saw[] = array(
            'id' => $mhs['id'],
            'npm' => $mhs['npm'],
            'nama' => $mhs['nama'],
            'c1' => $mhs['c1'],
            'c2' => $mhs['c2'],
            'c3' => $mhs['c3'],
            'c4' => $mhs['c4'],
            'c5' => $mhs['c5'],
            'nilai_v' => $nilai_v
        );
    }

    usort($hasil_saw, function ($a, $b) {
        if ($a['nilai_v'] == $b['nilai_v']) {
            return 0;
        }
        return ($a['nilai_v'] < $b['nilai_v']) ? 1 : -1;
    });
}

// ==========================================
// H. FUNGSI TAMPILAN
// ==========================================
function e($nilai) {
    return htmlspecialchars((string)$nilai, ENT_QUOTES, 'UTF-8');
}

function nilai_form($data, $kolom) {
    return e($data[$kolom] ?? '');
}

$total_mahasiswa = count($mahasiswa);
$total_kriteria = count($kriteria);
$nilai_tertinggi = !empty($hasil_saw)
    ? $hasil_saw[0]['nilai_v']
    : 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>BeasiswaSmart | SPK SAW</title>

<style>
:root {
    --lime: #B7FF00;
    --lime-dark: #65A000;
    --lime-light: #DFFF80;
    --lime-pale: #F5FFD6;
    --white: #FFFFFF;
    --text: #26330A;
    --muted: #60752E;
    --border: #B7FF00;
    --green: #39FF14;
}

* {
    box-sizing: border-box;
}

html {
    scroll-behavior: smooth;
}

body {
    margin: 0;
    background: #FFF5F9;
    font-family: "Segoe UI", Arial, sans-serif;
    color: var(--text);
}

a {
    color: inherit;
}

.navbar {
    background: white;
    border-bottom: 1px solid var(--border);
    position: sticky;
    top: 0;
    z-index: 10;
}

.nav-inner {
    max-width: 1250px;
    margin: auto;
    padding: 15px 25px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 18px;
}

.logo {
    display: flex;
    align-items: center;
    gap: 10px;
    font-weight: 800;
    font-size: 19px;
    color: var(--pink-dark);
}

.logo-icon {
    display: flex;
    width: 39px;
    height: 39px;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
    color: white;
    background: var(--pink);
    font-size: 17px;
}

.logo small {
    display: block;
    font-size: 10px;
    color: var(--muted);
    font-weight: 500;
    margin-top: 3px;
}

.nav-links {
    display: flex;
    gap: 21px;
    flex-wrap: wrap;
    font-size: 12px;
    font-weight: 600;
}

.nav-links a {
    text-decoration: none;
    color: #796472;
}

.nav-links a:hover {
    color: var(--pink);
}

.container {
    max-width: 1250px;
    margin: auto;
    padding: 27px 25px;
}

.hero {
    min-height: 235px;
    background: linear-gradient(120deg, #527A00 0%, #80B918 55%, #B7FF00 100%) !important;
    color: #FFFFFF !important;
    border-radius: 22px;
    padding: 35px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    position: relative;
    overflow: hidden;
    box-shadow: 0 12px 30px rgba(82, 122, 0, 0.18);
}

.hero h1 {
    color: #FFFFFF !important;
}

.hero p {
    color: #F7FFE8 !important;
}

.hero:after {
    content: "";
    position: absolute;
    width: 300px;
    height: 300px;
    border: 45px solid rgba(255,255,255,.10);
    border-radius: 50%;
    right: -50px;
    top: -130px;
}

.hero-content {
    position: relative;
    z-index: 1;
    max-width: 650px;
}

.eyebrow {
    display: inline-block;
    background: rgba(255,255,255,.19);
    padding: 7px 11px;
    border-radius: 7px;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 1px;
}

.hero h1 {
    font-size: clamp(27px, 4vw, 39px);
    line-height: 1.2;
    margin: 16px 0 12px;
}

.hero p {
    font-size: 13px;
    line-height: 1.8;
    max-width: 560px;
    color: #FFF3F8;
}

.hero-icon {
    width: 125px;
    height: 125px;
    flex-shrink: 0;
    border: 1px solid rgba(255,255,255,.35);
    background: rgba(255,255,255,.16);
    border-radius: 25px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 42px;
    font-weight: 800;
    position: relative;
    z-index: 1;
}

.stats {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 15px;
    margin: 22px 0;
}

.stat {
    background: white;
    border: 1px solid var(--border);
    border-radius: 15px;
    padding: 20px;
    box-shadow: 0 5px 18px rgba(91,42,67,.04);
}

.stat-label {
    font-size: 11px;
    color: var(--muted);
}

.stat-value {
    margin-top: 9px;
    color: var(--pink-dark);
    font-size: 25px;
    font-weight: 800;
}

.stat-desc {
    font-size: 10px;
    color: var(--muted);
    margin-top: 5px;
}

.card {
    background: white;
    border: 1px solid var(--border);
    border-radius: 18px;
    padding: 25px;
    margin-bottom: 22px;
    box-shadow: 0 6px 20px rgba(91,42,67,.04);
    scroll-margin-top: 90px;
}

.section-title {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 7px;
}

.section-number {
    width: 34px;
    height: 34px;
    border-radius: 11px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--pink-light);
    color: var(--pink-dark);
    font-size: 12px;
    font-weight: 800;
}

h2 {
    font-size: 18px;
    margin: 0;
    color: #493440;
}

.subtitle {
    font-size: 12px;
    color: var(--muted);
    margin: 0 0 22px 46px;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 17px;
}

.field {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.field label {
    font-size: 12px;
    font-weight: 700;
}

.field input {
    width: 100%;
    padding: 12px 14px;
    border: 1px solid #EBDDE5;
    border-radius: 9px;
    background: #FFFCFE;
    color: var(--text);
    font-family: inherit;
    font-size: 12px;
}

.field input:focus {
    outline: 2px solid var(--pink-light);
    border-color: var(--pink);
}

.hint {
    font-size: 10px;
    color: var(--muted);
    line-height: 1.5;
}

.actions {
    display: flex;
    flex-wrap: wrap;
    gap: 9px;
    margin-top: 22px;
}

.btn {
    display: inline-block;
    padding: 10px 14px;
    border: 0;
    border-radius: 8px;
    text-decoration: none;
    text-align: center;
    font-family: inherit;
    font-size: 11px;
    font-weight: 700;
    cursor: pointer;
}

.btn-primary {
    background: var(--pink);
    color: white;
}

.btn-primary:hover {
    background: var(--pink-dark);
}

.btn-soft {
    background: var(--pink-light);
    color: var(--pink-dark);
}

.btn-delete {
    background: #FFE6E9;
    color: #B42345;
}

.btn:hover {
    opacity: .85;
}

.notice {
    background: var(--pink-pale);
    border: 1px solid #F4BDD4;
    border-radius: 10px;
    padding: 13px 15px;
    margin-bottom: 18px;
    font-size: 12px;
    overflow-wrap: anywhere;
}

.table-wrap {
    overflow-x: auto;
    width: 100%;
}

table {
    width: 100%;
    border-collapse: collapse;
    font-size: 11px;
    margin-top: 12px;
}

th {
    background: #FFF0F6;
    color: #9D285C;
    font-weight: 800;
    text-transform: uppercase;
    font-size: 10px;
    letter-spacing: .2px;
}

th, td {
    padding: 14px 12px;
    border-bottom: 1px solid #F5E8EF;
    text-align: center;
    white-space: nowrap;
}

tbody tr:hover {
    background: #FFF8FB;
}

.rank-first {
    background: #F0FFF7;
}

.rank-badge {
    display: inline-block;
    min-width: 27px;
    padding: 6px;
    border-radius: 7px;
    background: var(--pink-light);
    color: var(--pink-dark);
    font-weight: 800;
}

.rank-first .rank-badge {
    background: #D9F7E8;
    color: #16764E;
}

.status {
    display: inline-block;
    padding: 6px 9px;
    border-radius: 7px;
    font-size: 10px;
    font-weight: 700;
    background: #F1EAF0;
    color: #756170;
}

.status-first {
    background: #D9F7E8;
    color: #16764E;
}

.empty {
    padding: 25px;
    color: var(--muted);
    text-align: center;
}

.info-box {
    background: #FFF4F8;
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 16px;
    margin-top: 18px;
    font-size: 11px;
    line-height: 1.8;
    color: #765666;
}

footer {
    text-align: center;
    padding: 24px;
    color: var(--muted);
    font-size: 11px;
}

@media (max-width: 800px) {
    .nav-inner {
        align-items: flex-start;
        flex-direction: column;
    }

    .nav-links {
        gap: 14px;
    }

    .stats {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .hero {
        padding: 25px;
    }

    .hero-icon {
        width: 80px;
        height: 80px;
        font-size: 27px;
    }
}

@media (max-width: 560px) {
    .container {
        padding: 18px 13px;
    }

    .hero {
        padding: 22px;
    }

    .hero-icon {
        display: none;
    }

    .form-grid {
        grid-template-columns: 1fr;
    }

    .card {
        padding: 18px 14px;
    }

    .subtitle {
        margin-left: 0;
        line-height: 1.6;
    }

    .nav-inner {
        padding: 13px;
    }

    .nav-links {
        font-size: 11px;
        gap: 12px;
    }
}
</style>
</head>

<body>

<nav class="navbar">
    <div class="nav-inner">
        <div class="logo">
            <div class="logo-icon">SAW</div>
            <div>
                BeasiswaSmart
                <small>Sistem Pendukung Keputusan</small>
            </div>
        </div>

        <div class="nav-links">
            <a href="#dashboard">Dashboard</a>
            <a href="#input">Input Data</a>
            <a href="#data">Data Mahasiswa</a>
            <a href="#ranking">Hasil Ranking</a>
        </div>
    </div>
</nav>

<main class="container">

    <!-- DASHBOARD -->
    <section class="hero" id="dashboard">
        <div class="hero-content">
            <span class="eyebrow">SISTEM SELEKSI BEASISWA</span>
            <h1>Seleksi Beasiswa<br>dengan Metode SAW</h1>
            <p>
                Kelola data calon penerima beasiswa dan lihat hasil
                perankingan berdasarkan kriteria yang telah ditentukan.
                Proses penilaian dilakukan menggunakan metode
                Simple Additive Weighting (SAW).
            </p>
            <a class="btn" href="#input"
               style="background:white;color:#C91663;margin-top:5px;">
                + Tambah Mahasiswa
            </a>
        </div>
        <div class="hero-icon">SAW</div>
    </section>

    <!-- STATISTIK -->
    <section class="stats">
        <div class="stat">
            <div class="stat-label">Total Mahasiswa</div>
            <div class="stat-value"><?= $total_mahasiswa; ?></div>
            <div class="stat-desc">Alternatif penerima beasiswa</div>
        </div>

        <div class="stat">
            <div class="stat-label">Total Kriteria</div>
            <div class="stat-value"><?= $total_kriteria; ?></div>
            <div class="stat-desc">Kriteria penilaian SAW</div>
        </div>

        <div class="stat">
            <div class="stat-label">Total Bobot</div>
            <div class="stat-value">
                <?= number_format($total_bobot * 100, 0, ',', '.'); ?>%
            </div>
            <div class="stat-desc">
                <?= $bobot_valid ? 'Akumulasi bobot valid' : 'Periksa bobot kriteria'; ?>
            </div>
        </div>

        <div class="stat">
            <div class="stat-label">Nilai Tertinggi</div>
            <div class="stat-value">
                <?= !empty($hasil_saw)
                    ? number_format($nilai_tertinggi, 4, ',', '.')
                    : '-'; ?>
            </div>
            <div class="stat-desc">
                <?= !empty($hasil_saw)
                    ? e($hasil_saw[0]['nama'])
                    : 'Belum ada hasil perhitungan'; ?>
            </div>
        </div>
    </section>

    <?php if ($error !== ''): ?>
        <div class="notice"><?= e($error); ?></div>
    <?php endif; ?>

    <!-- INPUT DATA -->
    <section class="card" id="input">
        <div class="section-title">
            <div class="section-number">01</div>
            <h2>
                <?= $edit_data
                    ? 'Edit Data Mahasiswa'
                    : 'Tambah Calon Penerima Beasiswa'; ?>
            </h2>
        </div>

        <p class="subtitle">
            Lengkapi informasi mahasiswa dan nilai setiap kriteria.
        </p>

        <form method="POST" action="index.php#input">
            <?php if ($edit_data): ?>
                <input type="hidden" name="id"
                       value="<?= e($edit_data['id']); ?>">
            <?php endif; ?>

            <div class="form-grid">
                <div class="field">
                    <label>NPM *</label>
                    <input type="text" name="npm" required
                        value="<?= nilai_form($edit_data, 'npm'); ?>"
                        placeholder="Contoh: 24441001">
                </div>

                <div class="field">
                    <label>Nama Mahasiswa *</label>
                    <input type="text" name="nama" required
                        value="<?= nilai_form($edit_data, 'nama'); ?>"
                        placeholder="Masukkan nama lengkap">
                </div>

                <div class="field">
                    <label>C1 — Penghasilan Orang Tua (Cost) *</label>
                    <input type="number" name="c1" min="0"
                        step="0.01" required
                        value="<?= nilai_form($edit_data, 'c1'); ?>"
                        placeholder="Contoh: 2500000">
                    <span class="hint">Semakin rendah penghasilan, semakin diprioritaskan.</span>
                </div>

                <div class="field">
                    <label>C2 — Jumlah Tanggungan (Benefit) *</label>
                    <input type="number" name="c2" min="0"
                        step="1" required
                        value="<?= nilai_form($edit_data, 'c2'); ?>"
                        placeholder="Contoh: 3">
                    <span class="hint">Masukkan jumlah tanggungan keluarga.</span>
                </div>

                <div class="field">
                    <label>C3 — IPK (Benefit) *</label>
                    <input type="number" name="c3" min="0" max="4"
                        step="0.01" required
                        value="<?= nilai_form($edit_data, 'c3'); ?>"
                        placeholder="Contoh: 3.75">
                    <span class="hint">Rentang IPK adalah 0 sampai 4.</span>
                </div>

                <div class="field">
                    <label>C4 — Kehadiran (Benefit) *</label>
                    <input type="number" name="c4" min="0" max="100"
                        step="0.01" required
                        value="<?= nilai_form($edit_data, 'c4'); ?>"
                        placeholder="Contoh: 95">
                    <span class="hint">Masukkan persentase kehadiran.</span>
                </div>

                <div class="field">
                    <label>C5 — Attitude (Benefit) *</label>
                    <input type="number" name="c5" min="1" max="5"
                        step="1" required
                        value="<?= nilai_form($edit_data, 'c5'); ?>"
                        placeholder="Contoh: 4">
                    <span class="hint">Skala penilaian 1 sampai 5.</span>
                </div>
            </div>

            <div class="actions">
                <button class="btn btn-primary" type="submit"
                    name="<?= $edit_data ? 'update' : 'tambah'; ?>">
                    <?= $edit_data ? 'Simpan Perubahan' : '+ Tambah Mahasiswa'; ?>
                </button>

                <?php if ($edit_data): ?>
                    <a class="btn btn-soft" href="index.php#data">Batal</a>
                <?php endif; ?>
            </div>
        </form>
    </section>

    <!-- DATA MASTER -->
    <section class="card" id="data">
        <div class="section-title">
            <div class="section-number">02</div>
            <h2>Data Master Mahasiswa</h2>
        </div>

        <p class="subtitle">
            Daftar alternatif calon penerima beasiswa.
        </p>

        <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>No.</th>
                    <th>NPM</th>
                    <th>Nama Mahasiswa</th>
                    <th>C1 · Cost</th>
                    <th>C2 · Benefit</th>
                    <th>C3 · Benefit</th>
                    <th>C4 · Benefit</th>
                    <th>C5 · Benefit</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($mahasiswa)): ?>
                <tr>
                    <td colspan="9" class="empty">
                        Belum ada data mahasiswa. Silakan tambahkan melalui formulir.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($mahasiswa as $i => $mhs): ?>
                <tr>
                    <td><?= $i + 1; ?></td>
                    <td><?= e($mhs['npm']); ?></td>
                    <td><?= e($mhs['nama']); ?></td>
                    <td>Rp <?= number_format((float)$mhs['c1'], 0, ',', '.'); ?></td>
                    <td><?= e($mhs['c2']); ?></td>
                    <td><?= number_format((float)$mhs['c3'], 2, ',', '.'); ?></td>
                    <td><?= number_format((float)$mhs['c4'], 2, ',', '.'); ?>%</td>
                    <td><?= e($mhs['c5']); ?></td>
                    <td>
                        <a class="btn btn-soft"
                           href="?edit=<?= (int)$mhs['id']; ?>#input">Edit</a>
                        <a class="btn btn-delete"
                           href="?hapus=<?= (int)$mhs['id']; ?>"
                           onclick="return confirm('Yakin ingin menghapus data ini?')">
                           Hapus
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
        </div>
    </section>

    <!-- HASIL RANKING -->
    <section class="card" id="ranking">
        <div class="section-title">
            <div class="section-number">03</div>
            <h2>Hasil Perhitungan &amp; Ranking</h2>
        </div>

        <p class="subtitle">
            Peringkat calon penerima beasiswa berdasarkan nilai preferensi SAW.
        </p>

        <?php if (!$bobot_valid): ?>
            <div class="notice">
                Total bobot kriteria harus sama dengan 1,00.
                Periksa tabel kriteria pada database.
            </div>
        <?php elseif (empty($mahasiswa)): ?>
            <div class="empty">
                Ranking akan muncul setelah data mahasiswa ditambahkan.
            </div>
        <?php else: ?>
            <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Peringkat</th>
                        <th>NPM</th>
                        <th>Nama Mahasiswa</th>
                        <th>Nilai Preferensi (V)</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($hasil_saw as $i => $row): ?>
                    <tr class="<?= $i === 0 ? 'rank-first' : ''; ?>">
                        <td><span class="rank-badge"><?= $i + 1; ?></span></td>
                        <td><?= e($row['npm']); ?></td>
                        <td><?= e($row['nama']); ?></td>
                        <td><strong><?= number_format($row['nilai_v'], 4, ',', '.'); ?></strong></td>
                        <td>
                            <?php if ($i === 0): ?>
                                <span class="status status-first">Peringkat Teratas</span>
                            <?php else: ?>
                                <span class="status">Alternatif</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>

            <div class="info-box">
                <strong>Informasi Perhitungan</strong><br>
                Metode SAW melakukan normalisasi setiap kriteria sesuai
                jenis benefit atau cost. Nilai preferensi diperoleh dari
                penjumlahan hasil normalisasi yang dikalikan bobot masing-masing
                kriteria. Nilai lebih tinggi menunjukkan peringkat lebih tinggi
                berdasarkan bobot dan data yang dimasukkan.
                <br><br>
                <strong>Catatan:</strong> Peringkat teratas merupakan hasil
                perhitungan, bukan keputusan final pemberian beasiswa.
                Penetapan penerima tetap mengikuti aturan seleksi yang berlaku.
            </div>
        <?php endif; ?>
    </section>

</main>

<footer>
    BeasiswaSmart · Sistem Pendukung Keputusan Metode SAW<br>
    <span style="color:#F12D83;">Pink Edition</span>
</footer>

</body>
</html>