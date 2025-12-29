<?php

use Illuminate\Database\Capsule\Manager as DB;

class arMediaTranscriptionJob extends arBaseJob
{
    public function runJob($parameters)
    {
        $doId = $parameters['digital_object_id'];
        
        $this->info("Starting transcription for digital object #$doId");
        
        // Get digital object info
        $do = DB::table('digital_object')
            ->where('id', $doId)
            ->first();
        
        if (!$do) {
            $this->error("Digital object not found: $doId");
            return false;
        }
        
        // Build file path
        $uploadDir = sfConfig::get('sf_upload_dir');
        $path = $do->path;
        
        // Handle path that already includes /uploads/
        if (strpos($path, '/uploads/') === 0) {
            $path = substr($path, 9);
        }
        
        $filePath = $uploadDir . '/' . ltrim($path, '/') . $do->name;
        
        if (!file_exists($filePath)) {
            $this->error("File not found: $filePath");
            return false;
        }
        
        $this->info("Processing file: $filePath");
        
        // Get transcription settings
        $settings = DB::table('media_processor_settings')
            ->whereIn('name', ['whisper_model', 'default_language'])
            ->pluck('value', 'name')
            ->toArray();
        
        $model = $settings['whisper_model'] ?? 'base';
        $language = $settings['default_language'] ?? 'en';
        
        $this->info("Using Whisper model: $model, language: $language");
        
        // Create temp directory for output
        $tempDir = sys_get_temp_dir() . '/whisper_' . $doId . '_' . time();
        @mkdir($tempDir, 0755, true);
        
        // Run whisper
        $cmd = sprintf(
            'whisper %s --model %s --language %s --output_format json --output_dir %s 2>&1',
            escapeshellarg($filePath),
            escapeshellarg($model),
            escapeshellarg($language),
            escapeshellarg($tempDir)
        );
        
        $this->info("Running: $cmd");
        $output = shell_exec($cmd);
        $this->info("Whisper output: $output");
        
        // Find output JSON file
        $baseName = pathinfo($do->name, PATHINFO_FILENAME);
        $jsonFile = $tempDir . '/' . $baseName . '.json';
        
        if (!file_exists($jsonFile)) {
            $this->error("Transcription output not found: $jsonFile");
            // Cleanup
            array_map('unlink', glob($tempDir . '/*'));
            @rmdir($tempDir);
            return false;
        }
        
        $transcription = json_decode(file_get_contents($jsonFile), true);
        
        // Extract segments
        $segments = [];
        $fullText = '';
        
        foreach (($transcription['segments'] ?? []) as $seg) {
            $segments[] = [
                'start' => $seg['start'],
                'end' => $seg['end'],
                'text' => trim($seg['text'])
            ];
            $fullText .= trim($seg['text']) . ' ';
        }
        
        $this->info("Extracted " . count($segments) . " segments");
        
        // Insert or update transcription
        $existing = DB::table('media_transcription')
            ->where('digital_object_id', $doId)
            ->first();
        
        $transData = [
            'digital_object_id' => $doId,
            'language' => $transcription['language'] ?? $language,
            'full_text' => trim($fullText),
            'segments' => json_encode($segments),
            'model_used' => $model,
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        
        if ($existing) {
            DB::table('media_transcription')
                ->where('digital_object_id', $doId)
                ->update($transData);
        } else {
            $transData['created_at'] = date('Y-m-d H:i:s');
            DB::table('media_transcription')->insert($transData);
        }
        
        // Cleanup temp files
        array_map('unlink', glob($tempDir . '/*'));
        @rmdir($tempDir);
        
        $this->info("Transcription completed successfully for digital object #$doId");
        
        return true;
    }
}