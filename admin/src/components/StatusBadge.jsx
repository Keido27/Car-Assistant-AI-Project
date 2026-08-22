const STYLES = {
  ready: 'bg-confirm/10 text-confirm border-confirm/30',
  booked: 'bg-signal/10 text-signal-dim border-signal/30',
  sold: 'bg-bay-200 text-bay-600 border-bay-200',

  bot_handling: 'bg-bay-100 text-bay-600 border-bay-200',
  needs_handoff: 'bg-signal/15 text-signal-dim border-signal/40 animate-pulse',
  human_handling: 'bg-confirm/10 text-confirm border-confirm/30',
  visit_scheduled: 'bg-confirm/10 text-confirm border-confirm/30',
  converted: 'bg-confirm text-white border-confirm',
  lost: 'bg-bay-100 text-bay-400 border-bay-200 line-through',
};

export default function StatusBadge({ status, label }) {
  return (
    <span
      className={`inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-medium ${
        STYLES[status] || 'bg-bay-100 text-bay-600 border-bay-200'
      }`}
    >
      {label}
    </span>
  );
}
