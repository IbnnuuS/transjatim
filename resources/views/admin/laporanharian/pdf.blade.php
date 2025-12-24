<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Laporan Harian {{ $rangeLabel ?? '' }}</title>
    <style>
        @page {
            size: A4 landscape;
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

        /* === TABLE === */
        .table-wrap {
            width: 100%;
        }

        table.main-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9pt;
        }

        table.main-table th,
        table.main-table td {
            border: 1px solid #999;
            padding: 5px;
            vertical-align: top;
            word-wrap: break-word;
        }

        table.main-table th {
            background-color: #eee;
            text-align: left;
            font-weight: bold;
            font-size: 8.5pt;
            text-transform: uppercase;
        }

        table.main-table tr:nth-child(even) {
            background-color: #fcfcfc;
        }

        .progress-col {
            text-align: center;
            font-weight: bold;
        }

        .status-col {
            text-transform: capitalize;
            font-weight: bold;
        }

        /* === BADGES === */
        .badge {
            display: inline-block;
            padding: 3px 6px;
            margin: 2px 4px 0 0;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 8.5pt;
            background: #fff;
        }

        .laporan-box {
            line-height: 1.4;
        }

        .laporan-box strong {
            display: inline-block;
            width: 55px;
        }

        a {
            color: #0d6efd;
            text-decoration: none;
            word-break: break-all;
        }

        .summary-box {
            margin-top: 15px;
            padding: 10px;
            border: 1px solid #ddd;
            background: #f9f9f9;
            font-weight: bold;
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

    <div class="page-title">Laporan Harian (Admin View)</div>

    <div class="meta-info">
        <strong>Rentang Tanggal:</strong> {{ $rangeLabel ?? 'Semua tanggal' }} <br>
        <strong>Dicetak:</strong> {{ now('Asia/Jakarta')->format('d/m/Y H:i') }} WIB
    </div>

    {{-- ================= TABLE ================= --}}
    <div class="table-wrap">
        <table class="main-table">
            <thead>
                <tr>
                    <th style="width:30px; text-align: center;">No</th>
                    <th style="width:75px;">Tanggal</th>
                    <th style="width:85px;">Jam</th>
                    <th style="width:140px;">Judul Task</th>
                    <th style="width:110px;">Nama Teams</th>
                    <th style="width:70px;">Divisi</th>
                    <th style="width:50px; text-align: center;">%</th>
                    <th style="width:80px;">Status</th>
                    <th style="width:200px;">Laporan</th>
                    <th style="width:60px;">Bukti</th>
                </tr>
            </thead>

            <tbody>
                @forelse($rows as $i => $t)
                    @php
                        $no = $i + 1;
                        $tgl = optional($t->schedule_date)->format('d/m/Y') ?? '—';
                        $time =
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
                        <td>{{ $time }}</td>
                        <td><strong>{{ $judul }}</strong></td>
                        <td>{{ $nama }}</td>
                        <td>{{ $divisi }}</td>
                        <td class="progress-col">{{ $prog }}%</td>
                        <td class="status-col">{{ ucwords(str_replace('_', ' ', $st)) }}</td>
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
    </div>

    {{-- ================= SUMMARY ================= --}}
    <div class="summary-box">
        <div style="margin-bottom: 5px;">Ringkasan Data:</div>
        <span class="badge" style="background:#333; color:#fff; border-color:#333;">Total:
            {{ $summary['total'] ?? 0 }}</span>

        @foreach (['pending', 'in_progress', 'verification', 'rework', 'delayed', 'cancelled', 'done'] as $st)
            @if (($summary[$st] ?? 0) > 0)
                <span class="badge">{{ ucwords(str_replace('_', ' ', $st)) }}: {{ $summary[$st] ?? 0 }}</span>
            @endif
        @endforeach
    </div>

</body>

</html>
