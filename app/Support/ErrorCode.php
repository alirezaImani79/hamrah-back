<?php

namespace App\Support;

/**
 * Stable, machine-readable codes returned on every API error response.
 *
 * The frontend uses these to look up a localized user-facing message instead
 * of parsing the English `message` string. The corresponding HTTP status is
 * documented per case as a hint, but the status code is always set by the
 * exception renderer in `bootstrap/app.php`, not by this enum.
 */
enum ErrorCode: string
{
    /** 422 — The request body failed validation rules. Per-field messages live under `errors`. */
    case ValidationFailed = 'VALIDATION_FAILED';

    /** 401 — No (or invalid) bearer token supplied. */
    case Unauthenticated = 'UNAUTHENTICATED';

    /** 403 — Authenticated, but a policy denied the action. */
    case Unauthorized = 'UNAUTHORIZED';

    /** 404 — A modeled resource (e.g. trip, vehicle, province) was not found. */
    case NotFound = 'NOT_FOUND';

    /** 404 — The requested route does not exist. */
    case EndpointNotFound = 'ENDPOINT_NOT_FOUND';

    /** 405 — The HTTP verb is not supported by the route. */
    case MethodNotAllowed = 'METHOD_NOT_ALLOWED';

    /** 429 — The rate-limit (throttle) middleware rejected the request. */
    case TooManyRequests = 'TOO_MANY_REQUESTS';

    /** 500 — Unexpected server error / generic fallback. */
    case ServerError = 'SERVER_ERROR';

    /** 422 — The OTP code is invalid, expired, already used, or exhausted its attempts. */
    case OtpInvalid = 'OTP_INVALID';

    /** 422 — A new OTP was requested within the per-number cooldown window. */
    case OtpRequestThrottled = 'OTP_REQUEST_THROTTLED';

    /** 409 — Identity verification is already in progress. */
    case IdentityVerificationInProgress = 'IDENTITY_VERIFICATION_IN_PROGRESS';

    /** 409 — The user's identity has already been verified. */
    case IdentityAlreadyVerified = 'IDENTITY_ALREADY_VERIFIED';

    /** 422 — The trip has no free seats left. */
    case TripFull = 'TRIP_FULL';

    /** 422 — The user has already joined this trip. */
    case TripAlreadyJoined = 'TRIP_ALREADY_JOINED';
}
