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
  .strip{height:5px;background:#00A3E0;}
  .body{padding:40px 48px;}
  .congrats{font-size:32px;margin-bottom:16px;}
  p{color:#374151;line-height:1.7;margin:0 0 16px;}
  .detail-box{background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:24px;margin:24px 0;}
  .detail-row{display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #e2e8f0;font-size:14px;}
  .detail-row:last-child{border-bottom:none;}
  .detail-label{color:#6b7a90;font-weight:500;}
  .detail-value{font-weight:700;color:#003366;}
  .award-badge{background:#dcf5ea;color:#0d5c38;border:1px solid #a7d9bc;padding:6px 14px;border-radius:20px;font-size:12px;font-weight:700;letter-spacing:.04em;text-transform:uppercase;display:inline-block;margin-bottom:20px;}
  .cta{display:inline-block;background:#00A3E0;color:#fff;padding:14px 32px;border-radius:8px;text-decoration:none;font-weight:700;font-size:15px;margin-top:8px;}
  .next-steps{background:#fff7e6;border:1px solid #fcd34d;border-radius:8px;padding:20px 24px;margin:24px 0;}
  .next-steps h3{font-size:14px;font-weight:700;color:#92400e;margin:0 0 8px;}
  .next-steps ul{margin:0;padding-left:18px;color:#78350f;font-size:14px;line-height:2;}
  .footer{background:#f8fafc;border-top:1px solid #e2e8f0;padding:24px 48px;font-size:12px;color:#9ca3af;text-align:center;}
</style>
</head>
<body>
<div class="wrap">
  <div class="header">
    <div class="header-logo">EliSOFT ERP — Procurement</div>
    <h1>Your Bid Has Been Awarded</h1>
  </div>
  <div class="strip"></div>
  <div class="body">
    <div class="congrats">🏆</div>
    <div class="award-badge">✓ Bid Awarded</div>
    <p>Dear <strong>{{ $bid->supplier->name }}</strong>,</p>
    <p>
      We are pleased to inform you that following a thorough evaluation process, your bid for the
      procurement tender listed below has been <strong>selected and awarded</strong>.
    </p>

    <div class="detail-box">
      <div class="detail-row">
        <span class="detail-label">Tender Number:</span>
        <span class="detail-value">{{ $bid->tender->tender_number }}</span>
      </div>
      <div class="detail-row">
        <span class="detail-label">Tender Title:</span>
        <span class="detail-value">{{ $bid->tender->title }}</span>
      </div>
      <div class="detail-row">
        <span class="detail-label">Your Bid Amount:</span>
        <span class="detail-value">{{ $bid->currency }} {{ number_format($bid->bid_amount, 2) }}</span>
      </div>
      <div class="detail-row">
        <span class="detail-label">Delivery Period:</span>
        <span class="detail-value">{{ $bid->delivery_days }} days</span>
      </div>
      @if($bid->composite_score)
      <div class="detail-row">
        <span class="detail-label">Evaluation Score:</span>
        <span class="detail-value">{{ number_format($bid->composite_score, 1) }} / 100</span>
      </div>
      @endif
      <div class="detail-row">
        <span class="detail-label">Award Date:</span>
        <span class="detail-value">{{ now()->format('d F Y') }}</span>
      </div>
    </div>

    <div class="next-steps">
      <h3>Next Steps</h3>
      <ul>
        <li>Our procurement team will contact you within <strong>3 business days</strong> to finalise contract details.</li>
        <li>Please ensure your banking and delivery information is current in the supplier portal.</li>
        <li>A Purchase Order (PO) will be issued once the contract is signed.</li>
      </ul>
    </div>

    <p>You may log in to the supplier portal to view your bid status and monitor further communications.</p>
    <a href="{{ app()->environment('production') ? 'https://elisoft-erp.elisoftsolution.com/portal/' : url('/portal') }}" class="cta">Visit Supplier Portal →</a>

    <p style="margin-top:32px;font-size:14px;color:#6b7a90;">
      If you have questions, please contact our procurement office at
      <a href="mailto:procurement@company.com" style="color:#00A3E0;">procurement@company.com</a>.
    </p>
  </div>
  <div class="footer">
    This email was sent by EliSOFT ERP Procurement System. &copy; {{ date('Y') }} All rights reserved.
  </div>
</div>
</body>
</html>
