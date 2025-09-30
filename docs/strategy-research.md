# Strategy Research: Processor Fee Structures

This research surveys publicly documented fee structures from major payment providers to inform reusable fee strategies. Formulas below are expressed with variables:

- `B` – base amount (customer-facing price or send amount).
- `F` – fee amount produced by the strategy.
- `T` – total amount (amount collected from payer or total debit).
- `r` – percentage rate expressed as decimal (e.g., 2.9% ⇒ 0.029).
- `f` – fixed fee amount.

Unless noted otherwise, forward direction means **compute fees from a known base amount** (solve `F` and `T` given `B`). Backward direction means **solve the required base amount given a target total** (`T` known, solve `B` and `F`).

## Stripe

| Strategy | Formula | Typical Use Case | Direction Notes | Source |
| --- | --- | --- | --- | --- |
| Standard U.S. card processing | `F = B × 0.029 + 0.30`, `T = B + F`. Backward: `B = (T - 0.30) ÷ 1.029`. | Domestic Visa/Mastercard/AmEx acceptance in the United States. | Supports forward/backward; backward fails if `T < 0.30`. | [Stripe Pricing – Card payments](https://stripe.com/pricing)【1】 |
| International surcharge | `F = B × 0.015`, `T = B + F`. Backward: `B = T ÷ 1.015`. | Additional fee when the card is issued outside the merchant’s country. | Applies only when international card detected; forward/backward supported. | [Stripe Pricing – International cards](https://stripe.com/pricing)【1】 |
| Currency conversion (optional extension) | `F = B × 0.01` layered on top of above. | When currency conversion to customer currency is enabled. | Use via separate percentage-only strategy; direction same as surcharge. | [Stripe Pricing – Currency conversion](https://stripe.com/pricing)【1】 |

## PayPal

| Strategy | Formula | Typical Use Case | Direction Notes | Source |
| --- | --- | --- | --- | --- |
| Commercial transaction (U.S.) | `F = B × (0.0349 + r_{extra}) + (0.49 + f_{extra})`, `T = B + F`. Backward: `B = (T - 0.49 - f_{extra}) ÷ (1 + 0.0349 + r_{extra})`. | Online card/PayPal wallet checkout for U.S. merchants. Extra percentages cover cross-border (1.5%) or currency conversion surcharges. | Forward/backward supported when total exceeds fixed fee sum. Configure `r_{extra}` and `f_{extra}` for cross-border and flat add-ons. | [PayPal Merchant Fees](https://www.paypal.com/us/webapps/mpp/merchant-fees)【2】 |

## Adyen (Interchange++)

| Strategy | Formula | Typical Use Case | Direction Notes | Source |
| --- | --- | --- | --- | --- |
| Interchange++ breakdown | `F = B × (r_{interchange} + r_{scheme} + r_{markup}) + (f_{interchange} + f_{scheme} + f_{markup})`, `T = B + F`. Backward: `B = (T - Σf) ÷ (1 + Σr)`. | Card acquiring on Adyen’s Interchange++ pricing where interchange, scheme, and Adyen markup are itemized. | Requires per-transaction interchange & scheme inputs (from card/bin tables). Supports forward/backward when `T ≥ Σf`. | [Adyen Pricing – Interchange++](https://www.adyen.com/pricing)【3】 |

**Direction requirements:** Interchange rates vary by card type and region; the strategy expects context values such as `interchange_percentage`, `scheme_percentage`, and markup values. Backward calculations require the same context used for forward runs.

## Wise (Remittance)

| Strategy | Formula | Typical Use Case | Direction Notes | Source |
| --- | --- | --- | --- | --- |
| Variable + fixed transfer fee | `F = B × (r_{base} + r_{adj}) + (f_{base} + f_{adj})`, `T = B + F`. Backward: `B = (T - f_{base} - f_{adj}) ÷ (1 + r_{base} + r_{adj})`. | International money transfers where Wise publishes route-specific variable and fixed fees. Context supplies actual rates per currency corridor. | Supports forward/backward when totals exceed fixed sums. Use context overrides to inject corridor data obtained via Wise pricing API. | [Wise Fees and Pricing](https://wise.com/help/articles/2932695/fees-and-pricing)【4】 |

## Card Scheme Components

Card schemes publish assessment and cross-border fees that flow into acquirer pricing. For example, Visa U.S. assessment is 0.13% on credit volume with cross-border surcharges of 1.00%–1.80% depending on region.【5】 These values feed into interchange or scheme components in the Adyen strategy and should be mapped into `scheme_percentage`/`scheme_fixed` fields. Merchants must update these values when card networks revise schedules.

[5]: https://usa.visa.com/dam/VCOM/download/merchants/visa-merchant-data-standards-manual.pdf

## Strategy Implementations

The following strategies implement `FeeStrategyInterface` with BC Math arithmetic:

- `StripeStandardCardStrategy` – domestic flat + percentage fee. Backward uses `B = (T - f) ÷ (1 + r)` to solve net amount.
- `StripeInternationalSurchargeStrategy` – percentage-only surcharge stacked onto domestic pricing.
- `PayPalCommercialTransactionStrategy` – allows context-driven surcharges for cross-border, currency conversion, and additional flat fees.
- `AdyenInterchangePlusPlusStrategy` – aggregates interchange, scheme, and markup components supplied in context.
- `WiseTransferFeeStrategy` – models Wise’s variable + fixed corridor pricing with optional adjustments.
- `CompositeFeeStrategy` – orchestrates multiple strategies deterministically, using binary search for backward calculations when layered fees are present.

## Configuration Examples

### Standalone Strategies

```php
use SomeWork\FeeCalculator\Strategy\StripeStandardCardStrategy;
use SomeWork\FeeCalculator\Strategy\StripeInternationalSurchargeStrategy;

$stripeStandard = new StripeStandardCardStrategy();
$stripeInternational = new StripeInternationalSurchargeStrategy(
    'stripe.intl_eu_surcharge',
    '0.02' // 2% surcharge for a specific market
);
```

### Composite Strategy with Guardrails

```php
use SomeWork\FeeCalculator\Contracts\CalculationRequest;
use SomeWork\FeeCalculator\Strategy\CompositeFeeStrategy;
use SomeWork\FeeCalculator\Strategy\StripeStandardCardStrategy;
use SomeWork\FeeCalculator\Strategy\StripeInternationalSurchargeStrategy;
use SomeWork\FeeCalculator\Currency\Currency;
use SomeWork\FeeCalculator\ValueObject\Amount;

$composite = new CompositeFeeStrategy([
    new StripeStandardCardStrategy(),
    new StripeInternationalSurchargeStrategy(),
], 'stripe.full_stack');

$currency = new Currency('USD', 2);
$request = CalculationRequest::forward('stripe.full_stack', Amount::fromString('100.00', $currency));
$result = $composite->calculateForward($request);
```

**Guardrails and precedence rules:**

1. Order matters—strategies are evaluated sequentially in the array supplied to `CompositeFeeStrategy`; each strategy receives the original base amount, and composite totals sum all child fees.
2. Each child strategy **must** support the requested direction; otherwise `UnsupportedCalculationDirectionException` is thrown.
3. Backward calculations validate the requested total against the minimal achievable total (sum of fixed fees at zero base). If the total is too low, an `InvalidArgumentException` is raised.
4. Component-specific context should be nested under `['components' => ['strategy.name' => [...]]]`. A shared context block (`['shared' => [...]]`) can distribute common metadata to every child.
5. Precision defaults to scale `8`; adjust the constructor argument for finer tolerance when solving backward totals.

These patterns enable layered fee modelling for realistic processor setups while maintaining explicit control over directionality and context injection.


[1]: https://stripe.com/pricing
[2]: https://www.paypal.com/us/webapps/mpp/merchant-fees
[3]: https://www.adyen.com/pricing
[4]: https://wise.com/help/articles/2932695/fees-and-pricing
