# AHG Voice Command System

## Overview
Voice-driven navigation and actions for AtoM Heratio using the Web Speech API. Includes dictation mode, metadata reading, and AI-powered image description via Ollama (local) or Anthropic Claude (cloud).

## Browser Compatibility
| Browser | Speech Recognition | Speech Synthesis | Notes |
|---------|-------------------|-----------------|-------|
| Chrome 33+ | Yes | Yes | Best support, recommended |
| Edge 79+ | Yes | Yes | Chromium-based, full support |
| Safari 14.1+ | Yes (webkit prefix) | Yes | macOS/iOS |
| Firefox | No | Yes | SpeechRecognition not supported |
| Opera | Yes | Yes | Chromium-based |

## Requirements
- HTTPS (microphone requires secure context)
- Microphone permission granted in browser
- Chrome or Edge browser recommended

## Keyboard Shortcuts
- Ctrl+Shift+V: Toggle voice recognition
- Ctrl+Shift+H: Open help modal

## Commands Reference

### Navigation Commands
| Command | Action |
|---------|--------|
| "go home" | Navigate to homepage |
| "browse" / "go to browse" | Browse archival records |
| "go to admin" | Go to admin panel |
| "go to settings" | Go to AHG Settings |
| "go to clipboard" | Open clipboard |
| "go back" | Browser back |
| "next page" | Click next page link |
| "previous page" | Click previous page link |
| "search for [term]" | Search for a term |
| "go to donors" | Browse donors |
| "go to research" | Go to research/reading room |
| "go to authorities" | Browse authority records |
| "go to places" | Browse places |
| "go to subjects" | Browse subjects |
| "go to digital objects" | Browse digital objects |
| "go to accessions" | Browse accessions |
| "go to repositories" | Browse repositories |

### Edit Page Actions (requires edit form)
| Command | Action |
|---------|--------|
| "save" / "save record" | Submit the form |
| "cancel" | Click cancel button |
| "delete" / "delete record" | Click delete button |

### View Page Actions
| Command | Action |
|---------|--------|
| "edit" / "edit record" | Click edit button |
| "print" | Print the page |
| "export csv" | Click CSV export link |
| "export ead" | Click EAD export link |

### Browse Page Actions
| Command | Action |
|---------|--------|
| "first result" / "open first" | Open first search result |
| "sort by title" | Sort results by title |
| "sort by date" | Sort results by date |

### Metadata Reading
| Command | Action |
|---------|--------|
| "read image info" | Read digital object metadata aloud |
| "read title" | Read the record title |
| "read description" | Read scope and content |
| "stop reading" / "shut up" | Stop speech synthesis |
| "slower" | Decrease speech rate |
| "faster" | Increase speech rate |

### AI Image Description (requires Ollama or Anthropic API key)
| Command | Action |
|---------|--------|
| "describe image" | Generate AI description |
| "save to description" | Save to scope_and_content |
| "save to alt text" | Save as image alt text |
| "save to both" | Save to both fields |
| "discard" | Discard AI description |

### Dictation Mode
| Command | Action |
|---------|--------|
| "start dictating" | Enter dictation mode |
| "stop dictating" | Exit dictation mode |

While dictating:
| Sub-command | Inserts |
|-------------|---------|
| "period" / "full stop" | . |
| "comma" | , |
| "question mark" | ? |
| "exclamation mark" | ! |
| "colon" | : |
| "semicolon" | ; |
| "new line" | Line break |
| "new paragraph" | Double line break |
| "open quote" / "close quote" | Curly quotes |
| "open bracket" / "close bracket" | ( ) |
| "dash" / "hyphen" | -- or - |
| "undo last" | Remove last segment |
| "clear field" | Clear field (with confirmation) |
| "read back" | Read field content aloud |

