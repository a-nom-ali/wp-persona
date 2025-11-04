import { defineConfig } from '@playwright/test';

const BASE_URL = process.env.AI_PERSONA_BASE_URL || 'http://campaign-forge.local';

export default defineConfig({
  testDir: './tests',
  use: {
    baseURL: BASE_URL,
    screenshot: 'only-on-failure',
    trace: 'retain-on-failure',
  },
});
