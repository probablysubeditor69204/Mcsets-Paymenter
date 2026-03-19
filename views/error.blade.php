<div style="max-width:540px;margin:2rem auto;padding:1.25rem 1.5rem;background:#fff8f8;border:1px solid #fca5a5;border-radius:10px;font-family:system-ui,-apple-system,sans-serif;">
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
            <circle cx="12" cy="12" r="10" stroke="#ef4444" stroke-width="1.8"/>
            <path d="M12 8v4M12 16h.01" stroke="#ef4444" stroke-width="2" stroke-linecap="round"/>
        </svg>
        <strong style="color:#dc2626;font-size:15px;">Payment Error</strong>
    </div>
    <p style="margin:0;color:#7f1d1d;font-size:14px;line-height:1.6;">{{ $error }}</p>
    <a href="javascript:history.back()" style="display:inline-block;margin-top:14px;padding:8px 16px;background:#dc2626;color:#fff;border-radius:6px;text-decoration:none;font-size:13px;font-weight:500;">← Go Back</a>
</div>