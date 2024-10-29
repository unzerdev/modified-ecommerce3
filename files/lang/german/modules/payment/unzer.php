<?php
include DIR_FS_CATALOG.'includes/modules/payment/unzer/src/TextPhrases/german/unzer.lang.inc.php';
/**
 * @var array $t_language_text_section_content_array
 */
foreach($t_language_text_section_content_array as $key => $value) {
    define($key, $value);
}