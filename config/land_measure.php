<?php

/**
 * Project-wide land area conversion standards.
 *
 * Internal storage remains marla (decimal). Display / conversion layers
 * read these ratios so the rest of the app does not hardcode assumptions.
 */
return [

    /** 1 acre = N kanal */
    'kanal_per_acre' => 8,

    /** 1 kanal = N marla */
    'marla_per_kanal' => 20,

    /** 1 marla = N square feet */
    'sqft_per_marla' => 225.0,

    /**
     * Display modes for labels / switchers.
     * - acre: residual A — K — M — SQFT
     * - kanal: residual K — M — SQFT (acres folded into kanal)
     * - marla: residual M — SQFT
     * - sqft: total square feet
     * - akms: alias of acre (full mixed label)
     */
    'display_units' => [
        'acre' => 'Acre (A)',
        'kanal' => 'Kanal (K)',
        'marla' => 'Marla (M)',
        'sqft' => 'Sqft',
    ],

    'default_display_unit' => 'acre',
];
