<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/functions.php';
require_role(['siswa']);

$u = current_user();
$id_siswa = $u['id_siswa'] ?? 0;
if(!$id_siswa){
  echo "<div class='alert alert-danger'>Data siswa tidak ditemukan.</div>";
  exit;
}

$s = fetch_one("SELECT s.*, k.tingkat, k.kelas FROM siswa s JOIN kelas k ON s.id_kelas=k.id_kelas WHERE id_siswa=?","i",[$id_siswa]);
if(!$s){
  echo '<div class="alert alert-warning">Siswa tidak ditemukan.</div>';
  exit;
}

$nilai = fetch_all("SELECT n.*, m.nama_mapel, m.kategori, g.nama AS nama_guru 
          FROM nilai n
          JOIN mapel_diampu md ON n.id_ampu=md.id_ampu
          JOIN mapel m ON md.id_mapel=m.id_mapel
          JOIN guru g ON md.id_guru=g.id_guru
          WHERE n.id_siswa=?
          ORDER BY semester, m.nama_mapel","i",[$id_siswa]);


//logika export (TIDAK ADA PERUBAHAN LOGIKA DI BLOK INI)
if (!empty($_GET['export'])) {
  $export = strtolower($_GET['export']);
  $export_id = isset($_GET['id']) ? (int)$_GET['id'] : $id_siswa;
  if ($export_id <= 0) {
    http_response_code(400);
    echo "ID siswa tidak valid";
    exit;
  }

  $s_ex = fetch_one("SELECT s.*, k.tingkat, k.kelas FROM siswa s JOIN kelas k ON s.id_kelas=k.id_kelas WHERE id_siswa=?","i",[$export_id]);
  if (!$s_ex) {
    http_response_code(404);
    echo "Siswa tidak ditemukan";
    exit;
  }
  $nilai_ex = fetch_all("SELECT n.*, m.nama_mapel, m.kategori, g.nama AS nama_guru 
          FROM nilai n
          JOIN mapel_diampu md ON n.id_ampu=md.id_ampu
          JOIN mapel m ON md.id_mapel=m.id_mapel
          JOIN guru g ON md.id_guru=g.id_guru
          WHERE n.id_siswa=?
          ORDER BY semester, m.nama_mapel","i",[$export_id]);

  if ($export === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    $safeName = preg_replace('/[^A-Za-z0-9_\-]/','_', $s_ex['nama']);
    $filename = 'nilai_'.$safeName.'.csv';
    header('Content-Disposition: attachment; filename="'.$filename.'"');
    $out = fopen('php://output', 'w');
    // BOM for excel
    fprintf($out, "%s", chr(0xEF) . chr(0xBB) . chr(0xBF));
    fputcsv($out, ['No','Mapel','Guru','Semester','Tugas','UTS','Akhir']);
    foreach ($nilai_ex as $i => $nrow) {
      fputcsv($out, [
        $i+1,
        ($nrow['nama_mapel'] ?? '') . ' (' . ($nrow['kategori'] ?? '') . ')',
        $nrow['nama_guru'] ?? '',
        $nrow['semester'] ?? '',
        $nrow['nilai_tugas'] ?? '',
        $nrow['nilai_uts'] ?? '',
        $nrow['nilai_akhir'] ?? ''
      ]);
    }
    fclose($out);
    exit;
  }

  if ($export === 'pdf') {
    $fpdf_path_candidates = [
      __DIR__ . '/../fpdf/fpdf.php',
      __DIR__ . '/../vendor/fpdf/fpdf.php'
    ];
    $found = false;
    foreach ($fpdf_path_candidates as $pp) {
      if (file_exists($pp)) {
        require_once $pp;
        $found = true;
        break;
      }
    }
    if (!$found || !class_exists('FPDF')) {
      header('Content-Type: text/html; charset=utf-8');
      echo '<h3>Library FPDF tidak ditemukan</h3>';
      echo '<p>Anda bisa gunakan <a href="?export=csv&id='.$export_id.'">Export CSV</a>.</p>';
      exit;
    }
    class PDFNilai extends FPDF {
      function NbLines($w, $txt) {
        $cw = &$this->CurrentFont['cw'];
        if ($w==0) $w = $this->w - $this->rMargin - $this->x;
        $wmax = ($w-2*$this->cMargin)*1000/$this->FontSize;
        $s = str_replace("\r",'',$txt);
        $nb = strlen($s);
        if ($nb>0 && $s[$nb-1]=="\n") $nb--;
        $sep = -1; $i = 0; $j = 0; $l = 0; $nl = 1;
        while ($i<$nb) {
          $c = $s[$i];
          if ($c=="\n") { $i++; $sep=-1; $j=$i; $l=0; $nl++; continue; }
          if ($c==' ') $sep = $i;
          $l += $cw[$c] ?? 0;
          if ($l > $wmax) {
            if ($sep == -1) {
              if ($i==$j) $i++;
            } else {
              $i = $sep+1;
            }
            $sep = -1; $j = $i; $l = 0; $nl++;
          } else $i++;
        }
        return $nl;
      }
      public function getPageBreakTrigger() {
        return $this->PageBreakTrigger;
      }
    }
    $widths = [10, 70, 40, 18, 18, 18, 18];
    $pdf = new PDFNilai('P','mm','A4');
    $pdf->SetLeftMargin(10);
    $pdf->AddPage();
    $pdf->SetFont('Arial','B',12);
    $title = 'Nilai - '.$s_ex['nama'].' ('.$s_ex['tingkat'].' '.$s_ex['kelas'].')';
    $pdf->Cell(0,10,$title,0,1,'C');
    $pdf->Ln(3);
    $pdf->SetFont('Arial','B',10);
    $header = ['No','Mapel','Guru','Semester','Tugas','UTS','Akhir'];
    foreach ($header as $i=>$h) {
      $pdf->Cell($widths[$i],8,$h,1,0,'C');
    }
    $pdf->Ln();
    $pdf->SetFont('Arial','',10);
    $lineHeight = 6;

    foreach ($nilai_ex as $i => $nrow) {
      $cells = [
        $i+1,
        ($nrow['nama_mapel'] ?? '') . ' (' . ($nrow['kategori'] ?? '') . ')',
        $nrow['nama_guru'] ?? '',
        $nrow['semester'] ?? '',
        $nrow['nilai_tugas'] ?? '',
        $nrow['nilai_uts'] ?? '',
        $nrow['nilai_akhir'] ?? ''
      ];

      // compute max lines for row
      $maxLines = 1;
      foreach ($cells as $j=>$c) {
        $nb = $pdf->NbLines($widths[$j], (string)$c);
        if ($nb > $maxLines) $maxLines = $nb;
      }
      $rowHeight = $lineHeight * $maxLines;

      // check page break using getter
      if ($pdf->GetY() + $rowHeight + 10 > $pdf->getPageBreakTrigger()) {
        $pdf->AddPage();
        // re-draw header
        $pdf->SetFont('Arial','B',10);
        foreach ($header as $k=>$h) $pdf->Cell($widths[$k],8,$h,1,0,'C');
        $pdf->Ln();
        $pdf->SetFont('Arial','',10);
      }

      $x = $pdf->GetX();
      $y = $pdf->GetY();
      foreach ($cells as $j=>$c) {
        $w = $widths[$j];
        $pdf->Rect($x, $y, $w, $rowHeight);
        $pdf->SetXY($x, $y);
        $pdf->MultiCell($w, $lineHeight, (string)$c, 0, 'L');
        $x += $w;
        $pdf->SetXY($x, $y);
      }
      $pdf->Ln($rowHeight);
    }

    $safeName = preg_replace('/[^A-Za-z0-9_\-]/','_', $s_ex['nama']);
    $filename = 'nilai_'.$safeName.'.pdf';
    $pdf->Output('D', $filename);
    exit;
  }

  http_response_code(400);
  echo "Tipe export tidak valid";
  exit;
}

// Logika statistik untuk sidebar
$total_mapel = count($nilai);
$total_nilai_akhir = 0;
$nilai_per_semester = [];
foreach($nilai as $n) {
    $total_nilai_akhir += $n['nilai_akhir'];
    $sem = $n['semester'];
    if (!isset($nilai_per_semester[$sem])) {
        $nilai_per_semester[$sem] = ['sum' => 0, 'count' => 0];
    }
    $nilai_per_semester[$sem]['sum'] += $n['nilai_akhir'];
    $nilai_per_semester[$sem]['count']++;
}
$rata_rata_global = $total_mapel > 0 ? round($total_nilai_akhir / $total_mapel, 2) : 0;

include __DIR__ . '/../inc/header.php';
?>

<div class="layout-golden">

  <div class="left-col">
  <div class="card shadow-sm p-4 mb-4">
   <h4 class="fw-bold mb-1">Nilai Saya</h4>
   <p class="mb-3 text-muted">Data nilai untuk <strong><?=htmlspecialchars($s['nama'])?></strong> (Kelas <?=$s['tingkat'].' '.$s['kelas']?>)</p>

   <div class="d-flex justify-content-end gap-2 mb-3">
    <a href="?export=csv&id=<?=$id_siswa?>" class="btn btn-sm btn-outline-primary" target="_blank">Export CSV</a>
    <a href="?export=pdf&id=<?=$id_siswa?>" class="btn btn-sm btn-outline-danger" target="_blank">Export PDF</a>
   </div>

   <div class="table-responsive">
    <table class="table table-striped table-hover mb-0">
     <thead>
      <tr>
       <th>No</th>
       <th>Mapel</th>
       <th>Guru</th>
       <th>Semester</th>
       <th>Tugas</th>
       <th>UTS</th>
       <th>Akhir</th>
      </tr>
     </thead>
     <tbody>
      <?php if(empty($nilai)): ?>
       <tr><td colspan="7" class="text-center">Belum ada nilai.</td></tr>
      <?php else: ?>
       <?php foreach($nilai as $i=>$n): ?>
        <tr>
         <td><?=$i+1?></td>
         <td><?=htmlspecialchars($n['nama_mapel'])?><br><span class="badge text-bg-secondary"><?=$n['kategori']?></span></td>
         <td><?=htmlspecialchars($n['nama_guru'])?></td>
         <td><?=htmlspecialchars($n['semester'])?></td>
         <td><?=htmlspecialchars($n['nilai_tugas'])?></td>
         <td><?=htmlspecialchars($n['nilai_uts'])?></td>
         <td class="fw-bold"><?=htmlspecialchars($n['nilai_akhir'])?></td>
        </tr>
       <?php endforeach; ?>
      <?php endif; ?>
     </tbody>
    </table>
   </div>
  </div>
 </div>

  <aside class="sidebar">
  <div class="stat-card">
   <h6>Rata-rata Nilai Akhir (Global)</h6>
   <div class="stat-value text-primary"><?= $rata_rata_global ?></div>
  </div>

  <div class="stat-card">
   <h6>Jumlah Mapel Tercatat</h6>
   <div class="stat-value"><?= $total_mapel ?></div>
  </div>

  <div class="stat-card">
   <h6>Rata-rata Nilai per Semester</h6>
   <div class="recent-list">
    <?php 
     ksort($nilai_per_semester);
     if(empty($nilai_per_semester)): 
    ?>
     <div class="recent-item">Belum ada data nilai</div>
    <?php else: foreach($nilai_per_semester as $sem=>$data):
       $avg = $data['count'] > 0 ? round($data['sum'] / $data['count'], 2) : 0;
    ?>
     <div class="recent-item">
      <div>Semester <?=$sem?> (<?=$data['count']?> mapel):</div>
      <strong class="text-primary"><?=$avg?></strong>
     </div>
    <?php endforeach; endif; ?>
   </div>
  </div>
 </aside>
</div>

<?php include __DIR__ . '/../inc/footer.php'; ?>