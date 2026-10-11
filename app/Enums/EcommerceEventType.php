<?php

namespace App\Enums;

enum EcommerceEventType: string
{
    case PortalVisit = 'portal_visit';

    case ProductViewed = 'product_viewed';

    case CartStarted = 'cart_started';

    case CheckoutStarted = 'checkout_started';

    case OrderCompleted = 'order_completed';
}
