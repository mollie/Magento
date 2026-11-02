import { test } from '@playwright/test';
import { expectOrderPaidByWebhook, expectSuccessPageAfterReturning } from '../support/assertions';
import { restockProduct, SIMPLE_PRODUCT } from '../support/catalog';
import { addProductToCart, placeGuestOrder } from '../support/checkout';
import { CountryCode, createBusinessCustomer, createCustomer } from '../support/customer';
import { enableTestCurrencies, switchCurrency, TestCurrency } from '../support/currency';
import { completeTestPaymentWithoutReturning } from '../support/mollie';

type RedirectMethod = {
  readonly name: string;
  readonly code: string;
  readonly country?: CountryCode;
  readonly currency?: TestCurrency;
  readonly isBusiness?: boolean;
};

const REDIRECT_METHODS: readonly RedirectMethod[] = [
  { name: 'Pay By Bank', code: 'mollie_paybybank' },
  { name: 'Wero', code: 'mollie_wero' },
  { name: 'Satispay', code: 'mollie_satispay' },
  { name: 'Bancomat Pay', code: 'mollie_bancomatpay' },
  { name: 'Bizum', code: 'mollie_bizum', country: 'ES' },
  { name: 'MB Way', code: 'mollie_mbway', country: 'PT' },
  { name: 'Vipps', code: 'mollie_vipps' },
  { name: 'MobilePay', code: 'mollie_mobilepay' },
  { name: 'TWINT', code: 'mollie_twint', currency: 'CHF' },
  { name: 'Blik', code: 'mollie_blik', currency: 'PLN' },
  { name: 'Swish', code: 'mollie_swish', currency: 'SEK' },
  { name: 'Billie', code: 'mollie_billie', isBusiness: true },
  { name: 'Multibanco', code: 'mollie_multibanco', country: 'PT' },
];

test.beforeAll(() => enableTestCurrencies());
test.beforeEach(() => restockProduct(SIMPLE_PRODUCT));

for (const method of REDIRECT_METHODS) {
  test(`a paid ${method.name} payment is processed by the webhook and shows the success page`, async ({ page }) => {
    const customer = method.isBusiness === true
      ? createBusinessCustomer(method.country ?? 'NL')
      : createCustomer(method.country ?? 'NL');

    if (method.currency !== undefined) {
      await switchCurrency(page, method.currency);
    }
    await addProductToCart(page, SIMPLE_PRODUCT);
    await placeGuestOrder(page, customer, method.code);
    const returnUrl = await completeTestPaymentWithoutReturning(page, 'paid');

    const paidOrder = await expectOrderPaidByWebhook(customer.email);
    await expectSuccessPageAfterReturning(page, returnUrl, paidOrder, customer.email);
  });
}
