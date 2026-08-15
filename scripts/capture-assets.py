"""
Renders assets/design.html into exact-pixel PNGs -- WP.org banners and the
GitHub social preview. (The plugin icon is GIF-only, see capture-gif.py --
not duplicated here as a static PNG.) Each target is one #id'd <div> in
that file, sized exactly via CSS; this just element-screenshots each one,
so the PNG dimensions always match the div's CSS width/height exactly.

Setup (run once, skip if already done for capture-screenshots.py):
    pip install playwright
    playwright install chromium

Usage:
    python scripts/capture-assets.py

Output lands in assets/ (repo-relative), ready to use as-is:
    banner-772x250.png, banner-1544x500.png
    github-social-preview.png (1280x640 -- upload under repo Settings ->
    General -> Social preview)
"""

from pathlib import Path
from playwright.sync_api import sync_playwright

ASSETS_DIR = Path(__file__).parent.parent / "assets"
SOURCE_FILE = ASSETS_DIR / "design.html"

# (element #id in design.html, output filename)
TARGETS = [
	("banner-772", "banner-772x250.png"),
	("banner-1544", "banner-1544x500.png"),
	("github-preview", "github-social-preview.png"),
]


def capture():
	with sync_playwright() as p:
		browser = p.chromium.launch()
		# Large viewport so nothing in design.html gets clipped/wrapped
		# oddly before the element screenshot crops to its own box.
		page = browser.new_page(viewport={"width": 1600, "height": 700})
		page.goto(SOURCE_FILE.as_uri())

		for element_id, filename in TARGETS:
			output_path = ASSETS_DIR / filename
			print(f"Capturing #{element_id} -> {filename}")
			page.locator(f"#{element_id}").screenshot(
				path=str(output_path), omit_background=True
			)

		browser.close()

	print(f"\nDone. Assets saved to {ASSETS_DIR}")


if __name__ == "__main__":
	capture()
