<?php

return array(

    // Set the selection of font sizes to use
    // values: array of integers
    'fontsizes' => array(15, 16, 17, 18, 19, 20),

    // Set the captcha type
    // values: 'alnum', 'num'
    // default: 'alnum'
    'type' => 'alnum',

    // Set the number of characters in the captcha code
    // values: integer
    // default: 6
    'length' => 6,

    // Set the width of the image
    // values: integer
    // default: 140
    'width' => 140,

    // Set the height of the image
    // values: integer
    // default: 30
    'height' => 30,

    // Set the spacing to use between each letter
    // values: array of integers, from 16 to 25
    'space' => array(18, 19, 20, 21, 22),

    // Set the selection of colors to use
    // values: array of rgb colours, decimal-comma-separated
    'colors' => array('128,23,23', '128,23,22', '33,67,87', '67,70,83', '31,56,163', '48,109,22', '165,42,166', '18,95,98', '213,99,8'),

    // Set whether the captcha must be case sensitive or not
    // values: boolean (true / false)
    'sensitive' => false,

    // Set the image output quality
    // values: integer (0 to 100)
    'quality' => 75,

);
