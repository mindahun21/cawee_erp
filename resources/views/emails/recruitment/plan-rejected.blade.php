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
  .strip{height:5px;background:#dc2626;}
  .body{padding:40px 48px;}
  p{color:#374151;line-height:1.7;margin:0 0 16px;}
  .detail-box{background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:24px;margin:24px 0;}
  .detail-row{display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #e2e8f0;font-size:14px;}
  .detail-row:last-child{border-bottom:none;}
  .detail-label{color:#6b7a90;font-weight:500;}
  .detail-value{font-weight:700;color:#003366;}
  .status-badge{background:#fef2f2;color:#991b1b;border:1px solid #fca5a5;padding:6px 14px;border-radius:20px;font-size:12px;font-weight:700;letter-spacing:.04em;text-transform:uppercase;display:inline-block;margin-bottom:20px;}
  .reason-box{background:#fef2f2;border:1px solid #fca5a5;border-radius:8px;padding:20px 24px;margin:24px 0;}
  .reason-box h3{font-size:14px;font-weight:700;color:#991b1b;margin:0 0 8px;}
  .reason-box p{color:#7f1d1d;font-size:14px;margin:0;}
  .next-steps{background:#fffbeb;border:1px solid #fcd34d;border-radius:8px;padding:20px 24px;margin:24px 0;}
  .next-steps h3{font-size:14px;font-weight:700;color:#92400e;margin:0 0 8px;}
  .next-steps ul{margin:0;padding-left:18px;color:#78350f;font-size:14px;line-height:2;}
  .footer{background:#f8fafc;border-top:1px solid #e2e8f0;padding:24px 48px;font-size:12px;color:#9ca3af;text-align:center;}
</style>
</head>
<body>
<div class="wrap">
  <div class="header">
    <div class="header-logo">EliSOFT ERP — Recruitment</div>
    <h1>Recruitment Plan — Returned for Revision</h1>
  </div>
  <div class="strip"></div>
  <div class="body">
    <div class="status-badge">✗ Returned</div>
    <p>Your recruitment plan has been <strong>returned for revision</strong>. Please review the feedback below and re-submit after making the requested changes.</p>

    <div class="detail-box">
      <div class="detail-row">
        <span class="detail-label">Position:</span>
        <span class="detail-value">{{ $plan->jobPosition->title }}</span>
      </div>
      <div class="detail-row">
        <span class="detail-label">Department:</span>
        <span class="detail-value">{{ $plan->department->name }}</span>
      </div>
      <div class="detail-row">
        <span class="detail-label">Vacancies:</span>
        <span class="detail-value">{{ $plan->vacancies_needed }}</span>
      </div>
    </div>

    @if($plan->notes)
    <div class="reason-box">
      <h3>Reviewer's Feedback</h3>
      <p>{!! $plan->notes !!}</p>
    </div>
    @endif

    <div class="next-steps">
      <h3>What To Do</h3>
      <ul>
        <li>Open the recruitment plan in the ERP.</li>
        <li>Address the feedback above.</li>
        <li>Re-submit the plan for approval.</li>
      </ul>
    </div>

    @if(!empty($viewUrl))
    <div style="text-align:center;margin:24px 0 16px;">
      <a href="{{ $viewUrl }}" style="display:inline-block;background:#dc2626;color:#fff;font-weight:700;font-size:15px;padding:14px 36px;border-radius:8px;text-decoration:none;letter-spacing:.02em;">Edit Plan →</a>
    </div>
    @endif
  </div>
  <div class="footer">
    This email was sent by EliSOFT ERP Recruitment System. &copy; {{ date('Y') }} All rights reserved.
  </div>
</div>
</body>
</html>
