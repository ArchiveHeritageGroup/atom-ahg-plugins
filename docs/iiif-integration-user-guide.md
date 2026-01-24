# IIIF Image Viewer

## User Guide

View high-resolution images with advanced zoom, pan, and comparison features using the IIIF (International Image Interoperability Framework) viewer.

---

## Overview
```
┌─────────────────────────────────────────────────────────────┐
│                    IIIF IMAGE VIEWER                        │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  ┌─────────────────────────────────────────────────────┐   │
│  │                                                     │   │
│  │              High Resolution Image                  │   │
│  │                                                     │   │
│  │                  Zoom & Pan                         │   │
│  │                                                     │   │
│  └─────────────────────────────────────────────────────┘   │
│                                                             │
│  🔍+ │ 🔍- │ 🏠 Reset │ ⬜ Full │ 📐 Rotate │ ℹ️ Info       │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

## What is IIIF?
```
┌─────────────────────────────────────────────────────────────┐
│  IIIF BENEFITS                                              │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  🔍 Deep Zoom      - View tiny details at full resolution   │
│  ⚡ Fast Loading   - Only loads what you're viewing         │
│  📖 Multi-page     - Browse documents page by page          │
│  🔗 Shareable      - Link directly to specific views        │
│  📊 Comparable     - View images side by side               │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

## Basic Navigation

### Zooming
```
┌─────────────────────────────────────────────────────────────┐
│  ZOOM CONTROLS                                              │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  🔍+  Click       - Zoom in                                 │
│  🔍-  Click       - Zoom out                                │
│  Scroll wheel     - Zoom in/out                             │
│  Double-click     - Zoom to that point                      │
│  🏠 Home          - Reset to full view                      │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

### Panning
```
  Click and drag to move around the image
  
  ┌───────────────────────────────────────┐
  │                                       │
  │     ←  Drag to move  →                │
  │           ↑                           │
  │           ↓                           │
  │                                       │
  └───────────────────────────────────────┘
```

---

## Viewer Toolbar
```
┌─────────────────────────────────────────────────────────────┐
│  TOOLBAR BUTTONS                                            │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  🔍+     Zoom in                                            │
│  🔍-     Zoom out                                           │
│  🏠      Reset view (fit to screen)                         │
│  ⬜      Toggle fullscreen                                  │
│  📐      Rotate image 90°                                   │
│  🔄      Flip horizontal/vertical                           │
│  ℹ️      Show image information                             │
│  📥      Download current view                              │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

## Multi-Page Documents

For documents with multiple pages:
```
┌─────────────────────────────────────────────────────────────┐
│  PAGE NAVIGATION                                            │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  ◀ Previous  │  Page 3 of 25  │  Next ▶                    │
│                                                             │
│  ┌─────┐ ┌─────┐ ┌─────┐ ┌─────┐ ┌─────┐                   │
│  │  1  │ │  2  │ │ [3] │ │  4  │ │  5  │  ...              │
│  └─────┘ └─────┘ └─────┘ └─────┘ └─────┘                   │
│                                                             │
│  Click thumbnail or use arrows to navigate                  │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

## Fullscreen Mode

### Enter Fullscreen

Click the **Fullscreen** button (⬜) or press **F**

### Exit Fullscreen

Press **Escape** or click the fullscreen button again
```
┌─────────────────────────────────────────────────────────────┐
│                                                             │
│                                                             │
│                    FULLSCREEN VIEW                          │
│                                                             │
│              Best for detailed examination                  │
│                                                             │
│                                                             │
│                                    [Press ESC to exit]      │
└─────────────────────────────────────────────────────────────┘
```

---

## Sharing a Specific View

You can share a link to your exact view (zoom level and position):

### Step 1: Navigate to Your View

Zoom and pan to the area you want to share.

### Step 2: Copy Link

Click **Share** or copy the URL from your browser.

### Step 3: Send Link

The recipient will see the exact same view.
```
  Example link:
  https://archive.org/iiif/image123/view?x=500&y=300&zoom=5
