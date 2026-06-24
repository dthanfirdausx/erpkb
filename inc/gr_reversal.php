<?php

require_once __DIR__ . '/accounting_journal.php';

if (!function_exists('gr_full_reversal_response')) {
    function gr_full_reversal_response($status, $message = '', $data = array())
    {
        return array(
            'status' => $status,
            'message' => $message,
            'data' => $data
        );
    }
}

if (!function_exists('gr_perform_full_reversal')) {
    function gr_perform_full_reversal($id, $reason, $reversalDate = '')
    {
        global $db;

        $id = intval($id);
        $reason = trim((string) $reason);
        $reversalDate = trim((string) $reversalDate) !== '' ? trim((string) $reversalDate) : date('Y-m-d');
        $username = isset($_SESSION['username']) ? $_SESSION['username'] : 'system';

        if ($id <= 0) {
            return gr_full_reversal_response('error', 'Dokumen GR tidak valid.');
        }

        if ($reason === '') {
            return gr_full_reversal_response('error', 'Alasan reversal wajib diisi.');
        }

        $header = $db->fetch("SELECT * FROM pemasukan WHERE id=? LIMIT 1", array('id' => $id));
        if (!$header) {
            return gr_full_reversal_response('error', 'Dokumen GR tidak ditemukan.');
        }

        if ($header->status === 'REVERSED') {
            return gr_full_reversal_response('error', 'Dokumen GR sudah pernah direversal.');
        }

        if ($header->is_reversal === 'Y') {
            return gr_full_reversal_response('error', 'Dokumen reversal tidak bisa direversal ulang dari menu ini.');
        }

        $details = array();
        $detailRows = $db->query(
            "SELECT * FROM pemasukan_detail WHERE no_bpb=? ORDER BY COALESCE(no_urut,id),id",
            array('no_bpb' => $header->no_bpb)
        );

        if (!$detailRows || $detailRows->rowCount() == 0) {
            return gr_full_reversal_response('error', 'Detail item GR tidak ditemukan.');
        }

        foreach ($detailRows as $detail) {
            $qty = abs((float) $detail->jumlah);
            if ($qty <= 0) {
                return gr_full_reversal_response('error', 'Qty detail item '.$detail->kode.' tidak valid.');
            }

            $layer = $db->fetch(
                "SELECT * FROM stock_layer
                 WHERE ref_table='pemasukan_detail'
                   AND ref_id=?
                   AND no_bpb=?
                   AND kode=?
                 LIMIT 1",
                array(
                    'ref_id' => $detail->id,
                    'no_bpb' => $header->no_bpb,
                    'kode' => $detail->kode
                )
            );

            if (!$layer) {
                return gr_full_reversal_response('error', 'Stock layer original untuk item '.$detail->kode.' tidak ditemukan.');
            }

            if ((float) $layer->qty_sisa + 0.00001 < $qty) {
                return gr_full_reversal_response(
                    'error',
                    'GR tidak bisa direversal penuh. Stok item '.$detail->kode.' sudah terpakai. Sisa layer '.$layer->qty_sisa.', kebutuhan reversal '.$qty.'.'
                );
            }

            $details[] = array(
                'detail' => $detail,
                'layer' => $layer,
                'qty' => $qty
            );
        }

        $isWithoutPo = ($header->nopo === 'GR_WITHOUT_PO');
        $isBlockedStock = (isset($header->stock_type) && $header->stock_type === 'BLOCKED');
        if ($isWithoutPo) {
            $moveCode = '502';
            $refType = 'GR_WO_PO_REV';
        } elseif ($isBlockedStock) {
            $moveCode = '104';
            $refType = 'GR_BLOCKED_REV';
        } else {
            $moveCode = '102';
            $refType = 'PO_REVERSAL';
        }
        $year = date('Y', strtotime($reversalDate));
        $reversalNoBpb = getNoBPB($year);
        $reversalNomor = get_nomor('pemasukan', 'id');
        $shortReason = substr('REVERSAL '.$header->no_bpb.' '.$reason, 0, 100);

        $db->query('START TRANSACTION');

        $reversalHeader = array(
            'no_bpb' => $reversalNoBpb,
            'nomor' => $reversalNomor,
            'tgl_bpb' => $reversalDate,
            'document_date' => $reversalDate,
            'posting_date' => $reversalDate,
            'pemasok' => $header->pemasok,
            'no_invoice' => $header->no_invoice,
            'tgl_invoice' => $header->tgl_invoice,
            'no_do' => $header->no_do,
            'catatan' => $shortReason,
            'no_aju' => $header->no_aju,
            'tgl_aju' => $header->tgl_aju,
            'jenis_dokpab' => $header->jenis_dokpab,
            'no_dokpab' => $header->no_dokpab,
            'tgl_dokpab' => $header->tgl_dokpab,
            'kantor_pabean' => $header->kantor_pabean,
            'negara_asal' => $header->negara_asal,
            'customs_status' => $header->customs_status,
            'userid' => $username,
            'kd_catdet' => $header->kd_catdet,
            'nopo' => $header->nopo,
            'plant_id' => $header->plant_id,
            'storage_location_id' => $header->storage_location_id,
            'stock_type' => $header->stock_type,
            'efaktur' => $header->efaktur,
            'tgl_efaktur' => $header->tgl_efaktur,
            'tipe' => $header->tipe,
            'valuta' => $header->valuta,
            'kurs' => $header->kurs,
            'ref_no' => 'REV-'.$header->no_bpb,
            'no_kontrak' => $header->no_kontrak,
            'tgl_kontrak' => $header->tgl_kontrak,
            'is_reversal' => 'Y',
            'status' => 'POSTED',
            'ref_reversal' => $header->no_bpb
        );

        if (!$db->insert('pemasukan', $reversalHeader)) {
            $error = $db->getErrorMessage();
            $db->query('ROLLBACK');
            return gr_full_reversal_response('error', $error);
        }

        foreach ($details as $row) {
            $detail = $row['detail'];
            $qty = $row['qty'];
            $nilai = abs((float) $detail->nilai);
            $berat = abs((float) $detail->berat);

            $reversalDetail = array(
                'nomor' => $reversalNomor,
                'id_po_detail' => $detail->id_po_detail,
                'no_bpb' => $reversalNoBpb,
                'tgl_bpb' => $reversalDate,
                'kode' => $detail->kode,
                'jumlah' => $qty * -1,
                'harga' => $detail->harga,
                'valuta' => $detail->valuta,
                'nilai' => $nilai * -1,
                'berat' => $berat * -1,
                'unit' => $detail->unit,
                'no_urut' => $detail->no_urut,
                'customs_item_no' => $detail->customs_item_no,
                'hs_code' => $detail->hs_code,
                'customs_qty' => abs((float) $detail->customs_qty) * -1,
                'customs_uom' => $detail->customs_uom,
                'customs_value' => abs((float) $detail->customs_value) * -1,
                'net_weight' => abs((float) $detail->net_weight) * -1,
                'gross_weight' => abs((float) $detail->gross_weight) * -1,
                'package_type' => $detail->package_type,
                'package_qty' => abs((float) $detail->package_qty) * -1,
                'origin_country' => $detail->origin_country,
                'no_aju' => $detail->no_aju,
                'tgl_aju' => $detail->tgl_aju,
                'tgl_masuk' => $reversalDate,
                'jenis_dokpab' => $detail->jenis_dokpab,
                'no_dokpab' => $detail->no_dokpab,
                'tgl_dokpab' => $detail->tgl_dokpab,
                'lokasi' => $detail->lokasi,
                'storage_bin_id' => $detail->storage_bin_id,
                'no_kontrak' => $detail->no_kontrak,
                'userid' => $username,
                'status' => $detail->status
            );

            if (!$db->insert('pemasukan_detail', $reversalDetail)) {
                $error = $db->getErrorMessage();
                $db->query('ROLLBACK');
                return gr_full_reversal_response('error', $error);
            }
            $reversalDetailId = $db->last_insert_id();

            $db->query(
                "UPDATE stock_layer SET qty_sisa=qty_sisa-? WHERE id=? AND qty_sisa>=?",
                array('qty' => $qty, 'id' => $row['layer']->id, 'qty_check' => $qty)
            );

            if (!$isWithoutPo && !empty($detail->id_po_detail)) {
                $db->query(
                    "UPDATE purchase_order_detail
                     SET received_qty=GREATEST(COALESCE(received_qty,0)-?,0)
                     WHERE id=?",
                    array('qty' => $qty, 'id' => $detail->id_po_detail)
                );
            }

            $transaction = array(
                'no_ref' => $reversalNoBpb,
                'ref_pengganti' => $header->no_bpb,
                'id_pemasukan' => $reversalNomor,
                'no_aju' => $header->no_aju,
                'no_dokpab' => $header->no_dokpab,
                'id_incoming_detail' => $detail->id,
                'move_code' => $moveCode,
                'no_urut' => $detail->no_urut,
                'posisi' => 'GUDANG',
                'qty' => $qty * -1,
                'id_bagian' => 1,
                'price' => $detail->harga,
                'weight' => $berat * -1,
                'kd_barang' => $detail->kode,
                'lokasi' => $detail->lokasi,
                'document_date' => $reversalDate,
                'posting_date' => $reversalDate,
                'user' => $username,
                'is_produksi' => '0',
                'direction' => 'OUT',
                'ref_type' => $refType,
                'ref_id' => $reversalDetailId,
                'is_reversal' => 1,
                'ref_detail_id' => $detail->id,
                'id_po_detail' => $detail->id_po_detail,
                'uom' => $detail->unit,
                'amount' => $nilai * -1,
                'reason' => $reason,
                'created_by' => $username,
                'no_bpb' => $header->no_bpb,
                'destination_material_code' => $detail->kode,
                'remark' => 'Reversal '.$header->no_bpb.' - '.$reason
            );

            if (!$db->insert('detail_transaksi', $transaction)) {
                $error = $db->getErrorMessage();
                $db->query('ROLLBACK');
                return gr_full_reversal_response('error', $error);
            }
        }

        $updateOriginal = array(
            'status' => 'REVERSED',
            'ref_reversal' => $reversalNoBpb
        );

        $db->update('pemasukan', $updateOriginal, 'id', $header->id);

        $accountingItems = array();
        foreach ($details as $row) {
            $detail = $row['detail'];
            $amount = abs((float) $detail->nilai);
            if ($amount <= 0) {
                $amount = abs((float) $detail->jumlah) * abs((float) $detail->harga);
            }
            $accountingItems[] = array(
                'kode' => $detail->kode,
                'amount' => $amount,
                'valuta' => $detail->valuta ?: $header->valuta,
                'kurs' => $header->kurs ?: 1
            );
        }

        $originalJournal = $db->fetch(
            "SELECT id FROM jurnal_header WHERE no_bukti=? AND ket LIKE 'AUTO:%' LIMIT 1",
            array('no_bukti' => $header->no_bpb)
        );
        if (!$originalJournal) {
            $backfillJournal = accounting_post_auto_journal(
                'pembelian',
                $header->jenis_dokpab,
                $accountingItems,
                array(
                    'no_bukti' => $header->no_bpb,
                    'tgl_jurnal' => $header->posting_date ?: $header->tgl_bpb,
                    'ket' => 'Backfill jurnal original sebelum reversal '.$header->no_bpb,
                    'valuta' => $header->valuta,
                    'kurs' => $header->kurs ?: 1
                )
            );

            if ($backfillJournal !== true) {
                $db->query('ROLLBACK');
                return gr_full_reversal_response('error', $backfillJournal);
            }
        }

        $journalResult = accounting_reverse_auto_journal(
            $header->no_bpb,
            $reversalNoBpb,
            array(
                'tgl_jurnal' => $reversalDate,
                'ket' => 'Reversal '.$header->no_bpb.' menjadi '.$reversalNoBpb.' - '.$reason
            )
        );

        if ($journalResult !== true) {
            $db->query('ROLLBACK');
            return gr_full_reversal_response('error', $journalResult);
        }

        $db->query('COMMIT');

        if (function_exists('simpan_log')) {
            simpan_log('Reversal GR '.$header->no_bpb.' menjadi '.$reversalNoBpb.' dengan alasan '.$reason, $username);
        }

        return gr_full_reversal_response(
            'good',
            'Reversal berhasil.',
            array('original_no_bpb' => $header->no_bpb, 'reversal_no_bpb' => $reversalNoBpb)
        );
    }
}
