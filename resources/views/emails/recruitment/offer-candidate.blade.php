<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
  body{font-family:'Helvetica Neue',Arial,sans-serif;background:#f4f6f8;margin:0;padding:40px 0;}
  .wrap{max-width:600px;margin:0 auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.08);}
  .header{background:#003366;padding:40px 48px;color:#fff;}
  .header-logo{font-size:13px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:rgba(255,255,255,.6);margin-bottom:12px;}
  .header h1{font-size:26px;font-weight:800;line-height:1.2;margin:0;}
  .strip{height:5px;background:#f59e0b;}
  .body{padding:40px 48px;}
  .congrats{font-size:48px;text-align:center;margin-bottom:12px;}
  p{color:#374151;line-height:1.7;margin:0 0 16px;}
  .detail-box{background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:24px;margin:24px 0;}
  .detail-row{display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #e2e8f0;font-size:14px;}
  .detail-row:last-child{border-bottom:none;}
  .detail-label{color:#6b7a90;font-weight:500;}
  .detail-value{font-weight:700;color:#003366;}
  .highlight-box{background:#fef9ec;border:2px solid #f59e0b;border-radius:8px;padding:20px 24px;margin:24px 0;text-align:center;}
  .highlight-box h3{font-size:18px;font-weight:800;color:#92400e;margin:0 0 8px;}
  .highlight-box p{color:#78350f;margin:0;font-size:14px;}
  .footer{background:#f8fafc;border-top:1px solid #e2e8f0;padding:24px 48px;font-size:12px;color:#9ca3af;text-align:center;}
</style>
</head>
<body>
<div class="wrap">
  <div class="header">
    <div class="header-logo">EliSOFT ERP — Recruitment</div>
    <h1>Congratulations! You Have a Job Offer</h1>
  </div>
  <div class="strip"></div>
  <div class="body">
    <div class="congrats">🎉</div>

    <p>Dear <strong>{{ $offer->application?->candidate?->first_name ?? 'Candidate' }}</strong>,</p>

    <p>We are excited to offer you the position. Please review the details of your offer below.</p>

    @if($offer->notes)
    <div style="margin: 20px 0; padding: 20px; border: 1px solid #e2e8f0; border-radius: 8px; background: #fff;">
        {!! $offer->notes !!}
    </div>
    @endif

    <p>
      We are thrilled to inform you that after a thorough review of your application and interviews, we are pleased to extend you a formal <strong>employment offer</strong>.
      Congratulations — your skills and dedication stood out!
    </p>

    <div class="detail-box">
      <div class="detail-row">
        <span class="detail-label">Position:</span>
        <span class="detail-value">{{ $offer->application?->campaign?->jobPosition?->title ?? '—' }}</span>
      </div>
      <div class="detail-row">
        <span class="detail-label">Campaign:</span>
        <span class="detail-value">{{ $offer->application?->campaign?->title ?? '—' }}</span>
      </div>
      @if($offer->offered_salary)
      <div class="detail-row">
        <span class="detail-label">Offered Salary:</span>
        <span class="detail-value">{{ number_format($offer->offered_salary, 2) }}</span>
      </div>
      @endif
      <div class="detail-row">
        <span class="detail-label">Offer Date:</span>
        <span class="detail-value">{{ $offer->offer_date?->format('M d, Y') ?? '—' }}</span>
      </div>
      @if($offer->offer_expiry_date)
      <div class="detail-row">
        <span class="detail-label">Respond By:</span>
        <span class="detail-value" style="color:#dc2626;">{{ $offer->offer_expiry_date->format('M d, Y') }}</span>
      </div>
      @endif
    </div>

    <p>
      Please review the full offer details, including terms and conditions, by clicking the button below. You can <strong>accept</strong> or <strong>decline</strong> the offer directly from the portal.
    </p>

    @if($offer->offer_expiry_date)
    <div class="highlight-box">
      <h3>⏰ Time-Sensitive</h3>
      <p>This offer expires on <strong>{{ $offer->offer_expiry_date->format('F d, Y') }}</strong>. Please respond before this date.</p>
    </div>
    @endif

    <div style="text-align:center;margin:24px 0 16px;">
      <a href="{{ $loginUrl }}" style="display:inline-block;background:#003366;color:#fff;font-weight:700;font-size:15px;padding:14px 36px;border-radius:8px;text-decoration:none;letter-spacing:.02em;">View My Offer →</a>
    </div>

    <p style="font-size:13px;color:#6b7a90;text-align:center;">
      If the button does not work, copy and paste this link into your browser:<br>
      <a href="{{ $loginUrl }}" style="color:#003366;word-break:break-all;">{{ $loginUrl }}</a>
    </p>

    <p>We look forward to welcoming you to our team!</p>
    <p>Warm regards,<br><strong>The HR Team</strong></p>
  </div>
  <div class="footer">
    This email was sent by EliSOFT ERP Recruitment System. &copy; {{ date('Y') }} All rights reserved.
  </div>
</div>
</body>
</html>
