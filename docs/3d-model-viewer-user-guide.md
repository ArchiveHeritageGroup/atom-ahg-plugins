# 3D Model Viewer

## User Guide

View and interact with 3D models of objects in your collection directly in your web browser.

---

## Overview
```
┌─────────────────────────────────────────────────────────────┐
│                    3D MODEL VIEWER                          │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│         🖱️ Rotate      🔍 Zoom      ↔️ Pan                   │
│              │            │           │                     │
│              ▼            ▼           ▼                     │
│        Click &       Scroll       Shift +                   │
│        Drag          Wheel        Drag                      │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

## Supported Formats
```
┌─────────────────────────────────────────────────────────────┐
│                    3D FILE FORMATS                          │
├─────────────────────────────────────────────────────────────┤
│  GLB/GLTF    - Standard web 3D format (recommended)         │
│  USDZ        - Apple AR format                              │
│  OBJ         - Common 3D format                             │
│  STL         - 3D printing format                           │
│  FBX         - Animation format                             │
└─────────────────────────────────────────────────────────────┘
```

---

## Viewing a 3D Model

### Step 1: Find a Record with 3D Model

Browse or search for a record that has a 3D model attached.

Look for the 3D icon: 🎲

### Step 2: Open the Viewer

Click on the 3D model thumbnail or the **View 3D** button.
```
┌─────────────────────────────────────────────────────────────┐
│  ┌───────────────────────────────────────────────────────┐  │
│  │                                                       │  │
│  │                                                       │  │
│  │                    [3D Model                          │  │
│  │                     Loading...]                       │  │
│  │                                                       │  │
│  │                                                       │  │
│  └───────────────────────────────────────────────────────┘  │
│                                                             │
│  🔄 Rotate  │  🔍 Zoom  │  📐 Measure  │  ⬜ Fullscreen     │
└─────────────────────────────────────────────────────────────┘
```

---

## Controls

### Mouse Controls
```
┌─────────────────────────────────────────────────────────────┐
│  ACTION              │  HOW TO                              │
├──────────────────────┼──────────────────────────────────────┤
│  Rotate              │  Click and drag                      │
│  Zoom in/out         │  Scroll wheel                        │
│  Pan (move)          │  Shift + click and drag              │
│  Reset view          │  Double-click                        │
└──────────────────────┴──────────────────────────────────────┘
```

### Touch Controls (Mobile/Tablet)
```
┌─────────────────────────────────────────────────────────────┐
│  ACTION              │  HOW TO                              │
├──────────────────────┼──────────────────────────────────────┤
│  Rotate              │  One finger drag                     │
│  Zoom                │  Pinch in/out                        │
│  Pan                 │  Two finger drag                     │
│  Reset               │  Double tap                          │
└──────────────────────┴──────────────────────────────────────┘
```

---

## Viewer Features

### Toolbar Options
```
┌─────────────────────────────────────────────────────────────┐
│  TOOLBAR                                                    │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  🔄 Auto-Rotate    - Spin model automatically               │
│  💡 Lighting       - Adjust light direction                 │
│  🎨 Background     - Change background color                │
│  📐 Wireframe      - Show mesh structure                    │
│  ⬜ Fullscreen     - Expand to full screen                  │
│  📷 Screenshot     - Save current view as image             │
│  📱 AR View        - View in augmented reality (mobile)     │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

## Augmented Reality (AR)

### On iPhone/iPad

1. Open the record on your device
2. Tap the **AR** button
3. Point camera at a flat surface
4. The object appears in your space!

### On Android

1. Open the record in Chrome
2. Tap the **AR** button
3. Follow prompts to place object
```
┌─────────────────────────────────────────────────────────────┐
│                    AR REQUIREMENTS                          │
├─────────────────────────────────────────────────────────────┤
│  iPhone/iPad    - iOS 12+ with ARKit support                │
│  Android        - ARCore compatible device + Chrome         │
└─────────────────────────────────────────────────────────────┘
```

---

## Tips for Best Experience
```
┌────────────────────────────────────────┬────────────────────┐
│  ✓ DO                                  │  ✗ DON'T           │
├────────────────────────────────────────┼────────────────────┤
│  Use a modern browser (Chrome/Firefox) │  Use Internet Explorer│
│  Wait for model to fully load          │  Interact while loading│
│  Use fullscreen for detail             │  View in small window│
│  Try AR on supported devices           │  Expect AR everywhere│
│  Allow time for large models           │  Give up on slow load│
└────────────────────────────────────────┴────────────────────┘
```

---

## Troubleshooting
```
Problem                          Solution
───────────────────────────────────────────────────────────
Model won't load              →  Refresh the page
                                 Try a different browser
                                 Check internet connection
                                 
Viewer is slow                →  Close other browser tabs
                                 Model may be very detailed
                                 
AR not available              →  Check device compatibility
                                 Use Safari (iOS) or Chrome (Android)
                                 
Model looks wrong             →  Try resetting the view
                                 Report to administrator
```

---

## Need Help?

Contact your system administrator if you experience issues.

---

*Part of the AtoM AHG Framework*
