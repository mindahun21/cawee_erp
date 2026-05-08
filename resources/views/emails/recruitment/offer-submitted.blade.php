<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
  body{font-family:'Helvetica Neue',Arial,sans-serif;background:#f4f6f8;margin:0;padding:40px 0;}
  .wrap{max-width:600px;margin:0 auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.08);}
  .header{background:#362A72;padding:40px 48px;color:#fff;}
  .header-logo{font-size:13px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:rgba(255,255,255,.6);margin-bottom:12px;}
  .header h1{font-size:26px;font-weight:800;line-height:1.2;margin:0;}
  .strip{height:5px;background:#f59e0b;}
  .body{padding:40px 48px;}
  p{color:#374151;line-height:1.7;margin:0 0 16px;}
  .detail-box{background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:24px;margin:24px 0;}
  .detail-row{display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #e2e8f0;font-size:14px;}
  .detail-row:last-child{border-bottom:none;}
  .detail-label{color:#6b7a90;font-weight:500;}
  .detail-value{font-weight:700;color:#362A72;}
  .status-badge{background:#fef3c7;color:#92400e;border:1px solid #fcd34d;padding:6px 14px;border-radius:20px;font-size:12px;font-weight:700;letter-spacing:.04em;text-transform:uppercase;display:inline-block;margin-bottom:20px;}
  .next-steps{background:#fffbeb;border:1px solid #fcd34d;border-radius:8px;padding:20px 24px;margin:24px 0;}
  .next-steps h3{font-size:14px;font-weight:700;color:#92400e;margin:0 0 8px;}
  .next-steps ul{margin:0;padding-left:18px;color:#78350f;font-size:14px;line-height:2;}
  .footer{background:#f8fafc;border-top:1px solid #e2e8f0;padding:24px 48px;font-size:12px;color:#9ca3af;text-align:center;}
</style>
</head>
<body>
<div class="wrap">
  <div class="header">
    <div class="header-logo">Cawee ERP — Recruitment</div>
    <h1>Employment Offer Submitted for Approval</h1>
  </div>
  <div class="strip"></div>
  <div class="body">
    <div class="status-badge">⏳ Awaiting Approval</div>
    <p>An employment offer has been submitted for your approval.</p>

    @if($offer->notes)
    <div style="margin: 20px 0; padding: 15px; border-left: 4px solid #3b82f6; background: #f8fafc;">
        <strong>Notes from Issuer:</strong><br>
        {!! $offer->notes !!}
    </div>
    @endif

    <div class="detail-box">
      <div class="detail-row">
        <span class="detail-label">Candidate:</span>
        <span class="detail-value">{{ $offer->application?->candidate?->first_name ?? '—' }} {{ $offer->application?->candidate?->last_name ?? '' }}</span>
      </div>
      <div class="detail-row">
        <span class="detail-label">Position:</span>
        <span class="detail-value">{{ $offer->application?->campaign?->jobPosition?->title ?? '—' }}</span>
      </div>
      <div class="detail-row">
        <span class="detail-label">Campaign:</span>
        <span class="detail-value">{{ $offer->application?->campaign?->title ?? '—' }}</span>
      </div>
      <div class="detail-row">
        <span class="detail-label">Offered Salary:</span>
        <span class="detail-value">{{ $offer->offered_salary ? number_format($offer->offered_salary, 2) : 'Not specified' }}</span>
      </div>
      <div class="detail-row">
        <span class="detail-label">Offer Date:</span>
        <span class="detail-value">{{ $offer->offer_date?->format('M d, Y') ?? '—' }}</span>
      </div>
      <div class="detail-row">
        <span class="detail-label">Expiry Date:</span>
        <span class="detail-value">{{ $offer->offer_expiry_date?->format('M d, Y') ?? 'No expiry set' }}</span>
      </div>
      <div class="detail-row">
        <span class="detail-label">Issued by:</span>
        <span class="detail-value">{{ $offer->issuer?->name ?? '—' }}</span>
      </div>
    </div>

    <div class="next-steps">
      <h3>Action Required</h3>
      <ul>
        <li>Review the offer details by clicking the button below.</li>
        <li>Approve to notify the candidate, or return it to HR for revision.</li>
      </ul>
    </div>

    @if(!empty($viewUrl))
    <div style="text-align:center;margin:24px 0 16px;">
      <a href="{{ $viewUrl }}" style="display:inline-block;background:#f59e0b;color:#fff;font-weight:700;font-size:15px;padding:14px 36px;border-radius:8px;text-decoration:none;letter-spacing:.02em;">Review Offer →</a>
    </div>
    @endif
  </div>
  <div class="footer">
    This email was sent by Cawee ERP Recruitment System. &copy; {{ date('Y') }} All rights reserved.
  </div>
</div>
</body>
</html>
