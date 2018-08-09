# TYPO3 Extension: FILE > Files on page

This extension provides a backend module which is basically a clone of the FILE > Filelist module with the difference
that the page tree is used for navigation. Therefore the files used on a page are displayed in the list module.

One benefit is that one can quickly move the files to the clipboard and later on paste those files inside a folder. 
This way you can organize files which are directly uploaded in content elements in a folder structure. 

The file module but with a page tree:

![Module Screenshot](Documentation/Images/screenshot.png?raw=true "The file Module but with a page tree")

### Status ###

In principle this is a modified FILE > Filelist module. This module seems to work as expected. It is not heavily tested.


## Usage

After installation you see the new module in the File section.

Further information and usage details see: https://docs.typo3.org/typo3cms/extensions/file_ref_list/

### Dependencies

* TYPO3 8.7

### Installation

#### Installation using Composer

In your Composer based TYPO3 project root, just do `composer require colorcube/file-ref-list`. 

#### Installation as extension from TYPO3 Extension Repository (TER)

Download and install the extension with the extension manager module.

## Contribute

- Send pull requests to the repository. <https://github.com/colorcube/file_ref_list>
- Use the issue tracker for feedback and discussions. <https://github.com/colorcube/file_ref_list/issues>