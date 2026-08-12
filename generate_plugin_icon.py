"""
Generate a premium animated GIF plugin icon (256x256) for Flux Blocks.
Renders at 4x resolution (1024x1024) then downscales for crisp anti-aliasing.
"""
import math, os

try:
    from PIL import Image, ImageDraw, ImageFilter, ImageChops
except ImportError:
    os.system("pip install Pillow")
    from PIL import Image, ImageDraw, ImageFilter, ImageChops


RENDER_SIZE = 1024  # 4x supersampling for crisp edges
FINAL_SIZE = 256
TOTAL_FRAMES = 40
S = RENDER_SIZE  # alias


def lerp(a, b, t):
    return a + (b - a) * t


def lerp_color(c1, c2, t):
    return tuple(int(lerp(c1[i], c2[i], t)) for i in range(len(c1)))


def ease_in_out(t):
    return t * t * (3 - 2 * t)


def draw_gradient_rect(img, bbox, color_top, color_bottom, radius=0):
    """Draw a vertical gradient filled rounded rectangle."""
    x0, y0, x1, y1 = [int(v) for v in bbox]
    h = y1 - y0
    overlay = Image.new("RGBA", img.size, (0, 0, 0, 0))
    od = ImageDraw.Draw(overlay)
    # Draw rounded rect as mask
    mask = Image.new("L", img.size, 0)
    md = ImageDraw.Draw(mask)
    md.rounded_rectangle([x0, y0, x1, y1], radius=radius, fill=255)
    # Fill gradient
    for row in range(h):
        t = row / max(h - 1, 1)
        c = lerp_color(color_top, color_bottom, t)
        od.line([(x0, y0 + row), (x1, y0 + row)], fill=(*c, 255))
    # Apply mask
    overlay.putalpha(mask.split()[0] if hasattr(mask, 'split') else mask)
    img.alpha_composite(overlay)


def draw_bolt(draw, cx, cy, scale, color, width=0):
    """Draw a refined lightning bolt polygon."""
    pts_raw = [
        (0.00, -0.48),
        (0.06, -0.48),
        (-0.08, -0.02),
        (0.04, -0.02),
        (-0.12, 0.48),
        (-0.06, 0.48),
        (0.14, 0.02),
        (0.00, 0.02),
    ]
    polygon = [(cx + p[0] * scale, cy + p[1] * scale) for p in pts_raw]
    if width > 0:
        draw.polygon(polygon, outline=(*color, 255), width=width)
    else:
        draw.polygon(polygon, fill=(*color, 255))


def draw_stylized_bolt(img, cx, cy, scale, t):
    """Draw a multi-layered stylized bolt with glow."""
    draw = ImageDraw.Draw(img, "RGBA")

    # Color cycle
    phase = 0.5 + 0.5 * math.sin(t * 2 * math.pi)
    teal = (13, 148, 136)
    cyan = (6, 182, 212)
    bright = (103, 232, 249)
    white = (224, 242, 254)

    main_color = lerp_color(teal, cyan, phase)
    edge_color = lerp_color(cyan, bright, phase)
    core_color = lerp_color(bright, white, phase * 0.6)

    # Outer glow layer (blurred)
    glow_layer = Image.new("RGBA", img.size, (0, 0, 0, 0))
    gd = ImageDraw.Draw(glow_layer, "RGBA")
    glow_scale = scale * (1.25 + 0.08 * math.sin(t * 2 * math.pi))
    gd.polygon(
        _bolt_pts(cx, cy, glow_scale),
        fill=(*main_color, 60),
    )
    glow_layer = glow_layer.filter(ImageFilter.GaussianBlur(radius=28))
    img.alpha_composite(glow_layer)

    # Mid glow
    glow2 = Image.new("RGBA", img.size, (0, 0, 0, 0))
    gd2 = ImageDraw.Draw(glow2, "RGBA")
    gd2.polygon(
        _bolt_pts(cx, cy, scale * 1.08),
        fill=(*edge_color, 45),
    )
    glow2 = glow2.filter(ImageFilter.GaussianBlur(radius=14))
    img.alpha_composite(glow2)

    # Main bolt body
    draw.polygon(_bolt_pts(cx, cy, scale), fill=(*main_color, 240))

    # Inner highlight
    draw.polygon(
        _bolt_pts(cx - scale * 0.01, cy - scale * 0.01, scale * 0.7),
        fill=(*edge_color, 130),
    )

    # Core bright line
    draw.polygon(
        _bolt_pts(cx - scale * 0.005, cy, scale * 0.38),
        fill=(*core_color, 100),
    )


def _bolt_pts(cx, cy, scale):
    pts_raw = [
        (0.00, -0.48),
        (0.07, -0.48),
        (-0.07, -0.02),
        (0.05, -0.02),
        (-0.11, 0.48),
        (-0.04, 0.48),
        (0.14, 0.02),
        (0.01, 0.02),
    ]
    return [(cx + p[0] * scale, cy + p[1] * scale) for p in pts_raw]


