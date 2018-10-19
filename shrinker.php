#!/usr/bin/env php
<?php

function short_identifier($number, $chars) {
    $return = '';
    while ($number >= 0) {
        $return .= $chars[$number % strlen($chars)];
        $number = floor($number / strlen($chars)) - 1;
    }
    return $return;
}

function php_shrink($input) {
    global $VERSION;
    $special_variables = array_flip(array('$this', '$GLOBALS', '$_GET', '$_POST', '$_FILES', '$_COOKIE', '$_SESSION', '$_SERVER', '$http_response_header', '$php_errormsg'));
    $short_variables = array();
    $shortening = true;
    $tokens = token_get_all($input);

    // remove unnecessary { }
    //! change also `while () { if () {;} }` to `while () if () ;` but be careful about `if () { if () { } } else { }
    $shorten = 0;
    $opening = -1;
    foreach ($tokens as $i => $token) {
        if (in_array($token[0], array(T_IF, T_ELSE, T_ELSEIF, T_WHILE, T_DO, T_FOR, T_FOREACH), true)) {
            $shorten = ($token[0] == T_FOR ? 4 : 2);
            $opening = -1;
        } elseif (in_array($token[0], array(T_SWITCH, T_FUNCTION, T_CLASS, T_CLOSE_TAG), true)) {
            $shorten = 0;
        } elseif ($token === ';') {
            $shorten--;
        } elseif ($token === '{') {
            if ($opening < 0) {
                $opening = $i;
            } elseif ($shorten > 1) {
                $shorten = 0;
            }
        } elseif ($token === '}' && $opening >= 0 && $shorten == 1) {
            unset($tokens[$opening]);
            unset($tokens[$i]);
            $shorten = 0;
            $opening = -1;
        }
    }
    $tokens = array_values($tokens);

    foreach ($tokens as $i => $token) {
        if ($token[0] === T_VARIABLE && !isset($special_variables[$token[1]])) {
            $short_variables[$token[1]]++;
        }
    }

    arsort($short_variables);
    $chars = implode(range('a', 'z')) . '_' . implode(range('A', 'Z'));
    // preserve variable names between versions if possible
    $short_variables2 = array_splice($short_variables, strlen($chars));
    ksort($short_variables);
    ksort($short_variables2);
    $short_variables += $short_variables2;
    foreach (array_keys($short_variables) as $number => $key) {
        $short_variables[$key] = short_identifier($number, $chars); // could use also numbers and \x7f-\xff
    }

    $set = array_flip(preg_split('//', '!"#$%&\'()*+,-./:;<=>?@[\]^`{|}'));
    $space = '';
    $output = '';
    $in_echo = false;
    $doc_comment = false; // include only first /**
    for (reset($tokens);list($i, $token) = each($tokens);) {
        if (!is_array($token)) {
            $token = array(0, $token);
        }
        if ($tokens[$i + 2][0] === T_CLOSE_TAG && $tokens[$i + 3][0] === T_INLINE_HTML && $tokens[$i + 4][0] === T_OPEN_TAG
            && strlen(add_apo_slashes($tokens[$i + 3][1])) < strlen($tokens[$i + 3][1]) + 3
        ) {
            $tokens[$i + 2] = array(T_ECHO, 'echo');
            $tokens[$i + 3] = array(T_CONSTANT_ENCAPSED_STRING, "'" . add_apo_slashes($tokens[$i + 3][1]) . "'");
            $tokens[$i + 4] = array(0, ';');
        }
        if ($token[0] == T_COMMENT || $token[0] == T_WHITESPACE || ($token[0] == T_DOC_COMMENT && $doc_comment)) {
            $space = "\n";
        } else {
            if ($token[0] == T_DOC_COMMENT) {
                $doc_comment = true;
                $token[1] = substr_replace($token[1], "* @version $VERSION\n", -2, 0);
            }
            if ($token[0] == T_VAR) {
                $shortening = false;
            } elseif (!$shortening) {
                if ($token[1] == ';') {
                    $shortening = true;
                }
            } elseif ($token[0] == T_ECHO) {
                $in_echo = true;
            } elseif ($token[1] == ';' && $in_echo) {
                if ($tokens[$i + 1][0] === T_WHITESPACE && $tokens[$i + 2][0] === T_ECHO) {
                    next($tokens);
                    $i++;
                }
                if ($tokens[$i + 1][0] === T_ECHO) {
                    // join two consecutive echos
                    next($tokens);
                    $token[1] = ','; // '.' would conflict with "a".1+2 and would use more memory //! remove ',' and "," but not $var","
                } else {
                    $in_echo = false;
                }
            } elseif ($token[0] === T_VARIABLE && !isset($special_variables[$token[1]])) {
                $token[1] = '$' . $short_variables[$token[1]];
            }
            if (isset($set[substr($output, -1)]) || isset($set[$token[1][0]])) {
                $space = '';
            }
            $output .= $space . $token[1];
            $space = '';
        }
    }
    return $output;
}

function minify_css($file) {
    return lzw_compress(preg_replace('~\s*([:;{},])\s*~', '\1', preg_replace('~/\*.*\*/~sU', '', $file)));
}

function minify_js($file) {
    if (function_exists('jsShrink')) {
        $file = jsShrink($file);
    }
    return lzw_compress($file);
}

function main($iarg) {
    if (array_key_exists(1, $iarg)) {
        switch ($iarg[1]) {
        case '-h':
        case '--help':
            print <<<EOD
Shrinker (Ver 1.0)

Syntax:
    shrinker <option>
    shrinker <flag> <input-file>
    shrinker <flag> <input-file> <output-file>

Options:
    --help     -h    to see the command line options
    --version  -v    to get the version and license info

Flags:
    php    to minify php code
    css    to minify css stylesheet
    js     to minify js code

Examples:
    shrinker --help
    shrinker --version
    shrinker php test.php
    shrinker php itest.php otest.php
    shrinker css test.css
    shrinker css itest.css otest.css
    shrinker js test.js
    shrinker js itest.js otest.js

Notes:
    Options & Flags can't be used together in a statement
    Order of command-line arguments should be maintained

EOD;
            break;
        case '-v':
        case '--version':
            print <<<EOD
Shrinker - The code minifier
Version 1.0

Copyright 2018 Abhishek Kumar

Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

    http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.

EOD;
            break;
        default:
            if (array_key_exists(2, $iarg)) {
                $ifile = $iarg[2];
                $content = file_get_contents($ifile);
                switch ($iarg[1]) {
                case 'php':
                    $output = php_shrink($content);
                    break;
                case 'js':
                    $output = minify_js($content);
                    break;
                case 'css':
                    $output = minify_css($content);
                    break;
                default:
                    $output = "";
                    break;
                }
                $ofile = (array_key_exists(3, $iarg)) ? $iarg[3] : $ifile;
                file_put_contents($ofile, $output);
            } else {
                echo 'Error: The input filename is missing.';
            }
            break;
        }
    } else {
        echo 'Error: The type of code is missing. For e.g. php, css, or js';
    }
}

main($argv);

?>