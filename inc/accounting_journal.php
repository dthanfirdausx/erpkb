<?php

if (!function_exists('accounting_normalize_bc')) {
    function accounting_normalize_bc($bc)
    {
        $bc = trim((string) $bc);
        if ($bc === '') {
            return '';
        }

        if (stripos($bc, 'BC ') === 0) {
            return $bc;
        }

        return 'BC '.$bc;
    }
}

if (!function_exists('accounting_get_item_category')) {
    function accounting_get_item_category($kode)
    {
        global $db;

        $barang = $db->fetch(
            "SELECT kd_kategori FROM barang WHERE kd_barang=? LIMIT 1",
            array('kode' => $kode)
        );

        return $barang ? $barang->kd_kategori : '';
    }
}

if (!function_exists('accounting_account_exists')) {
    function accounting_account_exists($account)
    {
        global $db;

        $row = $db->fetch(
            "SELECT no_rek FROM rekening WHERE no_rek=? LIMIT 1",
            array('no_rek' => $account)
        );

        return (bool) $row;
    }
}

if (!function_exists('accounting_table_exists')) {
    function accounting_table_exists($table)
    {
        global $db;

        $row = $db->fetch("SHOW TABLES LIKE ?", array($table));
        return (bool) $row;
    }
}

if (!function_exists('accounting_get_account')) {
    function accounting_get_account($account)
    {
        global $db;

        return $db->fetch(
            "SELECT r.no_rek,r.nama_rek,k.kategori_akun,k.kategori,k.saldo_normal,
                    COUNT(child.no_rek) child_count
             FROM rekening r
             INNER JOIN coa_kategori k ON k.id=r.kat_coa
             LEFT JOIN rekening child ON child.induk=r.no_rek
             WHERE r.no_rek=?
             GROUP BY r.no_rek,r.nama_rek,k.kategori_akun,k.kategori,k.saldo_normal
             LIMIT 1",
            array($account)
        );
    }
}

if (!function_exists('accounting_validate_posting_account')) {
    function accounting_validate_posting_account($account, $expectedCategory = '', $label = 'Akun')
    {
        $account = trim((string) $account);
        if ($account === '') {
            return $label.' wajib diisi.';
        }

        $row = accounting_get_account($account);
        if (!$row) {
            return $label.' '.$account.' belum ada di tabel rekening.';
        }
        if ((int) $row->child_count > 0) {
            return $label.' '.$account.' adalah akun induk. Posting GL wajib memakai akun detail/leaf.';
        }

        $expectedCategory = strtolower(trim((string) $expectedCategory));
        if ($expectedCategory !== '' && strtolower((string) $row->kategori_akun) !== $expectedCategory) {
            return $label.' '.$account.' kategori '.$row->kategori_akun.', seharusnya '.$expectedCategory.'.';
        }

        return true;
    }
}

if (!function_exists('accounting_period_open')) {
    function accounting_period_open($date)
    {
        global $db;

        $period = $db->fetch(
            "SELECT status FROM erp_financial_period WHERE ? BETWEEN start_date AND end_date LIMIT 1",
            array($date)
        );
        if (!$period) {
            return 'Fiscal period untuk tanggal '.$date.' belum dibuat.';
        }
        if ($period->status !== 'OPEN') {
            return 'Fiscal period tanggal '.$date.' status '.$period->status.', tidak boleh posting jurnal.';
        }
        return true;
    }
}

