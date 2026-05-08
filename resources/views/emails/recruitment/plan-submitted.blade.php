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
  .strip{height:5px;background:#d97706;}
  .body{padding:40px 48px;}
  p{color:#374151;line-height:1.7;margin:0 0 16px;}
  .detail-box{background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:24px;margin:24px 0;}
  .detail-row{display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #e2e8f0;font-size:14px;}
  .detail-row:last-child{border-bottom:none;}
  .detail-label{color:#6b7a90;font-weight:500;}
  .detail-value{font-weight:700;color:#362A72;}
  .status-badge{background:#d97706;color:#fff;padding:6px 14px;border-radius:20px;font-size:12px;font-weight:700;letter-spacing:.04em;text-transform:uppercase;display:inline-block;margin-bottom:20px;}
  .next-steps{background:#fff7e6;border:1px solid #fcd34d;border-radius:8px;padding:20px 24px;margin:24px 0;}
  .next-steps h3{font-size:14px;font-weight:700;color:#92400e;margin:0 0 8px;}
  .next-steps ul{margin:0;padding-left:18px;color:#78350f;font-size:14px;line-height:2;}
  .footer{background:#f8fafc;border-top:1px solid #e2e8f0;padding:24px 48px;font-size:12px;color:#9ca3af;text-align:center;}
</style>
</head>
<body>
<div class="wrap">
  <div class="header">
    <div class="header-logo">Cawee ERP — Recruitment</div>
    <h1>Recruitment Plan Awaiting Your Approval</h1>
  </div>
  <div class="strip"></div>
  <div class="body">
    <div class="status-badge">⏳ Pending Approval</div>
    <p>A recruitment plan has been submitted and requires your review and approval.</p>

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
      <div class="detail-row">
        <span class="detail-label">Expected Hire Date:</span>
        <span class="detail-value">{{ $plan->expected_hire_date->format('d F Y') }}</span>
      </div>
      @if($plan->budget)
      <div class="detail-row">
        <span class="detail-label">Budget:</span>
        <span class="detail-value">ETB {{ number_format($plan->budget, 2) }}</span>
      </div>
      @endif
      <div class="detail-row">
        <span class="detail-label">Submitted By:</span>
        <span class="detail-value">{{ $plan->creator->name }}</span>
      </div>
      <div class="detail-row">
        <span class="detail-label">Submitted On:</span>
        <span class="detail-value">{{ now()->format('d F Y, H:i') }}</span>
      </div>
    </div>

    <div class="next-steps">
      <h3>Action Required</h3>
      <ul>
        <li>Please review the plan details carefully.</li>
        <li>Approve the plan to allow job posting creation, or return it with notes for revision.</li>
      </ul>
    </div>

    @if($plan->notes)
    <p><strong>Notes:</strong> {!! $plan->notes !!}</p>
    @endif

    @if(!empty($viewUrl))
    <div style="text-align:center;margin:32px 0 16px;">
      <a href="{{ $viewUrl }}" style="display:inline-block;background:#362A72;color:#fff;font-weight:700;font-size:15px;padding:14px 36px;border-radius:8px;text-decoration:none;letter-spacing:.02em;">Review Plan →</a>
    </div>
    @endif

    <p style="margin-top:16px;font-size:13px;color:#9ca3af;text-align:center;">
      Or log in to the ERP system to review and take action.
    </p>
  </div>
  <div class="footer">
    This email was sent by Cawee ERP Recruitment System. &copy; {{ date('Y') }} All rights reserved.
  </div>
</div>
</body>
</html>
