<?php

namespace App\Enums;

enum IdentityVerificationStatus: string
{
    /** No identity information has been submitted yet. */
    case Pending = 'pending';

    /** Information was submitted and is awaiting the automated review. */
    case Verifying = 'verifying';

    /** The submitted data and documents were matched successfully. */
    case Verified = 'verified';

    /** The automated review could not confidently match the data. */
    case Rejected = 'rejected';
}
