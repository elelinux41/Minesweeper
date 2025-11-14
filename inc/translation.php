<?php
/**
 * Load translations from a CSV file.
 * 
 * @param string $filePath Path to the CSV file containing translations.
 * @param string $language in $_SERVER['HTTP_ACCEPT_LANGUAGE'] format.
 * 
 * @return array array of translations [string $key => string $translation_of_requested_language]
 */
function load_translation(string $filePath, string $language) {
    $translations = [];
    $lang = substr($language, 0, 2);
    if (($handle = fopen($filePath, 'r')) !== false) {
        $headers = fgetcsv($handle, separator: ';', escape: '');
        $lang = in_array($lang, $headers) ? $lang : "en";
        $languageIndex = array_search($lang, $headers);
        if ($languageIndex !== false) {
            while (($row = fgetcsv($handle, separator: ';', escape: '')) !== false) {
                $key = $row[0];
                $translations[$key] = $row[$languageIndex];
            }
        }
        fclose($handle);
    }
    return $translations;
}
?>