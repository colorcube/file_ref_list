.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt


Development
============

In principle this is a modified *FILE > Filelist* module.

This module seems to work as expected. It is not heavily tested and maybe it doesn't respect access restrictions very well.

That means the extension is not finished yet. Any collaboration is welcome.


:Git Repository:
        	https://github.com/colorcube/file_ref_list

To do
-----

* More testing
* Review modifications
* Reuse file list module and extend class?
* Maybe the module should be moved to the WEB section?


Known bugs
----------

The backend stores the last element the user selected. For the WEB section this is the page id and for the FILE section
this is the folder. But the module wants the page id in the FILE section. There's a patch included (deactivated) which
is kind of a hack. I must admit I can't remember what exactly is the behaviour that needs to be fixed. Needs review!

See also:

:Bug Tracker:
    	https://github.com/colorcube/file_ref_list/issues