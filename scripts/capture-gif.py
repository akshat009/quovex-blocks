"""
Captures the #icon-gif animation in assets/design.html as a looping GIF --
takes a screenshot every FRAME_INTERVAL_MS during one full CSS animation
cycle (after letting it run once so all three staggered blocks are in
steady-state phase), then assembles the frames with Pillow.

Setup (skip if already done for the other capture-*.py scripts):
    pip install playwright pillow
    playwright install chromium

Usage:
    python scripts/capture-gif.py

Output: assets/icon-256x256.gif, assets/icon-128x128.gif -- these ARE the
plugin's icon files (animated), not a separate "preview" alongside static
PNGs of the same name; capture-assets.py's icon-128/icon-256 targets
produce the matching static PNG fallback, not a competing icon.
"""

import io
import time
from pathlib import Path

from PIL import Image
from playwright.sync_api import sync_playwright

ASSETS_DIR = Path(__file__).parent.parent / "assets"
SOURCE_FILE = ASSETS_DIR / "design.html"

# (element #id in design.html, output filename)
TARGETS = [
	("icon-gif-256", "icon-256x256.gif"),
	("icon-gif-128", "icon-128x128.gif"),
]

CYCLE_MS = 2000  # must match the CSS animation-duration on the #icon-gif-* rects
STEADY_STATE_DELAY_MS = 500  # let the initial staggered entrance settle first
FRAME_INTERVAL_MS = 60  # ~16-17fps, smooth enough for a small icon GIF


def capture_one(page, element_id: str, output_path: Path) -> None:
	element = page.locator(f"#{element_id}")
	frames = []

	# Let the first (staggered-delay) cycle run so every block is in its
	# repeating steady-state phase before we start capturing frames.
	page.wait_for_timeout(STEADY_STATE_DELAY_MS)

	frame_count = CYCLE_MS // FRAME_INTERVAL_MS
	print(f"Capturing #{element_id}: {frame_count} frames over one {CYCLE_MS}ms cycle...")

	start = time.monotonic()
	for i in range(frame_count):
		png_bytes = element.screenshot(omit_background=True)
		frames.append(Image.open(io.BytesIO(png_bytes)).convert("RGBA"))
		# Pace captures against wall-clock time, not just a fixed sleep, so
		# screenshot latency doesn't accumulate drift across frames.
		target = start + (i + 1) * (FRAME_INTERVAL_MS / 1000)
		remaining = target - time.monotonic()
		if remaining > 0:
			page.wait_for_timeout(remaining * 1000)

	# Composite each RGBA frame onto a white background -- GIF has no real
	# alpha channel (only single-color transparency), so keeping true
	# translucency from the fade in/out would look wrong; flattening onto
	# white matches how the icon is used everywhere else (white cards/pages).
	flattened = []
	for frame in frames:
		bg = Image.new("RGB", frame.size, (255, 255, 255))
		bg.paste(frame, mask=frame.split()[3])
		flattened.append(bg)

	# optimize=True lets Pillow silently drop frames it judges visually
	# identical to reduce file size (it happily collapsed the ~500ms static
	# "hold" phase); leaving it off keeps every captured frame's own
	# duration, preserving the real timing instead of speeding up playback.
	flattened[0].save(
		output_path,
		save_all=True,
		append_images=flattened[1:],
		duration=FRAME_INTERVAL_MS,
		loop=0,
		optimize=False,
	)
	print(f"  -> saved {len(flattened)} frames to {output_path}")


def capture():
	with sync_playwright() as p:
		browser = p.chromium.launch()
		page = browser.new_page(viewport={"width": 1600, "height": 700})
		page.goto(SOURCE_FILE.as_uri())

		for element_id, filename in TARGETS:
			capture_one(page, element_id, ASSETS_DIR / filename)

		browser.close()

	print(f"\nDone. Assets saved to {ASSETS_DIR}")


if __name__ == "__main__":
	capture()
