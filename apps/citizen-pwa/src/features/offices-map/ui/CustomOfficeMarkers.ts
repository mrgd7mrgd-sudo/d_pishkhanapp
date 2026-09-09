import L from 'leaflet';
import type { OfficeMembershipStatus } from '@pishkhan/domain';

interface MarkerColorConfig {
  readonly bg: string;
  readonly border: string;
  readonly shadow: string;
  readonly text: string;
}

const STATUS_COLORS: Record<OfficeMembershipStatus, MarkerColorConfig> = {
  registered_online: {
    bg: '#059669', // emerald-600
    border: '#047857',
    shadow: 'rgba(5, 150, 105, 0.4)',
    text: '#ffffff',
  },
  registered_offline: {
    bg: '#d97706', // amber-600
    border: '#b45309',
    shadow: 'rgba(217, 119, 6, 0.4)',
    text: '#ffffff',
  },
  unregistered: {
    bg: '#64748b', // slate-500
    border: '#475569',
    shadow: 'rgba(100, 116, 139, 0.4)',
    text: '#ffffff',
  },
};

const getStatusBadgeSvg = (status: OfficeMembershipStatus): string => {
  switch (status) {
    case 'registered_online':
      // Sparkle / Zap icon
      return '<polygon points="12,2 15,9 22,9 17,14 19,21 12,17 5,21 7,14 2,9 9,9" fill="#ffffff" transform="scale(0.5) translate(6, 6)" />';
    case 'registered_offline':
      // Clock / Calendar icon
      return '<circle cx="12" cy="12" r="7" stroke="#ffffff" stroke-width="2" fill="none" transform="scale(0.6) translate(4, 4)" /><polyline points="12,8 12,12 15,14" stroke="#ffffff" stroke-width="2" stroke-linecap="round" fill="none" transform="scale(0.6) translate(4, 4)" />';
    case 'unregistered':
    default:
      // Info icon
      return '<circle cx="12" cy="12" r="7" stroke="#ffffff" stroke-width="2" fill="none" transform="scale(0.6) translate(4, 4)" /><line x1="12" y1="11" x2="12" y2="15" stroke="#ffffff" stroke-width="2" stroke-linecap="round" transform="scale(0.6) translate(4, 4)" /><circle cx="12" cy="8" r="1" fill="#ffffff" transform="scale(0.6) translate(4, 4)" />';
  }
};

export const createOfficeMarkerIcon = (
  status: OfficeMembershipStatus,
  isSelected = false,
): L.DivIcon => {
  const config = STATUS_COLORS[status] || STATUS_COLORS.unregistered;
  const size = isSelected ? 42 : 34;
  const pinSvg = `
    <div style="
      position: relative;
      width: ${size}px;
      height: ${size}px;
      display: flex;
      align-items: center;
      justify-content: center;
      transform: translate(-50%, -100%);
      cursor: pointer;
    " data-status="${status}" data-selected="${isSelected}">
      <svg width="${size}" height="${size}" viewBox="0 0 36 36" fill="none" style="filter: drop-shadow(0 4px 6px ${config.shadow});">
        <path d="M18 36C18 36 31.5 22.5 31.5 13.5C31.5 6.04416 25.4558 0 18 0C10.5442 0 4.5 6.04416 4.5 13.5C4.5 22.5 18 36 18 36Z" fill="${config.bg}" stroke="${config.border}" stroke-width="1.5" />
        <circle cx="18" cy="13.5" r="9" fill="rgba(0,0,0,0.15)" />
        ${getStatusBadgeSvg(status)}
      </svg>
      ${
        isSelected
          ? `<span style="
              position: absolute;
              inset: -4px;
              border-radius: 9999px;
              border: 2px solid ${config.bg};
              animation: ping 1.5s cubic-bezier(0, 0, 0.2, 1) infinite;
            "></span>`
          : ''
      }
    </div>
  `;

  return L.divIcon({
    html: pinSvg,
    className: 'custom-office-pin',
    iconSize: [size, size],
    iconAnchor: [size / 2, size],
    popupAnchor: [0, -size],
  });
};

export const createUserLocationIcon = (): L.DivIcon => {
  const html = `
    <div style="
      position: relative;
      width: 24px;
      height: 24px;
      display: flex;
      align-items: center;
      justify-content: center;
      transform: translate(-50%, -50%);
    " data-testid="user-location-marker">
      <span style="
        position: absolute;
        width: 24px;
        height: 24px;
        border-radius: 9999px;
        background-color: rgba(37, 99, 235, 0.35);
        animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
      "></span>
      <span style="
        position: relative;
        width: 12px;
        height: 12px;
        border-radius: 9999px;
        background-color: #2563eb;
        border: 2px solid #ffffff;
        box-shadow: 0 1px 3px rgba(0,0,0,0.3);
      "></span>
    </div>
  `;

  return L.divIcon({
    html,
    className: 'custom-user-location-pin',
    iconSize: [24, 24],
    iconAnchor: [12, 12],
  });
};
