const { test, expect } = require('@playwright/test');
const fs = require('node:fs');
const path = require('node:path');

/**
 * Regression: switching tabs must recalculate the editor layout *after* the meta box is visible.
 *
 * WordPress core (wp-admin/js/editor-expand.js) measures the editor toolbars to set the
 * `padding-top` of `.wp-editor-wrap`. When the meta box starts hidden (it is on another tab),
 * that measurement returns a stale height, so the wrap keeps a padding that no longer matches
 * its toolbars and an empty band stays between the media buttons and the editor toolbars.
 * Core only recomputes the padding on a `resize` event, which a tab switch never triggers.
 */
test('tab switch recalculates the editor layout once the panel is visible', async ({ page }) => {
	await page.route('http://tabify.test/**', (route) => {
		if (new URL(route.request().url()).pathname === '/edit') {
			return route.fulfill({
				contentType: 'text/html',
				body: fs.readFileSync(path.join(__dirname, 'editor.html'), 'utf8'),
			});
		}
		return route.fulfill({ status: 404, body: '' });
	});
	await page.goto('http://tabify.test/edit');
	await page.addScriptTag({ path: require.resolve('jquery/dist/jquery.js') });

	// Stub of wp-admin/js/editor-expand.js: the wrap padding is only recomputed on a resize event,
	// from the height of the toolbars measured at that moment.
	await page.evaluate(() => {
		jQuery.fx.off = true;
		window.__recalc = [];

		const wrap = document.getElementById('wp-content-wrap');
		const tools = wrap.querySelector('.wp-editor-tools');

		// Stale value: what core stores when it measures the toolbars of a hidden meta box.
		wrap.style.paddingTop = '0px';

		jQuery(window).on('resize', () => {
			const visible = wrap.offsetParent !== null;
			const height = visible ? tools.offsetHeight : 0;

			wrap.style.paddingTop = `${height}px`;
			window.__recalc.push({ visible, height });
		});
	});

	await page.addScriptTag({ path: path.resolve(__dirname, '../../js/tabs.js') });
	await expect.poll(() => page.evaluate(() => typeof jQuery.fn.tabify_tabs)).toBe('function');

	await page.locator('#tab-1').click();

	await expect(page.locator('#box-description')).toBeVisible();
	await expect(page.locator('#box-main')).toBeHidden();

	// The stale padding must be refreshed to the real height of the toolbars.
	const toolsHeight = await page.evaluate(
		() => document.querySelector('.wp-editor-tools').offsetHeight,
	);
	expect(toolsHeight).toBeGreaterThan(0);
	await expect
		.poll(() => page.evaluate(() => document.getElementById('wp-content-wrap').style.paddingTop))
		.toBe(`${toolsHeight}px`);

	// And the recalculation must have happened while the panel was visible, never before.
	const recalc = await page.evaluate(() => window.__recalc);
	expect(recalc.length).toBeGreaterThan(0);
	expect(recalc.every((entry) => entry.visible)).toBe(true);
});

test('switching back leaves the previous panel hidden and does not corrupt the padding', async ({ page }) => {
	await page.route('http://tabify.test/**', (route) => {
		if (new URL(route.request().url()).pathname === '/edit') {
			return route.fulfill({
				contentType: 'text/html',
				body: fs.readFileSync(path.join(__dirname, 'editor.html'), 'utf8'),
			});
		}
		return route.fulfill({ status: 404, body: '' });
	});
	await page.goto('http://tabify.test/edit');
	await page.addScriptTag({ path: require.resolve('jquery/dist/jquery.js') });

	await page.evaluate(() => {
		jQuery.fx.off = true;
		window.__recalc = [];

		const wrap = document.getElementById('wp-content-wrap');
		const tools = wrap.querySelector('.wp-editor-tools');
		wrap.style.paddingTop = '0px';

		jQuery(window).on('resize', () => {
			const visible = wrap.offsetParent !== null;
			wrap.style.paddingTop = `${visible ? tools.offsetHeight : 0}px`;
			window.__recalc.push({ visible });
		});
	});

	await page.addScriptTag({ path: path.resolve(__dirname, '../../js/tabs.js') });
	await expect.poll(() => page.evaluate(() => typeof jQuery.fn.tabify_tabs)).toBe('function');

	await page.locator('#tab-1').click();
	await expect(page.locator('#box-description')).toBeVisible();
	await page.locator('#tab-0').click();
	await expect(page.locator('#box-main')).toBeVisible();
	await expect(page.locator('#box-description')).toBeHidden();

	// Going back to the first tab must not leave the editor panel on screen.
	await expect
		.poll(() => page.evaluate(() => document.getElementById('wp-content-wrap').offsetParent === null))
		.toBe(true);
});
