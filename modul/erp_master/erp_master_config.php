<?php
function erp_master_lang_key($text)
{
    $text = strtolower(trim((string)$text));
    $text = preg_replace('/[^a-z0-9]+/', '_', $text);
    $text = trim($text, '_');
    return 'master_term_'.($text !== '' ? $text : 'text');
}

function erp_master_text($text)
{
    return function_exists('erp_t') ? erp_t(erp_master_lang_key($text), $text) : $text;
}

function erp_master_config_text($config, $key)
{
    return isset($config[$key]) ? erp_master_text($config[$key]) : '';
}

function erp_master_field_label($settings)
{
    return isset($settings['label']) ? erp_master_text($settings['label']) : '';
}

function erp_master_status_options()
{
    return array('Aktif' => 'Aktif', 'Nonaktif' => 'Nonaktif');
}

function erp_master_config($url)
{
    $status = array('label' => 'Status', 'type' => 'select', 'options' => erp_master_status_options());
    $plant = array('label' => 'Plant', 'type' => 'db_select', 'source_table' => 'erp_plant', 'source_value' => 'id', 'source_label' => 'plant_name', 'source_order' => 'plant_code');

    $configs = array(
        'master-customer' => array(
            'title' => 'Business Partner Customer', 'code' => 'SAP BP Customer',
            'table' => 'penerima', 'primary' => 'kode_penerima', 'order' => 'nama',
            'fields' => array(
                'kode_penerima' => array('label' => 'Kode BP Customer', 'required' => true, 'maxlength' => 8),
                'nama' => array('label' => 'Nama Business Partner', 'required' => true, 'maxlength' => 100),
                'npwp' => array('label' => 'NPWP', 'maxlength' => 30),
                'alamat' => array('label' => 'Alamat', 'maxlength' => 200),
                'kota' => array('label' => 'Kota', 'maxlength' => 100),
                'negara' => array('label' => 'Negara', 'maxlength' => 100),
                'notelp' => array('label' => 'Telepon', 'maxlength' => 100),
                'email' => array('label' => 'Email', 'maxlength' => 100),
                'status' => array('label' => 'Klasifikasi Customer', 'maxlength' => 50),
            ),
            'list' => array('kode_penerima', 'nama', 'npwp', 'kota', 'negara', 'notelp', 'status'),
        ),
        'plant' => array(
            'title' => 'Plant', 'code' => 'SAP Enterprise Structure - Plant',
            'table' => 'erp_plant', 'primary' => 'id', 'order' => 'plant_code',
            'fields' => array(
                'plant_code' => array('label' => 'Kode Plant', 'required' => true, 'maxlength' => 10),
                'plant_name' => array('label' => 'Nama Plant', 'required' => true, 'maxlength' => 100),
                'company_name' => array('label' => 'Perusahaan', 'maxlength' => 150),
                'address' => array('label' => 'Alamat', 'maxlength' => 255),
                'city' => array('label' => 'Kota', 'maxlength' => 100),
                'country' => array('label' => 'Kode Negara', 'required' => true, 'maxlength' => 3),
                'status' => $status,
            ),
            'list' => array('plant_code', 'plant_name', 'company_name', 'city', 'country', 'status'),
        ),
        'storage-location' => array(
            'title' => 'Storage Location', 'code' => 'SAP MM Storage Location',
            'table' => 'erp_storage_location', 'primary' => 'id', 'order' => 'storage_code',
            'fields' => array(
                'storage_code' => array('label' => 'Kode Storage', 'required' => true, 'maxlength' => 10),
                'plant_id' => array_merge($plant, array('required' => true)),
                'storage_name' => array('label' => 'Nama Storage Location', 'required' => true, 'maxlength' => 100),
                'storage_type' => array('label' => 'Tipe Storage', 'required' => true, 'type' => 'select', 'options' => array('RAW_MATERIAL'=>'Raw Material', 'WIP'=>'WIP', 'FINISHED_GOODS'=>'Finished Goods', 'SCRAP'=>'Scrap', 'GENERAL'=>'General')),
                'status' => $status,
            ),
            'list' => array('storage_code', 'plant_id', 'storage_name', 'storage_type', 'status'),
        ),
        'storage-bin' => array(
            'title' => 'Storage Bin', 'code' => 'SAP WM Storage Bin',
            'table' => 'erp_storage_bin', 'primary' => 'id', 'order' => 'bin_code',
            'fields' => array(
                'bin_code' => array('label' => 'Kode Bin', 'required' => true, 'maxlength' => 20),
                'storage_location_id' => array('label' => 'Storage Location', 'required' => true, 'type' => 'db_select', 'source_table' => 'erp_storage_location', 'source_value' => 'id', 'source_label' => 'storage_name', 'source_order' => 'storage_code'),
                'bin_name' => array('label' => 'Nama Bin', 'required' => true, 'maxlength' => 100),
                'zone' => array('label' => 'Zona', 'maxlength' => 50),
                'status' => $status,
            ),
            'list' => array('bin_code', 'storage_location_id', 'bin_name', 'zone', 'status'),
        ),
        'material-type' => array(
            'title' => 'Material Type', 'code' => 'SAP MM Material Type',
            'table' => 'erp_material_type', 'primary' => 'id', 'order' => 'type_code',
            'fields' => array(
                'type_code' => array('label' => 'Kode Tipe', 'required' => true, 'maxlength' => 10),
                'type_name' => array('label' => 'Nama Tipe Material', 'required' => true, 'maxlength' => 100),
                'inventory_managed' => array('label' => 'Kelola Persediaan', 'type' => 'select', 'options' => array('Ya'=>'Ya', 'Tidak'=>'Tidak')),
                'valuation_managed' => array('label' => 'Kelola Valuasi', 'type' => 'select', 'options' => array('Ya'=>'Ya', 'Tidak'=>'Tidak')),
                'status' => $status,
            ),
            'list' => array('type_code', 'type_name', 'inventory_managed', 'valuation_managed', 'status'),
        ),
        'material-group' => array(
            'title' => 'Material Group', 'code' => 'SAP MM Material Group',
            'table' => 'erp_material_group', 'primary' => 'id', 'order' => 'group_code',
            'fields' => array(
                'group_code' => array('label' => 'Kode Group', 'required' => true, 'maxlength' => 20),
                'group_name' => array('label' => 'Nama Material Group', 'required' => true, 'maxlength' => 100),
                'description' => array('label' => 'Deskripsi', 'maxlength' => 255),
                'status' => $status,
            ),
            'list' => array('group_code', 'group_name', 'description', 'status'),
        ),
        'purchasing-organization' => array(
            'title' => 'Purchasing Organization', 'code' => 'SAP MM Purchasing Organization',
            'table' => 'erp_purchasing_organization', 'primary' => 'id', 'order' => 'org_code',
            'fields' => array(
                'org_code' => array('label' => 'Kode Organisasi', 'required' => true, 'maxlength' => 10),
                'org_name' => array('label' => 'Nama Organisasi Purchasing', 'required' => true, 'maxlength' => 100),
                'plant_id' => $plant,
                'status' => $status,
            ),
            'list' => array('org_code', 'org_name', 'plant_id', 'status'),
        ),
        'purchasing-group' => array(
            'title' => 'Purchasing Group', 'code' => 'SAP MM Purchasing Group',
            'table' => 'erp_purchasing_group', 'primary' => 'id', 'order' => 'group_code',
            'fields' => array(
                'group_code' => array('label' => 'Kode Group', 'required' => true, 'maxlength' => 10),
                'group_name' => array('label' => 'Nama Purchasing Group', 'required' => true, 'maxlength' => 100),
                'purchasing_org_id' => array('label' => 'Purchasing Organization', 'type' => 'db_select', 'source_table' => 'erp_purchasing_organization', 'source_value' => 'id', 'source_label' => 'org_name', 'source_order' => 'org_code'),
                'buyer_name' => array('label' => 'Nama Buyer', 'maxlength' => 100),
                'email' => array('label' => 'Email', 'type' => 'email', 'maxlength' => 100),
                'status' => $status,
            ),
            'list' => array('group_code', 'group_name', 'purchasing_org_id', 'buyer_name', 'status'),
        ),
        'sales-organization' => array(
            'title' => 'Sales Organization', 'code' => 'SAP SD Sales Organization',
            'table' => 'erp_sales_organization', 'primary' => 'id', 'order' => 'org_code',
            'fields' => array(
                'org_code' => array('label' => 'Kode Organisasi', 'required' => true, 'maxlength' => 10),
                'org_name' => array('label' => 'Nama Organisasi Sales', 'required' => true, 'maxlength' => 100),
                'company_name' => array('label' => 'Perusahaan', 'maxlength' => 150),
                'status' => $status,
            ),
            'list' => array('org_code', 'org_name', 'company_name', 'status'),
        ),
        'distribution-channel' => array(
            'title' => 'Distribution Channel', 'code' => 'SAP SD Distribution Channel',
            'table' => 'erp_distribution_channel', 'primary' => 'id', 'order' => 'channel_code',
            'fields' => array(
                'channel_code' => array('label' => 'Kode Channel', 'required' => true, 'maxlength' => 10),
                'channel_name' => array('label' => 'Nama Channel', 'required' => true, 'maxlength' => 100),
                'sales_org_id' => array('label' => 'Sales Organization', 'required' => true, 'type' => 'db_select', 'source_table' => 'erp_sales_organization', 'source_value' => 'id', 'source_label' => 'org_name', 'source_order' => 'org_code'),
                'status' => $status,
            ),
            'list' => array('channel_code', 'channel_name', 'sales_org_id', 'status'),
        ),
        'shipping-point' => array(
            'title' => 'Shipping Point', 'code' => 'SAP SD Shipping Point',
            'table' => 'erp_shipping_point', 'primary' => 'id', 'order' => 'shipping_code',
            'fields' => array(
                'shipping_code' => array('label' => 'Kode Shipping Point', 'required' => true, 'maxlength' => 10),
                'shipping_name' => array('label' => 'Nama Shipping Point', 'required' => true, 'maxlength' => 100),
                'plant_id' => array_merge($plant, array('required' => true)),
                'status' => $status,
            ),
            'list' => array('shipping_code', 'shipping_name', 'plant_id', 'status'),
        ),
        'cost-center' => array(
            'title' => 'Cost Center', 'code' => 'SAP CO Cost Center',
            'table' => 'erp_cost_center', 'primary' => 'id', 'order' => 'cost_center_code',
            'fields' => array(
                'cost_center_code' => array('label' => 'Kode Cost Center', 'required' => true, 'maxlength' => 20),
                'cost_center_name' => array('label' => 'Nama Cost Center', 'required' => true, 'maxlength' => 100),
                'department_code' => array('label' => 'Department', 'type' => 'db_select', 'source_table' => 'dept', 'source_value' => 'kd_dept', 'source_label' => 'nm_dept', 'source_order' => 'kd_dept'),
                'valid_from' => array('label' => 'Berlaku Mulai', 'required' => true, 'type' => 'date'),
                'valid_to' => array('label' => 'Berlaku Sampai', 'required' => true, 'type' => 'date'),
                'status' => $status,
            ),
            'list' => array('cost_center_code', 'cost_center_name', 'department_code', 'valid_from', 'valid_to', 'status'),
        ),
        'profit-center' => array(
            'title' => 'Profit Center', 'code' => 'SAP CO Profit Center',
            'table' => 'erp_profit_center', 'primary' => 'id', 'order' => 'profit_center_code',
            'fields' => array(
                'profit_center_code' => array('label' => 'Kode Profit Center', 'required' => true, 'maxlength' => 20),
                'profit_center_name' => array('label' => 'Nama Profit Center', 'required' => true, 'maxlength' => 100),
                'valid_from' => array('label' => 'Berlaku Mulai', 'required' => true, 'type' => 'date'),
                'valid_to' => array('label' => 'Berlaku Sampai', 'required' => true, 'type' => 'date'),
                'status' => $status,
            ),
            'list' => array('profit_center_code', 'profit_center_name', 'valid_from', 'valid_to', 'status'),
        ),
        'fiscal-period' => array(
            'title' => 'Fiscal Period', 'code' => 'SAP FI Posting Period',
            'table' => 'erp_financial_period', 'primary' => 'id', 'order' => 'period_code',
            'fields' => array(
                'period_code' => array('label' => 'Periode (YYYY-MM)', 'required' => true, 'maxlength' => 7),
                'start_date' => array('label' => 'Tanggal Mulai', 'required' => true, 'type' => 'date'),
                'end_date' => array('label' => 'Tanggal Selesai', 'required' => true, 'type' => 'date'),
                'status' => array('label' => 'Status', 'required' => true, 'type' => 'select', 'options' => array('OPEN'=>'Open', 'CLOSING'=>'Closing', 'CLOSED'=>'Closed')),
                'notes' => array('label' => 'Catatan', 'maxlength' => 255),
            ),
            'list' => array('period_code', 'start_date', 'end_date', 'status', 'notes'),
        ),
        'tax-code' => array(
            'title' => 'Tax Code', 'code' => 'SAP FI Tax Code',
            'table' => 'erp_tax_code', 'primary' => 'id', 'order' => 'tax_code',
            'fields' => array(
                'tax_code' => array('label' => 'Kode Pajak', 'required' => true, 'maxlength' => 20),
                'tax_name' => array('label' => 'Nama Pajak', 'required' => true, 'maxlength' => 100),
                'tax_type' => array('label' => 'Tipe Pajak', 'required' => true, 'type' => 'select', 'options' => array('INPUT'=>'Input', 'OUTPUT'=>'Output', 'WITHHOLDING'=>'Withholding')),
                'rate' => array('label' => 'Tarif (%)', 'required' => true, 'type' => 'number', 'step' => '0.0001'),
                'valid_from' => array('label' => 'Berlaku Mulai', 'required' => true, 'type' => 'date'),
                'valid_to' => array('label' => 'Berlaku Sampai', 'required' => true, 'type' => 'date'),
                'status' => $status,
            ),
            'list' => array('tax_code', 'tax_name', 'tax_type', 'rate', 'valid_from', 'valid_to', 'status'),
        ),
        'exchange-rate' => array(
            'title' => 'Exchange Rate', 'code' => 'SAP FI Exchange Rate',
            'table' => 'erp_exchange_rate', 'primary' => 'id', 'order' => 'rate_date',
            'fields' => array(
                'currency_code' => array('label' => 'Mata Uang', 'required' => true, 'type' => 'db_select', 'source_table' => 'matauang', 'source_value' => 'jenis_valas', 'source_label' => 'nama_valas', 'source_order' => 'jenis_valas'),
                'rate_date' => array('label' => 'Tanggal Kurs', 'required' => true, 'type' => 'date'),
                'rate_type' => array('label' => 'Tipe Kurs', 'required' => true, 'maxlength' => 10),
                'rate_to_idr' => array('label' => 'Nilai terhadap IDR', 'required' => true, 'type' => 'number', 'step' => '0.000001'),
                'source' => array('label' => 'Sumber', 'maxlength' => 100),
            ),
            'list' => array('currency_code', 'rate_date', 'rate_type', 'rate_to_idr', 'source'),
        ),
        'coa' => array(
            'title' => 'Chart of Accounts', 'code' => 'SAP FI General Ledger Master',
            'table' => 'rekening', 'primary' => 'id', 'order' => 'no_rek',
            'fields' => array(
                'no_rek' => array('label' => 'No Rekening', 'required' => true, 'maxlength' => 50),
                'nama_rek' => array('label' => 'Nama Rekening', 'required' => true, 'maxlength' => 100),
                'induk' => array('label' => 'Parent Account', 'type' => 'db_select', 'source_table' => 'rekening', 'source_value' => 'no_rek', 'source_label' => 'nama_rek', 'source_order' => 'no_rek'),
                'level' => array('label' => 'Level', 'required' => true, 'type' => 'select', 'options' => array('1'=>'1', '2'=>'2', '3'=>'3', '4'=>'4', '5'=>'5')),
                'kat_coa' => array('label' => 'Kategori COA', 'type' => 'db_select', 'source_table' => 'coa_kategori', 'source_value' => 'id', 'source_label' => 'kategori_akun', 'source_order' => 'kategori'),
                'mapping_coa' => array('label' => 'Mapping COA', 'maxlength' => 50),
                'jenis' => array('label' => 'Jenis', 'type' => 'number'),
            ),
            'list' => array('no_rek', 'nama_rek', 'induk', 'level', 'kat_coa', 'mapping_coa'),
        ),
        'mata-uang' => array(
            'title' => 'Currency', 'code' => 'SAP FI Currency Master',
            'table' => 'matauang', 'primary' => 'kd_valas', 'order' => 'kd_valas',
            'fields' => array(
                'kd_valas' => array('label' => 'Kode Valas', 'required' => true, 'maxlength' => 6),
                'jenis_valas' => array('label' => 'Jenis Valas', 'required' => true, 'maxlength' => 100),
                'nama_valas' => array('label' => 'Nama Valas', 'required' => true, 'maxlength' => 100),
                'negara_valas' => array('label' => 'Negara', 'required' => true, 'maxlength' => 100),
            ),
            'list' => array('kd_valas', 'jenis_valas', 'nama_valas', 'negara_valas'),
        ),
        'master-bank' => array(
            'title'=>'Bank Master', 'code'=>'SAP FI-BL Bank', 'table'=>'ref_bank', 'primary'=>'id_bank', 'order'=>'nama_bank',
            'fields'=>array('kode_bank'=>array('label'=>'Kode Bank','required'=>true,'type'=>'number'), 'nama_bank'=>array('label'=>'Nama Bank','required'=>true,'maxlength'=>200)),
            'list'=>array('kode_bank','nama_bank'),
        ),
        'payment-terms' => array(
            'title'=>'Payment Terms', 'code'=>'SAP FI Terms of Payment', 'table'=>'term_payment', 'primary'=>'id', 'order'=>'net_day',
            'fields'=>array('jenis_term'=>array('label'=>'Nama Termin','required'=>true,'maxlength'=>50), 'net_day'=>array('label'=>'Jatuh Tempo (Hari)','required'=>true,'type'=>'number')),
            'list'=>array('jenis_term','net_day'),
        ),
        'work-center' => array(
            'title'=>'Work Center / Manufacturer', 'code'=>'SAP PP Work Center', 'table'=>'manufactur', 'primary'=>'id_manufactur', 'order'=>'nama_manufactur',
            'fields'=>array('initial'=>array('label'=>'Kode Work Center','required'=>true,'maxlength'=>100), 'nama_manufactur'=>array('label'=>'Nama Work Center','required'=>true,'maxlength'=>100)),
            'list'=>array('initial','nama_manufactur'),
        ),
        'production-type' => array(
            'title'=>'Production Type', 'code'=>'SAP PP Production Type', 'table'=>'jenis_produksi', 'primary'=>'id', 'order'=>'nm_jenis',
            'fields'=>array('nm_jenis'=>array('label'=>'Jenis Produksi','required'=>true,'maxlength'=>20)), 'list'=>array('nm_jenis'),
        ),
        'shift-management' => array(
            'title'=>'Shift Management', 'code'=>'SAP PP Shift Sequence', 'table'=>'erp_shift', 'primary'=>'id', 'order'=>'kode_shift',
            'fields'=>array('kode_shift'=>array('label'=>'Kode Shift','required'=>true,'maxlength'=>20), 'nama_shift'=>array('label'=>'Nama Shift','required'=>true,'maxlength'=>50), 'jam_mulai'=>array('label'=>'Jam Mulai','required'=>true,'type'=>'time'), 'jam_selesai'=>array('label'=>'Jam Selesai','required'=>true,'type'=>'time'), 'status'=>$status),
            'list'=>array('kode_shift','nama_shift','jam_mulai','jam_selesai','status'),
        ),
        'factory-calendar' => array(
            'title'=>'Factory Calendar', 'code'=>'SAP Factory Calendar', 'table'=>'erp_factory_calendar', 'primary'=>'id', 'order'=>'tanggal',
            'fields'=>array('tanggal'=>array('label'=>'Tanggal','required'=>true,'type'=>'date'), 'nama_hari'=>array('label'=>'Nama Hari / Libur','required'=>true,'maxlength'=>100), 'tipe_hari'=>array('label'=>'Tipe Hari','type'=>'select','options'=>array('Kerja'=>'Hari Kerja','Libur'=>'Hari Libur','Khusus'=>'Hari Khusus')), 'keterangan'=>array('label'=>'Keterangan','maxlength'=>200)),
            'list'=>array('tanggal','nama_hari','tipe_hari','keterangan'),
        ),
    );

    return isset($configs[$url]) ? $configs[$url] : null;
}
?>
