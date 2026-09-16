<?php

declare(strict_types=1);

namespace App\Shared\Audit;

enum AuditableAction: string
{
    // Auth (§7.6)
    case AUTH_LOGIN_SUCCESS = 'auth.login.success';
    case AUTH_LOGIN_FAILED = 'auth.login.failed';
    case AUTH_LOGOUT = 'auth.logout';
    case AUTH_TOKEN_REVOKED = 'auth.token.revoked';
    case AUTH_OTP_REQUESTED = 'auth.otp.requested';
    case AUTH_DEVICE_NEW = 'auth.device.new';

    // Case Workflow (§7.6)
    case CASE_CREATED = 'case.created';
    case CASE_TRANSITION = 'case.transition';
    case CASE_RETURNED = 'case.returned';
    case CASE_REJECTED = 'case.rejected';
    case CASE_COMPLETED = 'case.completed';
    case CASE_CANCELLED = 'case.cancelled';

    // Dispatch (§7.6)
    case OFFER_CREATED = 'offer.created';
    case OFFER_ACCEPTED = 'offer.accepted';
    case OFFER_DECLINED = 'offer.declined';
    case OFFER_EXPIRED = 'offer.expired';

    // Documents (§7.6)
    case DOCUMENT_UPLOADED = 'document.uploaded';
    case DOCUMENT_VIEWED = 'document.viewed';
    case DOCUMENT_DOWNLOADED = 'document.downloaded';
    case DOCUMENT_APPROVED = 'document.approved';
    case DOCUMENT_REJECTED = 'document.rejected';
    case DOCUMENT_DELETED = 'document.deleted';

    // Finance (§7.6)
    case PAYMENT_INITIATED = 'payment.initiated';
    case PAYMENT_VERIFIED = 'payment.verified';
    case PAYMENT_FAILED = 'payment.failed';
    case LEDGER_POSTED = 'ledger.posted';
    case REFUND_ISSUED = 'refund.issued';
    case PAYOUT_GENERATED = 'payout.generated';

    // Delivery (§7.6)
    case DELIVERY_CREATED = 'delivery.created';
    case DELIVERY_DISPATCHED = 'delivery.dispatched';
    case DELIVERY_OTP_VERIFIED = 'delivery.otp.verified';
    case DELIVERY_FAILED = 'delivery.failed';

    // Delegation (§7.6)
    case DELEGATION_CREATED = 'delegation.created';
    case DELEGATION_ACTIVATED = 'delegation.activated';
    case DELEGATION_USED = 'delegation.used';
    case DELEGATION_REVOKED = 'delegation.revoked';

    // In-Person Appointments & Reviews (§7.6)
    case APPOINTMENT_ATTENDANCE_UPDATED = 'appointment.attendance_updated';
    case APPOINTMENT_COMPLETION_UPDATED = 'appointment.completion_updated';
    case REVIEW_SUBMITTED = 'review.submitted';
    case REVIEW_REPLIED = 'review.replied';
    case SLA_BREACH_RECORDED = 'sla.breach_recorded';
    case SLA_SCORES_RECALCULATED = 'sla.scores_recalculated';

    // Administration (§7.6)
    case OFFICE_APPROVED = 'office.approved';
    case OFFICE_SUSPENDED = 'office.suspended';
    case OFFICE_PROFILE_UPDATED = 'office.profile.updated';
    case OFFICE_SPECIALTIES_UPDATED = 'office.specialties.updated';
    case OFFICE_COVERAGES_UPDATED = 'office.coverages.updated';
    case OFFICE_ANNOUNCEMENT_CREATED = 'office.announcement.created';
    case OFFICE_ANNOUNCEMENT_UPDATED = 'office.announcement.updated';
    case OFFICE_ANNOUNCEMENT_DELETED = 'office.announcement.deleted';
    case OPERATOR_CREATED = 'operator.created';
    case OPERATOR_DISABLED = 'operator.disabled';
    case OPERATOR_UPDATED = 'operator.updated';
    case ADVISOR_APPROVED = 'advisor.approved';
    case SERVICE_UPDATED = 'service.updated';
    case ROLE_CHANGED = 'role.changed';

    // PII (§7.6)
    case PII_EXPORTED = 'pii.exported';
    case PII_SEARCHED = 'pii.searched';
    case PII_ANONYMIZED = 'pii.anonymized';
}
