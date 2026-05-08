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
  .strip{height:5px;background:#6b7280;}
  .body{padding:40px 48px;}
  p{color:#374151;line-height:1.7;margin:0 0 16px;}
  .detail-box{background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:24px;margin:24px 0;}
  .detail-row{display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #e2e8f0;font-size:14px;}
  .detail-row:last-child{border-bottom:none;}
  .detail-label{color:#6b7a90;font-weight:500;}
  .detail-value{font-weight:700;color:#362A72;}
  .footer{background:#f8fafc;border-top:1px solid #e2e8f0;padding:24px 48px;font-size:12px;color:#9ca3af;text-align:center;}
</style>
</head>
<body>
<div class="wrap">
  <div class="header">
    <div class="header-logo">Cawee ERP — Recruitment</div>
    <h1>Application Update</h1>
  </div>
  <div class="strip"></div>
  <div class="body">
    <p>Dear {{ $application->candidate->first_name ?? 'Candidate' }},</p>
    
    <p>Thank you for giving us the opportunity to consider you for the <strong>{{ $application->campaign->jobPosition->title ?? $application->campaign->title ?? 'Position' }}</strong> position. We truly appreciated the time you spent with our team during the interview process.</p>

    <p>This was a difficult decision as we were fortunate to have a strong pool of candidates. However, at this time, we have decided to move forward with another applicant whose profile more closely aligns with our current requirements.</p>

    <div class="detail-box">
      <div class="detail-row">
        <span class="detail-label">Application for:</span>
        <span class="detail-value">{{ $application->campaign->jobPosition->title ?? $application->campaign->title ?? 'Position' }}</span>
      </div>
      <div class="detail-row">
        <span class="detail-label">Reference ID:</span>
        <span class="detail-value">#{{ $application->id }}</span>
      </div>
    </div>

    @if($reason)
    <p><strong>Additional Feedback:</strong><br>{{ $reason }}</p>
    @endif

    <p>We will keep your profile in our talent database and may reach out if a future opening matches your skills and experience. We wish you the very best in your professional endeavors and thank you for your interest in our organization.</p>

    <p>Best regards,<br>The Recruitment Team</p>
  </div>
  <div class="footer">
    This is an automated message from Cawee ERP Recruitment System. &copy; {{ date('Y') }} All rights reserved.
  </div>
</div>
</body>
</html>
