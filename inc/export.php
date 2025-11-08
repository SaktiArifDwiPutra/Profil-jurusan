<?php
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/functions.php';
require_role(['admin','guru','siswa']);
$type  = strtolower($_GET['type']  ?? 'csv');
$table = strtolower($_GET['table'] ?? '');

if (!$table) {
    http_response_code(400);
    echo "Table not specified";
    exit;
}

switch ($table) {
    case 'siswa':
        // support single export by id_siswa (keputusan sebelumnya)
        $single_id = (int)($_GET['id_siswa'] ?? 0);
        if ($single_id > 0) {
            $rows = fetch_all(
                "SELECT s.id_siswa, s.nisn, s.nama, s.jenis_kelamin, 
                        k.tingkat, k.kelas, s.no_hp, s.alamat
                 FROM siswa s
                 LEFT JOIN kelas k ON s.id_kelas = k.id_kelas
                 WHERE s.id_siswa = ?
                 ORDER BY s.nama",
                "i",
                [$single_id]
            );
        } else {
            $rows = fetch_all("SELECT s.id_siswa, s.nisn, s.nama, s.jenis_kelamin, 
                                    k.tingkat, k.kelas, s.no_hp, s.alamat
                               FROM siswa s
                               LEFT JOIN kelas k ON s.id_kelas = k.id_kelas
                               ORDER BY s.nama");
        }
        $headers = ['No','NISN','Nama','Jenis Kelamin','Kelas','No HP','Alamat'];
        break;

    case 'guru':
        // SUPPORT SINGLE EXPORT FOR GURU: id_guru param
        $single_gid = (int)($_GET['id_guru'] ?? 0);
        if ($single_gid > 0) {
            $rows = fetch_all(
                "SELECT g.id_guru, g.nip, g.nama, g.jenis_kelamin, g.email, g.no_hp,
                        GROUP_CONCAT(CONCAT(m.nama_mapel,' (',k.tingkat,' ',k.kelas,')') SEPARATOR ', ') AS mapel_diampu
                 FROM guru g
                 LEFT JOIN mapel_diampu md ON g.id_guru = md.id_guru
                 LEFT JOIN mapel m ON md.id_mapel = m.id_mapel
                 LEFT JOIN kelas k ON md.id_kelas = k.id_kelas
                 WHERE g.id_guru = ?
                 GROUP BY g.id_guru
                 ORDER BY g.nama",
                "i",
                [$single_gid]
            );
        } else {
            $rows = fetch_all("
                SELECT g.id_guru, g.nip, g.nama, g.jenis_kelamin, g.email, g.no_hp,
                    GROUP_CONCAT(CONCAT(m.nama_mapel,' (',k.tingkat,' ',k.kelas,')') SEPARATOR ', ') AS mapel_diampu
                FROM guru g
                LEFT JOIN mapel_diampu md ON g.id_guru = md.id_guru
                LEFT JOIN mapel m ON md.id_mapel = m.id_mapel
                LEFT JOIN kelas k ON md.id_kelas = k.id_kelas
                GROUP BY g.id_guru
                ORDER BY g.nama
            ");
        }
        $headers = ['No','NIP','Nama','JK','Email','No HP','Mapel Diampu'];
        break;

case 'kelas':
    // dukungan export tunggal: ?id_kelas=123
    $single_kelas = (int)($_GET['id_kelas'] ?? 0);
    if ($single_kelas > 0) {
        // jika id_kelas diberikan, ambil hanya baris itu (prepared)
        $rows = fetch_all(
            "SELECT k.id_kelas, k.tingkat, k.kelas, k.id_wali, g.nama AS wali_nama
             FROM kelas k
             LEFT JOIN guru g ON k.id_wali = g.id_guru
             WHERE k.id_kelas = ?
             ORDER BY k.tingkat, k.kelas",
            "i",
            [$single_kelas]
        );
    } else {
        // semua kelas
        $rows = fetch_all(
            "SELECT k.id_kelas, k.tingkat, k.kelas, k.id_wali, g.nama AS wali_nama
             FROM kelas k
             LEFT JOIN guru g ON k.id_wali = g.id_guru
             ORDER BY k.tingkat, k.kelas"
        );
    }

    $headers = ['No','Tingkat','Kelas','Wali Kelas'];
    break;


case 'mapel':
    $single_mapel = (int)($_GET['id_mapel'] ?? 0);
    if ($single_mapel > 0) {
        $rows = fetch_all(
            "SELECT id_mapel, nama_mapel, kategori
             FROM mapel
             WHERE id_mapel = ?
             ORDER BY nama_mapel",
            "i",
            [$single_mapel]
        );
    } else {
        $rows = fetch_all("SELECT id_mapel, nama_mapel, kategori FROM mapel ORDER BY nama_mapel");
    }
    $headers = ['No','Nama Mapel','Kategori'];
    break;


    case 'nilai':
        $id_siswa = (int)($_GET['id_siswa'] ?? 0);
        if(!$id_siswa){
            http_response_code(400);
            echo "ID siswa tidak valid";
            exit;
        }

        $s = fetch_one("SELECT s.*, k.tingkat, k.kelas 
                        FROM siswa s 
                        JOIN kelas k ON s.id_kelas = k.id_kelas
                        WHERE s.id_siswa=?","i",[$id_siswa]);
        if(!$s){
            http_response_code(404);
            echo "Siswa tidak ditemukan";
            exit;
        }

        $mapel = fetch_all("
            SELECT m.nama_mapel, g.nama AS nama_guru, n.semester, n.nilai_tugas, n.nilai_uts, n.nilai_akhir
            FROM mapel_diampu md
            JOIN mapel m ON md.id_mapel = m.id_mapel
            LEFT JOIN guru g ON md.id_guru = g.id_guru
            LEFT JOIN nilai n ON n.id_ampu = md.id_ampu AND n.id_siswa = ?
            WHERE md.id_kelas = ?
            ORDER BY m.nama_mapel
        ","ii",[$id_siswa, $s['id_kelas']]);

        $rows = $mapel;
        $headers = ['No','Mapel','Guru','Semester','Tugas','UTS','Akhir'];
        break;

    case 'user':
        $rows = fetch_all("
            SELECT u.username, u.role, 
                   COALESCE(s.nama, g.nama, '-') AS linked
            FROM user u
            LEFT JOIN siswa s ON u.id_siswa = s.id_siswa
            LEFT JOIN guru g ON u.id_guru = g.id_guru
            ORDER BY u.username
        ");
        $headers = ['No','Username','Role','Linked'];
        break;

    default:
        http_response_code(400);
        echo "Unknown table";
        exit;
}

function build_row_for_table($table, $i, $r) {
    switch ($table) {
        case 'siswa':
            return [
                $i+1,
                $r['nisn'] ?? '',
                $r['nama'] ?? '',
                $r['jenis_kelamin'] ?? '',
                (($r['tingkat'] ?? '') !== '' ? ($r['tingkat'] . ' - ' . ($r['kelas'] ?? '')) : ''),
                $r['no_hp'] ?? '',
                $r['alamat'] ?? ''
            ];

        case 'guru':
            return [
                $i+1,
                $r['nip'] ?? '',
                $r['nama'] ?? '',
                $r['jenis_kelamin'] ?? '',
                $r['email'] ?? '',
                $r['no_hp'] ?? '',
                $r['mapel_diampu'] ?? '-'
            ];

        case 'kelas':
            return [$i+1, $r['tingkat'] ?? '', $r['kelas'] ?? '', $r['wali_nama'] ?? '-'];

        case 'mapel':
            return [
                $i+1,
                $r['nama_mapel'] ?? '',
                $r['kategori'] ?? ''
            ];

        case 'nilai':
            return [
                $i+1,
                $r['nama_mapel'] ?? '',
                $r['nama_guru'] ?? '-',
                $r['semester'] ?? '-',
                $r['nilai_tugas'] ?? '-',
                $r['nilai_uts'] ?? '-',
                $r['nilai_akhir'] ?? '-'
            ];

        case 'user':
            return [
                $i+1,
                $r['username'] ?? '',
                $r['role'] ?? '',
                $r['linked'] ?? '-'
            ];
    }
}

if ($type === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="'.$table.'.csv"');
    $out = fopen('php://output', 'w');
    fprintf($out, "%s", chr(0xEF) . chr(0xBB) . chr(0xBF)); 
    fputcsv($out, $headers);
    foreach ($rows as $i => $r) {
        fputcsv($out, build_row_for_table($table, $i, $r));
    }
    fclose($out);
    exit;
}

if ($type === 'pdf') {
    $fpdf_path_candidates = [
        __DIR__ . '/../fpdf/fpdf.php',
        __DIR__ . '/vendor/ufpdf/fpdf.php'
    ];
    foreach ($fpdf_path_candidates as $pp) {
        if (file_exists($pp)) {
            require_once $pp;
            break;
        }
    }

    if (!class_exists('FPDF')) {
        header('Content-Type: text/html; charset=utf-8');
        echo '<h3>Library FPDF tidak ditemukan</h3>';
        echo '<p>Anda bisa gunakan <a href="?table='.$table.'&type=csv">Export CSV</a>.</p>';
        exit;
    }

    define('SCHOOL_NAME', 'SMKN 4 Padalarang');
    define('SCHOOL_SUBTITLE', 'RPL (Rekayasa Perangkat Lunak) ');
    define('SCHOOL_ADDRESS', 'Jalan Raya Padalarang No.451 Kode Pos 40553 Telp. (022) 6805406');
    $logoPath = __DIR__ . '/../assets/logo.png';

    class PDF extends FPDF {
        function Header() {
            global $logoPath;
            $this->SetFont('Arial', 'B', 14);
            $this->SetXY(10, 8);
            $this->Cell(0, 6, SCHOOL_NAME, 0, 1, 'L');
            $this->SetFont('Arial', '', 9);
            $this->SetX(10);
            if (defined('SCHOOL_SUBTITLE') && SCHOOL_SUBTITLE) {
                $this->Cell(0, 5, SCHOOL_SUBTITLE, 0, 1, 'L');
            }
            $this->SetX(10);
            $this->Cell(0, 5, SCHOOL_ADDRESS, 0, 1, 'L');

            if (file_exists($logoPath)) {
                $logoX = $this->w - 35;
                $this->Image($logoPath, $logoX, 8, 24);
            }

            $metaX = 150;
            $this->SetXY($metaX, 8);
            $this->SetDrawColor(200,200,200);
            $this->SetFillColor(250,250,250);
            $this->SetFont('Arial', '', 8);
            $u=current_user();
            $username=htmlspecialchars($u['username']??'');
            $this->Cell(55, 6, 'Dicetak oleh: ' . $username, 1, 2, 'L', true);
            $this->SetX($metaX);
            $this->Cell(55, 6, 'Tanggal: ' . date('d-m-Y H:i'), 1, 2, 'L', true);
            $this->SetX($metaX);
            $this->Cell(55, 6, 'Halaman: ' . $this->PageNo() . '/{nb}', 1, 2, 'L', true);

            $this->Ln(3);
            $this->SetDrawColor(180,180,180);
            $this->Line(10, 34, $this->w - 10, 34);

            $this->Ln(4);
        }

        function Footer() {
            $this->SetY(-18);
            $this->SetDrawColor(200,200,200);
            $this->Line(10, $this->GetY(), $this->w - 10, $this->GetY());
            $this->Ln(3);
            $this->SetFont('Arial', 'I', 8);
            $this->SetTextColor(100);
            $this->Cell(0, 6, 'Generated by School Information System  |  Printed: ' . date('d-m-Y H:i'), 0, 0, 'L');
            $this->Cell(0, 6, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'R');
        }

        function FancyTable($headers, $data, $widths, $aligns = []) {
            $usable = $this->w - $this->lMargin - $this->rMargin;
            $total = array_sum($widths);
            if ($total > $usable) {
                $scale = $usable / $total;
                foreach ($widths as &$w) $w = max(10, floor($w * $scale));
                unset($w);
            }

            $this->SetFont('Arial', 'B', 10);
            $this->SetFillColor(40, 116, 166);
            $this->SetTextColor(255);
            $this->SetDrawColor(160,160,160);
            $this->SetLineWidth(.2);
            $this->SetX($this->lMargin);
            foreach ($headers as $i => $h) {
                $w = $widths[$i] ?? 40;
                $this->Cell($w, 9, $h, 1, 0, 'C', true);
            }
            $this->Ln();

            $this->SetFont('Arial', '', 10);
            $this->SetTextColor(0);
            $lineHeight = 6;
            $fill = false;
            foreach ($data as $i => $row) {
                $maxLines = 1;
                foreach ($row as $j => $cell) {
                    $w = $widths[$j] ?? 40;
                    $nb = $this->NbLines($w - 4, (string)$cell);
                    if ($nb > $maxLines) $maxLines = $nb;
                }
                $rowH = $lineHeight * $maxLines + 4;
                if ($this->GetY() + $rowH > $this->h - $this->bMargin) {
                    $this->AddPage();
                    $this->SetFont('Arial', 'B', 10);
                    $this->SetFillColor(40, 116, 166);
                    $this->SetTextColor(255);
                    $this->SetX($this->lMargin);
                    foreach ($headers as $ii => $hh) {
                        $ww = $widths[$ii] ?? 40;
                        $this->Cell($ww, 9, $hh, 1, 0, 'C', true);
                    }
                    $this->Ln();
                    $this->SetFont('Arial', '', 10);
                    $this->SetTextColor(0);
                }

                $this->SetX($this->lMargin);
                if ($fill) {
                    $this->SetFillColor(245, 245, 245);
                    $this->Rect($this->lMargin, $this->GetY(), array_sum($widths), $rowH, 'F');
                }
                $x = $this->GetX();
                $y = $this->GetY();
                foreach ($row as $j => $cell) {
                    $w = $widths[$j] ?? 40;
                    $align = $aligns[$j] ?? 'L';
                    $this->Rect($x, $y, $w, $rowH);
                    $this->SetXY($x + 2, $y + 2);
                    if ($align === 'AUTO') {
                        $a = is_numeric(str_replace([',','.','-'],'',$cell)) ? 'R' : 'L';
                    } else $a = $align;
                    $this->MultiCell($w - 4, $lineHeight, (string)$cell, 0, $a);
                    $x += $w;
                    $this->SetXY($x, $y);
                }
                $this->Ln($rowH);
                $fill = !$fill;
            }
        }

        function NbLines($w, $txt) {
            $cw = &$this->CurrentFont['cw'];
            if ($w == 0) $w = $this->w - $this->rMargin - $this->x;
            $wmax = ($w - 2 * $this->cMargin) * 1000 / $this->FontSize;
            $s = str_replace("\r", '', $txt);
            $nb = strlen($s);
            if ($nb > 0 && $s[$nb - 1] == "\n") $nb--;
            $sep = -1;
            $i = 0;
            $j = 0;
            $l = 0;
            $nl = 1;
            while ($i < $nb) {
                $c = $s[$i];
                if ($c == "\n") {
                    $i++;
                    $sep = -1;
                    $j = $i;
                    $l = 0;
                    $nl++;
                    continue;
                }
                if ($c == ' ') $sep = $i;
                $l += $cw[$c];
                if ($l > $wmax) {
                    if ($sep == -1) {
                        if ($i == $j) $i++;
                    } else $i = $sep + 1;
                    $sep = -1;
                    $j = $i;
                    $l = 0;
                    $nl++;
                } else $i++;
            }
            return $nl;
        }
    }

    $widths_map = [
        'siswa' => [10, 35, 60, 30, 30, 35, 40],
        'guru'  => [10, 35, 60, 25, 45, 30, 60],
        'kelas' => [10, 55, 55, 70],
        'mapel' => [10, 120, 60],
        'nilai' => [10, 55, 50, 25, 25, 25, 25],
        'user'  => [10, 60, 40, 70],
    ];
    $widths = $widths_map[$table] ?? array_fill(0, count($headers), 40);

    $data_for_pdf = [];
    foreach ($rows as $i => $r) {
        $data_for_pdf[] = build_row_for_table($table, $i, $r);
    }

    $aligns = array_fill(0, count($widths), 'AUTO');
    if (isset($aligns[0])) $aligns[0] = 'C';

    $title = 'Data ' . ucfirst($table);
    if ($table === 'nilai' && !empty($id_siswa)) {
        $sName = fetch_one("SELECT nama FROM siswa WHERE id_siswa=?","i",[$id_siswa]);
        if ($sName) $title .= ' - ' . $sName['nama'];
    }
    if ($table === 'guru' && !empty($single_gid)) {
        $gName = fetch_one("SELECT nama FROM guru WHERE id_guru=?","i",[$single_gid]);
        if ($gName) $title .= ' - ' . $gName['nama'];
    }

    $pdf = new PDF('P', 'mm', 'A4');
    $pdf->SetLeftMargin(10);
    $pdf->SetRightMargin(10);
    $pdf->AliasNbPages();
    $pdf->SetAutoPageBreak(true, 25);
    $pdf->AddPage();

    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetFillColor(40, 116, 166);
    $pdf->SetTextColor(255);
    $pdf->Cell(0, 10, ' ' . $title . ' ', 0, 1, 'C', true);
    $pdf->Ln(2);
    $pdf->SetTextColor(0);

    $pdf->FancyTable($headers, $data_for_pdf, $widths, $aligns);

    $pdf->Output('D', $table . '.pdf');
    exit;
}
