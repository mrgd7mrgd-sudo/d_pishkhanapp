import { describe, it, expect } from 'vitest';
import {
  CASE_STATUSES,
  CASE_STATUS_META,
  ALLOWED_CASE_TRANSITIONS,
  canTransitionCase,
  assertValidCaseTransition,
  getCaseStatusMeta,
  InvalidCaseTransitionError,
  type CaseStatus,
} from './case-status.js';

describe('CaseStatus Domain', () => {
  it('should have exactly 11 valid case statuses', () => {
    expect(CASE_STATUSES).toHaveLength(11);
  });

  it('every case status should have complete non-empty Persian metadata', () => {
    for (const status of CASE_STATUSES) {
      const meta = getCaseStatusMeta(status);
      expect(meta).toBeDefined();
      expect(meta.code).toBe(status);
      expect(meta.label).toBeTruthy();
      expect(typeof meta.label).toBe('string');
      expect(meta.label.trim().length).toBeGreaterThan(0);
      expect(meta.color).toBeTruthy();
      expect(typeof meta.isTerminal).toBe('boolean');
    }
  });

  it('correctly identifies terminal statuses', () => {
    expect(CASE_STATUS_META.completed.isTerminal).toBe(true);
    expect(CASE_STATUS_META.rejected.isTerminal).toBe(true);
    expect(CASE_STATUS_META.cancelled.isTerminal).toBe(true);
    expect(CASE_STATUS_META.draft.isTerminal).toBe(false);
    expect(CASE_STATUS_META.expert_review.isTerminal).toBe(false);
  });

  describe('Case Transition State Machine (§3.5 Architecture)', () => {
    it('accepts all allowed transitions according to §3.5', () => {
      for (const [fromStatus, allowedTargets] of Object.entries(ALLOWED_CASE_TRANSITIONS)) {
        const from = fromStatus as CaseStatus;
        for (const to of allowedTargets) {
          expect(canTransitionCase(from, to)).toBe(true);
          expect(() => assertValidCaseTransition(from, to)).not.toThrow();
        }
      }
    });

    it('rejects all disallowed transitions', () => {
      for (const from of CASE_STATUSES) {
        const allowedTargets = ALLOWED_CASE_TRANSITIONS[from];
        for (const to of CASE_STATUSES) {
          if (!allowedTargets.includes(to)) {
            expect(canTransitionCase(from, to)).toBe(false);
            expect(() => assertValidCaseTransition(from, to)).toThrow(InvalidCaseTransitionError);
          }
        }
      }
    });

    it('creates informative InvalidCaseTransitionError message', () => {
      const err = new InvalidCaseTransitionError('draft', 'completed');
      expect(err.message).toContain("گذار غیرمجاز وضعیت پرونده از 'draft' به 'completed'");
      expect(err.name).toBe('InvalidCaseTransitionError');
    });
  });
});
