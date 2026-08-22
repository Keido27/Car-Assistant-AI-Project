export function formatIDR(value) {
  if (value === null || value === undefined) return '—';
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(value);
}

export function formatKm(value) {
  if (value === null || value === undefined) return '—';
  return `${new Intl.NumberFormat('id-ID').format(value)} km`;
}

export const CAR_STATUS_LABEL = {
  ready: 'Ready',
  booked: 'Booked',
  sold: 'Sold',
};

export const LEAD_STATUS_LABEL = {
  bot_handling: 'Bot handling',
  needs_handoff: 'Needs handoff',
  human_handling: 'With staff',
  visit_scheduled: 'Visit scheduled',
  converted: 'Converted',
  lost: 'Lost',
};
