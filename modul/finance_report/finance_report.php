<?php
if (!function_exists('finrep_t')) {
  function finrep_t($key, $fallback = '') { return function_exists('lang_text') ? lang_text($key, $fallback) : $fallback; }
}
if (!function_exists('finrep_h')) {
  function finrep_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
}

require_once "finance_report_catalog.php";

?>
<style>
.finance-report-scroll-ready .table-responsive{
  display:block;
  width:100%;
  max-width:100%;
  overflow-x:auto!important;
  overflow-y:hidden;
  -webkit-overflow-scrolling:touch;
}
.finance-report-scroll-ready .table-responsive>.table{
  width:max-content;
  min-width:100%;
  margin-bottom:0;
}
.finance-report-scroll-ready .table-responsive::-webkit-scrollbar{height:10px}
.finance-report-scroll-ready .table-responsive::-webkit-scrollbar-thumb{background:#b8c7ce;border-radius:8px}
</style>
<script>
document.documentElement.className += ' finance-report-scroll-ready';
</script>
<?php

$reportSlug = function_exists('uri_segment') ? trim((string)uri_segment(2)) : '';
if ($reportSlug !== '') {
  $report = finrep_find_report($reportSlug);
  if ($reportSlug === 'laba-rugi-multi-periode') {
    include "finance_report_laba_rugi_multi_view.php";
    return;
  }
  if ($reportSlug === 'neraca-multi-periode') {
    include "finance_report_neraca_multi_view.php";
    return;
  }
  if ($reportSlug === 'laba-rugi-multi-year') {
    include "finance_report_laba_rugi_multi_year_view.php";
    return;
  }
  if ($reportSlug === 'neraca-multi-year') {
    include "finance_report_neraca_multi_year_view.php";
    return;
  }
  if ($reportSlug === 'arus-kas-tak-langsung') {
    include "finance_report_arus_kas_tak_langsung_view.php";
    return;
  }
  if ($reportSlug === 'rincian-arus-kas-tak-langsung') {
    include "finance_report_rincian_arus_kas_tak_langsung_view.php";
    return;
  }
  if ($reportSlug === 'arus-kas-langsung') {
    include "finance_report_arus_kas_langsung_view.php";
    return;
  }
  if ($reportSlug === 'proyeksi-ketersediaan-kas') {
    include "finance_report_proyeksi_ketersediaan_kas_view.php";
    return;
  }
  if ($reportSlug === 'proyeksi-kas-per-bulan') {
    include "finance_report_proyeksi_kas_per_bulan_view.php";
    return;
  }
  if ($reportSlug === 'laba-rugi-perbandingan-periode') {
    include "finance_report_laba_rugi_perbandingan_periode_view.php";
    return;
  }
  if ($reportSlug === 'laba-rugi-kuartal') {
    include "finance_report_laba_rugi_kuartal_view.php";
    return;
  }
  if ($reportSlug === 'laba-rugi-perbandingan-anggaran') {
    include "finance_report_laba_rugi_perbandingan_anggaran_view.php";
    return;
  }
  if ($reportSlug === 'neraca-perbandingan-periode') {
    include "finance_report_neraca_perbandingan_periode_view.php";
    return;
  }
  if ($reportSlug === 'neraca-induk-skontro') {
    include "finance_report_neraca_induk_skontro_view.php";
    return;
  }
  if ($reportSlug === 'rasio-keuangan-per-tahun') {
    include "finance_report_rasio_keuangan_per_tahun_view.php";
    return;
  }
  if ($reportSlug === 'rasio-keuangan-per-bulan') {
    include "finance_report_rasio_keuangan_per_bulan_view.php";
    return;
  }
  if ($reportSlug === 'fokus-keuangan') {
    include "finance_report_fokus_keuangan_view.php";
    return;
  }
  if ($reportSlug === 'laba-ditahan') {
    include "finance_report_laba_ditahan_view.php";
    return;
  }
  if ($reportSlug === 'perubahan-ekuitas-pemilik') {
    include "finance_report_perubahan_ekuitas_pemilik_view.php";
    return;
  }
  if ($reportSlug === 'grafik-perbandingan-nilai-akun') {
    include "finance_report_grafik_perbandingan_nilai_akun_view.php";
    return;
  }
  if ($reportSlug === 'grafik-pendapatan-berbanding-biaya') {
    include "finance_report_grafik_pendapatan_berbanding_biaya_view.php";
    return;
  }
  if ($reportSlug === 'grafik-harta-bersih') {
    include "finance_report_grafik_harta_bersih_view.php";
    return;
  }
  if ($reportSlug === 'grafik-rasio-likuiditas') {
    include "finance_report_grafik_rasio_likuiditas_view.php";
    return;
  }
  if ($reportSlug === 'grafik-pengembalian-aset') {
    include "finance_report_grafik_pengembalian_aset_view.php";
    return;
  }
  if ($reportSlug === 'grafik-pengembalian-pada-modal') {
    include "finance_report_grafik_pengembalian_pada_modal_view.php";
    return;
  }
  if ($reportSlug === 'arus-kas-multi-period-langsung') {
    include "finance_report_arus_kas_multi_period_langsung_view.php";
    return;
  }
  if ($reportSlug === 'arus-kas-multi-period-tak-langsung') {
    include "finance_report_arus_kas_multi_period_tak_langsung_view.php";
    return;
  }
  if ($reportSlug === 'rincian-arus-kas-multi-period-tak-langsung') {
    include "finance_report_rincian_arus_kas_multi_period_tak_langsung_view.php";
    return;
  }
  include "finance_report_dummy.php";
  return;
}

include "finance_report_view.php";
?>
