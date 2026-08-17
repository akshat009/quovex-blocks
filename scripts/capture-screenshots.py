"""
Captures WP.org-ready screenshots by opening real pages on the local site
in a real browser (Playwright) and saving PNGs of just the block's own
markup -- not the theme's header/nav/footer or the WordPress page title
("demo-list" etc., a testing artifact, not something a real visitor's page
would show). Real captures only -- WordPress.org requires screenshots to
reflect actual plugin output, not mockups.

Each entry's BLOCK_SELECTOR crops to that block's own wrapper element
(.fb-query-grid / .fb-content-showcase / .fb-notification-banner) --
whatever the block itself renders, including its own heading ("Latest
Articles & News" etc., which IS legitimate block output, unlike the page
title around it.

Setup (run once):
    pip install playwright
    playwright install chromium

Usage:
    1. Edit the PAGES list below -- each entry is (url_path, output_filename).
    2. Create the matching demo pages in wp-admin first (see readme checklist).
    3. Run: python scripts/capture-screenshots.py
    4. PNGs land in scripts/output/ -- move the ones you want into
       .wordpress-org/ (or wherever your SVN assets/ folder is) and rename
       to screenshot-1.png, screenshot-2.png, ... to match readme.txt.
"""

from pathlib import Path
from playwright.sync_api import sync_playwright

SITE_URL = "http://my-plugin-check.local"

BLOCK_SELECTOR = {
	"query-grid": ".fb-query-grid",
	"content-showcase": ".fb-content-showcase",
	"notification-banner": ".fb-notification-banner",
}

# (url path on SITE_URL, output filename, which block wraps it, optional
# CSS selector to click before capturing)
PAGES = [
	("/demo-grid/", "query-grid-grid.png", "query-grid", None),
	("/demo-list/", "query-grid-list.png", "query-grid", None),
	("/demo-carauosel/", "query-grid-carousel.png", "query-grid", None),
	("/demo-masonary/", "query-grid-masonry.png", "query-grid", None),  # skip until images vary -- see chat
	("/demo-sidebar/", "query-grid-sidebar.png", "query-grid", None),
	("/demo-load-more/", "query-grid-load-more.png", "query-grid", None),
	("/demo-magazine/", "content-showcase-magazine.png", "content-showcase", None),
	("/demo-split/", "content-showcase-split.png", "content-showcase", None),
	("/demo-overlay/", "content-showcase-overlay.png", "content-showcase", None),
	("/demo-two-thirds/", "content-showcase-two-thirds.png", "content-showcase", None),
	# Clicks the first hotspot pin so its popover is open in the screenshot.
	("/demo-hotspot/", "content-showcase-hotspot.png", "content-showcase", ".fb-hotspot__pin"),
	("/demo-banner/", "notification-banner.png", "notification-banner", None),
	("/demo-banner-warning/", "notification-banner-warning.png", "notification-banner", None),
	("/demo-banner-success/", "notification-banner-success.png", "notification-banner", None),
]

OUTPUT_DIR = Path(__file__).parent / "output"
VIEWPORT = {"width": 1280, "height": 900}


def capture():
	OUTPUT_DIR.mkdir(exist_ok=True)

	with sync_playwright() as p:
		browser = p.chromium.launch()
		page = browser.new_page(viewport=VIEWPORT)

		for url_path, filename, block, click_selector in PAGES:
			url = SITE_URL.rstrip("/") + url_path
			print(f"Capturing {url} -> {filename}")

			page.goto(url, wait_until="networkidle")

			# Item images use loading="lazy" (real, correct behavior for a
			# live site) -- the browser never fetches them until they
			# scroll near the viewport, so a tall block (List/Masonry with
			# many stacked items) would screenshot with blank images past
			# the first viewport-height's worth. Scrolling the whole page
			# through once forces every image to come into view and load
			# before the crop below.
			page.evaluate(
				"""async () => {
					const step = window.innerHeight;
					const height = document.body.scrollHeight;
					for (let y = 0; y < height; y += step) {
						window.scrollTo(0, y);
						await new Promise((r) => setTimeout(r, 120));
					}
					window.scrollTo(0, 0);
				}"""
			)
			page.wait_for_load_state("networkidle")

			if click_selector:
				page.click(click_selector)
				page.wait_for_timeout(300)  # let the popover transition finish

			page.locator(BLOCK_SELECTOR[block]).first.screenshot(
				path=str(OUTPUT_DIR / filename)
			)

		browser.close()

	print(f"\nDone. Screenshots saved to {OUTPUT_DIR}")


if __name__ == "__main__":
	capture()
