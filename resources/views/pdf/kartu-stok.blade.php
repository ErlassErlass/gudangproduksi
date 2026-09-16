<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Kartu Stok - {{ $item->kode }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 10px;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .header-title {
            font-size: 16px;
            font-weight: bold;
            text-align: center;
            letter-spacing: 1px;
            text-decoration: underline;
        }
        .doc-code {
            text-align: right;
            font-size: 8px;
            vertical-align: top;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .info-table td {
            padding: 3px 5px;
            border: 1px solid #000;
        }
        .info-label {
            font-weight: bold;
            font-size: 9px;
        }
        .info-value {
            font-family: monospace;
            font-size: 10px;
        }
        .main-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }
        .main-table th {
            border: 1px solid #000;
            background-color: #f2f2f2;
            font-weight: bold;
            text-align: center;
            padding: 5px 3px;
            font-size: 8px;
            text-transform: uppercase;
        }
        .main-table td {
            border: 1px solid #000;
            padding: 4px 3px;
            font-size: 9px;
            text-align: center;
            font-family: monospace;
        }
        .number-col {
            font-size: 8px;
        }
        .text-left {
            text-align: left !important;
        }
        .text-right {
            text-align: right !important;
        }
        .highlight-row {
            color: #d9534f;
        }
        .saldo-row {
            font-weight: bold;
            background-color: #fafafa;
        }
    </style>
</head>
<body>

    <table class="header-table">
        <tr>
            <td style="width: 25%;"></td>
            <td class="header-title" style="width: 50%;">KARTU STOK</td>
            <td class="doc-code" style="width: 25%;">
                FRM.WRH.01.03/rev-4<br>
                J
            </td>
        </tr>
    </table>

    <table class="info-table">
        <tr>
            <td style="width: 12%;" class="info-label">* NAMA BARANG</td>
            <td style="width: 48%;" class="info-value">{{ $item->nama }}</td>
            <td style="width: 10%;" class="info-label">NO :</td>
            <td style="width: 30%;" class="info-value"></td>
        </tr>
        <tr>
            <td class="info-label">* KODE BARANG</td>
            <td class="info-value">{{ $item->kode }}</td>
            <td class="info-label">HAL :</td>
            <td class="info-value">1</td>
        </tr>
        <tr>
            <td class="info-label">* GROUP BARANG</td>
            <td class="info-value">{{ $item->produk }} / {{ $item->komponen }}</td>
            <td class="info-label">* SATUAN :</td>
            <td class="info-value">{{ strtoupper($item->satuan) }}</td>
        </tr>
        <tr>
            <td class="info-label">* TAHUN</td>
            <td class="info-value" colspan="3">{{ $year }}</td>
        </tr>
    </table>

    <table class="main-table">
        <thead>
            <tr>
                <th rowspan="2" style="width: 10%;">TGL/BLN</th>
                <th rowspan="2" style="width: 7%;">MASUK</th>
                <th rowspan="2" style="width: 7%;">KELUAR</th>
                <th rowspan="2" style="width: 8%;">SISA AKHIR</th>
                <th colspan="5">NOMOR</th>
                <th rowspan="2" style="width: 18%;">USER & PEMASOK</th>
                <th rowspan="2" style="width: 5%;">LOK</th>
                <th rowspan="2" style="width: 8%;">PIC</th>
                <th rowspan="2" style="width: 12%;">KET</th>
            </tr>
            <tr>
                <th style="width: 5%;">P.O</th>
                <th style="width: 5%;">P.R.N</th>
                <th style="width: 5%;">JOB</th>
                <th style="width: 5%;">TRANSFER ORDER</th>
                <th style="width: 5%;">TO R.WRH / P.R.O</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $row)
                @php
                    $isRed = ($row['keluar'] > 0);
                    $isSaldoAwal = (strpos($row['keterangan'], 'SALDO AWAL') !== false);
                @endphp
                <tr class="{{ $isRed ? 'highlight-row' : '' }} {{ $isSaldoAwal ? 'saldo-row' : '' }}">
                    <td>{{ $row['tanggal_formatted'] }}</td>
                    <td>{{ $row['masuk'] ?: '0' }}</td>
                    <td>{{ $row['keluar'] ?: '0' }}</td>
                    <td style="font-weight: bold;">{{ $row['sisa_akhir'] }}</td>
                    <td class="number-col">{{ $row['no_po'] }}</td>
                    <td class="number-col">{{ $row['no_prn'] }}</td>
                    <td class="number-col">{{ $row['job_number'] }}</td>
                    <td class="number-col">{{ $row['transfer_order'] }}</td>
                    <td class="number-col"></td>
                    <td class="text-left">{{ $row['user_pemasok'] }}</td>
                    <td>{{ $row['lokasi'] }}</td>
                    <td>{{ strtoupper($row['pic']) }}</td>
                    <td class="text-left" style="font-size: 8px;">{{ $row['keterangan'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

</body>
</html>
