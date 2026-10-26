const { test, expect } = require('@playwright/test');
const { mount, json, drag } = require('./helpers');

test('moving a meta box between tabs updates its submitted assignment', async ({ page }) => {
	await mount(page);
	await drag(page, page.locator('#post-sample'), page.locator('.tabifybox-post ul').nth(1), 20);
	await expect(page.locator('.tabifybox-post .tabify_tab').nth(1).locator('#post-sample')).toHaveCount(1);
	await expect(page.locator('#post-sample input')).toHaveAttribute('name', 'tabify[posttypes][post][tabs][1][items][]');
});

test('clicking a nested tab label shows the corresponding post type', async ({ page }) => {
	await mount(page);
	await page.locator('#tab-page span').click();
	await expect(page.locator('.tabifybox-page')).toBeVisible();
	await expect(page.locator('.tabifybox-post')).toBeHidden();
	await expect(page.locator('#tabify-submenu .current_tab')).toHaveValue('page');
});

test('reordering tabs keeps role permissions with the corresponding tab', async ({ page }) => {
	await mount(page);
	const tabs = page.locator('.tabifybox-post .tabify_tab');
	await drag(page, tabs.nth(1).locator('h2'), tabs.nth(0), -8);
	await expect(tabs.first().locator('h2 span')).toHaveText('Details');
	await expect(tabs.first().locator('input[value="editor"]')).toHaveAttribute('name', 'tabify[posttypes][post][tabs][0][permissions][]');
	await expect(tabs.nth(1).locator('input[value="administrator"]')).toHaveAttribute('name', 'tabify[posttypes][post][tabs][1][permissions][]');
	await expect(page.locator('#post-sample input')).toHaveAttribute('name', 'tabify[posttypes][post][tabs][1][items][]');
});

test('deleting an empty middle tab keeps unique indices', async ({ page }) => {
	await mount(page);
	await page.locator('#create_tab').click();
	const tabs = page.locator('.tabifybox-post .tabify_tab');
	await expect(tabs).toHaveCount(3);
	await tabs.nth(1).getByText('Remove', { exact: true }).click();
	await expect(tabs).toHaveCount(2);
	await page.locator('#create_tab').click();
	await expect(tabs).toHaveCount(3);
	for (let i = 0; i < 3; i++) {
		await expect(tabs.nth(i).locator('h2 input')).toHaveAttribute('name', `tabify[posttypes][post][tabs][${i}][title]`);
		await expect(tabs.nth(i).locator('input[type="checkbox"]')).toHaveAttribute('name', `tabify[posttypes][post][tabs][${i}][permissions][]`);
	}
});
