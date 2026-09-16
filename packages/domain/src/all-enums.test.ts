import { describe, it, expect } from 'vitest';
import {
  TURN_OWNERS,
  getTurnOwnerMeta,
  RETURN_REASON_CODES,
  getReturnReasonMeta,
  SERVICE_TAGS,
  getServiceTagMeta,
  DELIVERY_DOC_TYPES,
  getDeliveryDocTypeMeta,
  DELIVERY_STATUSES,
  getDeliveryStatusMeta,
  COURIER_TYPES,
  getCourierTypeMeta,
  DELIVERY_PAYMENT_METHODS,
  getDeliveryPaymentMethodMeta,
  OFFICE_MEMBERSHIP_STATUSES,
  getOfficeMembershipStatusMeta,
  CONSULTATION_CATEGORIES,
  getConsultationCategoryMeta,
  CONSULTATION_MODES,
  getConsultationModeMeta,
  TIMELINE_STEP_STATUSES,
  getTimelineStepStatusMeta,
  CITIZEN_TIERS,
  getCitizenTierMeta,
  DELEGATION_STATUSES,
  getDelegationStatusMeta,
  PAYMENT_INTENT_STATUSES,
  PAYMENT_INTENT_STATUS_DEFINITIONS,
  PAYOUT_STATUSES,
  PAYOUT_STATUS_DEFINITIONS,
  APPOINTMENT_STATUSES,
  APPOINTMENT_STATUS_META,
  APPOINTMENT_ATTENDANCES,
  APPOINTMENT_ATTENDANCE_META,
  APPOINTMENT_COMPLETIONS,
  APPOINTMENT_COMPLETION_META,
  APPOINTMENT_REMINDER_TYPES,
  APPOINTMENT_REMINDER_TYPE_META,
  AI_CHANNELS,
  AI_MESSAGE_ROLES,
  getAiChannelMeta,
} from './index.js';

describe('Turn Owners (5 owners)', () => {
  it('has 5 turn owners with non-empty Persian labels and descriptions', () => {
    expect(TURN_OWNERS).toHaveLength(5);
    for (const owner of TURN_OWNERS) {
      const meta = getTurnOwnerMeta(owner);
      expect(meta.code).toBe(owner);
      expect(meta.label.trim().length).toBeGreaterThan(0);
      expect(meta.color.trim().length).toBeGreaterThan(0);
      expect(meta.description.trim().length).toBeGreaterThan(0);
    }
  });
});

describe('Return Reasons (10 codes)', () => {
  it('has 10 return codes with Persian labels, messages, and colors', () => {
    expect(RETURN_REASON_CODES).toHaveLength(10);
    for (const code of RETURN_REASON_CODES) {
      const meta = getReturnReasonMeta(code);
      expect(meta.code).toBe(code);
      expect(meta.label.trim().length).toBeGreaterThan(0);
      expect(meta.defaultMessage.trim().length).toBeGreaterThan(0);
      expect(meta.color.trim().length).toBeGreaterThan(0);
    }
  });
});

describe('Service Tags (3 tags)', () => {
  it('has 3 service tags with Persian labels', () => {
    expect(SERVICE_TAGS).toHaveLength(3);
    for (const tag of SERVICE_TAGS) {
      const meta = getServiceTagMeta(tag);
      expect(meta.code).toBe(tag);
      expect(meta.label.trim().length).toBeGreaterThan(0);
      expect(meta.color.trim().length).toBeGreaterThan(0);
      expect(meta.description.trim().length).toBeGreaterThan(0);
    }
  });
});