if (!function_exists('accounting_get_default_map')) {
    function accounting_get_default_map($transaction, $bc, $category)
    {
        $transaction = strtolower(trim((string) $transaction));
        $bc = accounting_normalize_bc($bc);
        $category = strtoupper(trim((string) $category));

        if ($transaction === 'pembelian') {
            $inventoryAccount = in_array($category, array('K02', 'K07'))
                ? '14300'
                : '14101';

            return array(
                array('kode' => $inventoryAccount, 'posisi' => '1'),
                array('kode' => '21199', 'posisi' => '2')
            );
        }

        if ($transaction === 'penjualan') {
            $salesAccount = ($bc === 'BC 3.0') ? '41200' : '41100';

            return array(
                array('kode' => '12199', 'posisi' => '1'),
                array('kode' => $salesAccount, 'posisi' => '2')
            );
        }

        if ($transaction === 'issue_cost_center') {
            $inventoryAccount = in_array($category, array('K02', 'K07'))
                ? '14300'
                : '14101';

            return array(
                array('kode' => '62199', 'posisi' => '1'),
                array('kode' => $inventoryAccount, 'posisi' => '2')
            );
        }

        if ($transaction === 'issue_asset') {
            $inventoryAccount = in_array($category, array('K02', 'K07'))
                ? '14300'
                : '14101';

            return array(
                array('kode' => '15199', 'posisi' => '1'),
                array('kode' => $inventoryAccount, 'posisi' => '2')
            );
        }

        if ($transaction === 'scrap_issue') {
            $inventoryAccount = in_array($category, array('K02', 'K07'))
                ? '14300'
                : '14101';

            return array(
                array('kode' => '62299', 'posisi' => '1'),
                array('kode' => $inventoryAccount, 'posisi' => '2')
            );
        }

        if ($transaction === 'sample_issue') {
            $inventoryAccount = in_array($category, array('K02', 'K07'))
                ? '14300'
                : '14101';

            return array(
                array('kode' => '62399', 'posisi' => '1'),
                array('kode' => $inventoryAccount, 'posisi' => '2')
            );
        }

        if ($transaction === 'other_goods_issue') {
            $inventoryAccount = in_array($category, array('K02', 'K07'))
                ? '14300'
                : '14101';

            return array(
                array('kode' => '62499', 'posisi' => '1'),
                array('kode' => $inventoryAccount, 'posisi' => '2')
            );
        }

        if ($transaction === 'goods_issue_delivery') {
            $inventoryAccount = in_array($category, array('K02', 'K07'))
                ? '14300'
                : '14101';

            return array(
                array('kode' => '51100', 'posisi' => '1'),
                array('kode' => $inventoryAccount, 'posisi' => '2')
            );
        }

        if ($transaction === 'return_to_vendor') {
            $inventoryAccount = in_array($category, array('K02', 'K07'))
                ? '14300'
                : '14101';

            return array(
                array('kode' => '21199', 'posisi' => '1'),
                array('kode' => $inventoryAccount, 'posisi' => '2')
            );
        }

        if ($transaction === 'issue_production') {
            $inventoryAccount = in_array($category, array('K02', 'K07'))
                ? '14300'
                : '14101';

            return array(
                array('kode' => '14302', 'posisi' => '1'),
                array('kode' => $inventoryAccount, 'posisi' => '2')
            );
        }

        if ($transaction === 'gr_production') {
            $inventoryAccount = in_array($category, array('K02', 'K07'))
                ? '14300'
                : '14101';

            return array(
                array('kode' => $inventoryAccount, 'posisi' => '1'),
                array('kode' => '14302', 'posisi' => '2')
            );
        }

        if ($transaction === 'manual_adjust_increase' || $transaction === 'pi_diff_increase') {
            $inventoryAccount = in_array($category, array('K02', 'K07'))
                ? '14300'
                : '14101';

            return array(
                array('kode' => $inventoryAccount, 'posisi' => '1'),
                array('kode' => '71199', 'posisi' => '2')
            );
        }

        if ($transaction === 'manual_adjust_decrease' || $transaction === 'pi_diff_decrease') {
            $inventoryAccount = in_array($category, array('K02', 'K07'))
                ? '14300'
                : '14101';

            return array(
                array('kode' => '72199', 'posisi' => '1'),
                array('kode' => $inventoryAccount, 'posisi' => '2')
            );
        }

        return false;
    }
}

if (!function_exists('accounting_get_table_map')) {
    function accounting_get_table_map($transaction, $bc, $category)
    {
        global $db;

        if (!accounting_table_exists('erp_auto_journal_mapping')) {
            return false;
        }

        $transaction = strtolower(trim((string) $transaction));
        $bc = accounting_normalize_bc($bc);
        $category = strtoupper(trim((string) $category));

        $rows = $db->query(
            "SELECT line_no,account_no kode,dc_position posisi,expected_category,
                    (CASE WHEN bc_code=? THEN 0 ELSE 10 END) +
                    (CASE WHEN item_category=? THEN 0 ELSE 1 END) specificity
             FROM erp_auto_journal_mapping
             WHERE status='ACTIVE'
               AND transaction_code=?
               AND (bc_code=? OR bc_code='')
               AND (item_category=? OR item_category='*')
             ORDER BY
               CASE WHEN bc_code=? THEN 0 ELSE 1 END,
               CASE WHEN item_category=? THEN 0 ELSE 1 END,
               line_no,id",
            array($bc, $category, $transaction, $bc, $category, $bc, $category)
        );

        $best = array();
        foreach ($rows as $row) {
            $key = $row->line_no.'|'.$row->posisi;
            if (isset($best[$key]) && (int) $best[$key]->specificity <= (int) $row->specificity) {
                continue;
            }
            $best[$key] = $row;
        }

        uasort($best, function ($a, $b) {
            if ((int) $a->line_no === (int) $b->line_no) {
                return strcmp((string) $a->posisi, (string) $b->posisi);
            }
            return (int) $a->line_no < (int) $b->line_no ? -1 : 1;
        });

        $mapping = array();
        foreach ($best as $row) {
            $mapping[] = (object) array(
                'kode' => $row->kode,
                'posisi' => $row->posisi,
                'expected_category' => $row->expected_category
            );
        }

        return count($mapping) ? $mapping : false;
    }
}