def draw_card(img, x, y, w, h, accent, t_offset, t):
    """Draw a premium mini content card with subtle animation."""
    draw = ImageDraw.Draw(img, "RGBA")
    dark = (18, 24, 38)
    border = (*accent, 140)

    # Card background
    draw.rounded_rectangle(
        [x, y, x + w, y + h], radius=8, fill=(*dark, 230), outline=border, width=2
    )

    # Top accent bar
    bar_h = 5
    draw.rounded_rectangle(
        [x + 2, y + 2, x + w - 2, y + bar_h + 2],
        radius=3,
        fill=(*accent, int(120 + 60 * math.sin((t + t_offset) * 2 * math.pi))),
    )

    # Content lines
    line_y = y + bar_h + 10
    line_alpha = int(80 + 40 * math.sin((t + t_offset + 0.2) * 2 * math.pi))
    draw.rounded_rectangle(
        [x + 8, line_y, x + w - 12, line_y + 4], radius=2, fill=(120, 140, 160, line_alpha)
    )
    draw.rounded_rectangle(
        [x + 8, line_y + 9, x + w - 20, line_y + 13],
        radius=2,
        fill=(80, 100, 120, line_alpha - 20),
    )
    draw.rounded_rectangle(
        [x + 8, line_y + 18, x + w - 28, line_y + 22],
        radius=2,
        fill=(60, 80, 100, max(line_alpha - 40, 30)),
    )


def generate_frame(frame_idx):
    t = frame_idx / TOTAL_FRAMES

    img = Image.new("RGBA", (S, S), (0, 0, 0, 0))
    draw = ImageDraw.Draw(img, "RGBA")

    # --- Background with subtle gradient ---
    bg_top = (8, 12, 22)
    bg_bottom = (14, 20, 35)
    draw_gradient_rect(img, (0, 0, S - 1, S - 1), bg_top, bg_bottom, radius=80)

    # --- Subtle corner accents ---
    corner_alpha = int(20 + 12 * math.sin(t * 2 * math.pi))
    draw.ellipse([S - 200, S - 200, S + 50, S + 50], fill=(13, 148, 136, corner_alpha))
    draw.ellipse([-50, -50, 200, 200], fill=(6, 182, 212, corner_alpha))

    # Blur corners
    # (skip for performance, the gradient bg is enough)

    # --- Lightning bolt (centered, upper portion) ---
    bolt_cy = S * 0.40
    bolt_scale = S * 0.72 + S * 0.03 * math.sin(t * 2 * math.pi)
    draw_stylized_bolt(img, S // 2, bolt_cy, bolt_scale, t)

    # --- Three premium cards at bottom ---
    card_w = int(S * 0.22)
    card_h = int(S * 0.16)
    gap = int(S * 0.035)
    total_cards_w = card_w * 3 + gap * 2
    start_x = (S - total_cards_w) // 2
    base_y = int(S * 0.76)

    accents = [
        (13, 148, 136),   # teal
        (59, 130, 246),   # blue
        (245, 158, 11),   # amber
    ]

    for ci in range(3):
        phase = t + ci * 0.12
        bounce = int(S * 0.012 * math.sin(phase * 2 * math.pi))
        cx = start_x + ci * (card_w + gap)
        cy = base_y + bounce
        draw_card(img, cx, cy, card_w, card_h, accents[ci], ci * 0.15, t)

    # --- Downscale to final size with high quality ---
    final = img.resize((FINAL_SIZE, FINAL_SIZE), Image.LANCZOS)

    # Convert to RGB for GIF
    rgb = Image.new("RGB", (FINAL_SIZE, FINAL_SIZE), (8, 12, 22))
    rgb.paste(final, mask=final.split()[3])
    return rgb


# --- Generate all frames ---
print("Generating 40 frames at 1024x1024, downscaling to 256x256...")
frames = []
for i in range(TOTAL_FRAMES):
    frames.append(generate_frame(i))
    if (i + 1) % 10 == 0:
        print(f"  Frame {i + 1}/{TOTAL_FRAMES} done")

# --- Save outputs ---
output_dir = os.path.join(
    r"c:\Users\HP\Local Sites\my-plugin-check\app\public\wp-content\plugins\flux-blocks",
    "assets",
)
os.makedirs(output_dir, exist_ok=True)

gif_path = os.path.join(output_dir, "icon-256x256.gif")
frames[0].save(
    gif_path,
    save_all=True,
    append_images=frames[1:],
    duration=70,
    loop=0,
    optimize=True,
)
print(f"SUCCESS: Animated GIF -> {gif_path}")

png256 = os.path.join(output_dir, "icon-256x256.png")
frames[0].save(png256)
print(f"SUCCESS: Static PNG 256 -> {png256}")

png128 = os.path.join(output_dir, "icon-128x128.png")
frames[0].resize((128, 128), Image.LANCZOS).save(png128)
print(f"SUCCESS: Static PNG 128 -> {png128}")

print("\nDone! All icons in:", output_dir)
