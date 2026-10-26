const { test, expect } = require('@playwright/test');
const { mount, json, drag } = require('./helpers');

test('moving a meta box between tabs updates its submitted assignment', async ({ page }) => {
	await mount(page);
	await drag(page, page.locator('#post-sample'), page.locator('.tabifybox-post ul').nth(1), 20);
	await expect(page.locator('.tabifybox-post .tabify_tab').nth(1).locator('#post-sample')).toHaveCount(1);
	await expect(page.locator('#post-sample input')).toHaveAttribute('name', 'tabify[posttypes][post][tabs][1][items][]');
});
