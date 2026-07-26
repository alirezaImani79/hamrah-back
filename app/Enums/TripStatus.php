<?php

namespace App\Enums;

enum TripStatus: string
{
    /** The trip is scheduled and open for passengers to join. */
    case Scheduled = 'scheduled';

    /** The driver has started the trip and it is currently on the road. */
    case Ongoing = 'ongoing';

    /** The trip finished successfully. */
    case Completed = 'completed';

    /** The trip was cancelled by the driver before or during the ride. */
    case Cancelled = 'cancelled';
}
