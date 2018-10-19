# Shrinker - The code minifier
> Code minifier for php, css, javascript

*Copyright 2018 [Abhishek Kumar](https://github.com/isurfer21). Licensed under the [Apache License, Version 2.0](http://www.apache.org/licenses/LICENSE-2.0) (the "License").*

## Intro
Although there are lot many tools out there to minify the codes of php, css or js; but this one seems to be simple and working without any clutter.

The minifier code has been extracted from another project [Adminer](https://github.com/vrana/adminer) and wrapped inside a command-line app; so as to make it CLI utility tool.

### Setup
1. Prerequisites: git and php-cli (command line interface) packages as per your system's OS
2. Navigate to the directory where you want to install shrinker (/usr/local is a good idea): 
   ```
   $ cd /usr/local 
   ```
3. Then retrieve from GitHub: 
   ```
   $ git clone https://github.com/isurfer21/shrinker.git
   ```
4. Go to the shrinker directory: 
   ```
   $ cd shrinker 
   ```
5. Check that shrinker.php has execute rights, otherwise:
   ```
   $ chmod a+x shrinker.php 
   ```
6. Create a symbolic link in the /usr/local/bin directory
   ```
   $ cd /usr/local/bin 
   $ ln -s /usr/local/shrinker/shrinker.php shrinker 
   ```
7. You can now run shrinker 
   ```
   $ shrinker --help 
   $ shrinker php inputfile.php outputfile.php
   ```

### Help
```
Shrinker (Ver 1.0)

Syntax:
    shrinker <option>
    shrinker <flag> <input-file>
    shrinker <flag> <input-file> <output-file>

Options:
    --help     -h    to see the command line options
    --version  -v    to get the version and license info

Flags:
    php    to minify php code
    css    to minify css stylesheet
    js     to minify js code

Examples:
    shrinker --help
    shrinker --version
    shrinker php test.php
    shrinker php itest.php otest.php
    shrinker css test.css
    shrinker css itest.css otest.css
    shrinker js test.js
    shrinker js itest.js otest.js

Notes:
    Options & Flags can't be used together in a statement.
    Order of command-line arguments should be maintained.

```

### References
 - [Adminer](https://github.com/vrana/adminer)
 - [YAKPro-PO](https://github.com/pk-fr/yakpro-po)