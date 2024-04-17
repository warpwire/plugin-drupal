<?php

namespace Drupal\warpwire\Lib;

/**
 * Provide general purpose static utility functions
 */
class WarpwireUtils
{

    /**
     * Build a simple HTML error message using Warpwire styling
     * @param string $message - The error message to display
     * @return string HTML
     */
    static function buildErrorMessageHtml(string $message): string
    {
        $content = '';
        $content .= '<style type="text/css">
        body {
            margin: 0;
            padding: 0;
            text-align: center;
            font-size: 14px;
            font-weight: 400;
            line-height: 22px;
            font-family: helvetica;
            background: #333;
            color: #fff;
        }
        .warpwire-error-message {
            margin: 0;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);          
        }
        </style>
        ';
        $content .= '<div class="warpwire-error-message">' . $message . '</div>';
        return $content;
    }

    /**
     * Select a subset of an associative array by providing the keys.
     *
     * @param array $assoc
     * @param array $keys
     * @return array
     */
    static function arraySelect(array $assoc, array $keys)
    {
        return array_intersect_key($assoc, array_flip($keys));
    }
}
