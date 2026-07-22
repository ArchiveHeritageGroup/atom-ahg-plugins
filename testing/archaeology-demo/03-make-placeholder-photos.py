#!/usr/bin/env python3
"""
Generate placeholder object photographs for the archaeology demo.

These stand in for real finds photography so the browse, lightbox and IIIF views
have something to show. Each is drawn to look like a catalogue shot - neutral
background, object silhouette, scale bar, and the find number - and every image
is stamped DEMONSTRATION so it can never be mistaken for a real record photo.

Nothing here is copied from any external collection.
"""
import subprocess
import sys
from pathlib import Path

OUT = Path(sys.argv[1] if len(sys.argv) > 1 else '/tmp/arch-photos')
OUT.mkdir(parents=True, exist_ok=True)

W, H = 1600, 1200
BG = '#e8e4dc'        # neutral photographic backdrop
INK = '#2b2b2b'

# identifier, label, shape spec drawn with ImageMagick primitives
FINDS = [
    ('KRK-SF214', 'Glass beads', 'beads', '#3f6fa8'),
    ('KRK-SF215', 'Potsherds', 'sherds', '#a8663f'),
    ('KRK-SF221', 'Glass bead assemblage', 'beads', '#b8a13f'),
    ('KRK-SF224', 'Grindstone fragment', 'cobble', '#8a8a80'),
    ('KRK-SF230', 'Clay figurine fragment', 'figurine', '#9c6b4a'),
    ('KRK-SF231', 'Iron slag', 'lumps', '#4a4a4a'),
    ('KRK-SF238', 'Ostrich eggshell beads', 'beads', '#ded4bc'),
]


def shape_args(kind: str, colour: str) -> list:
    """ImageMagick draw primitives for each object class."""
    a = ['-fill', colour, '-stroke', '#00000055', '-strokewidth', '2']
    if kind == 'beads':
        cx, cy, r = 420, 620, 46
        for row in range(3):
            for col in range(5):
                x = cx + col * 130 + (row % 2) * 60
                y = cy + row * 130
                a += ['-draw', f'circle {x},{y} {x + r},{y}']
                a += ['-fill', '#00000033', '-draw', f'circle {x},{y} {x + 14},{y}', '-fill', colour]
    elif kind == 'sherds':
        a += ['-draw', 'polygon 380,520 700,470 780,720 640,820 400,760']
        a += ['-draw', 'polygon 840,560 1080,540 1140,760 900,800']
        a += ['-draw', 'polygon 470,880 720,860 760,1010 500,1030']
    elif kind == 'cobble':
        a += ['-draw', 'ellipse 800,700 300,190 0,360']
        a += ['-fill', '#00000022', '-draw', 'ellipse 800,690 210,120 0,360']
    elif kind == 'figurine':
        a += ['-draw', 'roundrectangle 700,470 900,900 40,40']
        a += ['-draw', 'circle 800,470 800,400']
    elif kind == 'lumps':
        a += ['-draw', 'polygon 460,620 700,570 760,800 520,860']
        a += ['-draw', 'polygon 820,660 1010,630 1060,820 850,860']
        a += ['-draw', 'polygon 640,880 850,870 880,990 660,1000']
    return a


made = []
for ident, label, kind, colour in FINDS:
    dest = OUT / f'{ident}.jpg'
    cmd = ['convert', '-size', f'{W}x{H}', f'xc:{BG}']
    cmd += shape_args(kind, colour)
    # scale bar - 100 mm, the archaeological convention
    cmd += ['-stroke', 'none', '-fill', INK, '-draw', 'rectangle 1150,1080 1450,1104']
    cmd += ['-fill', '#ffffff', '-draw', 'rectangle 1200,1080 1250,1104']
    cmd += ['-fill', '#ffffff', '-draw', 'rectangle 1300,1080 1350,1104']
    cmd += ['-fill', '#ffffff', '-draw', 'rectangle 1400,1080 1450,1104']
    cmd += ['-fill', INK, '-pointsize', '34', '-annotate', '+1150+1150', '100 mm']
    # find number and label
    cmd += ['-pointsize', '58', '-annotate', '+90+120', ident]
    cmd += ['-pointsize', '38', '-fill', '#555', '-annotate', '+90+180', label]
    # unmistakable demo watermark
    cmd += ['-pointsize', '46', '-fill', '#00000018', '-annotate', '+90+1150', 'DEMONSTRATION - NOT A REAL RECORD']
    cmd += ['-quality', '88', str(dest)]

    subprocess.run(cmd, check=True, capture_output=True)
    made.append((ident, dest, dest.stat().st_size))

for ident, path, size in made:
    print(f'  {ident:<11} {path.name:<18} {size // 1024} KB')
print(f'\n  {len(made)} placeholder images in {OUT}')
