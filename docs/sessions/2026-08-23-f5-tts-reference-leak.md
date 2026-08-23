# F5-TTS was speaking its own reference recording

Date: 2026-08-23
Service: F5-TTS sidecar, 192.168.0.76:7860 (RTX 3090 node)
Affects: every narrated video, plus Workbench read-aloud, generated audio and podcasts

## Symptom

Generated narration contained a sentence nobody wrote - *"I'd knock out that review you
parked yesterday, then circle back to the bigger piece"* - repeated between every narrated
sentence, roughly six times a minute. Three walkthrough videos shipped carrying it.

## Cause

The voice's registered reference, not the model and not one bad voice. Every id cut from
that recording session leaked a fragment of its own reference:

| voice | leaked fragment | reference |
|---|---|---|
| johan-final | "I'd knock out that review you part yesterday..." | ~12.1s |
| johan-c2 | "I can select the specific item on the left hand side" | ~16.0s, 40-word unpunctuated transcript |
| johan | "testing one two three" | ~5.2s |

F5-TTS wants a short reference (~5-10s) whose `ref_text` matches the audio closely. A long
reference with a loose transcript makes the model emit the reference. Long input text is
chunked per sentence and each chunk carries the reference again - hence the recurrence
rather than a single leak at the start.

`johan-c2` is `READ_ALOUD_VOICE` in `workbench api/.env` and the default for everything
that speaks, so read-aloud, generated audio and podcasts were all affected.

## The detection lesson

Two checks cannot see this fault, and both were used to wrongly clear the audio:

- **Duration / wpm analysis.** The leak is spoken fast and never made a file look
  anomalously long. Every line measured 94-132 wpm and looked fine.
- **RMS silence segmentation.** The leak can overlay rather than append, so the envelope
  shows one continuous speech segment.

Only transcription settles it. There is no ASR service on the fleet, but 112 has a local
CPU `whisper` binary with `base.en`/`base`/`medium` cached - ample for a one-minute clip.
Deliveries are now gated on transcribing the artefact itself and grepping for known
reference fragments, rather than on a log line or a duration.

## Fix

Re-registered both `johan-c2` and a new `johan-narration` with a 6.9s reference: two
complete sentences cut from `workbench/stuff/johan-voice-c2-CLEAN.mp3`, transcript
verified word-for-word. Fresh renders transcribe back as the target text and nothing else.

API surface:

    POST /voices/{voice_id}              multipart: file=@ref.wav, ref_text="..."
    POST /voices/{voice_id}/retranscribe
    GET  /voices                         -- there is NO DELETE

Send the BARE voice id to `/tts`; the `f5:` prefix used inside the Workbench returns 404.

## Operational notes

- References live at `/opt/f5-tts/voices/{id}/ref.wav` + `ref_text.txt` on .76, reachable
  as **`ahg-admin@192.168.0.76`** (root and johanpiet are refused). Back up before
  overwriting - there is no delete, so an overwrite is otherwise one-way.
- **Do not put backups under `voices/`** - the service enumerates that directory, so a
  backup folder shows up as a selectable voice. Use `/opt/f5-tts/voice-backups/`.
  The original johan-c2 reference is preserved there, with an md5-verified copy on 112.
- The new reference is a calmer read, so **the same `speed` value now yields slower
  speech**. Calibrated narration speed is **0.55** (~100-135 wpm on multi-sentence prose),
  replacing the 0.38 that suited the old reference. Anything with a tuned speed constant
  against johan-c2 may want re-calibrating.
