<div style="font-family:Arial,Helvetica,sans-serif;background:#f4f6f9;padding:24px;">
  <div style="max-width:480px;margin:0 auto;background:#ffffff;border-radius:12px;overflow:hidden;border:1px solid #e5e7eb;">
    <div style="background:#5c0a0a;color:#ffffff;padding:18px 24px;font-weight:bold;font-size:18px;">ACADOCS</div>
    <div style="padding:24px;color:#111827;font-size:14px;line-height:1.6;">
      <p style="margin:0 0 12px;">Hi <?= esc($name) ?>,</p>
      <p style="margin:0 0 16px;">We received a request to reset your ACADOCS password. Enter this code on the reset page:</p>
      <div style="font-size:32px;font-weight:bold;letter-spacing:8px;text-align:center;background:#fff0f0;color:#5c0a0a;border-radius:8px;padding:14px 0;margin:0 0 16px;">
        <?= esc($code) ?>
      </div>
      <p style="margin:0 0 12px;">The code expires in <?= (int) $minutes ?> minutes.</p>
      <p style="margin:0;color:#6b7280;font-size:12px;">If you didn't ask to reset your password, you can ignore this email — your password stays the same.</p>
    </div>
  </div>
</div>
