/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 *
 */
require(
	[
		'TYPO3/CMS/Backend/ModuleMenu'
	],
	function () {

        TYPO3.ModuleMenu.App.includeId = function (mod, params) {
            if (typeof mod !== 'string') {
                return params;
            }

            var section = mod.split('_')[0];
            if(mod==='file_FileRefListListref') {
                section = 'web';
            }
            //get id
            if (top.fsMod.recentIds[section]) {
                params = 'id=' + top.fsMod.recentIds[section] + '&' + params;
            }

            return params;
        };
    }
);
