const { test, expect } = require('@playwright/test');
const { mount, json } = require('./helpers');

test('fresh caches wait for manual refresh and preserve existing assignments', async ({ page }) => {
	let requests = 0;
	await mount(page, { detection: true, respond: route => { requests++; return json(route, { sample: { title: 'Sample' }, extra: { title: 'Extra' } }); } });
	await expect(page.locator('.tabify-detection-status')).toBeEmpty();
	expect(requests).toBe(0);
	await page.getByRole('button', { name: 'Refresh meta boxes' }).click();
	await expect(page.locator('#post-extra')).toBeVisible();
	await expect(page.locator('#post-extra input')).toHaveAttribute('name', 'tabify[posttypes][post][tabs][1][items][]');
	await expect(page.locator('.tabifybox-post .tabify_tab').first().locator('#post-sample')).toBeVisible();
	await page.getByRole('button', { name: 'Refresh meta boxes' }).click();
	await expect.poll(() => requests).toBe(2);
	await expect(page.locator('#post-extra')).toHaveCount(1);
});

test('missing caches detect automatically and failures can be retried', async ({ page }) => {
	let requests = 0;
	await mount(page, { detection: true, needsDetection: true, respond: route => ++requests === 1 ? json(route, { success: false }, 503) : json(route, { recovered: { title: 'Recovered' } }) });
	await expect(page.getByRole('status')).toHaveText('Could not refresh. Try again.');
	await page.getByRole('button', { name: 'Refresh meta boxes' }).click();
	await expect(page.locator('#post-recovered')).toBeVisible();
	await expect(page.getByRole('status')).toHaveText('Meta boxes refreshed');
});

test('invalid JSON results are recoverable and titles are rendered as text', async ({ page }) => {
	let requests = 0;
	await mount(page, { detection: true, needsDetection: true, respond: route => json(route, ++requests === 1 ? false : requests === 2 ? { success: false } : { safe: { title: '<img src=x onerror="window.injected=true">' } }) });
	await expect(page.getByRole('status')).toHaveText('Could not refresh. Try again.');
	await page.getByRole('button', { name: 'Refresh meta boxes' }).click();
	await expect.poll(() => requests).toBe(2);
	await expect(page.getByRole('button', { name: 'Refresh meta boxes' })).toBeEnabled();
	await expect(page.getByRole('status')).toHaveText('Could not refresh. Try again.');
	await page.getByRole('button', { name: 'Refresh meta boxes' }).click();
	await expect(page.locator('#post-safe .item-title')).toHaveText('<img src=x onerror="window.injected=true">');
	await expect(page.locator('#post-safe img')).toHaveCount(0);
	expect(await page.evaluate(() => window.injected)).toBeUndefined();
});

test('a pending request disables refresh and a nested tab label selects the correct post type', async ({ page }) => {
	const requests = [];
	let release;
	const hold = new Promise(resolve => { release = resolve; });
	await mount(page, { detection: true, needsDetection: true, respond: async route => {
		const type = new URL(route.request().url()).searchParams.get('post_type');
		requests.push(type);
		if (type === 'post') await hold;
		return json(route, { [type + '-extra']: { title: type + ' extra' } });
	} });
	await expect(page.getByRole('button', { name: 'Refresh meta boxes' })).toBeDisabled();
	await page.locator('#tab-page span').click();
	await expect(page.locator('.tabifybox-page')).toBeVisible();
	await expect(page.locator('#page-page-extra')).toBeVisible();
	release();
	await expect(page.locator('#post-post-extra')).toHaveCount(1);
	await expect(page.locator('.tabifybox-post')).toBeHidden();
	expect(requests).toEqual(['post', 'page']);
});