if (!function_exists('accounting_get_journal_map')) {
    function accounting_get_journal_map($transaction, $bc, $category)
    {
        $mapping = accounting_get_table_map($transaction, $bc, $category);
        if (!$mapping) {
            $mapping = accounting_get_default_map($transaction, $bc, $category);
        }
        if (!$mapping) {
            return false;
        }

        foreach ($mapping as $line) {
            $account = is_array($line) ? $line['kode'] : $line->kode;
            $expectedCategory = is_array($line) || !isset($line->expected_category) ? '' : $line->expected_category;
            $valid = accounting_validate_posting_account($account, $expectedCategory, 'Akun auto-journal');
            if ($valid !== true) {
                return $valid;
            }
        }

        return array_map(
            function ($line) {
                return is_object($line) ? $line : (object) $line;
            },
            $mapping
        );
    }
}

if (!function_exists('finance_post_journal')) {
    function finance_post_journal($payload)
    {
        global $db;

        $payload = (array) $payload;
        $sourceModule = strtoupper(trim((string) (isset($payload['source_module']) ? $payload['source_module'] : '')));
        $sourceDocumentNo = trim((string) (isset($payload['source_document_no']) ? $payload['source_document_no'] : ''));
        $noBukti = trim((string) (isset($payload['no_bukti']) ? $payload['no_bukti'] : $sourceDocumentNo));
        $tanggal = trim((string) (isset($payload['tgl_jurnal']) ? $payload['tgl_jurnal'] : date('Y-m-d')));
        $documentType = strtoupper(trim((string) (isset($payload['document_type']) ? $payload['document_type'] : 'SA')));
        $postingStatus = strtoupper(trim((string) (isset($payload['posting_status']) ? $payload['posting_status'] : 'POSTED')));
        $ket = trim((string) (isset($payload['ket']) ? $payload['ket'] : $sourceModule.' '.$sourceDocumentNo));
        $username = isset($_SESSION['username']) && $_SESSION['username'] !== '' ? $_SESSION['username'] : 'system';
        $lines = isset($payload['lines']) ? (array) $payload['lines'] : array();

        if ($sourceModule === '' || $sourceDocumentNo === '') {
            return 'source_module dan source_document_no wajib diisi untuk posting jurnal.';
        }
        if ($noBukti === '') {
            $noBukti = $sourceDocumentNo;
        }
        if (!in_array($postingStatus, array('DRAFT','POSTED','REVERSED'), true)) {
            return 'Posting status jurnal tidak valid.';
        }
        if ($postingStatus === 'POSTED') {
            $periodStatus = accounting_period_open($tanggal);
            if ($periodStatus !== true) {
                return $periodStatus;
            }
        }
        if (!count($lines)) {
            return 'Detail jurnal wajib diisi.';
        }

        $totalDebet = 0;
        $totalKredit = 0;
        $normalizedLines = array();
        $lineNo = 1;
        foreach ($lines as $line) {
            $line = (array) $line;
            $account = trim((string) (isset($line['no_rek']) ? $line['no_rek'] : ''));
            $expectedCategory = isset($line['expected_category']) ? $line['expected_category'] : '';
            $validAccount = accounting_validate_posting_account($account, $expectedCategory, 'Akun jurnal');
            if ($validAccount !== true) {
                return $validAccount;
            }

            $debet = round((float) (isset($line['debet']) ? $line['debet'] : 0), 2);
            $kredit = round((float) (isset($line['kredit']) ? $line['kredit'] : 0), 2);
            if ($debet < 0 || $kredit < 0) {
                return 'Nilai debit/kredit tidak boleh negatif.';
            }
            if ($debet > 0 && $kredit > 0) {
                return 'Satu baris jurnal tidak boleh berisi debit dan kredit sekaligus.';
            }
            if ($debet <= 0 && $kredit <= 0) {
                continue;
            }

            $totalDebet += $debet;
            $totalKredit += $kredit;
            $normalizedLines[] = array(
                'line_no' => isset($line['line_no']) && (int) $line['line_no'] > 0 ? (int) $line['line_no'] : $lineNo++,
                'no_rek' => $account,
                'line_text' => isset($line['line_text']) ? trim((string) $line['line_text']) : $ket,
                'cost_center_id' => isset($line['cost_center_id']) && $line['cost_center_id'] !== '' ? (int) $line['cost_center_id'] : null,
                'profit_center_id' => isset($line['profit_center_id']) && $line['profit_center_id'] !== '' ? (int) $line['profit_center_id'] : null,
                'tax_code_id' => isset($line['tax_code_id']) && $line['tax_code_id'] !== '' ? (int) $line['tax_code_id'] : null,
                'debet' => $debet,
                'kredit' => $kredit,
                'valuta' => isset($line['valuta']) && $line['valuta'] !== '' ? strtoupper(trim((string) $line['valuta'])) : (isset($payload['valuta']) ? $payload['valuta'] : 'IDR'),
                'kurs' => isset($line['kurs']) && (float) $line['kurs'] > 0 ? (float) $line['kurs'] : (isset($payload['kurs']) && (float) $payload['kurs'] > 0 ? (float) $payload['kurs'] : 1)
            );
        }

        if (count($normalizedLines) < 2) {
            return 'Jurnal minimal harus memiliki dua baris detail.';
        }
        if (abs($totalDebet - $totalKredit) > 0.01) {
            return 'Jurnal tidak balance. Debet '.$totalDebet.' Kredit '.$totalKredit.'.';
        }

        $ownsTransaction = method_exists($db, 'inTransaction') ? !$db->inTransaction() : false;
        if ($ownsTransaction) {
            $db->query('START TRANSACTION');
        }

        try {
            $existing = $db->fetch(
                "SELECT id FROM jurnal_header
                 WHERE source_module=? AND source_document_no=? AND posting_status<>'REVERSED'
                 ORDER BY id LIMIT 1",
                array($sourceModule, $sourceDocumentNo)
            );

            $headerData = array(
                'document_type' => $documentType ?: 'SA',
                'posting_status' => $postingStatus,
                'tgl_jurnal' => $tanggal,
                'ket' => $ket,
                'no_bukti' => $noBukti,
                'source_module' => $sourceModule,
                'source_document_no' => $sourceDocumentNo,
                'username' => $username,
                'updated_by' => $username,
                'updated_at' => date('Y-m-d H:i:s')
            );
            if ($postingStatus === 'POSTED') {
                $headerData['posted_by'] = $username;
                $headerData['posted_at'] = date('Y-m-d H:i:s');
            }
            if (isset($payload['reversal_of']) && (int) $payload['reversal_of'] > 0) {
                $headerData['reversal_of'] = (int) $payload['reversal_of'];
            }

            if ($existing) {
                $idHeader = $existing->id;
                $db->update('jurnal_header', $headerData, 'id', $idHeader);
                $db->delete('jurnal_detail', 'id_header', $idHeader);
            } else {
                $headerData['no_jurnal'] = isset($payload['no_jurnal']) && trim((string) $payload['no_jurnal']) !== ''
                    ? trim((string) $payload['no_jurnal'])
                    : generate_no_jurnal();
                $headerData['tgl_insert'] = date('Y-m-d H:i:s');
                if (!$db->insert('jurnal_header', $headerData)) {
                    throw new Exception($db->getErrorMessage() ?: 'Gagal insert jurnal header.');
                }
                $idHeader = $db->last_insert_id();
            }

            foreach ($normalizedLines as $line) {
                $line['id_header'] = $idHeader;
                if (!$db->insert('jurnal_detail', $line)) {
                    throw new Exception($db->getErrorMessage() ?: 'Gagal insert jurnal detail.');
                }
            }

            if ($ownsTransaction) {
                $db->query('COMMIT');
            }
            return true;
        } catch (Exception $e) {
            if ($ownsTransaction) {
                $db->query('ROLLBACK');
            }
            return $e->getMessage();
        }
    }
}

