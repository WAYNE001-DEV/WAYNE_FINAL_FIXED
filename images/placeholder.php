<?php
// Generates a simple SVG placeholder image for missing product photos
header('Content-Type: image/svg+xml');
$cat = $_GET['cat'] ?? 'Item';
$cat = htmlspecialchars(substr($cat, 0, 14));
echo <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="400" height="300" viewBox="0 0 400 300">
  <rect width="400" height="300" fill="#1a1a1a"/>
  <text x="200" y="140" font-family="sans-serif" font-size="48" fill="#2a2a2a" text-anchor="middle">&#x1F455;</text>
  <text x="200" y="175" font-family="sans-serif" font-size="14" fill="#555" text-anchor="middle">$cat</text>
</svg>
SVG;
