=== Block Styles ===
Contributors: aristath
Donate link: https://github.com/sponsors/aristath/
Tags: blocks, styles, performance, sustainable
Requires at least: 5.0
Requires PHP: 5.6
Tested up to: 5.4
Stable tag: 1.0.1
License: MIT

Change the way styles for blocks get loaded, optimize your site's performance and sustainability!

== Description ==

Increase the performance of your website by removing the default styles for WordPress-Core blocks, and only enqueue styles for the blocks you use on a page.

You can choose to add styles on the head of your pages, on the footer, inline only when the block exists, or even completely disable styles. And all of that, per-block!

IMPORTANT: Depending on how your theme overrides default styles - if it even does, you may see issues due to the loading order of CSS files, or insufficient specificity from the theme's CSS rules.
We urge you to experiment with the options for blocks and find what suits your use-case better.