if (!function_exists('accounting_post_auto_journal')) {
    function accounting_post_auto_journal($transaction, $bc, $items, $options = array())
    {
        global $db;

        $bc = accounting_normalize_bc($bc);
        $noBukti = isset($options['no_bukti']) ? trim((string) $options['no_bukti']) : '';
        $tanggal = isset($options['tgl_jurnal']) && $options['tgl_jurnal'] !== ''
            ? $options['tgl_jurnal']
            : date('Y-m-d');
        $username = isset($_SESSION['username']) ? $_SESSION['username'] : 'system';
        $keterangan = isset($options['ket']) && trim((string) $options['ket']) !== ''
            ? trim((string) $options['ket'])
            : strtoupper($transaction).' '.$noBukti;
        $valutaDefault = isset($options['valuta']) && $options['valuta'] !== '' ? $options['valuta'] : 'IDR';
        $kursDefault = isset($options['kurs']) && floatval($options['kurs']) > 0 ? floatval($options['kurs']) : 1;

        if ($noBukti === '') {
            return 'Nomor bukti jurnal otomatis belum tersedia.';
        }
        $periodStatus = accounting_period_open($tanggal);
        if ($periodStatus !== true) {
            return $periodStatus;
        }

        $journalLines = array();
        $totalDebet = 0;
        $totalKredit = 0;

        foreach ((array) $items as $item) {
            $kode = isset($item['kode']) ? $item['kode'] : '';
            $amount = isset($item['amount']) ? round(floatval($item['amount']), 2) : 0;
            if ($amount <= 0) {
                continue;
            }

            $category = isset($item['kat_barang']) && $item['kat_barang'] !== ''
                ? $item['kat_barang']
                : accounting_get_item_category($kode);
            $mapping = accounting_get_journal_map($transaction, $bc, $category);
            if (is_string($mapping)) {
                return $mapping;
            }
            if (!$mapping) {
                return 'Mapping jurnal otomatis belum tersedia untuk transaksi '.$transaction.', dokumen '.$bc.', kategori barang '.$category.'.';
            }

            $valuta = isset($item['valuta']) && $item['valuta'] !== '' ? $item['valuta'] : $valutaDefault;
            $kurs = isset($item['kurs']) && floatval($item['kurs']) > 0 ? floatval($item['kurs']) : $kursDefault;

            foreach ($mapping as $map) {
                $side = ((string) $map->posisi === '1') ? 'debet' : 'kredit';
                $key = $map->kode.'|'.$side.'|'.$valuta.'|'.$kurs;
                if (!isset($journalLines[$key])) {
                    $journalLines[$key] = array(
                        'no_rek' => $map->kode,
                        'debet' => 0,
                        'kredit' => 0,
                        'valuta' => $valuta,
                        'kurs' => $kurs
                    );
                }
                $journalLines[$key][$side] += $amount;
                if ($side === 'debet') {
                    $totalDebet += $amount;
                } else {
                    $totalKredit += $amount;
                }
            }
        }

        if (!count($journalLines)) {
            return 'Tidak ada nilai yang bisa dijurnal.';
        }

        if (abs($totalDebet - $totalKredit) > 0.01) {
            return 'Jurnal otomatis tidak balance. Debet '.$totalDebet.' Kredit '.$totalKredit.'.';
        }

        $autoKet = 'AUTO: '.$keterangan;
        $lines = array();
        foreach ($journalLines as $line) {
            $lines[] = array(
                'no_rek' => $line['no_rek'],
                'line_text' => $keterangan,
                'debet' => round($line['debet'], 2),
                'kredit' => round($line['kredit'], 2),
                'valuta' => $line['valuta'],
                'kurs' => $line['kurs']
            );
        }

        $result = finance_post_journal(array(
            'document_type' => isset($options['document_type']) ? $options['document_type'] : 'SA',
            'posting_status' => 'POSTED',
            'tgl_jurnal' => $tanggal,
            'ket' => $autoKet,
            'no_bukti' => $noBukti,
            'source_module' => isset($options['source_module']) ? $options['source_module'] : strtoupper($transaction),
            'source_document_no' => $noBukti,
            'valuta' => $valutaDefault,
            'kurs' => $kursDefault,
            'lines' => $lines
        ));
        if ($result !== true) {
            return $result;
        }

        if (function_exists('simpan_log')) {
            simpan_log('Jurnal otomatis '.$transaction.' untuk bukti '.$noBukti.' berhasil dibuat', $username);
        }
        return true;
    }
}

