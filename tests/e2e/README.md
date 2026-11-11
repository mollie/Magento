# End-to-end tests

Playwright tests that place real orders against Mollie test mode.

## Requirements

- An OpenMage shop with this module, Madison Island sample data, and Mollie configured in test mode with iDEAL enabled.
- Mollie must be able to reach the shop's webhook. For a local shop, start a tunnel (for example `ddev share --provider=cloudflared`), then set *Use webhooks* to *Custom URL* and enter `<tunnel url>/mpm/api/webhook/`.
- The tests read orders straight from the database with the `mysql` client, so they run inside the web container.

## Running

```bash
ddev exec -d /var/www/html/vendor/mollie/magento/tests/e2e 'npm ci && npx playwright install --with-deps chromium'
ddev exec -d /var/www/html/vendor/mollie/magento/tests/e2e 'npx playwright test'
```

The shop url comes from `MAGENTO_URL`, or from `DDEV_PRIMARY_URL` inside DDEV.

## How the iDEAL test proves the webhook works

The shop also processes the payment when the customer returns from Mollie. To make sure the webhook did the work, the test holds the browser at Mollie: it intercepts Mollie's redirect back to the shop, waits until the order is paid and invoiced, and only then sends the browser to the shop's return url.
