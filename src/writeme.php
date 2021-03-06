<?php
/**
 * Create and keep updated a README.md for your project, fetching details from
 * composer.json and Git.
 *
 * Composer does not need to be installed to use this tool.
 */

define('WRITEME_START', '<!-- writeme -->');
define('WRITEME_END', '<!-- endwriteme -->');


// Extract composer.json data.
if (!file_exists("composer.json")) {
  die("No composer.json file found.  Cannot extract data for README.\n");
}

$composer = json_decode(file_get_contents('composer.json'));

$composer_name = $composer->name;
$composer_keywords = isset($composer->keywords) ? implode(", ",$composer->keywords) : "";
$composer_description = isset($composer->description) ? $composer->description : "";
$composer_homepage = isset($composer->homepage) ? $composer->homepage : "";
$composer_license = isset($composer->license) ? $composer->license : "";
$composer_extra_copyright_author = isset($composer->extra->copyright_author) ? $composer->extra->copyright_author : "";
$composer_extra_license_title = isset($composer->extra->license_title) ? $composer->extra->license_title : "";
$composer_extra_license_url = isset($composer->extra->license_url) ? $composer->extra->license_url : "";

$composer_support = [];
foreach (['email', 'issues', 'forum', 'wiki', 'irc', 'source', 'docs', 'rss'] as $type) {
  $composer_support[$type] = isset($composer->support->$type) ? $composer->support->$type : "";
}

$composer_authors = "";
if (isset($composer->authors)) {
  foreach ($composer->authors as $author) {
    if (isset($author->homepage)) {
      $composer_authors .= " * " . $author->name . " - ". $author->homepage;
    } elseif (isset($author->email)) {
      $composer_authors .= " * " . $author->name . " - ". $author->email;
    }
  }
}

if (isset($composer->require)) {
  foreach ($composer->require as $dependency => $version){
    $composer_requirements = " * $dependency $version\n";
  }
} else {
  $composer_requirements = "No dependencies.\n";
}

$composer_suggest = "";
if (isset($composer->suggest)) {
  foreach ($composer->suggest as $dependency => $text){
    $composer_suggest .= " * $dependency: $text\n";
  }
}

$name = ucwords(str_replace("_", " ", explode("/", $composer_name)[1]));
// Prepare README Markdown content.
$md = WRITEME_START . "\n";
$md .= "$name\n";
$md .= str_repeat("=", strlen($name)) . "\n\n"; 
$md .= $composer_description . "\n\n";

if ($composer_homepage) {
  $md .= " * $composer_homepage\n";
}
if ($composer_support['docs']) {
  $md .= " * Documentation: " . $composer_support['docs'] . "\n"; 
}
if ($composer_support['wiki']) {
  $md .= " * Wiki: " . $composer_support['wiki'] . "\n"; 
}
if ($composer_support['issues']) {
  $md .= " * Issues: " . $composer_support['issues'] . "\n"; 
}
if ($composer_support['forum']) {
  $md .= " * Forum: " . $composer_support['forum'] . "\n"; 
}
if ($composer_support['irc']) {
  $md .= " * IRC: " . $composer_support['irc'] . "\n"; 
}
if ($composer_support['source']) {
  $md .= " * Source code: " . $composer_support['source'] . "\n"; 
}
if ($composer_support['email']) {
  $md .= " * E-mail: " . $composer_support['email'] . "\n"; 
}
if ($composer_support['rss']) {
  $md .= " * RSS: " . $composer_support['rss'] . "\n"; 
}
if ($composer_keywords) {
  $md .= " * Keywords: $composer_keywords\n";
}

$md .= " * Package name: $composer_name\n";

if ($composer_authors) {
  $md .= "\n\n### Maintainers\n\n";
  $md .= $composer_authors;
}

if ($composer_requirements) {
  $md .= "\n\n### Requirements\n\n";
  $md .= $composer_requirements;
}

if ($composer_suggest) {
  $md .= "\n\n### Recommended\n\n";
  $md .= $composer_suggest;
}

if ($composer_license) {
  $md .= "\n\n### License\n\n";
  $combined_title = $composer_license . (($composer_extra_license_title) ? ": $composer_extra_license_title" : "");
  $md .= ($composer_extra_license_url) ? "[" : "";
  $md .= $combined_title;
  $md .= ($composer_extra_license_url) ? "]($composer_extra_license_url)" : "";
  $md .= "\n";
}
if ($composer_extra_copyright_author) {
  $md .= "© $composer_extra_copyright_author\n";
}

$md .= "\n" . WRITEME_END . "\n";

// Recursively list all matched markdown files.
$files = [];
$path = getcwd() . '/';
$files_regex='/^.+(.md)$/i';
$directory = new RecursiveDirectoryIterator($path);
$iterator = new RecursiveIteratorIterator($directory);
$regex = new RegexIterator($iterator, $files_regex, RecursiveRegexIterator::GET_MATCH);
foreach ($regex as $filepath => $regex) {
  $files[] = $filepath;
}

$create = ($files) ? false : true;


// Write the README.
function writeme($filepath, $md, $create){
  $contents = "";
  if ($create) {
    $contents = $md;
  }
  else {
    $file = file_get_contents($filepath);
    if (strpos($file, WRITEME_START) !== false) {
      // Get the first line with a start tag and the last line with an end tag.
      $writeme_start = false;
      $lines = file($filepath);
      foreach ($lines as $num => $line) {
        if ($writeme_start === false and strpos($line, WRITEME_START) === 0) {
          $writeme_start = $num;
        } 
        if (strpos($line, WRITEME_END) === 0) {
          $writeme_end = $num;
        }
      }
      if (!isset($writeme_end)) {
        $writeme_end = $num;
      }
      $replace = "";
      for ($i = $writeme_start; $i <= $writeme_end; $i++) {
        $replace .= $lines[$i];
      }
      $contents = str_replace($replace, $md, $file);
    }
  }
  if ($contents) { 
    file_put_contents($filepath, $contents);
    return true;
  }
  else {
    return false;
  }
}

if ($create) {
  $files[] = $path . "README.md";
}

// Update README file.
foreach ($files as $filepath){
  $success = writeme($filepath, $md, $create);
  if ($success) {
    echo "$filepath written!\n";
  }
  else {
    echo "README ($filepath) already existed and " . WRITEME_START . " not found in it.\n";
  }
}
