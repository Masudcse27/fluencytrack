<?php
namespace mod_fluencytrack\api;

defined('MOODLE_INTERNAL') || die();

class assemblyai {
    public static function transcribe($filepath) {
       
        $apiKey = get_config('mod_fluencytrack', 'assemblyai_api_key');
        $endpoint = rtrim(get_config('mod_fluencytrack', 'assemblyai_api_endpoint'), '/');
        if (empty($apiKey) || empty($endpoint)) {
            return '[Error: Missing API credentials or endpoint in plugin settings]';
        }

        $uploadurl = self::upload($filepath, $apiKey, $endpoint);
        if (!$uploadurl) {
            return '[Error: Upload failed or returned no URL]';
        }

        $transcriptId = self::startTranscription($uploadurl, $apiKey, $endpoint);
        if (!$transcriptId) {
            return '[Error: Could not start transcription]';
        }

        $maxRetries = 30;
        $retry = 0;
        do {
            sleep(2);
            $status = self::check_status($transcriptId, $apiKey, $endpoint);
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

    private static function upload($filepath, $apiKey, $endpoint) {
        $uploadUrl = $endpoint.'/upload';

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

    private static function startTranscription($audioUrl, $apiKey, $endpoint) {
        $url = $endpoint.'/transcript';

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

    private static function check_status($transcriptId, $apiKey, $endpoint) {
        $url = $endpoint."/transcript/$transcriptId";

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
