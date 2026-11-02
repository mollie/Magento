import { execFileSync } from 'node:child_process';
import { queryDatabase, quote } from './database';

const MAGENTO_ROOT = process.env.MAGENTO_ROOT ?? '/var/www/html/pub';

export const cleanConfigCache = (): void => {
  execFileSync('php', ['-r', 'require "app/Mage.php"; Mage::app()->getCacheInstance()->cleanType("config");'], {
    cwd: MAGENTO_ROOT,
  });
};

export const getConfig = (path: string): string | null => {
  const value = queryDatabase(
    `SELECT value FROM core_config_data WHERE scope = 'default' AND scope_id = 0 AND path = ${quote(path)}`,
  );

  return value === '' ? null : value;
};

export const setConfig = (path: string, value: string | null): void => {
  queryDatabase(
    value === null
      ? `DELETE FROM core_config_data WHERE scope = 'default' AND scope_id = 0 AND path = ${quote(path)}`
      : `INSERT INTO core_config_data (scope, scope_id, path, value) VALUES ('default', 0, ${quote(path)}, ${quote(value)})
         ON DUPLICATE KEY UPDATE value = VALUES(value)`,
  );
  cleanConfigCache();
};
