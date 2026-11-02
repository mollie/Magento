import { expect, Page } from '@playwright/test';
import { releaseCustomer } from './mollie';
import { findLatestOrderByEmail, PlacedOrder } from './orders';

export const expectOrderPaidByWebhook = async (email: string, expectedState = 'processing'): Promise<PlacedOrder> => {
  await expect
    .poll(
      () => {
        const order = findLatestOrderByEmail(email);
        return `${order?.invoiceCount} invoice(s), state ${order?.state}`;
      },
      { message: 'the webhook created the invoice and finished the order', timeout: 60_000 },
    )
    .toBe(`1 invoice(s), state ${expectedState}`);

  const order = findLatestOrderByEmail(email);
  expect(order?.totalPaid).toBe(order?.grandTotal);

  return order as PlacedOrder;
};

export const expectSuccessPageForOrder = async (page: Page, returnUrl: string, incrementId: string): Promise<void> => {
  expect(new URL(returnUrl).pathname).toContain('/mpm/api/return');

  await releaseCustomer(page);
  await page.goto(returnUrl);

  await expect(page).toHaveURL(/checkout\/onepage\/success/);
  await expect(page.locator('body')).toContainText(`Your order # is: ${incrementId}`);
};

export const expectSuccessPageAfterReturning = async (
  page: Page,
  returnUrl: string,
  paidOrder: PlacedOrder,
  email: string,
): Promise<void> => {
  await expectSuccessPageForOrder(page, returnUrl, paidOrder.incrementId);
  const orderAfterReturn = findLatestOrderByEmail(email);
  expect(orderAfterReturn?.totalPaid).toBe(paidOrder.grandTotal);
  expect(orderAfterReturn?.invoiceCount).toBe(1);
};

export const expectOrderAuthorizedByWebhook = async (email: string): Promise<PlacedOrder> => {
  await expect
    .poll(() => findLatestOrderByEmail(email)?.state, {
      message: 'the webhook moved the authorized order to processing while the customer was still at Mollie',
      timeout: 60_000,
    })
    .toBe('processing');

  const order = findLatestOrderByEmail(email) as PlacedOrder;
  expect(order.invoiceCount).toBe(0);
  expect(order.totalPaid).toBeNull();

  return order;
};
