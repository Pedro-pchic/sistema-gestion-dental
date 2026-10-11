<?php

namespace App\Actions;

use App\Enums\EcommerceEventType;
use App\Models\EcommerceEvent;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Throwable;

class RecordEcommerceEvent
{
    private const string PORTAL_VISIT_KEY = 'ecommerce_analytics.portal_visit_recorded';

    private const string LAST_PRODUCT_VIEW_KEY = 'ecommerce_analytics.last_product_viewed';

    private const string CART_STARTED_KEY = 'ecommerce_analytics.cart_started';

    private const string CHECKOUT_STARTED_KEY = 'ecommerce_analytics.checkout_started';

    public function recordPortalVisit(Request $request): void
    {
        $this->recordOnce($request, EcommerceEventType::PortalVisit, self::PORTAL_VISIT_KEY);
    }

    public function recordProductViewed(Request $request, Product $product): void
    {
        $this->recordOnce(
            $request,
            EcommerceEventType::ProductViewed,
            self::LAST_PRODUCT_VIEW_KEY,
            $product->getKey(),
            $product,
        );
    }

    public function recordCartStarted(Request $request): void
    {
        $this->recordOnce($request, EcommerceEventType::CartStarted, self::CART_STARTED_KEY);
    }

    public function recordCheckoutStarted(Request $request): void
    {
        $this->recordOnce($request, EcommerceEventType::CheckoutStarted, self::CHECKOUT_STARTED_KEY);
    }

    public function recordOrderCompleted(Request $request, Order $order): void
    {
        try {
            EcommerceEvent::query()->firstOrCreate(
                ['order_id' => $order->getKey()],
                [
                    'event_type' => EcommerceEventType::OrderCompleted,
                    'session_identifier' => $this->sessionIdentifier($request),
                    'occurred_at' => now(),
                ],
            );
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    public function resetCartLifecycle(Request $request): void
    {
        $request->session()->forget([
            self::CART_STARTED_KEY,
            self::CHECKOUT_STARTED_KEY,
        ]);
    }

    private function recordOnce(
        Request $request,
        EcommerceEventType $eventType,
        string $sessionKey,
        bool|int|null $sessionValue = true,
        ?Product $product = null,
    ): void {
        if ($request->session()->get($sessionKey) === $sessionValue) {
            return;
        }

        try {
            EcommerceEvent::query()->create([
                'event_type' => $eventType,
                'product_id' => $product?->getKey(),
                'session_identifier' => $this->sessionIdentifier($request),
                'occurred_at' => now(),
            ]);

            $request->session()->put($sessionKey, $sessionValue);
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    private function sessionIdentifier(Request $request): string
    {
        return hash('sha256', $request->session()->getId());
    }
}