describe('Delivery Doc Types and Statuses', () => {
  it('has 6 delivery doc types with non-empty Persian labels', () => {
    expect(DELIVERY_DOC_TYPES).toHaveLength(6);
    for (const type of DELIVERY_DOC_TYPES) {
      const meta = getDeliveryDocTypeMeta(type);
      expect(meta.code).toBe(type);
      expect(meta.label.trim().length).toBeGreaterThan(0);
      expect(meta.color.trim().length).toBeGreaterThan(0);
    }
  });

  it('has 5 delivery statuses with non-empty Persian labels', () => {
    expect(DELIVERY_STATUSES).toHaveLength(5);
    for (const status of DELIVERY_STATUSES) {
      const meta = getDeliveryStatusMeta(status);
      expect(meta.code).toBe(status);
      expect(meta.label.trim().length).toBeGreaterThan(0);
      expect(meta.color.trim().length).toBeGreaterThan(0);
    }
  });
});

describe('Delivery Couriers and Payment Methods', () => {
  it('has 3 courier types with non-empty Persian labels', () => {
    expect(COURIER_TYPES).toHaveLength(3);
    for (const type of COURIER_TYPES) {
      const meta = getCourierTypeMeta(type);
      expect(meta.code).toBe(type);
      expect(meta.label.trim().length).toBeGreaterThan(0);
      expect(meta.color.trim().length).toBeGreaterThan(0);
    }
  });

  it('has 3 delivery payment methods with non-empty Persian labels', () => {
    expect(DELIVERY_PAYMENT_METHODS).toHaveLength(3);
    for (const method of DELIVERY_PAYMENT_METHODS) {
      const meta = getDeliveryPaymentMethodMeta(method);
      expect(meta.code).toBe(method);
      expect(meta.label.trim().length).toBeGreaterThan(0);
      expect(meta.color.trim().length).toBeGreaterThan(0);
    }
  });
});

describe('Office Membership Statuses (3 statuses)', () => {
  it('has 3 office membership statuses with non-empty Persian labels', () => {
    expect(OFFICE_MEMBERSHIP_STATUSES).toHaveLength(3);
    for (const status of OFFICE_MEMBERSHIP_STATUSES) {
      const meta = getOfficeMembershipStatusMeta(status);
      expect(meta.code).toBe(status);
      expect(meta.label.trim().length).toBeGreaterThan(0);
      expect(meta.color.trim().length).toBeGreaterThan(0);
      expect(meta.description.trim().length).toBeGreaterThan(0);
    }
  });
});

describe('Consultation Enums', () => {
  it('has 6 consultation categories with non-empty Persian labels', () => {
    expect(CONSULTATION_CATEGORIES).toHaveLength(6);
    for (const cat of CONSULTATION_CATEGORIES) {
      const meta = getConsultationCategoryMeta(cat);
      expect(meta.code).toBe(cat);
      expect(meta.label.trim().length).toBeGreaterThan(0);
      expect(meta.color.trim().length).toBeGreaterThan(0);
      expect(meta.description.trim().length).toBeGreaterThan(0);
    }
  });

  it('has 3 consultation modes with non-empty Persian labels', () => {
    expect(CONSULTATION_MODES).toHaveLength(3);
    for (const mode of CONSULTATION_MODES) {
      const meta = getConsultationModeMeta(mode);
      expect(meta.code).toBe(mode);
      expect(meta.label.trim().length).toBeGreaterThan(0);
      expect(meta.color.trim().length).toBeGreaterThan(0);
    }
  });
});

