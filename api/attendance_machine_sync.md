# Attendance Machine Sync API

Endpoint:

```text
POST /erpkb/api/attendance_machine_sync.php
```

Authentication:

```text
X-API-Key: <device api key>
```

The request must identify the machine with `device_code` or `SN`. API key is stored as SHA-256 hash in `erp_attendance_device.api_key_hash`.

## JSON Payload

```json
{
  "device_code": "SOL-DEMO",
  "logs": [
    {
      "machine_user_id": "EMP-0007",
      "employee_no": "EMP-0007",
      "punch_time": "2026-06-20 07:01:00",
      "punch_type": "IN",
      "verify_type": "FINGER",
      "work_code": ""
    },
    {
      "machine_user_id": "EMP-0007",
      "punch_time": "2026-06-20 15:05:00",
      "punch_type": "OUT",
      "verify_type": "FINGER"
    }
  ]
}
```

`employee_no` is optional when `machine_user_id` is mapped in `erp_attendance_device_user_map`.

Supported `punch_type` values:

```text
IN, OUT, BREAK_IN, BREAK_OUT, UNKNOWN
```

Numeric machine status is mapped as:

```text
0 = IN
1 = OUT
2 = BREAK_OUT
3 = BREAK_IN
4 = IN
5 = OUT
```

## ADMS / ATTLOG Payload

For machines that post raw ATTLOG text, use query parameters:

```text
POST /erpkb/api/attendance_machine_sync.php?SN=SOL-DEMO&api_key=<key>
```

Body line format:

```text
EMP-0007    2026-06-20 07:01:00    0    FINGER
EMP-0007    2026-06-20 15:05:00    1    FINGER
```

Tabs or multiple spaces are accepted.

## Tables

`erp_attendance_device`

Master mesin absensi: kode mesin, serial number, brand/model, lokasi, mode sync, API key hash, status, dan last sync.

`erp_attendance_device_user_map`

Mapping user id di mesin ke employee master. Satu employee bisa punya machine id berbeda per device.

`erp_attendance_machine_batch`

Header setiap request sync. Menyimpan payload mentah, jumlah log, accepted/duplicate/error, status proses.

`erp_attendance_machine_log`

Raw punch log per baris dari mesin. Tabel ini menjadi audit trail sebelum direkap ke `erp_attendance`.

`erp_attendance`

Hasil rekap harian. Punch pertama menjadi `actual_clock_in`, punch terakhir menjadi `actual_clock_out`, source menjadi `MACHINE`, status awal `RECORDED`.

## Response

```json
{
  "status": "good",
  "batch_no": "AMS-20260620-000414-13CC5B",
  "total": 2,
  "accepted": 2,
  "duplicate": 0,
  "errors": 0,
  "messages": []
}
```

## Demo Device

Migration creates a demo device:

```text
device_code: SOL-DEMO
api_key: erpkb-attendance-demo-key
```

Change this key before production.
