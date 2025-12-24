<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Laporan Bulanan {{ $bulan }}</title>

    <style>
        @page {
            size: A4 landscape;
            /* Using landscape to fit many columns */
            margin: 10mm 15mm 15mm 15mm;
        }

        body {
            font-family: sans-serif;
            font-size: 10pt;
            color: #333;
        }

        /* === KOP SURAT === */
        .kop-surat {
            border-bottom: 3px double #333;
            padding-bottom: 10px;
            margin-bottom: 20px;
            text-align: center;
            position: relative;
        }

        .kop-logo {
            position: absolute;
            left: 0;
            top: 5px;
            width: 80px;
            height: auto;
        }

        .kop-info h1 {
            margin: 0;
            font-size: 18pt;
            text-transform: uppercase;
            font-weight: bold;
            letter-spacing: 1px;
        }

        .kop-info p {
            margin: 2px 0;
            font-size: 9pt;
        }

        /* === TITLE === */
        .page-title {
            text-align: center;
            margin-bottom: 15px;
            text-transform: uppercase;
            font-size: 14pt;
            font-weight: bold;
            text-decoration: underline;
        }

        .meta-info {
            margin-bottom: 15px;
            font-size: 9pt;
        }

        /* === SUMMARY === */
        .summary-box {
            margin-top: 20px;
            padding: 10px;
            border: 1px solid #ccc;
            background: #f8f8f8;
            border-radius: 4px;
        }

        .summary-title {
            font-weight: bold;
            margin-bottom: 5px;
        }

        /* === TABLE === */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 9pt;
            table-layout: fixed;
            /* Fix column widths */
        }

        table th,
        table td {
            border: 1px solid #999;
            padding: 4px 6px;
            vertical-align: top;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        table th {
            background-color: #eee;
            text-align: left;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 8.5pt;
        }

        table tr:nth-child(even) {
            background-color: #fcfcfc;
        }

        /* === COL WIDTHS (Adjust as needed) === */
        th:nth-child(1),
        td:nth-child(1) {
            width: 5%;
            text-align: center;
        }

        /* No */
        th:nth-child(2),
        td:nth-child(2) {
            width: 9%;
        }

        /* Tanggal */
        th:nth-child(3),
        td:nth-child(3) {
            width: 9%;
        }

        /* Jam */
        th:nth-child(4),
        td:nth-child(4) {
            width: 14%;
        }

        /* Judul */
        th:nth-child(5),
        td:nth-child(5) {
            width: 10%;
        }

        /* Nama */
        th:nth-child(6),
        td:nth-child(6) {
            width: 9%;
        }

        /* Divisi */
        th:nth-child(7),
        td:nth-child(7) {
            width: 6%;
            text-align: center;
        }

        /* % */
        th:nth-child(8),
        td:nth-child(8) {
            width: 15%;
        }

        /* Status */
        th:nth-child(9),
        td:nth-child(9) {
            width: 15%;
        }

        /* Laporan */
        th:nth-child(10),
        td:nth-child(10) {
            width: 8%;
            text-align: center;
        }

        /* Bukti */

        /* === OTHER === */
        .badge {
            display: inline-block;
            padding: 3px 6px;
            margin: 2px 4px 0 0;
            border-radius: 4px;
            font-size: 8pt;
            border: 1px solid #aaa;
            background: white;
            color: #333;
        }

        .badge.total-badge {
            background: #333;
            color: #fff;
            border-color: #333;
        }

        .laporan-box {
            line-height: 1.35;
        }

        .laporan-box strong {
            display: inline-block;
            width: 50px;
            font-weight: 600;
        }

        a {
            color: #0d6efd;
            text-decoration: none;
        }
    </style>
</head>

<body>

    {{-- ================= KOP SURAT ================= --}}
    <div class="kop-surat">
        {{-- <img src="{{ public_path('img/logo.png') }}" class="kop-logo" alt="Logo"> --}}

        <div class="kop-info">
            <h1>Dinas Perhubungan Provinsi Jawa Timur</h1>
            <p>Jl. Ahmad Yani No.268, Menanggal, Kec. Gayungan, Surabaya, Jawa Timur 60234</p>
            <p>Telp: 0318292276 | Website: dishub.jatimprov.go.id</p>
        </div>
    </div>

    {{-- ================= TITLE ================= --}}
    <div class="page-title">Laporan Bulanan (Admin View)</div>

    <div class="meta-info">
        <strong>Bulan/Periode:</strong> {{ $bulan }} <br>
        <strong>Dicetak Pada:</strong> {{ now('Asia/Jakarta')->translatedFormat('d F Y H:i') }} WIB
    </div>

    {{-- ================= TABLE ================= --}}
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>Jam</th>
                <th>Judul Task</th>
                <th>Nama Teams</th>
                <th>Divisi</th>
                <th>%</th>
                <th>Status</th>
                <th>Laporan</th>
                <th>Bukti</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $i => $t)
                @php
                    $no = $i + 1;
                    $tgl = optional($t->schedule_date)->format('d/m/Y') ?? '—';
                    $jam =
                        ($t->start_time ?? null) && ($t->end_time ?? null)
                            ? $t->start_time . ' - ' . $t->end_time
                            : $t->start_time ?? '—';
                    $judul = $t->judul ?? '(Tanpa judul)';
                    $nama = $t->jobdesk?->user?->name ?? '—';
                    $divisi = $t->jobdesk?->division ?? ($t->jobdesk?->user?->division ?? '—');
                    $prog = max(0, min(100, (int) ($t->progress ?? 0)));
                    $st = strtolower($t->status ?? '');

                    $hasil = $t->result ?: '-';
                    $kendala = $t->shortcoming ?: '-';
                    $detail = $t->detail ?: '-';

                    $proofLink = $t->proof_link ?? null;
                    if ($proofLink && !preg_match('/^https?:\/\//i', $proofLink)) {
                        $proofLink = null;
                    }
                @endphp
                <tr>
                    <td style="text-align: center;">{{ $no }}</td>
                    <td>{{ $tgl }}</td>
                    <td>{{ $jam }}</td>
                    <td><strong>{{ $judul }}</strong></td>
                    <td>{{ $nama }}</td>
                    <td>{{ $divisi }}</td>
                    <td style="text-align: center; font-weight:bold;">{{ $prog }}%</td>
                    <td style="text-transform: capitalize; font-weight:bold;">{{ ucwords(str_replace('_', ' ', $st)) }}
                    </td>
                    <td>
                        <div class="laporan-box">
                            <div><strong>Hasil:</strong> {{ $hasil }}</div>
                            <div><strong>Kendala:</strong> {{ $kendala }}</div>
                            <div><strong>Detail:</strong> {{ $detail }}</div>
                        </div>
                    </td>
                    <td style="text-align:center;">
                        @if ($proofLink)
                            <a href="{{ $proofLink }}" target="_blank">Lihat</a>
                        @else
                            <span style="color:#aaa;">-</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" style="text-align:center; padding: 20px; color:#666;">
                        Tidak ada data laporan.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- ================= SUMMARY ================= --}}
    <div class="summary-box">
        <div class="summary-title">Ringkasan:</div>
        <span class="badge total-badge">
            Total Task: {{ $summary['total'] ?? 0 }}
        </span>

        @php
            $statusList = ['pending', 'in_progress', 'verification', 'rework', 'delayed', 'cancelled', 'done'];
        @endphp

        @foreach ($statusList as $st)
            @if (($summary[$st] ?? 0) > 0)
                <span class="badge">
                    {{ ucwords(str_replace('_', ' ', $st)) }}: {{ $summary[$st] ?? 0 }}
                </span>
            @endif
        @endforeach
    </div>

</body>

</html>
