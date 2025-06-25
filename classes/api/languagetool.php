<?php
namespace mod_fluencytrack\api;

defined('MOODLE_INTERNAL') || die();

class languagetool
{
    public static function check($text)
    {
        $url = get_config('mod_fluencytrack', 'languagetool_api_endpoint');;

        $data = "text=" . urlencode($text) . "&language=en-US";
        $postFields = $data;

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postFields,
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
        ]);

        $response = curl_exec($ch);
        
        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            return "⚠️ Grammar check failed: $error";
        }

        curl_close($ch);

        $result = json_decode($response, true);

        if (empty($result['matches'])) {
            return '✅ No grammar issues found.';
        }

        $issues = [];
        foreach ($result['matches'] as $match) {
            $highlight = substr($text, $match['offset'], $match['length']);
            $suggestions = implode(', ', array_column($match['replacements'], 'value'));

            $issues[] = "✏️ *{$highlight}* — {$match['message']} (Suggestions: {$suggestions})";
        }

        return implode("\n", $issues);
    }

    public static function estimate_fluency($text, $grammarIssueCount, $filepath = null)
    {
        $wordCount = str_word_count($text);
        $sentenceCount = max(1, substr_count($text, '.') + substr_count($text, '!') + substr_count($text, '?'));
        $avgWordsPerSentence = $wordCount / $sentenceCount;

        $baseScore = min(100, max(10, $avgWordsPerSentence * 5));
        $penalty = min(50, $grammarIssueCount * 5);

        // Optional: Use audio length to adjust fluency (WPM check)
        if ($filepath && file_exists($filepath)) {
            $durationSeconds = self::get_audio_duration($filepath);
            if ($durationSeconds > 0) {
                $wpm = $wordCount / ($durationSeconds / 60);
                if ($wpm < 60) {
                    $baseScore -= 10; // too slow
                } elseif ($wpm > 160) {
                    $baseScore -= 5; // too fast
                }
            }
        }

        return max(10, min(100, $baseScore - $penalty));
    }

    public static function get_audio_duration($filepath)
    {
        if (!file_exists($filepath)) {
            return 0;
        }

        $cmd = "ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 " . escapeshellarg($filepath);
        $duration = shell_exec($cmd);
        return floatval($duration); // returns in seconds
    }
}
