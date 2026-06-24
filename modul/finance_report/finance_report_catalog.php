<?php
if (!function_exists('finrep_reports')) {
  function finrep_reports()
  {
    return array(
      array(
        'slug' => 'laba-rugi-standar',
        'title' => 'Laba/Rugi (Standar)',
        'description' => 'Menampilkan laporan laba rugi untuk periode yg dipilih',
        'icon' => 'fa-file-text-o',
        'external_url' => 'laporan-rugi-laba',
        'type' => 'Profit & Loss'
      ),
      array(
        'slug' => 'neraca-standar',
        'title' => 'Neraca (Standar)',
        'description' => 'Menampilkan Neraca Standar',
        'icon' => 'fa-file-text-o',
        'external_url' => 'neraca',
        'type' => 'Balance Sheet'
      ),
      array(
        'slug' => 'laba-rugi-multi-periode',
        'title' => 'Laba/Rugi (Multi Periode)',
        'description' => 'Menampilkan laba rugi bulanan pada rentang periode terpilih',
        'icon' => 'fa-file-text-o',
        'type' => 'Profit & Loss'
      ),
      array(
        'slug' => 'neraca-multi-periode',
        'title' => 'Neraca (Multi Periode)',
        'description' => 'Menampilkan Neraca per akhir bulan pada rentang periode terpilih',
        'icon' => 'fa-file-text-o',
        'type' => 'Balance Sheet'
      ),
      array(
        'slug' => 'laba-rugi-multi-year',
        'title' => 'Laba/Rugi (Multi Year)',
        'description' => 'Menampilkan laba rugi per akhir tahun pada rentang periode 3 tahun terakhir',
        'icon' => 'fa-file-text-o',
        'type' => 'Profit & Loss'
      ),
      array(
        'slug' => 'neraca-multi-year',
        'title' => 'Neraca (Multi Year)',
        'description' => 'Menampilkan Neraca per akhir tahun pada rentang periode 3 tahun terakhir',
        'icon' => 'fa-file-text-o',
        'type' => 'Balance Sheet'
      ),
      array(
        'slug' => 'arus-kas-tak-langsung',
        'title' => 'Arus Kas (Tak Langsung)',
        'description' => 'Menampilkan arus kas dan aliran keluar kas untuk spesifik periode.',
        'icon' => 'fa-file-text-o',
        'type' => 'Cash Flow'
      ),
      array(
        'slug' => 'proyeksi-ketersediaan-kas',
        'title' => 'Proyeksi Ketersediaan Kas',
        'description' => 'Menampilkan grafik proyeksi ketersediaan atau kecukupan nilai kas di periode yang akan datang',
        'icon' => 'fa-line-chart',
        'accent' => 'orange',
        'type' => 'Cash Projection'
      ),
      array(
        'slug' => 'proyeksi-kas-per-bulan',
        'title' => 'Proyeksi Kas per Bulan',
        'description' => 'Menampilkan perkiraan nilai kas pada 5 bulan kedepan',
        'icon' => 'fa-file-text-o',
        'type' => 'Cash Projection'
      ),
      array(
        'slug' => 'laba-rugi-perbandingan-periode',
        'title' => 'Laba/Rugi (Perbandingan Periode)',
        'description' => 'Menampilkan laba rugi dibandingkan dengan periode lalu dari selisihnya ditampilkan dengan persentase',
        'icon' => 'fa-file-text-o',
        'type' => 'Profit & Loss'
      ),
      array(
        'slug' => 'laba-rugi-kuartal',
        'title' => 'Laba/Rugi (Kuartal)',
        'description' => 'Menampilkan laba rugi kuartal pada tahun yang dipilih',
        'icon' => 'fa-file-text-o',
        'type' => 'Profit & Loss'
      ),
      array(
        'slug' => 'laba-rugi-perbandingan-anggaran',
        'title' => 'Laba/Rugi (Perbandingan Anggaran)',
        'description' => 'Menampilkan laba/rugi dengan penambahan kolom anggaran dan perbandingannya dan juga persentasenya',
        'icon' => 'fa-file-text-o',
        'type' => 'Profit & Loss'
      ),
      array(
        'slug' => 'neraca-perbandingan-periode',
        'title' => 'Neraca (Perbandingan Periode)',
        'description' => 'Menampilkan Neraca dibandingkan dengan periode lalu dari selisihnya ditampilkan dengan persentase',
        'icon' => 'fa-file-text-o',
        'type' => 'Balance Sheet'
      ),
      array(
        'slug' => 'neraca-induk-skontro',
        'title' => 'Neraca (Induk Skontro)',
        'description' => 'Menampilkan laporan neraca secara horisontal',
        'icon' => 'fa-file-text-o',
        'type' => 'Balance Sheet'
      ),
      array(
        'slug' => 'arus-kas-langsung',
        'title' => 'Arus Kas (Langsung)',
        'description' => 'Menampilkan arus kas dan aliran keluar kas untuk spesifik periode.',
        'icon' => 'fa-file-text-o',
        'type' => 'Cash Flow'
      ),
      array(
        'slug' => 'rincian-arus-kas-tak-langsung',
        'title' => 'Rincian Arus Kas (Tak Langsung)',
        'description' => 'Menampilkan arus kas dan aliran keluar kas untuk spesifik periode. Laporan ini terdiri dari 3 bagian: aktivitas operasi, aktivitas investasi dan aktivitas pendanaan.',
        'icon' => 'fa-file-text-o',
        'type' => 'Cash Flow'
      ),
      array(
        'slug' => 'rasio-keuangan-per-tahun',
        'title' => 'Rasio Keuangan (Per Tahun)',
        'description' => 'Menampilkan informasi rasio tahunan atas data perusahaan anda',
        'icon' => 'fa-file-text-o',
        'type' => 'Financial Ratio'
      ),
      array(
        'slug' => 'rasio-keuangan-per-bulan',
        'title' => 'Rasio Keuangan (Per Bulan)',
        'description' => 'Menampilkan informasi rasio per bulan atas data perusahaan anda',
        'icon' => 'fa-file-text-o',
        'type' => 'Financial Ratio'
      ),
      array(
        'slug' => 'fokus-keuangan',
        'title' => 'Fokus Keuangan',
        'description' => 'Laporan keuangan yang mencerminkan operasi perusahaan anda secara keseluruhan untuk periode tertentu. Terdiri dari total pendapatan, laba kotor, dan ringkasan indikator keuangan.',
        'icon' => 'fa-file-text-o',
        'type' => 'Financial Summary'
      ),
      array(
        'slug' => 'laba-ditahan',
        'title' => 'Laba Ditahan',
        'description' => 'Menampilkan nilai laba ditahan hingga tahun terpilih',
        'icon' => 'fa-file-text-o',
        'type' => 'Equity'
      ),
      array(
        'slug' => 'perubahan-ekuitas-pemilik',
        'title' => 'Perubahan Ekuitas Pemilik',
        'description' => 'Laporan perubahan ekuitas pemilik',
        'icon' => 'fa-file-text-o',
        'type' => 'Equity'
      ),
      array(
        'slug' => 'grafik-perbandingan-nilai-akun',
        'title' => 'Grafik Perbandingan Nilai Akun',
        'description' => 'Menampilkan grafik garis perbandingan nilai akun yang dipilih',
        'icon' => 'fa-line-chart',
        'accent' => 'orange',
        'type' => 'Finance Chart'
      ),
      array(
        'slug' => 'grafik-pendapatan-berbanding-biaya',
        'title' => 'Grafik Pendapatan berbanding Biaya',
        'description' => 'Menampilkan grafik nilai pendapatan dibandingkan dengan nilai pengeluaran',
        'icon' => 'fa-line-chart',
        'accent' => 'orange',
        'type' => 'Finance Chart'
      ),
      array(
        'slug' => 'grafik-harta-bersih',
        'title' => 'Grafik Harta Bersih',
        'description' => 'Menampilkan nilai aset berbanding pada kewajiban untuk memperlihatkan nilai harta per bulan',
        'icon' => 'fa-line-chart',
        'accent' => 'orange',
        'type' => 'Finance Chart'
      ),
      array(
        'slug' => 'grafik-rasio-likuiditas',
        'title' => 'Grafik Rasio Likuiditas',
        'description' => 'Menampilkan nilai total aset likuid pada utang',
        'icon' => 'fa-line-chart',
        'accent' => 'orange',
        'type' => 'Finance Chart'
      ),
      array(
        'slug' => 'grafik-pengembalian-aset',
        'title' => 'Grafik Pengembalian Aset',
        'description' => 'Menampilkan nilai perbandingan dari laba bersih dibagi total aset',
        'icon' => 'fa-line-chart',
        'accent' => 'orange',
        'type' => 'Finance Chart'
      ),
      array(
        'slug' => 'grafik-pengembalian-pada-modal',
        'title' => 'Grafik Pengembalian pada Modal',
        'description' => 'Menampilkan nilai perbandingan dari laba bersih dibagi modal yang disetor',
        'icon' => 'fa-line-chart',
        'accent' => 'orange',
        'type' => 'Finance Chart'
      ),
      array(
        'slug' => 'arus-kas-multi-period-langsung',
        'title' => 'Arus Kas Multi Period (Langsung)',
        'description' => 'Menampilkan arus kas dan aliran keluar kas untuk spesifik per bulan.',
        'icon' => 'fa-file-text-o',
        'type' => 'Cash Flow'
      ),
      array(
        'slug' => 'arus-kas-multi-period-tak-langsung',
        'title' => 'Arus Kas Multi Period (Tak Langsung)',
        'description' => 'Menampilkan arus kas dan aliran keluar kas untuk spesifik per bulan.',
        'icon' => 'fa-file-text-o',
        'type' => 'Cash Flow'
      ),
      array(
        'slug' => 'rincian-arus-kas-multi-period-tak-langsung',
        'title' => 'Rincian Arus Kas Multi Period (Tak Langsung)',
        'description' => 'Menampilkan arus kas dan aliran keluar kas untuk spesifik per bulan. Laporan ini terdiri dari 3 bagian: aktivitas operasi, aktivitas investasi dan aktivitas pendanaan.',
        'icon' => 'fa-file-text-o',
        'type' => 'Cash Flow'
      )
    );
  }
}

if (!function_exists('finrep_find_report')) {
  function finrep_find_report($slug)
  {
    foreach (finrep_reports() as $report) {
      if ($report['slug'] === $slug) {
        return $report;
      }
    }
    return null;
  }
}
?>
