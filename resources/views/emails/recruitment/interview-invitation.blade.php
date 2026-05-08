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
  .strip{height:5px;background:#16a34a;}
  .body{padding:40px 48px;}
  p{color:#374151;line-height:1.7;margin:0 0 16px;}
  .detail-box{background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:24px;margin:24px 0;}
  .detail-row{display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #e2e8f0;font-size:14px;}
  .detail-row:last-child{border-bottom:none;}
  .detail-label{color:#6b7a90;font-weight:500;}
  .detail-value{font-weight:700;color:#362A72;}
  .status-badge{background:#dcf5ea;color:#0d5c38;border:1px solid #a7d9bc;padding:6px 14px;border-radius:20px;font-size:12px;font-weight:700;letter-spacing:.04em;text-transform:uppercase;display:inline-block;margin-bottom:20px;}
  .footer{background:#f8fafc;border-top:1px solid #e2e8f0;padding:24px 48px;font-size:12px;color:#9ca3af;text-align:center;}
</style>
</head>
<body>
<div class="wrap">
  <div class="header">
    <div class="header-logo">Cawee ERP — Recruitment</div>
    <h1>Interview Invitation</h1>
  </div>
  <div class="strip"></div>
  <div class="body">
    <p>Dear <strong>{{ $candidate->first_name }}</strong>,</p>
    <p>We are pleased to invite you to an interview for the position of <strong>{{ $campaignTitle }}</strong>.</p>

    <div class="detail-box">
      <div class="detail-row">
        <span class="detail-label">Date:</span>
        <span class="detail-value">{{ $schedule->interview_date->format('l, j F Y') }}</span>
      </div>
      <div class="detail-row">
        <span class="detail-label">Time:</span>
        <span class="detail-value">{{ $slotStart }} to {{ $slotEnd }}</span>
      </div>
      <div class="detail-row">
        <span class="detail-label">Location / Link:</span>
        <span class="detail-value">
          @if(filter_var($schedule->location, FILTER_VALIDATE_URL))
            <a href="{{ $schedule->location }}" style="color:#362A72;text-decoration:none;">Join Meeting</a>
          @else
            {{ $schedule->location }}
          @endif
        </span>
      </div>
    </div>

    <p style="margin-top:24px;">Please let us know if you have any questions or require special accommodations. We look forward to speaking with you.</p>
    <p>Best regards,<br><strong>The Recruitment Team</strong></p>
  </div>
  <div class="footer">
    This email was sent by Cawee ERP Recruitment System. &copy; {{ date('Y') }} All rights reserved.
  </div>
</div>
</body>
</html>