### Global Commands
| Command | Action |
|---------|--------|
| "toggle advanced search" | Toggle advanced search |
| "clear search" | Clear search input |
| "scroll down" / "scroll up" | Scroll page |
| "scroll to top" / "scroll to bottom" | Scroll to extremes |
| "help" / "show commands" | Open help modal |

## Ollama Setup (Local LLM)

```bash
# Install Ollama
curl -fsSL https://ollama.com/install.sh | sh

# Pull LLaVA vision model
ollama pull llava:7b

# Enable auto-start
sudo systemctl enable ollama

# Verify
curl http://localhost:11434/api/tags
```

### Resource Limits (dev server)
```bash
sudo mkdir -p /etc/systemd/system/ollama.service.d
cat > /tmp/ollama-override.conf << 'EOF'
[Service]
Environment="OLLAMA_MAX_LOADED_MODELS=1"
Environment="OLLAMA_NUM_PARALLEL=1"
Environment="OLLAMA_HOST=0.0.0.0"
EOF
sudo mv /tmp/ollama-override.conf /etc/systemd/system/ollama.service.d/override.conf
sudo systemctl daemon-reload
sudo systemctl restart ollama
```

### Moving LLM to Dedicated Machine
1. Install Ollama on new machine
2. Pull models: `ollama pull llava:7b`
3. Set `OLLAMA_HOST=0.0.0.0` on new machine
4. Update AHG Settings > Voice & AI > Local LLM URL to `http://192.168.0.XXX:11434`
5. No code changes needed

## Configuration

### Admin Settings
Go to **Admin > AHG Settings > Voice & AI** to configure:

**Voice Commands:**
- Enable/disable voice commands
- Language selection
- Confidence threshold (0.3 - 0.95)
- Continuous listening mode
- Floating button visibility
- Speech rate

**AI Image Description:**
- LLM Provider: local (Ollama), cloud (Anthropic), or hybrid
- Local LLM URL and model
- Anthropic API key
- Daily cloud usage limit
- Audit logging

### Config File
Manual configuration: `/usr/share/nginx/archive/plugins/ahgThemeB5Plugin/config/voiceConfig.php`

## Architecture

### Files
| File | Purpose |
|------|---------|
| `js/voiceCommands.js` | Core engine (AHGVoiceCommands class) |
| `js/voiceCommandRegistry.js` | Command definitions (AHGVoiceRegistry) |
| `css/voiceCommands.css` | All voice UI styles |
| `templates/_voiceCommands.php` | HTML partial (buttons, help modal) |
| `templates/_layout_end.php` | Includes voice CSS/JS/partial |
| `modules/ahgVoice/` | Server-side AI endpoints |
| `config/voiceConfig.php` | LLM configuration |

### UI Components
- **Navbar mic button**: Injected via JS into `#top-bar .navbar-nav`
- **Floating mic button**: Fixed bottom-right, z-index 1050
- **Listening indicator**: 3px bar at top of page (blue=command, green=dictation, amber=AI processing)
- **Toast notifications**: Bottom-right, auto-dismiss after 2s
- **Help modal**: Bootstrap 5 modal with searchable command list
- **Field mic icons**: Small mic icons on text inputs/textareas on edit pages

### CSP
All script/style tags include dynamic CSP nonces via `sfConfig::get('csp_nonce')`.

## Troubleshooting

| Issue | Solution |
|-------|----------|
| No mic button visible | Check browser supports SpeechRecognition (Chrome/Edge). Check F12 Console for CSP errors. |
| "Command not recognized" | Check F12 Console for `[Voice] Heard:` log. Speak clearly. Try lowering confidence threshold in settings. |
| AI describe fails | Check Ollama is running: `curl http://localhost:11434/api/tags`. For cloud: verify API key in settings. |
| Page appears frozen | CSP blocking scripts -- check nonce attributes on script tags. |
| Dictation not inserting text | Ensure a text field is focused. Try clicking the field mic icon instead. |
| Speech too fast/slow | Say "slower" or "faster", or adjust in AHG Settings > Voice & AI. |
