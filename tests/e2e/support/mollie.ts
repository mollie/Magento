import { APIRequestContext, APIResponse, Page, Route } from '@playwright/test';

export type MollieTestStatus = 'open' | 'paid' | 'authorized' | 'failed' | 'canceled' | 'expired';

export const ING_ISSUER = 'ideal_INGBNL2A';

const MOLLIE_HOST = 'www.mollie.com';
const MOLLIE_TEST_MODE_URL = 'https://www.mollie.com/checkout/test-mode**';
const MAX_MOLLIE_REDIRECTS = 5;

const isMollieReturn = (url: URL): boolean =>
  url.host === MOLLIE_HOST && /^\/checkout\/(?:[a-z0-9]+\/)?return\//.test(url.pathname);

const isMollieTestMode = (url: URL): boolean => url.host === MOLLIE_HOST && url.pathname === '/checkout/test-mode';

const leadsBackToTheShop = (url: URL): boolean => isMollieReturn(url) || isMollieTestMode(url);

const redirectTarget = (response: APIResponse): URL | null => {
  const location = response.headers()['location'];

  return location === undefined ? null : new URL(location, response.url());
};

const followMollieRedirects = async (request: APIRequestContext, first: APIResponse): Promise<URL | null> => {
  let target = redirectTarget(first);
  for (let hop = 0; target !== null && target.host === MOLLIE_HOST && hop < MAX_MOLLIE_REDIRECTS; hop++) {
    target = redirectTarget(await request.get(target.toString(), { maxRedirects: 0 }));
  }

  return target !== null && target.host !== MOLLIE_HOST ? target : null;
};

const HOLDING_PAGE = { status: 200, contentType: 'text/html', body: '<p>Holding the customer at Mollie</p>' };

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

const isSupersededNavigation = (error: unknown): boolean =>
  error instanceof Error && /Route is already handled|Target page, context or browser has been closed/.test(error.message);

const holdCustomer = async (route: Route): Promise<void> => {
  try {
    await route.fulfill(HOLDING_PAGE);
  } catch (error) {
    if (!isSupersededNavigation(error)) {
      throw error;
    }
  }
};

/**
 * Keeps the browser at Mollie, also for later navigations back to the shop, until the test releases it
 * with releaseCustomer(). Mollie's page can navigate back more than once.
 */
export const completeTestPaymentWithoutReturning = async (page: Page, status: MollieTestStatus): Promise<string> => {
  let shopReturnUrl: string | null = null;
  const returnUrl = new Promise<string>((resolve) => {
    void page.route(leadsBackToTheShop, async (route) => {
      if (route.request().method() === 'GET' && isMollieTestMode(new URL(route.request().url()))) {
        await route.continue();
        return;
      }

      if (shopReturnUrl !== null) {
        await holdCustomer(route);
        return;
      }

      const response = await route.fetch({ maxRedirects: 0 });
      const shopUrl = await followMollieRedirects(page.context().request, response);
      if (shopUrl === null) {
        await route.fulfill({ response });
        return;
      }

      shopReturnUrl = shopUrl.toString();
      resolve(shopReturnUrl);
      await holdCustomer(route);
    });
  });

  await submitTestStatus(page, status);

  return returnUrl;
};

export const releaseCustomer = async (page: Page): Promise<void> => {
  await page.unroute(leadsBackToTheShop);
};
