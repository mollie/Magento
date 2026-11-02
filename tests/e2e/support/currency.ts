import { Page } from '@playwright/test';
import { cleanConfigCache } from './config';
import { queryDatabase } from './database';

export type TestCurrency = 'CHF' | 'PLN' | 'SEK';

const BASE_CURRENCY = 'EUR';
const RATES: Readonly<Record<TestCurrency, number>> = { CHF: 0.94, PLN: 4.25, SEK: 11.2 };

const allowedCurrencies = (): string => [BASE_CURRENCY, ...Object.keys(RATES)].join(',');

export const enableTestCurrencies = (): void => {
  const rates = Object.entries(RATES)
    .map(([currency, rate]) => `('${BASE_CURRENCY}', '${currency}', ${rate})`)
    .join(', ');
  queryDatabase(`
    INSERT INTO core_config_data (scope, scope_id, path, value) VALUES ('default', 0, 'currency/options/allow', '${allowedCurrencies()}')
      ON DUPLICATE KEY UPDATE value = VALUES(value);
    INSERT INTO directory_currency_rate (currency_from, currency_to, rate) VALUES ${rates}
      ON DUPLICATE KEY UPDATE rate = VALUES(rate);
  `);
  cleanConfigCache();
};

export const switchCurrency = async (page: Page, currency: TestCurrency): Promise<void> => {
  await page.goto(`/directory/currency/switch/currency/${currency}/`);
};
