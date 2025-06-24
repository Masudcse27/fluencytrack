<?php
namespace mod_fluencytrack\api;

defined('MOODLE_INTERNAL') || die();

class assemblyai {
    public static function transcribe($filepath) {
        global $CFG;

        
        $apiKey = isset($CFG->assemblyai_api_key) ? $CFG->assemblyai_api_key : '25f52db7ddeb418cafbccabc63038230';

        $uploadurl = self::upload($filepath, $apiKey);
        if (!$uploadurl) {
            return '[Error: Upload failed or returned no URL]';
        }

        $transcriptId = self::startTranscription($uploadurl, $apiKey);
        if (!$transcriptId) {
            return '[Error: Could not start transcription]';
        }

        $maxRetries = 30;
        $retry = 0;
        do {
            sleep(2);
            $status = self::check_status($transcriptId, $apiKey);
            $retry++;
            if (!$status) {
                return '[Error: Failed to get transcription status]';
            }
        } while ($status['status'] === 'processing' && $retry < $maxRetries);

        if ($status['status'] === 'completed') {
            return $status['text'] ?? '';
        } else {
            $err = $status['error'] ?? 'Unknown error';
            return '[Transcription failed: ' . $err . ']';
        }
    }

    private static function upload($filepath, $apiKey) {
        $uploadUrl = 'https://api.assemblyai.com/v2/upload';

        $ch = curl_init($uploadUrl);
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER => ['Authorization: ' . $apiKey],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => file_get_contents($filepath),
        ]);
        $resp = curl_exec($ch);
        if ($resp === false) {
            curl_close($ch);
            return false;
        }
        $data = json_decode($resp, true);
        curl_close($ch);

        if (empty($data['upload_url'])) {
            return false;
        }
        return $data['upload_url'];
    }

    private static function startTranscription($audioUrl, $apiKey) {
        $url = 'https://api.assemblyai.com/v2/transcript';

        $payload = json_encode([
            'audio_url' => $audioUrl,
            'speaker_labels' => false,
        ]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER => [
                'Authorization: ' . $apiKey,
                'Content-Type: application/json',
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
        ]);

        $resp = curl_exec($ch);
        if ($resp === false) {
            curl_close($ch);
            return false;
        }

        $data = json_decode($resp, true);
        curl_close($ch);

        return $data['id'] ?? false;
    }

    private static function check_status($transcriptId, $apiKey) {
        $url = "https://api.assemblyai.com/v2/transcript/$transcriptId";

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER => ['Authorization: ' . $apiKey],
            CURLOPT_RETURNTRANSFER => true,
        ]);

        $resp = curl_exec($ch);
        if ($resp === false) {
            curl_close($ch);
            return false;
        }
        $data = json_decode($resp, true);
        curl_close($ch);

        return $data ?: false;
    }
}
