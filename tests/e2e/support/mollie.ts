import { APIRequestContext, APIResponse, Page } from '@playwright/test';

export type MollieTestStatus = 'open' | 'paid' | 'failed' | 'canceled' | 'expired';

export const ING_ISSUER = 'ideal_INGBNL2A';

const MOLLIE_HOST = 'www.mollie.com';
const MOLLIE_TEST_MODE_URL = 'https://www.mollie.com/checkout/test-mode**';
const MAX_MOLLIE_REDIRECTS = 5;

const isMollieReturn = (url: URL): boolean =>
  url.host === MOLLIE_HOST && /^\/checkout\/(?:[a-z0-9]+\/)?return\//.test(url.pathname);

const redirectTarget = (response: APIResponse): URL | null => {
  const location = response.headers()['location'];

  return location === undefined ? null : new URL(location, response.url());
};

const followMollieRedirects = async (request: APIRequestContext, first: APIResponse): Promise<URL> => {
  let target = redirectTarget(first);
  for (let hop = 0; target !== null && target.host === MOLLIE_HOST && hop < MAX_MOLLIE_REDIRECTS; hop++) {
    target = redirectTarget(await request.get(target.toString(), { maxRedirects: 0 }));
  }

  if (target === null || target.host === MOLLIE_HOST) {
    throw new Error(`Mollie did not redirect back to the shop, last hop: ${String(target)}`);
  }

  return target;
};

export const selectIdealIssuer = async (page: Page, issuer: string): Promise<void> => {
  await page.waitForURL('https://www.mollie.com/checkout/select-issuer/ideal/**');
  await page.locator(`button[name=issuer][value=${issuer}]`).click();
};

const submitTestStatus = async (page: Page, status: MollieTestStatus): Promise<void> => {
  await page.waitForURL(MOLLIE_TEST_MODE_URL);
  await page.locator(`input[name=final_state][value=${status}]`).check();
  await page.locator('button[name=submit]').click();
};

export const completeTestPayment = async (page: Page, status: MollieTestStatus): Promise<void> => {
  await submitTestStatus(page, status);
};

export const completeTestPaymentWithoutReturning = async (page: Page, status: MollieTestStatus): Promise<string> => {
  const returnUrl = new Promise<string>((resolve, reject) => {
    void page.route(isMollieReturn, async (route) => {
      try {
        const shopUrl = await followMollieRedirects(page.context().request, await route.fetch({ maxRedirects: 0 }));
        resolve(shopUrl.toString());
      } catch (error) {
        reject(error);
      }
      await route.fulfill({ status: 200, contentType: 'text/html', body: '<p>Holding the customer at Mollie</p>' });
    });
  });

  await submitTestStatus(page, status);
  const url = await returnUrl;
  await page.unroute(isMollieReturn);

  return url;
};
