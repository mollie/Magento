import { test } from '@playwright/test';
import { expectOrderPaidByWebhook, expectSuccessPageAfterReturning } from '../support/assertions';
import { restockProduct, SIMPLE_PRODUCT } from '../support/catalog';
import { addProductToCart, placeGuestOrder } from '../support/checkout';
import { CountryCode, createCustomer } from '../support/customer';
import { enableTestCurrencies, switchCurrency, TestCurrency } from '../support/currency';
import { completeTestPaymentWithoutReturning } from '../support/mollie';

type RedirectMethod = {
  readonly name: string;
  readonly code: string;
  readonly country?: CountryCode;
  readonly currency?: TestCurrency;
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
];

test.beforeAll(() => enableTestCurrencies());
test.beforeEach(() => restockProduct(SIMPLE_PRODUCT));

for (const method of REDIRECT_METHODS) {
  test(`a paid ${method.name} payment is processed by the webhook and shows the success page`, async ({ page }) => {
    const customer = createCustomer(method.country ?? 'NL');

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
