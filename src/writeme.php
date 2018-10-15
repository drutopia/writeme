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


$composer_authors = "";
if (isset($composer->authors)) {
  foreach ($composer->authors as $author) {
    if (isset($author->homepage)) {
      $composer_authors_list[] = $author->name." - ".$author->homepage;
    } else if (isset($author->email)) {
      $composer_authors_list[] = $author->name." - ".$author->email;
    }
  }
  $composer_authors = "\n * " . implode("\n * ", $composer_authors_list);
}

if (isset($composer->require)) {
  foreach($composer->require as $dependency => $version){
    $composer_requirements_list[] = $dependency . " " . $version;
  }
  $composer_requirements = implode("\n * ", $composer_requirements_list);
} else {
  $composer_requirements = "\nNo dependencies.";
}


// Extract .git/HEAD data.
$git_branch_version = "";
if (file_exists('.git/HEAD')) {
  $file = file('.git/HEAD', FILE_USE_INCLUDE_PATH);
  $git_branch_version = trim(explode("/", $file[0], 3)[2]);
}
else {
  echo "Not a git repository; no version for project found.";
}

$name = ucwords(str_replace("_", " ", explode("/", $composer_name)[1]));
// Prepare README Markdown content.
$md = WRITEME_START . "\n";
$md .= "$name\n";
$md .= str_repeat("=", strlen($name)) . "\n\n"; 
$md .= $composer_description . "\n\n";

if ($composer_homepage) {
  $md .= "* $composer_homepage\n";
}
$md .= "* Package name: $composer_name\n";
if ($git_branch_version) {
  $md .= "* Git branch: $git_branch_version\n";
}

if ($composer_keywords) {
  $md .= "* Keywords: $composer_keywords\n";
}

if ($composer_authors) {
  $md .= "\n\n### Maintainers\n";
  $md .= $composer_authors;
}

if ($composer_requirements) {
  $md .= "\n\n### Requirements\n";
  $md .= $composer_requirements . "\n";
}

if ($composer_license) {
  $md .= "\n\n### License\n\n";
  $combined_title = $composer_license . ($composer_extra_license_title) ? ": $composer_extra_license_title" : "";
  $md .= ($composer_extra_license_url) ? "[" : "";
  $md .= $combined_title;
  $md .= ($composer_extra_license_url) ? "]($composer_extra_license_url)" : "";
}
if ($composer_extra_copyright_author) {
  $md .= "© $composer_extra_copyright_author";
}

$md .= "\n" . WRITEME_END . "\n";

// Recursively list all matched files.
$files = [];
$path = getcwd() . '/';
$filetypes_regex='/^.+(.md)$/i'; //regex of file search
$directory = new RecursiveDirectoryIterator($path);
$iterator = new RecursiveIteratorIterator($directory);
$regex = new RegexIterator($iterator, $filetypes_regex, RecursiveRegexIterator::GET_MATCH);
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
