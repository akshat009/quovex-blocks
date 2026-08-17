"""
Adds a professional frame to raw block screenshots -- a fake browser chrome
bar (traffic-light dots + address pill, so it reads as "a real running
site" at a glance), light padding, a subtle 1px border, soft drop shadow,
and gently rounded corners. Purely a presentation treatment on top of
scripts/capture-screenshots.py's real captures; doesn't touch the plugin's
own markup. Very tall captures (long post grids) are cropped to a punchier
max height first -- a screenshot that trails off mid-scroll reads better
than one showing every last item.

Usage:
    python scripts/frame-screenshots.py

Reads every *.png in scripts/output/ (skips already-framed files) and
writes framed-<name>.png alongside it. Review those, then rename the ones
you want to screenshot-1.png etc.
"""

from pathlib import Path

from PIL import Image, ImageDraw, ImageFilter, ImageFont

OUTPUT_DIR = Path(__file__).parent / "output"

MAX_SHOT_HEIGHT = 1500  # crop very tall captures (long grids) to this before framing
PADDING = 56  # space between the screenshot and the canvas edge
CANVAS_BG = (247, 247, 248, 255)  # soft neutral gray, not stark white
CORNER_RADIUS = 14
BORDER_COLOR = (225, 226, 229, 255)
BORDER_WIDTH = 1
SHADOW_COLOR = (15, 15, 20, 60)  # translucent -- composited, not opaque
SHADOW_BLUR = 28
SHADOW_OFFSET_Y = 14

CHROME_HEIGHT = 44
CHROME_BG = (255, 255, 255, 255)
CHROME_BORDER = (225, 226, 229, 255)
DOT_COLORS = [(237, 106, 94), (245, 191, 79), (97, 197, 79)]  # red, yellow, green
DOT_RADIUS = 6
DOT_GAP = 22
ADDRESS_BAR_BG = (241, 242, 244, 255)
ADDRESS_BAR_TEXT = (110, 112, 120, 255)
ADDRESS_TEXT = "my-plugin-check.local"


def rounded_mask(size: tuple, radius: int) -> Image.Image:
	mask = Image.new("L", size, 0)
	ImageDraw.Draw(mask).rounded_rectangle(
		[(0, 0), (size[0] - 1, size[1] - 1)], radius=radius, fill=255
	)
	return mask


def build_chrome_bar(width: int) -> Image.Image:
	"""Fake macOS-style browser chrome: traffic-light dots + an address pill."""
	bar = Image.new("RGBA", (width, CHROME_HEIGHT), CHROME_BG)
	draw = ImageDraw.Draw(bar)

	cy = CHROME_HEIGHT // 2
	for i, color in enumerate(DOT_COLORS):
		cx = 20 + i * DOT_GAP
		draw.ellipse(
			[(cx - DOT_RADIUS, cy - DOT_RADIUS), (cx + DOT_RADIUS, cy + DOT_RADIUS)],
			fill=color,
		)

	pill_w, pill_h = min(340, width - 220), 24
	pill_x = (width - pill_w) // 2
	pill_y = cy - pill_h // 2
	draw.rounded_rectangle(
		[(pill_x, pill_y), (pill_x + pill_w, pill_y + pill_h)],
		radius=pill_h // 2,
		fill=ADDRESS_BAR_BG,
	)
	try:
		font = ImageFont.truetype("segoeui.ttf", 13)
	except OSError:
		font = ImageFont.load_default()
	text_bbox = draw.textbbox((0, 0), ADDRESS_TEXT, font=font)
	text_w = text_bbox[2] - text_bbox[0]
	draw.text(
		(pill_x + (pill_w - text_w) / 2, pill_y + 4),
		ADDRESS_TEXT,
		fill=ADDRESS_BAR_TEXT,
		font=font,
	)

	return bar


def frame_one(src_path: Path, dest_path: Path) -> None:
	shot = Image.open(src_path).convert("RGBA")
	if shot.height > MAX_SHOT_HEIGHT:
		shot = shot.crop((0, 0, shot.width, MAX_SHOT_HEIGHT))
	shot_w, shot_h = shot.size

	chrome = build_chrome_bar(shot_w)
	# Composite chrome bar + screenshot into one block before the outer
	# frame/shadow/rounding treatment, so they read as a single window.
	block_h = CHROME_HEIGHT + shot_h
	block = Image.new("RGBA", (shot_w, block_h), (0, 0, 0, 0))
	block.paste(chrome, (0, 0))
	block.paste(shot, (0, CHROME_HEIGHT))
	draw = ImageDraw.Draw(block)
	draw.line([(0, CHROME_HEIGHT), (shot_w, CHROME_HEIGHT)], fill=CHROME_BORDER, width=1)

	block_w, block_h = block.size
	canvas_w = block_w + PADDING * 2
	canvas_h = block_h + PADDING * 2 + SHADOW_OFFSET_Y

	canvas = Image.new("RGBA", (canvas_w, canvas_h), CANVAS_BG)

	# Shadow: a blurred, rounded, dark rect placed slightly below where the
	# block will sit, composited onto its own full-size transparent layer
	# first so the Gaussian blur has room to spread without clipping.
	shadow_layer = Image.new("RGBA", (canvas_w, canvas_h), (0, 0, 0, 0))
	shadow_shape = Image.new("RGBA", (block_w, block_h), SHADOW_COLOR)
	shadow_mask = rounded_mask((block_w, block_h), CORNER_RADIUS)
	shadow_layer.paste(shadow_shape, (PADDING, PADDING + SHADOW_OFFSET_Y), shadow_mask)
	shadow_layer = shadow_layer.filter(ImageFilter.GaussianBlur(SHADOW_BLUR))
	canvas = Image.alpha_composite(canvas, shadow_layer)

	# Chrome bar + screenshot block, rounded corners via mask.
	block_mask = rounded_mask((block_w, block_h), CORNER_RADIUS)
	canvas.paste(block, (PADDING, PADDING), block_mask)

	# Thin border traced on top, same rounded rect as the mask.
	draw = ImageDraw.Draw(canvas)
	draw.rounded_rectangle(
		[
			(PADDING, PADDING),
			(PADDING + block_w - 1, PADDING + block_h - 1),
		],
		radius=CORNER_RADIUS,
		outline=BORDER_COLOR,
		width=BORDER_WIDTH,
	)

	canvas.convert("RGB").save(dest_path)


def main():
	pngs = sorted(
		p for p in OUTPUT_DIR.glob("*.png") if not p.name.startswith("framed-")
	)
	if not pngs:
		print(f"No PNGs found in {OUTPUT_DIR}")
		return

	for src in pngs:
		dest = src.with_name(f"framed-{src.name}")
		print(f"Framing {src.name} -> {dest.name}")
		frame_one(src, dest)

	print(f"\nDone. Framed versions saved to {OUTPUT_DIR}")


if __name__ == "__main__":
	main()
