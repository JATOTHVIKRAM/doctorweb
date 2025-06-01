<?php
// Script to remove theme toggle button and related code from doctor_dashboard.php

// Read the file content
$file = 'doctor_dashboard.php';
$content = file_get_contents($file);

// Remove the HTML for the theme toggle button
$pattern = '/<li class="nav-item me-3">\s*<button class="btn btn-outline-secondary btn-sm" id="themeToggle">\s*<i class="fas fa-moon"><\/i>\s*<\/button>\s*<\/li>/s';
$content = preg_replace($pattern, '', $content);

// Remove JavaScript related to theme toggle
$jsPattern = '/\/\/ Theme Toggle.*?\/\/ Change Password Form Submit/s';
$content = preg_replace($jsPattern, '// Change Password Form Submit', $content);

// Remove the color theme loading
$themeLoadPattern = '/\/\/ Load saved color theme.*?function confirmLogout/s';
$content = preg_replace($themeLoadPattern, 'function confirmLogout', $content);

// Remove the dark-mode CSS styles
$darkModePattern = '/\/\* Dark Mode Styles \*\/.*?\/\* Theme Colors \*\//s';
$content = preg_replace($darkModePattern, '/* Theme Colors */', $content);

// Save the modified content back to the file
file_put_contents($file, $content);

echo "Theme toggle button and related code have been removed from doctor_dashboard.php";
?> 