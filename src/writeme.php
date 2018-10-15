<?php
/**
 * Create and keep updated a README.md for your project, fetching details from
 * composer.json and Git.
 *
 * Composer does not need to be installed to use this tool.
 */

/* Default config */

// Markdown
$md_trigger_start='<!--';
$md_doc="$md_trigger_start writeme -->\n";
$md_doc .= "<composer_description>\n\n";
$md_doc .= "Package: <composer_name>\n\n";
$md_doc .= "Version: <git_branch_version>\n\n";
$md_doc .= "Tags: <composer_keywords>\n\n";
$md_doc .= "Project URL: <composer_homepage>\n\n";
$md_doc .= "<composer_authors_list>";
$md_authors_linestart = "Author: ";
$md_doc .= "Copyright (<composer_license>) <copyright_year>, <composer_extra_copyright_author>";
$md_doc .= "License: <a href='<composer_extra_license_url>'><composer_extra_license_title></a>";
$md_doc .= "<composer_deps_list>";
$md_deps_header = "\n\nDependencies\n";
$md_deps_linestart=" &#8226; ";
$md_trigger_end="- @writem";
$md_doc .= "\n\n<!-$md_trigger_end <docbloc_version> -->\n";

//Variables to be extracted..
$vars["composer_description"]="";
$vars["composer_name"]="";
$vars["git_branch_version"]="";
$vars["composer_keywords"]="";
$vars["composer_homepage"]="";
$vars["composer_authors_list"]="";
$vars["composer_license"]="";
$vars["composer_copyright_year"]="";
$vars["composer_extra_copyright_author"]="";
$vars["composer_extra_license_title"]="";
$vars["composer_extra_license_url"]="";
$vars["composer_deps_list"]="";
$vars["docbloc_version"]="";

/* Extracting/formating composer.json data */
$composer = json_decode(file_get_contents('composer.json'));
$vars["composer_keywords"] = isset($composer->keywords) ? implode(", ",$composer->keywords) : '';
$vars["composer_description"] = $composer->description;
$vars["composer_name"] = $composer->name;
$vars["composer_homepage"] = $composer->homepage;
$vars["composer_license"] = $composer->license;
$vars["copyright_year"] = date("Y");
$vars["composer_extra_copyright_author"] = $composer->extra->copyright_author;
$vars["composer_extra_license_title"] = $composer->extra->license_title;
$vars["composer_extra_license_url"] = $composer->extra->license_url;

foreach ($composer->authors as $author) {
  if (isset($author->homepage)) {
    $vars["composer_authors_list"][]=$author->name." - ".$author->homepage;
  } else if (isset($author->email)) {
    $vars["composer_authors_list"][]=$author->name." - ".$author->email;
  }
}
$vars["composer_authors_list"]=implode("<authors_linestart>",$vars["composer_authors_list"]);
if (count($vars["composer_authors_list"])==1){
  $vars["composer_authors_list"]="<authors_linestart>".$vars["composer_authors_list"];
}
if (isset($composer->require)){
  $vars["composer_deps_list"]="<deps_header>";
  foreach($composer->require as $dep=>$vers){
    $composer_deps_listarray[]="<deps_linestart>".$dep." ".$vers;
  }
  $vars["composer_deps_list"].=implode("",$composer_deps_listarray);
}else{
  $vars["composer_deps_list"]="<deps_header>No";
}


/* Extracting/formating .git/HEAD data */
$git_branch_version="";
if(file_exists('.git/HEAD')){
  $stringfromfile = file('.git/HEAD', FILE_USE_INCLUDE_PATH);
  $firstLine = $stringfromfile[0];
  $explodedstring = explode("/", $firstLine, 3);
  $git_branch_version = trim($explodedstring[2]);
  $vars["git_branch_version"]=$git_branch_version;
}


if (!file_exists("composer.json"))
  die(" ** ./composer.json file not found, aborting...\n");
if (!file_exists(".git/HEAD"))
  echo " ** .git/HEAD file not found, the version of your project will not be fetched...\n";

/* Recursively list all matched files */
$path=__DIR__.'/'; //current dir and upper levels
$filetypes_regex='/^.+(.md)$/i'; //regex of file search
$directory = new RecursiveDirectoryIterator($path);
$iterator = new RecursiveIteratorIterator($directory);
$regex = new RegexIterator($iterator, $filetypes_regex, RecursiveRegexIterator::GET_MATCH);
$exclude="";
foreach($regex as $filepath => $regex){
  if (strpos($filepath,$exclude)===false) $files[]=$filepath;
}

/* Generate docbloc function */
function writeme($file,$filepath,$filetype,$doc){
  global ${$filetype."_trigger_end"}, ${$filetype."_trigger_start"};
  if (strpos($file,${$filetype."_trigger_end"}) !== false){
    $matches=preg_grep('/'.${$filetype."_trigger_start"}.'/', file($filepath));
    //var_dump($matches);
    unset($line_start);
    unset($line_end);
    foreach ($matches as $key=>$lin){
      if (strpos($lin,${$filetype."_trigger_end"}) !== false) $line_end=$key;
    }
    foreach ($matches as $key=>$lin){
      if (strpos($lin,${$filetype."_trigger_start"}) !== false and strpos($lin,${$filetype."_trigger_end"})===false and $key<=$line_end) $line_start=$key;
    }
    if (!isset($line_start)) {
      $line_start = $line_end;
    }
    $filecontent = file($filepath);
    $remove = "";
    //echo $line_start."@".$line_end;
    for ($i = $line_start; $i <= $line_end; $i++) { $remove.=$filecontent[$i]; }
    $file = str_replace($remove, $doc, $file);
    echo "  > $filepath";
    $file = file_put_contents($filepath,$file);
    echo ".. updated.\n";
  }
}

/* Updating your project scripts w/ new docBlock */
foreach ($files as $filepath){
  /* Generating docBlock */
  $filetype = pathinfo($filepath, PATHINFO_EXTENSION);
  $doc = ${$filetype."_doc"};
  foreach ($vars as $key=>$var){
    $doc = str_replace("<$key>", $var, $doc);
  }
  $doc = str_replace("<authors_linestart>", ${$filetype."_authors_linestart"}, $doc);
  $doc = str_replace("<deps_header>", ${$filetype."_deps_header"}, $doc);
  $doc = str_replace("<deps_linestart>", ${$filetype."_deps_linestart"}, $doc);
  echo $doc;
  $file = file_get_contents($filepath);
  if ($filetype=="md"){
    writeme($file,$filepath,$filetype,$doc);
  }
}
echo "# README.md written!\n";
