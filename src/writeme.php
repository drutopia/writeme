<?php
/**
 * Create and keep updated a README.md for your project, fetching details from
 * composer.json and Git.
 *
 * Composer does not need to be installed to use this tool.
 */

define('WRITEME_START', '<!-- writeme -->');
define('WRITEME_END', '<!-- endwriteme -->');

$create = false;

// Extract composer.json data.
if (!file_exists("composer.json")) {
  die("No composer.json file found.  Cannot extract data for README.\n");
}

$composer = json_decode(file_get_contents('composer.json'));

$vars["composer_name"] = $composer->name;
$vars["composer_keywords"] = isset($composer->keywords) ? implode(", ",$composer->keywords) : "";
$vars["composer_description"] = isset($composer->description) ? $composer->description : "";
$vars["composer_homepage"] = isset($composer->homepage) ? $composer->homepage : "";
$vars["composer_license"] = isset($composer->license) ? $composer->license : "";
$vars["composer_extra_copyright_author"] = isset($composer->extra->copyright_author) ? $composer->extra->copyright_author : "";
$vars["composer_extra_license_title"] = isset($composer->extra->license_title) ? $composer->extra->license_title : "";
$vars["composer_extra_license_url"] = isset($composer->extra->license_url) ? $composer->extra->license_url : "";

$vars["copyright_year"] = date("Y");

if (isset($composer->authors)) {
  foreach ($composer->authors as $author) {
    if (isset($author->homepage)) {
      $vars["composer_authors_list"][] = $author->name." - ".$author->homepage;
    } else if (isset($author->email)) {
      $vars["composer_authors_list"][] = $author->name." - ".$author->email;
    }
  }
  $vars["composer_authors_list"] = implode("<authors_linestart>",$vars["composer_authors_list"]);
  if (count($vars["composer_authors_list"]) == 1) {
    $vars["composer_authors_list"]="<authors_linestart>".$vars["composer_authors_list"];
  }
}
else {
  $vars["composer_authors_list"] = "";
}

if (isset($composer->require)) {
  $vars["composer_deps_list"]="<deps_header>";
  foreach($composer->require as $dep=>$vers){
    $composer_deps_listarray[] = "<deps_linestart>".$dep." ".$vers;
  }
  $vars["composer_deps_list"] .= implode("",$composer_deps_listarray);
} else {
  $vars["composer_deps_list"] = "<deps_header>No";
}


// Extract .git/HEAD data.
$git_branch_version = "";
if (file_exists('.git/HEAD')) {
  $stringfromfile = file('.git/HEAD', FILE_USE_INCLUDE_PATH);
  $firstLine = $stringfromfile[0];
  $explodedstring = explode("/", $firstLine, 3);
  $git_branch_version = trim($explodedstring[2]);
  $vars["git_branch_version"] = $git_branch_version;
}
else {
  echo "Not a git repository; no version for project found."
}


// Prepare README Markdown content.
$md = WRITEME_START . "\n";
$md .= "<composer_description>\n\n";
$md .= "Package: <composer_name>\n\n";
$md .= "Version: <git_branch_version>\n\n";
$md .= "Tags: <composer_keywords>\n\n";
$md .= "Project URL: <composer_homepage>\n\n";
$md .= "<composer_authors_list>";
$md_authors_linestart = "Author: ";
$md .= "Copyright (<composer_license>) <copyright_year>, <composer_extra_copyright_author>";
$md .= "License: <a href='<composer_extra_license_url>'><composer_extra_license_title></a>";
$md .= "<composer_deps_list>";
$md_deps_header = "\n\nDependencies\n";
$md_deps_linestart=" &#8226; ";
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

if (!$files) {
  $create = true;
}

// Write the README.
function writeme($file, $filepath, $md, $create){
  if ($create) {
  if (strpos($file, WRITEME_START) !== false) {
    $matches = preg_grep('/'.${$filetype."_trigger_start"}.'/', file($filepath));
    foreach ($matches as $key=>$lin){
      if (strpos($lin,${$filetype."_trigger_end"}) !== false) {
        $line_end = $key;
      }
    }
    foreach ($matches as $key=>$lin){
      if (strpos($lin,${$filetype."_trigger_start"}) !== false and strpos($lin,${$filetype."_trigger_end"}) === false and $key <= $line_end) {
        $line_start=$key;
      }
    }
    if (!isset($line_start)) {
      $line_start = $line_end;
    }
    $filecontent = file($filepath);
    $remove = "";
    for ($i = $line_start; $i <= $line_end; $i++) {
      $remove .= $filecontent[$i];
    }
    $contents = str_replace($remove, $md, $file);
    file_put_contents($filepath, $contents);
  }
}

// Update README files.
foreach ($files as $filepath){
  foreach ($vars as $key => $var){
    $md = str_replace("<$key>", $var, $md);
  }
  $md = str_replace("<authors_linestart>", $md_authors_linestart, $md);
  $md = str_replace("<deps_header>", $md_deps_header, $md);
  $md = str_replace("<deps_linestart>", $md_deps_linestart, $md);
  echo $md;
  $file = file_get_contents($filepath);
  writeme($file, $filepath, $md);
  echo "$filepath/$file written!\n";
}