describe('Timeline, Citizen Tiers and Delegations', () => {
  it('has 5 timeline step statuses with non-empty Persian labels', () => {
    expect(TIMELINE_STEP_STATUSES).toHaveLength(5);
    for (const status of TIMELINE_STEP_STATUSES) {
      const meta = getTimelineStepStatusMeta(status);
      expect(meta.code).toBe(status);
      expect(meta.label.trim().length).toBeGreaterThan(0);
      expect(meta.color.trim().length).toBeGreaterThan(0);
    }
  });

  it('has 3 citizen tiers with non-empty Persian labels and badges', () => {
    expect(CITIZEN_TIERS).toHaveLength(3);
    for (const tier of CITIZEN_TIERS) {
      const meta = getCitizenTierMeta(tier);
      expect(meta.code).toBe(tier);
      expect(meta.label.trim().length).toBeGreaterThan(0);
      expect(meta.color.trim().length).toBeGreaterThan(0);
      expect(meta.badge.trim().length).toBeGreaterThan(0);
    }
  });

  it('has 4 delegation statuses with non-empty Persian labels', () => {
    expect(DELEGATION_STATUSES).toHaveLength(4);
    for (const status of DELEGATION_STATUSES) {
      const meta = getDelegationStatusMeta(status);
      expect(meta.code).toBe(status);
      expect(meta.label.trim().length).toBeGreaterThan(0);
      expect(meta.color.trim().length).toBeGreaterThan(0);
      expect(meta.description.trim().length).toBeGreaterThan(0);
    }
  });

  it('has 6 payment intent statuses with non-empty Persian labels', () => {
    expect(PAYMENT_INTENT_STATUSES).toHaveLength(6);
    for (const status of PAYMENT_INTENT_STATUSES) {
      const meta = PAYMENT_INTENT_STATUS_DEFINITIONS[status];
      expect(meta.code).toBe(status);
      expect(meta.label.trim().length).toBeGreaterThan(0);
    }
  });

  it('has 4 payout statuses with non-empty Persian labels', () => {
    expect(PAYOUT_STATUSES).toHaveLength(4);
    for (const status of PAYOUT_STATUSES) {
      const meta = PAYOUT_STATUS_DEFINITIONS[status];
      expect(meta.code).toBe(status);
      expect(meta.label.trim().length).toBeGreaterThan(0);
    }
  });

  it('has 3 appointment statuses with non-empty Persian labels', () => {
    expect(APPOINTMENT_STATUSES).toHaveLength(3);
    for (const status of APPOINTMENT_STATUSES) {
      const meta = APPOINTMENT_STATUS_META[status];
      expect(meta.code).toBe(status);
      expect(meta.label.trim().length).toBeGreaterThan(0);
    }
  });

  it('has 3 appointment attendances with non-empty Persian labels', () => {
    expect(APPOINTMENT_ATTENDANCES).toHaveLength(3);
    for (const attendance of APPOINTMENT_ATTENDANCES) {
      const meta = APPOINTMENT_ATTENDANCE_META[attendance];
      expect(meta.code).toBe(attendance);
      expect(meta.label.trim().length).toBeGreaterThan(0);
    }
  });

  it('has 4 appointment completions with non-empty Persian labels', () => {
    expect(APPOINTMENT_COMPLETIONS).toHaveLength(4);
    for (const completion of APPOINTMENT_COMPLETIONS) {
      const meta = APPOINTMENT_COMPLETION_META[completion];
      expect(meta.code).toBe(completion);
      expect(meta.label.trim().length).toBeGreaterThan(0);
    }
  });

  it('has 3 appointment reminder types with non-empty Persian labels', () => {
    expect(APPOINTMENT_REMINDER_TYPES).toHaveLength(3);
    for (const reminder of APPOINTMENT_REMINDER_TYPES) {
      const meta = APPOINTMENT_REMINDER_TYPE_META[reminder];
      expect(meta.code).toBe(reminder);
      expect(meta.label.trim().length).toBeGreaterThan(0);
    }
  });
});

describe('AI Assistance Enums (§6.1, §8.1)', () => {
  it('has 2 AI channels with Persian labels', () => {
    expect(AI_CHANNELS).toHaveLength(2);
    for (const channel of AI_CHANNELS) {
      const meta = getAiChannelMeta(channel);
      expect(meta.code).toBe(channel);
      expect(meta.label.trim().length).toBeGreaterThan(0);
    }
  });

  it('has 3 AI message roles', () => {
    expect(AI_MESSAGE_ROLES).toEqual(['user', 'assistant', 'system']);
  });
});
