<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 30px 40px; }
        body { font-family: "Helvetica Neue", Helvetica, Arial, sans-serif; color: #1e293b; font-size: 13px; line-height: 1.5; }
        .header-bar { background: linear-gradient(135deg, #1e293b, #334155); color: white; padding: 20px 30px; border-radius: 8px; margin-bottom: 20px; }
        .header-bar h1 { font-size: 18px; margin: 0 0 4px 0; letter-spacing: 1px; }
        .header-bar p { font-size: 11px; margin: 0; opacity: 0.8; }
        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .info-table td { padding: 8px 14px; border-bottom: 1px solid #e2e8f0; font-size: 12px; }
        .info-label { font-weight: 600; color: #64748b; width: 20%; background: #f8fafc; }
        .info-value { color: #1e293b; width: 30%; }
        .test-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .test-table th { background: #f1f5f9; padding: 10px 14px; text-align: left; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #475569; border-bottom: 2px solid #e2e8f0; }
        .test-table td { padding: 10px 14px; border-bottom: 1px solid #e2e8f0; font-size: 12px; }
        .signature-box { display: inline-block; width: 45%; text-align: center; margin-top: 20px; }
        .signature-line { border-top: 1px solid #94a3b8; margin-top: 50px; padding-top: 8px; font-size: 11px; color: #64748b; }
        .footer { border-top: 1px solid #e2e8f0; padding-top: 12px; margin-top: 30px; font-size: 9px; color: #94a3b8; text-align: center; }
    </style>
</head>
<body>
    <div class="header-bar">
        <h1>QC LAB TEST CERTIFICATE</h1>
        <p>Official Quality Control Inspection Report — Document #{{ $job->transaction_id }}</p>
    </div>

    <table class="info-table">
        <tr>
            <td class="info-label">Document No.</td>
            <td class="info-value">QC-{{ str_pad($job->transaction_id, 5, '0', STR_PAD_LEFT) }}</td>
            <td class="info-label">Date Issued</td>
            <td class="info-value">{{ now()->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <td class="info-label">Equipment</td>
            <td class="info-value">{{ $job->equipment_name }}</td>
            <td class="info-label">DMC</td>
            <td class="info-value">{{ $job->dmc ?: '-' }}</td>
        </tr>
        <tr>
            <td class="info-label">Line</td>
            <td class="info-value">{{ $job->line ?: '-' }}</td>
            <td class="info-label">Receive Date</td>
            <td class="info-value">{{ \Carbon\Carbon::parse($job->receive_date)->format('d/m/Y H:i') }}</td>
        </tr>
        <tr>
            <td class="info-label">Sender</td>
            <td class="info-value">{{ $job->sender }}</td>
            <td class="info-label">Receiver</td>
            <td class="info-value">{{ $job->receiver }}</td>
        </tr>
    </table>

    @php
        $overallColor = $overallJudgement === 'OK' ? '#059669' : '#dc2626';
        $overallBg = $overallJudgement === 'OK' ? '#ecfdf5' : '#fef2f2';
        $overallBorder = $overallJudgement === 'OK' ? '#a7f3d0' : '#fecaca';
    @endphp

    <div style="background:{{ $overallBg }};border:2px solid {{ $overallBorder }};border-radius:8px;padding:12px 20px;margin-bottom:20px;text-align:center;">
        <span style="font-size:11px;color:#64748b;">Overall Judgement</span><br>
        <span style="font-size:24px;font-weight:800;color:{{ $overallColor }};">{{ $overallJudgement }}</span>
    </div>

    <table class="test-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Method</th>
                <th>Inspector</th>
                <th>Start</th>
                <th>End</th>
                <th style="text-align:center;">Result</th>
                <th>Remark</th>
            </tr>
        </thead>
        <tbody>
            @forelse($details as $i => $d)
                <tr style="background:{{ $i % 2 === 0 ? '#f8fafc' : '#ffffff' }};">
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $d->method_name }}</td>
                    <td>{{ $d->inspector }}</td>
                    <td>{{ $d->start_time ? \Carbon\Carbon::parse($d->start_time)->format('d/m/Y H:i') : '-' }}</td>
                    <td>{{ $d->end_time ? \Carbon\Carbon::parse($d->end_time)->format('d/m/Y H:i') : '-' }}</td>
                    <td style="text-align:center;">
                        @php $jColor = $d->judgement === 'OK' ? '#059669' : '#dc2626'; $jBg = $d->judgement === 'OK' ? '#ecfdf5' : '#fef2f2'; @endphp
                        <span style="background:{{ $jBg }};color:{{ $jColor }};padding:3px 12px;border-radius:20px;font-weight:700;font-size:11px;">{{ $d->judgement }}</span>
                    </td>
                    <td style="color:#64748b;max-width:120px;">{{ $d->remark ?? '' }}</td>
                </tr>
            @empty
                <tr><td colspan="7" style="text-align:center;padding:30px;color:#94a3b8;">No test results recorded yet.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div style="margin-top:40px;">
        <div class="signature-box" style="float:left;">
            <div class="signature-line">Inspected By</div>
            <p style="font-size:10px;color:#94a3b8;margin-top:4px;">Date: _____ / _____ / _____</p>
        </div>
        <div class="signature-box" style="float:right;">
            <div class="signature-line">Approved By</div>
            <p style="font-size:10px;color:#94a3b8;margin-top:4px;">Date: _____ / _____ / _____</p>
        </div>
        <div style="clear:both;"></div>
    </div>

    <div class="footer">
        QC Lab Tracking System &bull; Generated on {{ now()->format('d/m/Y H:i') }} &bull; This is a computer-generated document.
    </div>
</body>
</html>
