{{-- Shared styles for the Developer inbox and the sender's "Report to Developer"
     page — builds on layouts.app's .ticket-card / .sidebar-card theme. --}}
<style>
    .dc-card { padding: 16px 18px 16px 22px; }
    .dc-card.urgency-High::before   { background: #e24b4a; }
    .dc-card.urgency-Medium::before { background: #f5c842; }
    .dc-card.urgency-Low::before    { background: #3fb950; }
    .dc-subject { font-family: 'Nunito', sans-serif; font-weight: 800; font-size: 15px; color: var(--gd); margin: 4px 0 6px; }
    .dc-details { font-size: 13px; color: #555; white-space: pre-line; word-break: break-word; }
    .dc-meta { font-size: 12px; color: #777; display: flex; flex-wrap: wrap; gap: 14px; margin-top: 10px; }
    .dc-chip { font-size: 11px; font-weight: 800; border-radius: 20px; padding: 2px 10px; white-space: nowrap; }
    .dc-status-Open         { background: #fff4cc; color: #8a6d00; }
    .dc-status-Acknowledged { background: #dbeafe; color: #1e4fa0; }
    .dc-status-Closed       { background: #d4f0d4; color: #1f6b2a; }
    .dc-urgency-High   { background: #fde8e8; color: #b42318; }
    .dc-urgency-Medium { background: #fff4cc; color: #8a6d00; }
    .dc-urgency-Low    { background: #e8f5e8; color: #2d6a2d; }
    .dc-note { background: var(--ygl); border-radius: 12px; padding: 10px 14px; font-size: 13px; margin-top: 12px; white-space: pre-line; word-break: break-word; }
    .dc-btn { font-family: 'Nunito', sans-serif; font-weight: 800; font-size: 13px; border-radius: 50px; padding: 6px 16px; border: 1.5px solid var(--bd); background: #fff; color: var(--gd); }
    .dc-btn.primary { background: var(--gd); color: var(--yg); border-color: var(--gd); }
    .dc-form label { font-size: 12px; font-weight: 800; color: var(--gd); text-transform: uppercase; letter-spacing: .4px; }
    .dc-empty { text-align: center; color: #888; padding: 40px 16px; background: #fff; border-radius: 16px; border: 1.5px dashed var(--bd); }
</style>
