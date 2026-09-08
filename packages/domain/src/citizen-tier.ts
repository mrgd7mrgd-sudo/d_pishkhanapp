export const CITIZEN_TIERS = ['bronze', 'silver', 'gold'] as const;

export type CitizenTier = (typeof CITIZEN_TIERS)[number];

export interface CitizenTierMeta {
  readonly code: CitizenTier;
  readonly label: string;
  readonly color: string;
  readonly badge: string;
}

export const CITIZEN_TIER_META: Record<CitizenTier, CitizenTierMeta> = {
  bronze: {
    code: 'bronze',
    label: 'شهروند برنزی (پایه)',
    color: 'amber',
    badge: '🥉 برنزی',
  },
  silver: {
    code: 'silver',
    label: 'شهروند نقره‌ای (ویژه)',
    color: 'slate',
    badge: '🥈 نقره‌ای',
  },
  gold: {
    code: 'gold',
    label: 'شهروند طلایی (VIP)',
    color: 'yellow',
    badge: '🥇 طلایی',
  },
} as const;

export const getCitizenTierMeta = (tier: CitizenTier): CitizenTierMeta => {
  return CITIZEN_TIER_META[tier];
};
