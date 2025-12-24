<!DOCTYPE html>
<html>

<head>
    <title>Rekap Bulanan - {{ $monthLabel ?? '-' }}</title>
    <style>
        @page {
            size: A4 portrait;
            /* Or landscape if table is wide */
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

        /* === DAY BOX === */
        .day-box {
            page-break-inside: avoid;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 4px;
            overflow: hidden;
        }

        .day-header {
            background: #eee;
            padding: 8px 10px;
            font-weight: bold;
            font-size: 10pt;
            border-bottom: 1px solid #ddd;
        }

        /* === TABLE === */
        table.main-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9pt;
        }

        table.main-table th,
        table.main-table td {
            border: 1px solid #ccc;
            /* Lighter border inside day box */
            padding: 5px 8px;
            vertical-align: top;
        }

        table.main-table th {
            background-color: #f8f8f8;
            text-transform: uppercase;
            font-size: 8pt;
            font-weight: bold;
            text-align: left;
        }

        table.main-table tr:last-child td {
            border-bottom: none;
        }

        /* === BADGES === */
        .badge {
            display: inline-block;
            padding: 3px 6px;
            border-radius: 3px;
            font-size: 7.5pt;
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

        .muted {
            color: #777;
        }

        .small {
            font-size: 8.5pt;
        }

        a {
            color: #0d6efd;
            text-decoration: none;
            word-break: break-all;
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

    {{-- ================= PAGE TITLE ================= --}}
    <div class="page-title">Rekap Bulanan Pekerjaan</div>

    {{-- ================= USER INFO ================= --}}
    @php
        $userModel = isset($selectedId) ? \App\Models\User::find($selectedId) : null;
        $userName = $userModel ? $userModel->name : '-';
        $userDiv = $userModel ? $userModel->division ?? ($userModel->divisi ?? '-') : '-';

        // Helper functions
        if (!function_exists('statusBadgePdf')) {
            function statusBadgePdf($st)
            {
                $st = strtolower((string) $st);
                return match ($st) {
                    'done' => 'bg-success',
                    'in_progress' => 'bg-info',
                    'pending' => 'bg-secondary',
                    'cancelled' => 'bg-dark',
                    'verification' => 'bg-primary',
                    'delayed' => 'bg-warning',
                    'rework' => 'bg-danger',
                    default => 'bg-secondary',
                };
            }
        }
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
            <td style="width: 100px; font-weight: bold;">Bulan</td>
            <td style="width: 10px;">:</td>
            <td>{{ $monthLabel ?? '-' }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Divisi</td>
            <td>:</td>
            <td>{{ $userDiv }}</td>
            <td style="font-weight: bold;">Total Tugas</td>
            <td>:</td>
            <td>{{ $monthTotals['tasks'] ?? 0 }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Dicetak</td>
            <td>:</td>
            <td>{{ now('Asia/Jakarta')->translatedFormat('d F Y H:i') }} WIB</td>
            <td style="font-weight: bold;">Avg Progress</td>
            <td>:</td>
            <td>{{ number_format($monthTotals['avg_progress'] ?? 0, 0) }}%</td>
        </tr>
    </table>

    {{-- ================= LOOP DAYS ================= --}}
    @foreach ($days as $ymd => $d)
        @php
            $dateFormatted = \Illuminate\Support\Carbon::parse($ymd)->translatedFormat('d F Y');
        @endphp

        <div class="day-box">
            <div class="day-header">
                {{ $dateFormatted }} &nbsp;|&nbsp; Total: {{ $d['count'] ?? 0 }} &nbsp;|&nbsp; Avg:
                {{ number_format($d['avg_progress'] ?? 0, 0) }}%
            </div>

            <table class="main-table">
                <thead>
                    <tr>
                        <th style="width: 25px; text-align:center;">No</th>
                        <th style="width: 70px;">Waktu</th>
                        <th style="width: 160px;">Judul & Bukti</th>
                        <th style="width: 80px;">Status</th>
                        <th style="width: 35px; text-align:center;">%</th>
                        <th style="width: 120px;">Hasil</th>
                        <th style="width: 120px;">Kekurangan</th>
                        <th>Detail</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($d['tasks'] ?? [] as $i => $t)
                        @php
                            $st = strtolower($t->status ?? '');
                            if ($st === 'to_do') {
                                continue;
                            }

                            $bg = statusBadgePdf($st);
                            $pct = max(1, min(100, (int) ($t->progress ?? 1)));
                            $proof = resolveProofLinkPdf($t);
                        @endphp
                        <tr>
                            <td style="text-align:center;">{{ $i + 1 }}</td>
                            <td>
                                {{ fmtTimePdf($t->start_time ?? null) }}
                                @if (!empty($t->start_time) && !empty($t->end_time))
                                    – {{ fmtTimePdf($t->end_time ?? null) }}
                                @endif
                            </td>
                            <td>
                                <strong>{{ $t->judul ?? '-' }}</strong>
                                @if (!empty($t->project_name))
                                    <br><span class="small muted">Project: {{ $t->project_name }}</span>
                                @endif
                                <br>
                                <div style="margin-top:4px;">
                                    @if ($proof)
                                        <a href="{{ $proof }}" target="_blank" style="font-size:8.5pt;">[Lihat
                                            Bukti]</a>
                                    @else
                                        <span class="muted small">[Tanpa Bukti]</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <span class="badge {{ $bg }}">
                                    {{ ucwords(str_replace('_', ' ', $st ?: 'unknown')) }}
                                </span>
                            </td>
                            <td style="text-align:center;">{{ $pct }}%</td>
                            <td>{{ $t->result ?? '-' }}</td>
                            <td>{{ $t->shortcoming ?? '-' }}</td>
                            <td>{{ $t->detail ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endforeach

</body>

</html>
