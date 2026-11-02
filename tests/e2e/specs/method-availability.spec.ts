import { expect, test } from '@playwright/test';
import { restockProduct, SIMPLE_PRODUCT } from '../support/catalog';
import { addProductToCart, getOfferedPaymentMethods } from '../support/checkout';
import { createBusinessCustomer, createCustomer } from '../support/customer';

test.beforeEach(() => restockProduct(SIMPLE_PRODUCT));

test('Bizum is offered to a Spanish customer', async ({ page }) => {
  await addProductToCart(page, SIMPLE_PRODUCT);

  const offered = await getOfferedPaymentMethods(page, createCustomer('ES'));

  expect(offered).toContain('mollie_bizum');
});

test('Bizum is not offered to a Dutch customer', async ({ page }) => {
  await addProductToCart(page, SIMPLE_PRODUCT);

  const offered = await getOfferedPaymentMethods(page, createCustomer('NL'));

  expect(offered).toContain('mollie_ideal');
  expect(offered).not.toContain('mollie_bizum');
});

test('Billie is offered to a customer with a company', async ({ page }) => {
  await addProductToCart(page, SIMPLE_PRODUCT);

  const offered = await getOfferedPaymentMethods(page, createBusinessCustomer('NL'));

  expect(offered).toContain('mollie_billie');
});

test('Billie is not offered to a customer without a company', async ({ page }) => {
  await addProductToCart(page, SIMPLE_PRODUCT);

  const offered = await getOfferedPaymentMethods(page, createCustomer('NL'));

  expect(offered).toContain('mollie_ideal');
  expect(offered).not.toContain('mollie_billie');
});
