<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 13px; color: #1a1a2e; background: #fff; }

        .header {
            background: #1a1a2e;
            color: white;
            padding: 30px 40px;
            margin-bottom: 30px;
        }
        .header h1 { font-size: 26px; letter-spacing: 2px; margin-bottom: 4px; }
        .header p  { font-size: 12px; opacity: 0.7; }

        .badge {
            display: inline-block;
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
            margin-top: 10px;
        }
        .badge-approved  { background: #22c55e; color: white; }
        .badge-submitted { background: #f59e0b; color: white; }
        .badge-draft     { background: #94a3b8; color: white; }
        .badge-rejected  { background: #ef4444; color: white; }

        .body { padding: 0 40px 40px; }

        .section {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 20px 24px;
            margin-bottom: 20px;
        }
        .section-title {
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #64748b;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 8px;
            margin-bottom: 14px;
        }

        table { width: 100%; border-collapse: collapse; }
        td { padding: 7px 0; vertical-align: top; }
        td:first-child { color: #64748b; font-size: 12px; width: 40%; }
        td:last-child  { font-weight: 600; font-size: 13px; }

        .finding-item {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 10px 14px;
            margin-bottom: 8px;
        }
        .finding-label { font-size: 11px; color: #64748b; margin-bottom: 3px; }
        .finding-value { font-weight: 600; font-size: 13px; }

        .risk-bar-bg {
            background: #e2e8f0;
            border-radius: 10px;
            height: 10px;
            margin-top: 6px;
            width: 100%;
        }
        .risk-bar {
            height: 10px;
            border-radius: 10px;
        }

        .footer {
            margin-top: 30px;
            padding-top: 16px;
            border-top: 1px solid #e2e8f0;
            font-size: 11px;
            color: #94a3b8;
            text-align: center;
        }
    </style>
</head>
<body>

    <div class="header">
        <h1>VINCHECK</h1>
        <p>Vehicle Inspection Report</p>
        <div class="badge badge-{{ $report->status }}">{{ strtoupper($report->status) }}</div>
    </div>

    <div class="body">

        <div class="section">
            <div class="section-title">Report Information</div>
            <table>
                <tr>
                    <td>Report ID</td>
                    <td>#{{ $report->id }}</td>
                </tr>
                <tr>
                    <td>Report Type</td>
                    <td>{{ ucwords(str_replace('_', ' ', $report->report_type)) }}</td>
                </tr>
                <tr>
                    <td>Report Date</td>
                    <td>{{ \Carbon\Carbon::parse($report->report_date)->format('d M Y') }}</td>
                </tr>
                <tr>
                    <td>Generated At</td>
                    <td>{{ now()->format('d M Y, H:i') }}</td>
                </tr>
            </table>
        </div>

        <div class="section">
            <div class="section-title">Vehicle Information</div>
            <table>
                <tr>
                    <td>Plate Number</td>
                    <td>{{ $report->vehicle->plate_number ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td>VIN</td>
                    <td>{{ $report->vehicle->vin ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td>Brand</td>
                    <td>{{ $report->vehicle->brand ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td>Model</td>
                    <td>{{ $report->vehicle->model ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td>Year</td>
                    <td>{{ $report->vehicle->year ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td>Color</td>
                    <td>{{ $report->vehicle->color ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td>Kilometrage</td>
                    <td>{{ number_format($report->kilometrage) }} km</td>
                </tr>
            </table>
        </div>

        <div class="section">
            <div class="section-title">Inspected By</div>
            <table>
                <tr>
                    <td>Partner Name</td>
                    <td>{{ $report->partner->name ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td>Partner Email</td>
                    <td>{{ $report->partner->email ?? 'N/A' }}</td>
                </tr>
            </table>
        </div>

        @if($report->risk_score !== null)
        <div class="section">
            <div class="section-title">Risk Assessment</div>
            <table>
                <tr>
                    <td>Risk Score</td>
                    <td>{{ $report->risk_score }} / 100</td>
                </tr>
            </table>
            <div class="risk-bar-bg">
                @php
                    $score = $report->risk_score ?? 0;
                    $color = $score > 70 ? '#ef4444' : ($score > 40 ? '#f59e0b' : '#22c55e');
                @endphp
                <div class="risk-bar" style="width: {{ $score }}%; background: {{ $color }};"></div>
            </div>
        </div>
        @endif

        <div class="section">
            <div class="section-title">Inspection Findings</div>
            @foreach($report->findings as $key => $value)
            <div class="finding-item">
                <div class="finding-label">{{ ucwords(str_replace('_', ' ', $key)) }}</div>
                <div class="finding-value">{{ $value }}</div>
            </div>
            @endforeach
        </div>

        <div class="footer">
            This report was generated by VinCheck — Report #{{ $report->id }} — {{ now()->format('Y-m-d H:i:s') }}
        </div>

    </div>
</body>
</html>
