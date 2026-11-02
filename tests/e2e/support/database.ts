import { execFileSync } from 'node:child_process';

export const queryDatabase = (sql: string): string =>
  execFileSync('mysql', ['--batch', '--skip-column-names', '-e', sql], { encoding: 'utf8' }).trim();

export const quote = (value: string): string => `'${value.replace(/'/g, "''")}'`;
