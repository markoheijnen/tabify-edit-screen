const { expect } = require('@playwright/test');
const fs = require('node:fs');
const path = require('node:path');
const root = path.resolve(__dirname, '../..');

async function mount(page, { detection = false, needsDetection = false, respond = () => ({}) } = {}) {
	await page.route('http://tabify.test/**', async route => {
		if (new URL(route.request().url()).pathname === '/settings') {
			return route.fulfill({ contentType: 'text/html', body: fs.readFileSync(path.join(__dirname, 'settings.html'), 'utf8') });
		}
		return respond(route);
	});
	await page.goto('http://tabify.test/settings');
	await page.addScriptTag({ path: require.resolve('jquery/dist/jquery.js') });
	await page.addScriptTag({ path: require.resolve('jquery-ui-dist/jquery-ui.js') });
	await page.addScriptTag({ path: require.resolve('underscore/underscore-umd.js') });
	await page.evaluate(needs => {
		jQuery.fx.off = true;
		window.wp = { template: id => _.template(document.getElementById('tmpl-' + id).textContent, { interpolate: /\{\{([\s\S]+?)\}\}/g, variable: 'data' }) };
		window.tabify_l10 = { remove: 'Remove', cancel: 'Cancel', move_meta_boxes: 'Move meta boxes to' };
		window.tabify_detection = {
			posttype_links: { post: '/detect?post_type=post', page: '/detect?post_type=page' },
			needs_detection: { post: needs, page: needs },
			loading: 'Checking for meta boxes', complete: 'Meta boxes refreshed',
			empty: 'No meta boxes found', failed: 'Could not refresh. Try again.', unavailable: 'No editable screen'
		};
	}, needsDetection);
	const scripts = ['js/tabs.js', 'js/admin.js'];
	if (detection) scripts.push('features/detection/detection.js');
	for (const file of scripts) {
		await page.addScriptTag({ path: path.join(root, file) });
	}
	await expect(page.locator('.tabifybox-post ul').first()).toHaveClass(/ui-sortable/);
}

async function json(route, value, status = 200) {
	return route.fulfill({ status, contentType: 'application/json', body: JSON.stringify(value) });
}

async function drag(page, source, target, offset = 0) {
	const start = await source.boundingBox();
	const end = await target.boundingBox();
	await page.mouse.move(start.x + 30, start.y + start.height / 2);
	await page.mouse.down();
	await page.mouse.move(start.x + 35, start.y + start.height / 2 + 8, { steps: 3 });
	await page.mouse.move(end.x + 30, end.y + 10 + offset, { steps: 20 });
	await page.mouse.up();
}

module.exports = { mount, json, drag };