if (!function_exists('accounting_reverse_auto_journal')) {
    function accounting_reverse_auto_journal($originalNoBukti, $reversalNoBukti, $options = array())
    {
        global $db;

        $originalNoBukti = trim((string) $originalNoBukti);
        $reversalNoBukti = trim((string) $reversalNoBukti);
        $tanggal = isset($options['tgl_jurnal']) && $options['tgl_jurnal'] !== ''
            ? $options['tgl_jurnal']
            : date('Y-m-d');
        $username = isset($_SESSION['username']) ? $_SESSION['username'] : 'system';
        $keterangan = isset($options['ket']) && trim((string) $options['ket']) !== ''
            ? trim((string) $options['ket'])
            : 'Reversal '.$originalNoBukti;

        if ($originalNoBukti === '' || $reversalNoBukti === '') {
            return 'Nomor bukti jurnal reversal belum lengkap.';
        }
        $periodStatus = accounting_period_open($tanggal);
        if ($periodStatus !== true) {
            return $periodStatus;
        }

        $originalHeader = $db->fetch(
            "SELECT id,no_jurnal,source_module,posting_status FROM jurnal_header WHERE no_bukti=? AND ket LIKE 'AUTO:%' LIMIT 1",
            array('no_bukti' => $originalNoBukti)
        );
        if (!$originalHeader) {
            return 'Jurnal original untuk bukti '.$originalNoBukti.' tidak ditemukan.';
        }
        if ($originalHeader->posting_status === 'REVERSED') {
            return 'Jurnal original untuk bukti '.$originalNoBukti.' sudah berstatus REVERSED.';
        }
        $existingReversal = $db->fetch(
            "SELECT id,no_bukti FROM jurnal_header WHERE reversal_of=? AND posting_status<>'REVERSED' LIMIT 1",
            array($originalHeader->id)
        );
        if ($existingReversal && $existingReversal->no_bukti !== $reversalNoBukti) {
            return 'Jurnal original untuk bukti '.$originalNoBukti.' sudah punya reversal '.$existingReversal->no_bukti.'.';
        }

        $originalDetails = $db->query(
            "SELECT no_rek,line_text,cost_center_id,profit_center_id,tax_code_id,debet,kredit,valuta,kurs FROM jurnal_detail WHERE id_header=? ORDER BY id",
            array('id_header' => $originalHeader->id)
        );
        if (!$originalDetails || $originalDetails->rowCount() == 0) {
            return 'Detail jurnal original untuk bukti '.$originalNoBukti.' tidak ditemukan.';
        }

        $ownsTransaction = method_exists($db, 'inTransaction') ? !$db->inTransaction() : false;
        if ($ownsTransaction) {
            $db->query('START TRANSACTION');
        }

        $reversalSourceModule = isset($options['source_module']) && trim((string) $options['source_module']) !== ''
            ? strtoupper(trim((string) $options['source_module']))
            : strtoupper($originalHeader->source_module).'_REVERSAL';
        $lines = array();
        foreach ($originalDetails as $line) {
            $lines[] = array(
                'no_rek' => $line->no_rek,
                'line_text' => 'Reversal '.$line->line_text,
                'cost_center_id' => $line->cost_center_id,
                'profit_center_id' => $line->profit_center_id,
                'tax_code_id' => $line->tax_code_id,
                'debet' => round((float) $line->kredit, 2),
                'kredit' => round((float) $line->debet, 2),
                'valuta' => $line->valuta,
                'kurs' => $line->kurs
            );
        }

        $postResult = finance_post_journal(array(
            'document_type' => 'RV',
            'posting_status' => 'POSTED',
            'tgl_jurnal' => $tanggal,
            'ket' => 'AUTO REVERSAL: '.$keterangan,
            'no_bukti' => $reversalNoBukti,
            'source_module' => $reversalSourceModule,
            'source_document_no' => $originalNoBukti,
            'reversal_of' => $originalHeader->id,
            'lines' => $lines
        ));
        if ($postResult !== true) {
            if ($ownsTransaction) {
                $db->query('ROLLBACK');
            }
            return $postResult;
        }

        $db->update(
            'jurnal_header',
            array('posting_status' => 'REVERSED', 'updated_by' => $username, 'updated_at' => date('Y-m-d H:i:s')),
            'id',
            $originalHeader->id
        );

        if (function_exists('simpan_log')) {
            simpan_log('Jurnal reversal otomatis untuk bukti '.$originalNoBukti.' dibuat dengan bukti '.$reversalNoBukti, $username);
        }

        if ($ownsTransaction) {
            $db->query('COMMIT');
        }

        return true;
    }
}