```

---

## Keyboard Shortcuts
```
┌─────────────────────────────────────────────────────────────┐
│  KEY              │  ACTION                                 │
├───────────────────┼─────────────────────────────────────────┤
│  + or =           │  Zoom in                                │
│  - or _           │  Zoom out                               │
│  0 (zero)         │  Reset view                             │
│  F                │  Toggle fullscreen                      │
│  ← →              │  Previous / Next page                   │
│  Home             │  First page                             │
│  End              │  Last page                              │
│  R                │  Rotate 90°                             │
└───────────────────┴─────────────────────────────────────────┘
```

---

## Comparing Images

Some viewers allow side-by-side comparison:
```
┌────────────────────────┬────────────────────────┐
│                        │                        │
│    Image 1             │    Image 2             │
│                        │                        │
│  (Before restoration)  │  (After restoration)   │
│                        │                        │
└────────────────────────┴────────────────────────┘

  [Sync zoom: ON]  - Both images zoom together
```

---

## Tips
```
┌────────────────────────────────┬────────────────────────────┐
│  ✓ DO                          │  ✗ DON'T                   │
├────────────────────────────────┼────────────────────────────┤
│  Use fullscreen for detail     │  Squint at small views     │
│  Let tiles load before moving  │  Pan rapidly               │
│  Use keyboard for speed        │  Click everything          │
│  Share links to specific views │  Describe locations        │
│  Download for offline viewing  │  Screenshot low-res        │
└────────────────────────────────┴────────────────────────────┘
```

---

## Protected Content

Some images may require authentication to view at full resolution.

### Access Levels
```
┌─────────────────────────────────────────────────────────────┐
│  ACCESS TYPES                                               │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  🔓 Public          - No login required                     │
│  📋 Clickthrough    - Agree to terms of use                 │
│  🔐 Login Required  - Must have an account                  │
│  🚫 Restricted      - Special permission needed             │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

### Clickthrough Access

Some content requires you to agree to terms before viewing:
```
┌─────────────────────────────────────────────────────────────┐
│                     ACCESS REQUIRED                          │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  This resource requires acknowledgment of terms.            │
│                                                             │
│  By clicking "I Agree" you acknowledge that:                │
│  • This material is for personal research only              │
│  • You will not redistribute without permission             │
│  • Copyright may apply to this content                      │
│                                                             │
│                    [ I Agree ]                              │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

### Login Required

Protected content shows a login prompt:
```
┌─────────────────────────────────────────────────────────────┐
│                   AUTHENTICATION REQUIRED                    │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  This content requires you to log in.                       │
│                                                             │
│  Please log in to your account to access                    │
│  full resolution images.                                    │
│                                                             │
│                    [ Login ]                                │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

### What You'll See Without Access
```
Without authentication:
┌─────────────────────────────────────────────────────────────┐
│                                                             │
│            ┌─────────────────────┐                          │
│            │                     │                          │
│            │   Low-resolution    │ ← Thumbnail only         │
│            │     thumbnail       │                          │
│            │                     │                          │
│            └─────────────────────┘                          │
│                                                             │
│  🔐 Login to view full resolution                           │
│                                                             │
└─────────────────────────────────────────────────────────────┘

After authentication:
┌─────────────────────────────────────────────────────────────┐
│                                                             │
│  ┌───────────────────────────────────────────────────────┐ │
│  │                                                       │ │
│  │              Full resolution image                    │ │
│  │                                                       │ │
│  │              Deep zoom enabled                        │ │
│  │                                                       │ │
│  └───────────────────────────────────────────────────────┘ │
│                                                             │
│  🔓 Full access granted                                     │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

### Authentication Flow
```
1. View protected image
         │
         ▼
2. Click "Login" or "I Agree"
         │
         ▼
3. Pop-up window opens
         │
         ▼
4. Log in or accept terms ────► Window closes automatically
         │
         ▼
5. Full image loads
```

---

## Troubleshooting
```
Problem                          Solution
───────────────────────────────────────────────────────────
Image loads slowly            →  Wait for tiles to load
                                 Check internet connection
                                 
Blurry when zoomed            →  Wait for high-res tiles
                                 May be limit of original scan
                                 
Viewer won't load             →  Try refreshing page
                                 Try a different browser
                                 
Fullscreen not working        →  Browser may block it
                                 Try F11 for browser fullscreen
```

---

## Need Help?

Contact your system administrator if you experience issues.

---

*Part of the AtoM AHG Framework*
