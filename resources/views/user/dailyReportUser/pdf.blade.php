<!DOCTYPE html>
<html>

<head>
    <title>Laporan Harian - {{ $pickedYmd ?? '' }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 10mm 15mm 15mm 15mm;
            /* Top margin reduced for kop surat */
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

        /* === HEADER INFO === */
        .page-title {
            text-align: center;
            margin-bottom: 20px;
            text-transform: uppercase;
            font-size: 14pt;
            font-weight: bold;
            text-decoration: underline;
        }

        .info-table {
            width: 100%;
            margin-bottom: 15px;
            font-size: 10pt;
        }

        .info-table td {
            padding: 2px;
            vertical-align: top;
        }

        /* === SUMMARY === */
        .summary-box {
            padding: 10px;
            border: 1px solid #ddd;
            background: #f9f9f9;
            margin-bottom: 20px;
            border-radius: 4px;
        }

        .summary-item {
            display: inline-block;
            margin-right: 25px;
            font-weight: bold;
        }

        /* === MAIN TABLE === */
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
            padding: 6px 8px;
            vertical-align: top;
        }

        table.main-table th {
            background-color: #eee;
            text-transform: uppercase;
            font-size: 8.5pt;
            font-weight: bold;
            text-align: left;
        }

        table.main-table tr:nth-child(even) {
            background-color: #fcfcfc;
        }

        /* === BADGES === */
        .badge {
            display: inline-block;
            padding: 3px 6px;
            border-radius: 3px;
            font-size: 8pt;
            color: white;
            font-weight: bold;
            text-transform: capitalize;
        }

        .bg-success {
            background-color: #198754;
        }

        .bg-info {
            background-color: #0dcaf0;
            color: #000;
        }

        .bg-secondary {
            background-color: #6c757d;
        }

        .bg-dark {
            background-color: #212529;
        }

        .bg-primary {
            background-color: #0d6efd;
        }

        .bg-warning {
            background-color: #ffc107;
            color: #000;
        }

        .bg-danger {
            background-color: #dc3545;
        }

        .bg-light {
            background-color: #f8f9fa;
            color: #000;
            border: 1px solid #ccc;
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
        {{-- LOGO Placeholder: Uncomment line below and add valid path --}}
        {{-- <img src="{{ public_path('img/logo.png') }}" class="kop-logo" alt="Logo"> --}}

        <div class="kop-info">
            <h1>Dinas Perhubungan Provinsi Jawa Timur</h1>
            <p>Jl. Ahmad Yani No.268, Menanggal, Kec. Gayungan, Surabaya, Jawa Timur 60234</p>
            <p>Telp: 0318292276 | Website: dishub.jatimprov.go.id</p>
        </div>
    </div>

    {{-- ================= PAGE TITLE ================= --}}
    <div class="page-title">Laporan Harian Pekerjaan</div>

    {{-- ================= USER INFO ================= --}}
    @php
        $userModel = isset($selectedId) ? \App\Models\User::find($selectedId) : null;
        $userName = $userModel ? $userModel->name : '-';
        $userDiv = $userModel ? $userModel->division ?? ($userModel->divisi ?? '-') : '-';

        if (!function_exists('fmtTimePdf')) {
            function fmtTimePdf($t)
            {
                if (!$t) {
                    return '';
                }
                try {
                    return substr((string) $t, 0, 5);
                } catch (\Throwable $e) {
                    return $t;
                }
            }
        }

        if (!function_exists('resolveProofLinkPdf')) {
            function resolveProofLinkPdf($task)
            {
                $candidates = [
                    $task->proof_link ?? null,
                    $task->bukti_link ?? null,
                    $task->bukti ?? null,
                    $task->link ?? null,
                    $task->url ?? null,
                ];
                $raw = null;
                foreach ($candidates as $v) {
                    if (!empty($v)) {
                        $raw = trim((string) $v);
                        break;
                    }
                }
                if (!$raw) {
                    return null;
                }
                if (preg_match('~^https?://~i', $raw)) {
                    return $raw;
                }
                return asset('storage/' . ltrim($raw, '/'));
            }
        }
    @endphp

    <table class="info-table">
        <tr>
            <td style="width: 100px; font-weight: bold;">Nama</td>
            <td style="width: 10px;">:</td>
            <td>{{ $userName }}</td>
            <td style="width: 100px; font-weight: bold;">Tanggal</td>
            <td style="width: 10px;">:</td>
            <td>{{ $rangeLabel ?? ($pickedYmd ?? '-') }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Divisi</td>
            <td>:</td>
            <td>{{ $userDiv }}</td>
            <td style="font-weight: bold;">Dicetak</td>
            <td>:</td>
            <td>{{ now('Asia/Jakarta')->translatedFormat('d F Y H:i') }} WIB</td>
        </tr>
    </table>

    {{-- ================= SUMMARY ================= --}}
    <div class="summary-box">
        <div class="summary-item">Total Tugas: {{ $summary['total_tasks'] ?? 0 }}</div>
        <div class="summary-item">Selesai: {{ $summary['done'] ?? 0 }}</div>
        <div class="summary-item">Rata-rata Progress: {{ $summary['avg_progress'] ?? 0 }}%</div>
    </div>

    {{-- ================= TABLE CONTENT ================= --}}
    <div class="table-wrap">
        <table class="main-table">
            <thead>
                <tr>
                    <th style="width: 30px; text-align: center;">No</th>
                    <th style="width: 70px;">Waktu</th>
                    <th style="width: 150px;">Pekerjaan</th>
                    <th style="width: 80px;">Status</th>
                    <th style="width: 40px; text-align: center;">%</th>
                    <th style="width: 60px;">Bukti</th>
                    <th>Hasil</th>
                    <th>Kekurangan</th>
                    <th>Detail</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tasks as $i => $t)
                    @php
                        $st = strtolower($t->status ?? '');
                        $bg = match ($st) {
                            'done' => 'bg-success',
                            'in_progress' => 'bg-info',
                            'pending' => 'bg-secondary',
                            'cancelled' => 'bg-dark',
                            'verification' => 'bg-primary',
                            'delayed' => 'bg-warning',
                            'rework' => 'bg-danger',
                            'to_do' => 'bg-light',
                            default => 'bg-secondary',
                        };
                        $proofLink = resolveProofLinkPdf($t);
                    @endphp
                    <tr>
                        <td style="text-align: center;">{{ $i + 1 }}</td>
                        <td>
                            {{ fmtTimePdf($t->start_time ?? null) }}
                            @if (!empty($t->start_time) && !empty($t->end_time))
                                – {{ fmtTimePdf($t->end_time ?? null) }}
                            @endif
                        </td>
                        <td>
                            <strong>{{ $t->judul ?? '-' }}</strong>
                            @if (!empty($t->project_name))
                                <br><small style="color:#666;">Project: {{ $t->project_name }}</small>
                            @endif
                        </td>
                        <td>
                            <span class="badge {{ $bg }}">
                                {{ ucwords(str_replace('_', ' ', $st ?: 'unknown')) }}
                            </span>
                        </td>
                        <td style="text-align: center;">{{ $t->progress ?? 0 }}%</td>
                        <td>
                            @if ($proofLink)
                                <a href="{{ $proofLink }}" target="_blank">Lihat</a>
                            @else
                                <span style="color:#aaa;">-</span>
                            @endif
                        </td>
                        <td>{{ $t->result ?? '-' }}</td>
                        <td>{{ $t->shortcoming ?? '-' }}</td>
                        <td>{{ $t->detail ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" style="text-align: center; padding: 20px;">Tidak ada aktivitas.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</body>

</html>
