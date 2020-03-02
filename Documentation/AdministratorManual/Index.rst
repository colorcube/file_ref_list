.. include:: ../Includes.txt


.. _admin-manual:

Administrator Manual
====================

Installation
------------

There are two ways to properly install the extension.

1. Composer installation
^^^^^^^^^^^^^^^^^^^^^^^^

In case you use Composer to manage dependencies of your TYPO3 project,
you can just issue the following Composer command in your project root directory.

.. code-block:: bash

	composer require colorcube/file-ref-list

2. Installation with Extension Manager
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Download and install the extension with the extension manager module.


Configuration
-------------

As this is a modified version of the file list module the TSconfig options from the file module affects this module too.

Example for user TSconfig:

.. code-block:: typoscript

	options.file_list.enableClipBoard = activated