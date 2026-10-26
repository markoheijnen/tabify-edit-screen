const { defineConfig } = require('@playwright/test');

module.exports = defineConfig({
	testDir: '.',
	testMatch: '*.spec.js',
	fullyParallel: true,
	forbidOnly: !!process.env.CI,
	retries: 0,
	use: {
		headless: true,
		channel: process.env.PLAYWRIGHT_CHANNEL || undefined,
		viewport: { width: 1280, height: 900 },
		trace: 'retain-on-failure'
	}
});
