"""
Captures WP.org-ready screenshots by opening real pages on the local site
in a real browser (Playwright) and saving full-page PNGs. Real captures
only -- WordPress.org requires screenshots to reflect actual plugin output,
not mockups.

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

# (url path on SITE_URL, output filename, optional CSS selector to click before capturing)
PAGES = [
	("/demo-grid/", "query-grid-grid.png", None),
	("/demo-carousel/", "query-grid-carousel.png", None),
	("/demo-masonry/", "query-grid-masonry.png", None),
	("/demo-sidebar/", "query-grid-sidebar.png", None),
	("/demo-magazine/", "content-showcase-magazine.png", None),
	("/demo-split/", "content-showcase-split.png", None),
	("/demo-overlay/", "content-showcase-overlay.png", None),
	# Clicks the first hotspot pin so its popover is open in the screenshot.
	("/demo-hotspot/", "content-showcase-hotspot.png", ".fb-hotspot__pin"),
	("/demo-banner/", "notification-banner.png", None),
]

OUTPUT_DIR = Path(__file__).parent / "output"
VIEWPORT = {"width": 1280, "height": 900}


def capture():
	OUTPUT_DIR.mkdir(exist_ok=True)

	with sync_playwright() as p:
		browser = p.chromium.launch()
		page = browser.new_page(viewport=VIEWPORT)

		for url_path, filename, click_selector in PAGES:
			url = SITE_URL.rstrip("/") + url_path
			print(f"Capturing {url} -> {filename}")

			page.goto(url, wait_until="networkidle")

			if click_selector:
				page.click(click_selector)
				page.wait_for_timeout(300)  # let the popover transition finish

			page.screenshot(path=str(OUTPUT_DIR / filename), full_page=True)

		browser.close()

	print(f"\nDone. Screenshots saved to {OUTPUT_DIR}")


if __name__ == "__main__":
	capture()
